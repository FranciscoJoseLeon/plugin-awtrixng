<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class awtrixng extends eqLogic {

    public static function cron() {
        foreach (self::byType('awtrixng', true) as $eqLogic) {
            $auto_refresh = $eqLogic->getConfiguration('auto_refresh', 1);
            if ($auto_refresh) {
                try {
                    $eqLogic->refresh();
                    $eqLogic->syncPanelApps();
                } catch (Exception $e) {
                    log::add('awtrixng', 'debug', 'Error en cron para ' . $eqLogic->getName() . ': ' . $e->getMessage());
                }
            }
        }
    }

    public function postSave() {
        $ip = trim($this->getConfiguration('ip', ''));
        $autoDetect = intval($this->getConfiguration('auto_detect_hardware', 1));
        if (!empty($ip) && $autoDetect === 1 && empty($this->_skipAutoDetectOnSave)) {
            try {
                $this->autoDetectAndConfigure(false);
            } catch (Exception $e) {
                log::add('awtrixng', 'debug', $this->getLogPrefix() . 'Auto-detección en postSave omitida (panel desconectado): ' . $e->getMessage());
            }
        }
        $this->createCommands();
        $this->fixCommandTypes();
        if (!empty($ip)) {
            try {
                $this->syncPanelApps();
            } catch (Exception $e) {
                log::add('awtrixng', 'debug', $this->getLogPrefix() . 'Sincronización de apps en postSave omitida: ' . $e->getMessage());
            }
        }
    }

    public function postUpdate() {
        $this->createCommands();
        $this->fixCommandTypes();
        $this->refresh();
    }

    /**
     * Interroga al panel Awtrix NG por HTTP cuando se establece la comunicación
     * y detecta automáticamente:
     * - Tamaño de la matriz LED (32×8, 52×16, 64×8, 96×8, 128×8)
     * - Modelo de dispositivo (TC001, TC002, DIY)
     * - Sensores instalados físicamente (Batería, Luminosidad LDR, Temperatura, Humedad, Audio/TTS)
     * - Versión de firmware, hostname y estado inicial
     */
    public static function curlQuick($url, $method = 'POST', $payload = array(), $password = '') {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    public static function probeAndDetectHardware($ip, $port = 80, $password = '') {
        $ip = trim($ip);
        $port = intval($port) > 0 ? intval($port) : 80;
        if (empty($ip)) {
            throw new Exception(__('Debes introducir una dirección IP o hostname válido.', __FILE__));
        }

        $fetchUrl = function($path) use ($ip, $port, $password) {
            $url = "http://{$ip}:{$port}/" . ltrim($path, '/');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            if (!empty($password)) {
                curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
            }
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && !empty($res)) {
                $decoded = json_decode($res, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return null;
        };

        // 1. Probar endpoint nativo de Awtrix NG (/api/v1/device, /api/v1/capabilities y /api/v1/display) y endpoint clásico (/api/stats y /api/settings)
        $v1Device = $fetchUrl('api/v1/device');
        $v1Display = null;
        $v1Capabilities = null;
        $legacyStats = null;
        $legacySettings = null;

        if (is_array($v1Device)) {
            $v1Capabilities = $fetchUrl('api/v1/capabilities');
            $v1Display = $fetchUrl('api/v1/display');
        } else {
            $legacyStats = $fetchUrl('api/stats');
            if (is_array($legacyStats)) {
                $legacySettings = $fetchUrl('api/settings');
            }
        }

        if (!is_array($v1Device) && !is_array($legacyStats)) {
            throw new Exception(__("No se pudo establecer comunicación con el panel Awtrix en http://{$ip}:{$port}", __FILE__));
        }

        $raw = is_array($v1Device) ? $v1Device : $legacyStats;
        $apiVersion = is_array($v1Device) ? 'v1' : 'legacy';

        // --- Detectar Dimensiones de la Matriz LED (Ancho × Alto) ---
        $width = 32;
        $height = 8;

        if (isset($v1Capabilities['display']['width'])) $width = intval($v1Capabilities['display']['width']);
        elseif (isset($raw['display']['width'])) $width = intval($raw['display']['width']);
        elseif (isset($v1Display['width'])) $width = intval($v1Display['width']);
        elseif (isset($raw['matrix']['width'])) $width = intval($raw['matrix']['width']);
        elseif (isset($raw['width'])) $width = intval($raw['width']);
        elseif (isset($legacySettings['WIDTH'])) $width = intval($legacySettings['WIDTH']);
        elseif (isset($legacySettings['MATRIX_WIDTH'])) $width = intval($legacySettings['MATRIX_WIDTH']);

        if (isset($v1Capabilities['display']['height'])) $height = intval($v1Capabilities['display']['height']);
        elseif (isset($raw['display']['height'])) $height = intval($raw['display']['height']);
        elseif (isset($v1Display['height'])) $height = intval($v1Display['height']);
        elseif (isset($raw['matrix']['height'])) $height = intval($raw['matrix']['height']);
        elseif (isset($raw['height'])) $height = intval($raw['height']);
        elseif (isset($legacySettings['HEIGHT'])) $height = intval($legacySettings['HEIGHT']);

        // Identificar por nombre de placa/modelo si viene reportado (ej. TC002 = 52x16)
        $boardStr = '';
        foreach (array('board', 'boardType', 'model', 'device', 'hardware', 'type') as $k) {
            if (isset($raw[$k]) && is_string($raw[$k])) {
                $boardStr .= ' ' . strtolower($raw[$k]);
            }
        }
        if (strpos($boardStr, 'tc002') !== false || strpos($boardStr, '52x16') !== false) {
            $width = 52;
            $height = 16;
        }

        // Clasificar dentro de los perfiles oficiales de Awtrix NG
        if ($width === 52 && $height === 16) {
            $model = '52x16';
        } elseif ($width > 32) {
            $model = 'custom';
            $height = 8;
            $width = max(32, min(128, $width));
        } else {
            $model = '32x8';
            $width = 32;
            $height = 8;
        }

        // --- Detectar Sensores Instalados Físicamente en este Panel ---
        $sensorsBlock = isset($raw['sensors']) && is_array($raw['sensors']) ? $raw['sensors'] : $raw;
        $batteryBlock = isset($raw['battery']) && is_array($raw['battery']) ? $raw['battery'] : $raw;

        // 1. Batería
        $batVal = null;
        if (isset($raw['batteryPercent']) && is_numeric($raw['batteryPercent'])) $batVal = intval($raw['batteryPercent']);
        elseif (isset($batteryBlock['level'])) $batVal = intval($batteryBlock['level']);
        elseif (isset($batteryBlock['percentage'])) $batVal = intval($batteryBlock['percentage']);
        elseif (isset($raw['battery']) && is_numeric($raw['battery'])) $batVal = intval($raw['battery']);
        elseif (isset($raw['bat']) && is_numeric($raw['bat'])) $batVal = intval($raw['bat']);

        $hasBattery = ($batVal !== null && $batVal > 0);
        if (isset($batteryBlock['present'])) {
            $hasBattery = boolval($batteryBlock['present']);
        }

        // 2. Luminosidad (LDR / Lux)
        $luxVal = null;
        if (isset($raw['lightLevel']) && is_numeric($raw['lightLevel'])) $luxVal = floatval($raw['lightLevel']);
        elseif (isset($sensorsBlock['lux']) && is_numeric($sensorsBlock['lux'])) $luxVal = floatval($sensorsBlock['lux']);
        elseif (isset($raw['lux']) && is_numeric($raw['lux'])) $luxVal = floatval($raw['lux']);
        elseif (isset($raw['ldrRaw']) && is_numeric($raw['ldrRaw'])) $luxVal = floatval($raw['ldrRaw']);
        elseif (isset($raw['ldr_raw']) && is_numeric($raw['ldr_raw'])) $luxVal = floatval($raw['ldr_raw']);
        $hasLux = ($luxVal !== null && $luxVal >= 0) || !empty($v1Capabilities['sensors']['light']);

        // 3. Temperatura
        $tempVal = null;
        if (isset($raw['temperature']) && is_numeric($raw['temperature'])) $tempVal = round(floatval($raw['temperature']), 1);
        elseif (isset($sensorsBlock['temperature']) && is_numeric($sensorsBlock['temperature'])) $tempVal = round(floatval($sensorsBlock['temperature']), 1);
        elseif (isset($sensorsBlock['temp']) && is_numeric($sensorsBlock['temp'])) $tempVal = round(floatval($sensorsBlock['temp']), 1);
        elseif (isset($raw['temp']) && is_numeric($raw['temp'])) $tempVal = round(floatval($raw['temp']), 1);
        $hasTemp = ($tempVal !== null && $tempVal > -40 && $tempVal != 0);

        // 4. Humedad
        $humVal = null;
        if (isset($raw['humidity']) && is_numeric($raw['humidity'])) $humVal = round(floatval($raw['humidity']), 1);
        elseif (isset($sensorsBlock['humidity']) && is_numeric($sensorsBlock['humidity'])) $humVal = round(floatval($sensorsBlock['humidity']), 1);
        elseif (isset($sensorsBlock['hum']) && is_numeric($sensorsBlock['hum'])) $humVal = round(floatval($sensorsBlock['hum']), 1);
        elseif (isset($raw['hum']) && is_numeric($raw['hum'])) $humVal = round(floatval($raw['hum']), 1);
        $hasHumidity = ($humVal !== null && $humVal > 0);

        // 5. Audio / TTS
        $hasAudioTts = ($model === '52x16') || !empty($v1Capabilities['audio']['speech']) || !empty($v1Capabilities['audio']['mp3']) || !empty($raw['audio']) || !empty($raw['tts']);

        // --- Identidad, Firmware y Estado ---
        $firmware = 'Awtrix NG';
        if (isset($raw['firmware']['version'])) $firmware = (string)$raw['firmware']['version'];
        elseif (isset($raw['version'])) $firmware = (string)$raw['version'];

        $hostname = '';
        if (isset($raw['identity']['hostname'])) $hostname = (string)$raw['identity']['hostname'];
        elseif (isset($raw['network']['hostname'])) $hostname = (string)$raw['network']['hostname'];
        elseif (isset($raw['hostname'])) $hostname = (string)$raw['hostname'];
        elseif (isset($raw['uid'])) $hostname = (string)$raw['uid'];

        $wifiSignal = null;
        if (isset($raw['network']['wifiRssi'])) $wifiSignal = intval($raw['network']['wifiRssi']);
        elseif (isset($raw['wifiRssi'])) $wifiSignal = intval($raw['wifiRssi']);
        elseif (isset($raw['wifi_signal'])) $wifiSignal = intval($raw['wifi_signal']);

        $uptimeSec = null;
        if (isset($raw['uptimeSeconds'])) $uptimeSec = intval($raw['uptimeSeconds']);
        elseif (isset($raw['uptime'])) $uptimeSec = intval($raw['uptime']);

        $currentApp = '';
        if (isset($raw['currentApp'])) $currentApp = (string)$raw['currentApp'];
        elseif (isset($v1Display['currentApp'])) $currentApp = (string)$v1Display['currentApp'];
        elseif (isset($raw['display']['currentApp'])) $currentApp = (string)$raw['display']['currentApp'];
        elseif (isset($raw['app'])) $currentApp = (string)$raw['app'];

        $brightnessPercent = null;
        if (isset($raw['brightness']) && is_numeric($raw['brightness'])) {
            $b = intval($raw['brightness']);
            $brightnessPercent = round((min(255, max(0, $b)) / 255) * 100);
        } elseif (isset($v1Display['brightness']) && is_numeric($v1Display['brightness'])) {
            $b = intval($v1Display['brightness']);
            $brightnessPercent = round((min(255, max(0, $b)) / 255) * 100);
        } elseif (isset($raw['display']['brightness'])) {
            $b = intval($raw['display']['brightness']);
            $brightnessPercent = ($b > 100) ? round(($b / 255) * 100) : $b;
        } elseif (isset($raw['bri'])) {
            $brightnessPercent = round((intval($raw['bri']) / 255) * 100);
        } elseif (isset($legacySettings['BRI'])) {
            $brightnessPercent = round((intval($legacySettings['BRI']) / 255) * 100);
        }

        $powerState = 1;
        if (isset($raw['matrixPower'])) $powerState = $raw['matrixPower'] ? 1 : 0;
        elseif (isset($v1Display['power'])) $powerState = $v1Display['power'] ? 1 : 0;
        elseif (isset($raw['display']['power'])) $powerState = $raw['display']['power'] ? 1 : 0;
        elseif (isset($legacySettings['SLEEP'])) $powerState = $legacySettings['SLEEP'] ? 0 : 1;

        return array(
            'api_version' => $apiVersion,
            'model' => $model,
            'matrix_width' => $width,
            'matrix_height' => $height,
            'size_label' => ($model === '52x16') ? '52×16 (TC002)' : (($model === 'custom') ? "{$width}×8 (DIY)" : '32×8 (TC001 & DIY)'),
            'firmware' => $firmware,
            'hostname' => $hostname,
            'sensors' => array(
                'has_battery' => $hasBattery ? 1 : 0,
                'has_lux' => $hasLux ? 1 : 0,
                'has_temp' => $hasTemp ? 1 : 0,
                'has_humidity' => $hasHumidity ? 1 : 0,
                'has_audio_tts' => $hasAudioTts ? 1 : 0
            ),
            'telemetry' => array(
                'battery' => $batVal,
                'lux' => $luxVal,
                'temp' => $tempVal,
                'humidity' => $humVal,
                'wifi_signal' => $wifiSignal,
                'uptime' => $uptimeSec,
                'current_app' => $currentApp,
                'brightness' => $brightnessPercent,
                'power' => $powerState
            ),
            'raw' => $raw
        );
    }

    /**
     * Conecta con el panel, autodetecta su tamaño de matriz, modelo y sensores disponibles,
     * los guarda en la configuración del equipo y actualiza los comandos de Jeedom.
     */
    public function autoDetectAndConfigure($saveAndRecreateCmds = true) {
        $ip = trim($this->getConfiguration('ip', ''));
        $port = intval($this->getConfiguration('port', 80));
        $password = trim($this->getConfiguration('auth_password', ''));

        $detected = self::probeAndDetectHardware($ip, $port, $password);

        $this->setConfiguration('api_version', $detected['api_version']);
        $this->setConfiguration('model', $detected['model']);
        $this->setConfiguration('matrix_width', $detected['matrix_width']);
        $this->setConfiguration('matrix_height', $detected['matrix_height']);
        $this->setConfiguration('detected_firmware', $detected['firmware']);
        $this->setConfiguration('detected_hostname', $detected['hostname']);
        $this->setConfiguration('has_battery', $detected['sensors']['has_battery']);
        $this->setConfiguration('has_lux', $detected['sensors']['has_lux']);
        $this->setConfiguration('has_temp', $detected['sensors']['has_temp']);
        $this->setConfiguration('has_humidity', $detected['sensors']['has_humidity']);
        $this->setConfiguration('has_audio_tts', $detected['sensors']['has_audio_tts']);
        $this->setConfiguration('hardware_auto_detected', 1);

        if ($saveAndRecreateCmds && $this->getId()) {
            $this->_skipAutoDetectOnSave = true;
            $this->save();
            $this->_skipAutoDetectOnSave = false;
        }

        log::add('awtrixng', 'info', $this->getLogPrefix() . "Hardware autodetectado con éxito: Matriz {$detected['matrix_width']}×{$detected['matrix_height']}, Firmware={$detected['firmware']}, Batería={$detected['sensors']['has_battery']}, Lux={$detected['sensors']['has_lux']}, Temp={$detected['sensors']['has_temp']}, Hum={$detected['sensors']['has_humidity']}");

        return $detected;
    }

    /**
     * Devuelve una etiqueta legible con las dimensiones de la matriz de este panel
     * Ciñéndose a los paneles compatibles oficialmente con Awtrix NG:
     * - 32×8 (TC001 & DIY estándar)
     * - 52×16 (TC002)
     * - Matriz DIY de 32×8 a 128×8 (ej. 64×8, 96×8, 128×8)
     */
    public function getMatrixSizeLabel() {
        $model = $this->getConfiguration('model', '32x8');
        if ($model === '52x16') {
            return '52×16 (TC002)';
        } elseif ($model === 'custom') {
            $w = intval($this->getConfiguration('matrix_width', 64));
            if ($w < 32) $w = 32;
            if ($w > 128) $w = 128;
            return "{$w}×8 (DIY)";
        }
        return '32×8 (TC001)';
    }

    /**
     * Devuelve la imagen correspondiente al tipo de pantalla configurada:
     * - 32x8: Ulanzi TC001 (tc001_32x8.jpg)
     * - 52x16: Ulanzi TC002 (tc002_52x16.jpg)
     * - custom: Matriz DIY 32×8 a 128×8 (diy_matrix.png)
     */
    public function getImage() {
        $model = $this->getConfiguration('model', '32x8');
        if ($model === '52x16') {
            return 'plugins/awtrixng/plugin_info/images/tc002_52x16.jpg';
        } elseif ($model === 'custom') {
            return 'plugins/awtrixng/plugin_info/images/diy_matrix.png';
        }
        return 'plugins/awtrixng/plugin_info/images/tc001_32x8.jpg';
    }

    public function createCommands() {
        $model = $this->getConfiguration('model', '32x8'); // '32x8' (TC001 & DIY), '52x16' (TC002) o 'custom'
        $autoDetected = intval($this->getConfiguration('hardware_auto_detected', 0)) === 1;

        $hasBattery = intval($this->getConfiguration('has_battery', 1));
        $hasLux = intval($this->getConfiguration('has_lux', 1));
        $hasTemp = intval($this->getConfiguration('has_temp', 1));
        $hasHumidity = intval($this->getConfiguration('has_humidity', 1));
        $hasAudioTts = intval($this->getConfiguration('has_audio_tts', ($model === '52x16') ? 1 : 0));

        $commands = array(
            // --- Info Commands (Telemetría y Sensores) ---
            array('logicalId' => 'online', 'name' => __('Conexión', __FILE__), 'type' => 'info', 'subType' => 'binary', 'unite' => '', 'isVisible' => 1, 'display' => array('icon' => 'fas fa-wifi')),
            array('logicalId' => 'battery', 'name' => __('Batería', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => '%', 'isVisible' => ($autoDetected ? $hasBattery : 1), 'display' => array('icon' => 'fas fa-battery-three-quarters')),
            array('logicalId' => 'lux', 'name' => __('Luminosidad', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => 'lux', 'isVisible' => ($autoDetected ? $hasLux : 1), 'display' => array('icon' => 'fas fa-sun')),
            array('logicalId' => 'temp', 'name' => __('Temperatura', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => '°C', 'isVisible' => ($autoDetected ? $hasTemp : 1), 'display' => array('icon' => 'fas fa-thermometer-half')),
            array('logicalId' => 'humidity', 'name' => __('Humedad', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => '%', 'isVisible' => ($autoDetected ? $hasHumidity : 1), 'display' => array('icon' => 'fas fa-tint')),
            array('logicalId' => 'power_state', 'name' => __('Pantalla Encendida', __FILE__), 'type' => 'info', 'subType' => 'binary', 'unite' => '', 'isVisible' => 1, 'display' => array('icon' => 'fas fa-power-off')),
            array('logicalId' => 'brightness_state', 'name' => __('Nivel de Brillo', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => '%', 'isVisible' => 1, 'display' => array('icon' => 'fas fa-adjust')),
            array('logicalId' => 'current_app', 'name' => __('App en Pantalla', __FILE__), 'type' => 'info', 'subType' => 'string', 'unite' => '', 'isVisible' => 1, 'display' => array('icon' => 'fas fa-desktop')),
            array('logicalId' => 'wifi_signal', 'name' => __('Señal WiFi', __FILE__), 'type' => 'info', 'subType' => 'numeric', 'unite' => 'dBm', 'isVisible' => 1, 'display' => array('icon' => 'fas fa-signal')),
            array('logicalId' => 'uptime_str', 'name' => __('Tiempo Activo', __FILE__), 'type' => 'info', 'subType' => 'string', 'unite' => '', 'isVisible' => 1),

            // --- Action Commands (Control de Pantalla) ---
            array('logicalId' => 'refresh', 'name' => __('Refrescar', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-sync')),
            array('logicalId' => 'power_on', 'name' => __('Encender Pantalla', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-lightbulb')),
            array('logicalId' => 'power_off', 'name' => __('Apagar Pantalla', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'far fa-lightbulb')),
            array('logicalId' => 'toggle_power', 'name' => __('Alternar Pantalla', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-toggle-on')),
            array('logicalId' => 'set_brightness', 'name' => __('Ajustar Brillo', __FILE__), 'type' => 'action', 'subType' => 'slider', 'configuration' => array('minValue' => 0, 'maxValue' => 100)),
            array('logicalId' => 'next_app', 'name' => __('Siguiente App', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-step-forward')),
            array('logicalId' => 'prev_app', 'name' => __('App Anterior', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-step-backward')),

            // --- Action Commands (Mensajería, Iconos y Colores) ---
            array('logicalId' => 'notify', 'name' => __('Notificar (Popup)', __FILE__), 'type' => 'action', 'subType' => 'message', 'display' => array('icon' => 'fas fa-bell', 'title_placeholder' => __('Icono, Color o Duración (ej. plant-watering | #00FF00 | fija)', __FILE__), 'message_placeholder' => __('Mensaje a mostrar', __FILE__))),
            array('logicalId' => 'dismiss_notif', 'name' => __('Descartar Notificación', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-bell-slash')),
            array('logicalId' => 'custom_app', 'name' => __('App Carrusel (Publicar)', __FILE__), 'type' => 'action', 'subType' => 'message', 'display' => array('icon' => 'fas fa-layer-group', 'title_placeholder' => __('Nombre App (ej. termo, riego)', __FILE__), 'message_placeholder' => __('Texto de la app', __FILE__))),
            array('logicalId' => 'delete_app', 'name' => __('Eliminar App Carrusel', __FILE__), 'type' => 'action', 'subType' => 'message', 'display' => array('icon' => 'fas fa-trash-alt', 'title_placeholder' => __('Nombre de la App a borrar', __FILE__))),
            array('logicalId' => 'sync_apps', 'name' => __('Sincronizar Info del Panel', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-cloud-upload-alt')),
            array('logicalId' => 'play_sound', 'name' => __('Reproducir Sonido / RTTTL', __FILE__), 'type' => 'action', 'subType' => 'message', 'display' => array('icon' => 'fas fa-volume-up', 'message_placeholder' => __('beep, alarm o melodía RTTTL / MP3', __FILE__))),
            array('logicalId' => 'reboot', 'name' => __('Reiniciar Reloj', __FILE__), 'type' => 'action', 'subType' => 'other', 'display' => array('icon' => 'fas fa-redo'))
        );

        // Comandos adicionales para dispositivos con altavoz/TTS (ej. TC002 52×16 o audio autodetectado)
        if ($model === '52x16' || $hasAudioTts) {
            $commands[] = array('logicalId' => 'tts', 'name' => __('Voz / Síntesis TTS', __FILE__), 'type' => 'action', 'subType' => 'message', 'display' => array('icon' => 'fas fa-comment-dots', 'message_placeholder' => __('Texto que dirá en voz alta el reloj', __FILE__)));
            $commands[] = array('logicalId' => 'set_volume', 'name' => __('Volumen Audio', __FILE__), 'type' => 'action', 'subType' => 'slider', 'configuration' => array('minValue' => 0, 'maxValue' => 100), 'display' => array('icon' => 'fas fa-volume-up'));
        }

        $order = 0;
        foreach ($commands as $cmdDef) {
            $cmd = $this->getCmd(null, $cmdDef['logicalId']);
            if (!is_object($cmd)) {
                $cmd = new awtrixngCmd();
                $cmd->setLogicalId($cmdDef['logicalId']);
                $cmd->setEqLogic_id($this->getId());
            }
            $cmd->setName($cmdDef['name']);
            $cmd->setType($cmdDef['type']);
            $cmd->setSubType($cmdDef['subType']);
            if (isset($cmdDef['unite'])) {
                $cmd->setUnite($cmdDef['unite']);
            }
            if (isset($cmdDef['isVisible'])) {
                $cmd->setIsVisible(intval($cmdDef['isVisible']));
            }
            if (isset($cmdDef['display'])) {
                foreach ($cmdDef['display'] as $k => $v) {
                    $cmd->setDisplay($k, $v);
                }
            }
            if (isset($cmdDef['configuration'])) {
                foreach ($cmdDef['configuration'] as $k => $v) {
                    $cmd->setConfiguration($k, $v);
                }
            }
            $cmd->setOrder(++$order);
            $cmd->save();
        }
    }

    /**
     * Corrige y repara de forma estricta los tipos de comandos en la base de datos de Jeedom.
     * Garantiza que los comandos de acción nunca se guarden ni se muestren como 'info',
     * devolviendo el botón 'Tester' / 'Probar' y los campos de títulos/mensajes en escenarios.
     */
    public function fixCommandTypes() {
        $actionCmds = array(
            'refresh' => 'other',
            'power_on' => 'other',
            'power_off' => 'other',
            'toggle_power' => 'other',
            'set_brightness' => 'slider',
            'next_app' => 'other',
            'prev_app' => 'other',
            'notify' => 'message',
            'dismiss_notif' => 'other',
            'custom_app' => 'message',
            'delete_app' => 'message',
            'sync_apps' => 'other',
            'play_sound' => 'message',
            'reboot' => 'other',
            'tts' => 'message',
            'set_volume' => 'slider'
        );
        $infoCmds = array(
            'online' => 'binary',
            'battery' => 'numeric',
            'lux' => 'numeric',
            'temp' => 'numeric',
            'humidity' => 'numeric',
            'power_state' => 'binary',
            'brightness_state' => 'numeric',
            'current_app' => 'string',
            'wifi_signal' => 'numeric',
            'uptime_str' => 'string'
        );
        foreach ($this->getCmd() as $cmd) {
            $logicalId = $cmd->getLogicalId();
            if (isset($actionCmds[$logicalId])) {
                $changed = false;
                if ($cmd->getType() !== 'action') {
                    $cmd->setType('action');
                    $changed = true;
                }
                if ($cmd->getSubType() !== $actionCmds[$logicalId] && in_array($cmd->getSubType(), array('string', 'numeric', 'binary', '', 'other'))) {
                    $cmd->setSubType($actionCmds[$logicalId]);
                    $changed = true;
                }
                if ($changed) {
                    $cmd->save();
                }
            } elseif (isset($infoCmds[$logicalId])) {
                $changed = false;
                if ($cmd->getType() !== 'info') {
                    $cmd->setType('info');
                    $changed = true;
                }
                if ($cmd->getSubType() !== $infoCmds[$logicalId] && empty($cmd->getSubType())) {
                    $cmd->setSubType($infoCmds[$logicalId]);
                    $changed = true;
                }
                if ($changed) {
                    $cmd->save();
                }
            }
        }
    }

    /**
     * Prefijo identificativo para los logs de Jeedom (Nombre del panel + IP + Tamaño)
     */
    public function getLogPrefix() {
        $ip = trim($this->getConfiguration('ip', 'Sin-IP'));
        $size = $this->getMatrixSizeLabel();
        return '[' . $this->getName() . ' | ' . $ip . ' | ' . $size . '] ';
    }

    public function isV1Api() {
        return $this->getConfiguration('api_version', 'v1') !== 'legacy';
    }

    public function apiRequest($endpoint, $method = 'GET', $payload = null) {
        $ip = trim($this->getConfiguration('ip', ''));
        if (empty($ip)) {
            throw new Exception(__('Debes configurar la dirección IP de tu Awtrix NG en el equipo.', __FILE__));
        }

        $port = intval($this->getConfiguration('port', 80));
        if ($port <= 0) $port = 80;

        $url = 'http://' . $ip . ':' . $port . '/api/' . ltrim($endpoint, '/');
        $timeout = intval(config::byKey('timeout', 'awtrixng', 5));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $headers = array('Content-Type: application/json; charset=utf-8');

        $password = trim($this->getConfiguration('auth_password', ''));
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }

        $method = strtoupper(trim($method));
        $jsonPayload = '';
        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload !== null) {
                $jsonPayload = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : (string)$payload;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        log::add('awtrixng', 'debug', $this->getLogPrefix() . "Petición HTTP {$method} {$url}" . ($jsonPayload !== '' ? " Payload: {$jsonPayload}" : ""));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            if ($response === false) {
                $this->checkAndUpdateCmd('online', 0);
            }
            $errDetail = $curlError ?: $response;
            $errMsg = $this->getLogPrefix() . "Error al comunicar con Awtrix en {$url} (HTTP {$httpCode}): {$errDetail}";
            log::add('awtrixng', 'error', $errMsg);
            throw new Exception($errMsg, $httpCode);
        }

        $this->checkAndUpdateCmd('online', 1);
        log::add('awtrixng', 'debug', $this->getLogPrefix() . "Respuesta HTTP {$httpCode} recibida correctamente");

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : $response;
    }

    public function refresh() {
        $ip = trim($this->getConfiguration('ip', ''));
        if (empty($ip)) return;

        try {
            $port = intval($this->getConfiguration('port', 80));
            $password = trim($this->getConfiguration('auth_password', ''));
            $detected = self::probeAndDetectHardware($ip, $port, $password);

            $this->checkAndUpdateCmd('online', 1);

            // Si tiene activada la autodetección de hardware y aún no se había sincronizado o cambió el tamaño/sensores, se actualiza solo
            $autoDetectEnabled = intval($this->getConfiguration('auto_detect_hardware', 1)) === 1;
            $alreadyDetected = intval($this->getConfiguration('hardware_auto_detected', 0)) === 1;
            if ($autoDetectEnabled && !$alreadyDetected) {
                $this->setConfiguration('api_version', $detected['api_version']);
                $this->setConfiguration('model', $detected['model']);
                $this->setConfiguration('matrix_width', $detected['matrix_width']);
                $this->setConfiguration('matrix_height', $detected['matrix_height']);
                $this->setConfiguration('detected_firmware', $detected['firmware']);
                $this->setConfiguration('detected_hostname', $detected['hostname']);
                $this->setConfiguration('has_battery', $detected['sensors']['has_battery']);
                $this->setConfiguration('has_lux', $detected['sensors']['has_lux']);
                $this->setConfiguration('has_temp', $detected['sensors']['has_temp']);
                $this->setConfiguration('has_humidity', $detected['sensors']['has_humidity']);
                $this->setConfiguration('has_audio_tts', $detected['sensors']['has_audio_tts']);
                $this->setConfiguration('hardware_auto_detected', 1);
                $this->_skipAutoDetectOnSave = true;
                $this->save();
                $this->_skipAutoDetectOnSave = false;
            }

            $tel = $detected['telemetry'];
            if ($tel['battery'] !== null) {
                $this->checkAndUpdateCmd('battery', intval($tel['battery']));
            }
            if ($tel['lux'] !== null) {
                $this->checkAndUpdateCmd('lux', round(floatval($tel['lux']), 1));
            }
            if ($tel['temp'] !== null) {
                $this->checkAndUpdateCmd('temp', round(floatval($tel['temp']), 1));
            }
            if ($tel['humidity'] !== null) {
                $this->checkAndUpdateCmd('humidity', round(floatval($tel['humidity']), 1));
            }
            if (!empty($tel['current_app'])) {
                $this->checkAndUpdateCmd('current_app', (string)$tel['current_app']);
            }
            if ($tel['wifi_signal'] !== null) {
                $this->checkAndUpdateCmd('wifi_signal', intval($tel['wifi_signal']));
            }
            if ($tel['uptime'] !== null) {
                $uptimeSec = intval($tel['uptime']);
                $days = floor($uptimeSec / 86400);
                $hours = floor(($uptimeSec % 86400) / 3600);
                $minutes = floor(($uptimeSec % 3600) / 60);
                $uptimeStr = ($days > 0 ? "{$days}d " : "") . "{$hours}h {$minutes}m";
                $this->checkAndUpdateCmd('uptime_str', $uptimeStr);
            }
            if ($tel['brightness'] !== null) {
                $this->checkAndUpdateCmd('brightness_state', intval($tel['brightness']));
            }
            if ($tel['power'] !== null) {
                $this->checkAndUpdateCmd('power_state', intval($tel['power']));
            }

        } catch (Exception $e) {
            $this->checkAndUpdateCmd('online', 0);
            log::add('awtrixng', 'warning', $this->getLogPrefix() . 'No se pudo refrescar Awtrix: ' . $e->getMessage());
        }
    }

    public function setPower($state) {
        $powerBool = boolval($state);
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/display', 'PATCH', array('power' => $powerBool));
            } catch (Exception $e) {
                $this->apiRequest('power', 'POST', array('power' => $powerBool));
            }
        } else {
            $this->apiRequest('power', 'POST', array('power' => $powerBool));
        }
        $this->checkAndUpdateCmd('power_state', $powerBool ? 1 : 0);
        log::add('awtrixng', 'info', 'Pantalla Awtrix ' . ($powerBool ? 'encendida' : 'apagada'));
    }

    public function setBrightness($percent) {
        $percent = max(0, min(100, intval($percent)));
        $rawBri = intval(round(($percent / 100.0) * 255));
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/settings', 'PATCH', array('brightness' => $rawBri));
            } catch (Exception $e) {
                $this->apiRequest('settings', 'POST', array('BRI' => $rawBri));
            }
        } else {
            $this->apiRequest('settings', 'POST', array('BRI' => $rawBri));
        }
        $this->checkAndUpdateCmd('brightness_state', $percent);
        log::add('awtrixng', 'info', "Brillo de Awtrix ajustado al {$percent}% ({$rawBri}/255)");
    }

    public function setVolume($percent) {
        $percent = max(0, min(100, intval($percent)));
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/settings', 'PATCH', array('volume' => $percent));
            } catch (Exception $e) {
                $this->apiRequest('settings', 'POST', array('VOL' => $percent));
            }
        } else {
            $this->apiRequest('settings', 'POST', array('VOL' => $percent));
        }
        log::add('awtrixng', 'info', "Volumen de audio Awtrix ajustado al {$percent}%");
    }

    public function nextApp() {
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/apps/next', 'POST', array());
                return;
            } catch (Exception $e) {}
        }
        $this->apiRequest('nextapp', 'POST', array());
    }

    public function prevApp() {
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/apps/previous', 'POST', array());
                return;
            } catch (Exception $e) {}
        }
        $this->apiRequest('previousapp', 'POST', array());
    }

    public function reboot() {
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/device/reboot', 'POST', array());
                log::add('awtrixng', 'info', 'Reinicio de Awtrix solicitado');
                return;
            } catch (Exception $e) {}
        }
        $this->apiRequest('reboot', 'POST', array());
        log::add('awtrixng', 'info', 'Reinicio de Awtrix solicitado');
    }

    public function playSound($sound) {
        $sound = trim($sound);
        if (empty($sound)) return;

        // Si empieza directamente por "d=" o "b=" (cabecera RTTTL sin el nombre inicial), anteponer "tone:"
        if (preg_match('/^[a-z]=[0-9]/i', $sound)) {
            $sound = 'tone:' . $sound;
        }

        // Si es un alias conocido de la librería integrada (ej. water, alarm, mario, chime, siren, tetris...)
        $builtinMatch = null;
        $builtins = self::getBuiltinSoundLibrary();
        foreach ($builtins as $item) {
            if (strtolower($item['id']) === strtolower($sound)) {
                $builtinMatch = $item;
                break;
            }
        }

        // Si coincide con la librería integrada y el dispositivo es TC001 (zumbador piezo) o no tiene altavoz MP3, usar RTTTL
        $hasAudioTts = intval($this->getConfiguration('has_audio_tts', 0));
        if ($builtinMatch !== null) {
            if (!$hasAudioTts || strpos($sound, ':') !== false) {
                $sound = $builtinMatch['rtttl'];
            }
        }

        // Verificar si es una melodía personalizada guardada por el usuario
        if (strpos($sound, ':') === false && !preg_match('/\.mp3$/i', $sound)) {
            $customs = $this->getCustomRtttls();
            foreach ($customs as $c) {
                if (strtolower($c['name']) === strtolower($sound) || $c['id'] === $sound) {
                    $sound = $c['code'];
                    break;
                }
            }
        }

        if ($this->isV1Api()) {
            try {
                if (strpos($sound, ':') !== false) {
                    $this->apiRequest('v1/audio/play', 'POST', array('rtttl' => $sound));
                } else {
                    $cleanName = preg_replace('/\.mp3$/i', '', $sound);
                    $this->apiRequest('v1/audio/play', 'POST', array('file' => $cleanName));
                }
                log::add('awtrixng', 'info', "Sonido reproducido en Awtrix: {$sound}");
                return;
            } catch (Exception $e) {}
        }

        // Fallback Legacy API
        if (strpos($sound, ':') !== false) {
            $this->apiRequest('rtttl', 'POST', $sound);
        } elseif (preg_match('/\.mp3$/i', $sound)) {
            $this->apiRequest('mp3', 'POST', array('file' => $sound));
        } else {
            $this->apiRequest('sound', 'POST', array('sound' => $sound));
        }
        log::add('awtrixng', 'info', "Sonido reproducido en Awtrix: {$sound}");
    }

    public function sendTTS($text) {
        $text = trim($text);
        if (empty($text)) return;
        if ($this->isV1Api()) {
            try {
                $this->apiRequest('v1/audio/play', 'POST', array('speech' => $text));
                log::add('awtrixng', 'info', "Voz TTS enviada a Awtrix: '{$text}'");
                return;
            } catch (Exception $e) {}
        }
        $this->apiRequest('speech', 'POST', array('text' => $text));
        log::add('awtrixng', 'info', "Voz TTS enviada a Awtrix: '{$text}'");
    }

    /**
     * Helper to parse and normalize color input (Hex string #00FF00, named colors, or RGB array)
     */
    protected function normalizeColor($color) {
        if (empty($color)) return null;
        $color = trim($color);

        // Predefined friendly colors
        $namedColors = array(
            'red' => '#FF0000',
            'rojo' => '#FF0000',
            'green' => '#00FF00',
            'verde' => '#00FF00',
            'blue' => '#0080FF',
            'azul' => '#0080FF',
            'yellow' => '#FFD700',
            'amarillo' => '#FFD700',
            'orange' => '#FF8C00',
            'naranja' => '#FF8C00',
            'cyan' => '#00FFFF',
            'white' => '#FFFFFF',
            'blanco' => '#FFFFFF',
            'purple' => '#9370DB',
            'morado' => '#9370DB'
        );

        $lower = strtolower($color);
        if (isset($namedColors[$lower])) {
            return $namedColors[$lower];
        }

        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $color)) {
            return '#' . strtoupper(ltrim($color, '#'));
        }

        return $color;
    }

    /**
     * Normaliza el nombre del icono (quita extensión .gif/.png y traduce IDs numéricos de plantillas antiguas a slugs de Awtrix NG)
     */
    public static function resolveIconName($icon) {
        $icon = preg_replace('/\.(gif|jpg|jpeg|png|bmp)$/i', '', trim((string)$icon));
        if (empty($icon)) return '';

        // Mapeo bidireccional inteligente de alias domésticos a iconos instalados en Awtrix
        $map = array(
            // Riego / Macetas / Plantas -> plant-watering
            'plant-watering' => 'plant-watering',
            '2284' => 'plant-watering',
            '2273' => 'plant-watering',
            'riego' => 'plant-watering',
            'regar' => 'plant-watering',
            'maceta' => 'plant-watering',
            'macetas' => 'plant-watering',
            'planta' => 'plant-watering',
            'plantas' => 'plant-watering',
            'plant' => 'plant-watering',
            'watering' => 'plant-watering',
            'arrosage' => 'plant-watering',
            'plante' => 'plant-watering',
            'fleur' => 'plant-watering',
            'fleurs' => 'plant-watering',

            // Agua / Fugas -> water-leaked
            'water-leaked' => 'water-leaked',
            '962' => 'water-leaked',
            '1077' => 'water-leaked',
            'agua' => 'water-leaked',
            'water' => 'water-leaked',
            'eau' => 'water-leaked',
            'fuga' => 'water-leaked',
            'fuite' => 'water-leaked',
            'leak' => 'water-leaked',

            // Temperatura -> thermometer2
            'thermometer2' => 'thermometer2',
            '1425' => 'thermometer2',
            'temp' => 'thermometer2',
            'temperatura' => 'thermometer2',
            'temperature' => 'thermometer2',
            'termo' => 'thermometer2',
            'outdoor-temp' => 'outdoor-temp',
            '2355' => 'outdoor-temp',
            'temp-high' => 'temp-high',
            'temp-low2' => 'temp-low2',

            // Puerta / Exterior -> outdoor
            'outdoor' => 'outdoor',
            '1841' => 'outdoor',
            'puerta' => 'outdoor',
            'door' => 'outdoor',
            'porte' => 'outdoor',

            // Batería -> battery-empty-2
            'battery-empty-2' => 'battery-empty-2',
            '27229' => 'battery-empty-2',
            'bateria' => 'battery-empty-2',
            'battery' => 'battery-empty-2',
            'pile' => 'battery-empty-2',

            // Navidad -> christmas
            'christmas' => 'christmas',
            'navidad' => 'christmas',
            'noel' => 'christmas'
        );
        $lower = strtolower($icon);
        if (isset($map[$lower])) {
            return $map[$lower];
        }
        return $icon;
    }

    /**
     * Obtiene la lista de iconos instalados en la memoria del panel Awtrix NG (/api/v1/files)
     */
    public static function fetchPanelIcons($ip, $port = 80, $password = '') {
        $ip = trim($ip);
        $port = intval($port) > 0 ? intval($port) : 80;
        if (empty($ip)) return array();

        $baseUrl = "http://{$ip}" . ($port != 80 ? ":{$port}" : "");
        $ch = curl_init("{$baseUrl}/api/v1/files");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $icons = array();
        if ($code === 200 && !empty($res)) {
            $decoded = json_decode($res, true);
            $files = isset($decoded['files']) && is_array($decoded['files']) ? $decoded['files'] : array();
            foreach ($files as $f) {
                if (!empty($f['name'])) {
                    $name = (string)$f['name'];
                    $slug = preg_replace('/\.(gif|jpg|jpeg|png|bmp)$/i', '', $name);
                    $icons[] = array(
                        'name' => $name,
                        'slug' => $slug,
                        'url' => "{$baseUrl}/ICONS/" . rawurlencode($name)
                    );
                }
            }
        }
        return $icons;
    }

    public function dismissNotification() {
        $dismissed = false;

        // 1. Endpoints nativos de Awtrix NG v1 con método DELETE
        $v1Endpoints = array(
            'v1/notifications/dismiss',
            'v1/notifications/active',
            'v1/notifications'
        );
        foreach ($v1Endpoints as $ep) {
            try {
                $this->apiRequest($ep, 'DELETE');
                log::add('awtrixng', 'info', "Notificación descartada/detenida con éxito en {$ep} (DELETE)");
                $dismissed = true;
                break;
            } catch (Exception $e) {}
        }

        // 2. Endpoints de compatibilidad Awtrix Light / Legacy (POST)
        if (!$dismissed) {
            $legacyEndpoints = array(
                array('notify/dismiss', 'POST', array()),
                array('notify', 'POST', array()),
                array('notify', 'POST', array('text' => ''))
            );
            foreach ($legacyEndpoints as $lep) {
                try {
                    $this->apiRequest($lep[0], $lep[1], $lep[2]);
                    log::add('awtrixng', 'info', "Notificación descartada/detenida en {$lep[0]}");
                    $dismissed = true;
                    break;
                } catch (Exception $e) {}
            }
        }

        // 3. Fallback Infalible: sobreescritura limpia en blanco de 100ms
        // En cualquier firmware de Awtrix, un mensaje urgente en blanco sin hold y duración de 100ms
        // desaloja de inmediato cualquier notificación fija bloqueada y devuelve el reloj al carrusel normal.
        if (!$dismissed) {
            try {
                if ($this->isV1Api()) {
                    $this->apiRequest('v1/notifications', 'POST', array('text' => ' ', 'durationMs' => 100, 'stack' => false));
                } else {
                    $this->apiRequest('notify', 'POST', array('text' => ' ', 'duration' => 1, 'stack' => false));
                }
                log::add('awtrixng', 'info', "Notificación fija cancelada mediante sobreescritura limpia de 100ms.");
                $dismissed = true;
            } catch (Exception $e) {}
        }

        return $dismissed;
    }

    public function sendNotification($title, $message, $options = array()) {
        $text = trim($message);
        $icon = '';
        $color = '';
        $durationSec = isset($options['duration']) && intval($options['duration']) > 0 ? intval($options['duration']) : 12;
        $hold = isset($options['hold']) && boolval($options['hold']);
        $stack = true;
        if (isset($options['stack'])) {
            $stack = boolval($options['stack']);
        } elseif (isset($options['priority']) && in_array(strtolower($options['priority']), array('urgent', 'urgente', 'alta', 'high'))) {
            $stack = false;
        }

        $lowerMsg = strtolower($text);
        $lowerTitle = strtolower(trim($title));

        // Detección de orden de descarte / borrado sin JSON (Multilingüe: ES / FR / EN)
        $dismissKeywords = array('dismiss', 'borrar', 'descartar', 'clear', 'off', 'annuler', 'effacer', 'supprimer', 'ignorer', 'stop');
        if (in_array($lowerMsg, $dismissKeywords) || in_array($lowerTitle, $dismissKeywords)) {
            $this->dismissNotification();
            return;
        }

        // Si en el título aparece 'urgente', 'urgent', 'prioridad' o 'priorite', marcar prioridad inmediata (stack = false)
        if (strpos($lowerTitle, 'urgente') !== false || strpos($lowerTitle, 'urgent') !== false || strpos($lowerTitle, 'prioridad') !== false || strpos($lowerTitle, 'priorite') !== false || strpos($lowerTitle, 'priority') !== false) {
            $stack = false;
        }

        // Flexible parameter parsing: title can be icon, color, key=value or pipe format
        if (!empty($title)) {
            $trimmedTitle = trim($title);
            if (strpos($trimmedTitle, '=') !== false) {
                parse_str(str_replace(' ', '&', $trimmedTitle), $parsedParams);
                if (!empty($parsedParams['icon'])) $icon = $parsedParams['icon'];
                if (!empty($parsedParams['color'])) $color = $this->normalizeColor($parsedParams['color']);
                if (!empty($parsedParams['duration'])) $durationSec = intval($parsedParams['duration']);
                if (isset($parsedParams['hold'])) $hold = boolval($parsedParams['hold']);
                if (isset($parsedParams['stack'])) $stack = boolval($parsedParams['stack']);
                if (!empty($parsedParams['sound'])) $options['sound'] = $parsedParams['sound'];
            } elseif (strpos($trimmedTitle, '|') !== false) {
                $rawParts = explode('|', $trimmedTitle);
                $parts = array();
                foreach ($rawParts as $rp) {
                    $item = strtolower(trim($rp));
                    if (in_array($item, array('urgente', 'urgent', 'prioridad', 'priorite', 'priority', 'inmediata', 'inmediato', 'immediate'))) {
                        $stack = false;
                    } else {
                        $parts[] = trim($rp);
                    }
                }
                if (isset($parts[0]) && trim($parts[0]) !== '') $icon = trim($parts[0]);
                if (isset($parts[1]) && trim($parts[1]) !== '') $color = $this->normalizeColor(trim($parts[1]));
                if (isset($parts[2])) {
                    $p2 = strtolower(trim($parts[2]));
                    if (in_array($p2, array('fija', 'fijo', 'hold', 'permanente', 'infinito', 'fixe', 'permanent'))) {
                        $hold = true;
                    } elseif (preg_match('/^([0-9]+)\s*m(in)?$/', $p2, $mMatch)) {
                        $durationSec = intval($mMatch[1]) * 60;
                    } elseif (preg_match('/^([0-9]+)\s*s(eg)?$/', $p2, $sMatch)) {
                        $durationSec = intval($sMatch[1]);
                    } elseif (intval($p2) > 0) {
                        $durationSec = intval($p2);
                    }
                }
            } elseif (in_array($lowerTitle, array('fija', 'fijo', 'hold', 'permanente', 'fixe', 'permanent'))) {
                $hold = true;
            } elseif (in_array($lowerTitle, array('urgente', 'urgent', 'prioridad', 'priorite', 'priority'))) {
                $stack = false;
            } elseif (is_numeric($trimmedTitle)) {
                $icon = $trimmedTitle;
            } elseif (preg_match('/^#?[0-9a-fA-F]{6}$/', $trimmedTitle) || in_array(strtolower($trimmedTitle), array('rojo', 'red', 'verde', 'green', 'azul', 'blue', 'amarillo', 'yellow', 'naranja', 'orange'))) {
                $color = $this->normalizeColor($trimmedTitle);
            } else {
                $icon = $trimmedTitle;
            }
        }

        if (empty($text) && !empty($title) && !$hold) {
            $text = trim($title);
            $icon = '';
        }

        // Evaluar variables y comandos de Jeedom (ej. #[Azotea][Temp]#, #2997#, etc.)
        if (class_exists('jeedom') && method_exists('jeedom', 'evaluateExpression')) {
            $text = jeedom::evaluateExpression($text);
        }

        // Apply device default icon if none provided
        if (empty($icon)) {
            $icon = $this->getConfiguration('default_icon', '');
        }
        $icon = self::resolveIconName($icon);

        // Apply device default text color if none provided
        if (empty($color)) {
            $color = isset($options['color']) ? $options['color'] : $this->getConfiguration('default_color', '');
        }
        $color = $this->normalizeColor($color);

        $repeat = isset($options['repeat']) ? intval($options['repeat']) : 2;
        $wakeup = isset($options['wakeup']) ? boolval($options['wakeup']) : true;
        if (isset($options['stack'])) {
            $stack = boolval($options['stack']);
        }

        $prioStr = $stack ? 'Normal (Encolada)' : 'URGENTE (Interrupción Inmediata)';

        if ($this->isV1Api()) {
            $v1Payload = array(
                'text' => $text,
                'repeat' => $repeat,
                'wakeup' => $wakeup,
                'stack' => $stack
            );
            if ($hold) {
                $v1Payload['hold'] = true;
            } else {
                $v1Payload['durationMs'] = $durationSec * 1000;
            }
            if (!empty($icon)) {
                $v1Payload['icon'] = $icon;
            }
            if (!empty($color)) {
                $v1Payload['textColor'] = $color;
            }
            if (!empty($options['sound'])) {
                $v1Payload['sound'] = $options['sound'];
            }
            if (!empty($options['rainbow']) && boolval($options['rainbow'])) {
                $v1Payload['effect'] = 'Rainbow';
            }

            try {
                $this->apiRequest('v1/notifications', 'POST', $v1Payload);
                $modeStr = ($hold ? 'FIJA (Hold)' : "duración {$durationSec}s") . " | [{$prioStr}]";
                log::add('awtrixng', 'info', "Notificación v1 enviada a Awtrix [{$modeStr}]: '{$text}' (Icono: " . ($icon ?: 'ninguno') . ", Color: " . ($color ?: 'por defecto') . ")");
                return;
            } catch (Exception $e) {
                log::add('awtrixng', 'debug', "Endpoint v1/notifications no respondió (" . $e->getMessage() . "), enviando por fallback legacy /api/notify");
            }
        }

        // Fallback Legacy API (/api/notify) - only reached if api_version is configured as legacy
        $payload = array(
            'text' => $text,
            'duration' => $durationSec,
            'repeat' => $repeat,
            'wakeup' => $wakeup,
            'stack' => $stack
        );
        if ($hold) {
            $payload['hold'] = true;
        }

        if (!empty($icon)) {
            $payload['icon'] = $icon;
        }
        if (!empty($color)) {
            $payload['color'] = $color;
        }
        if (!empty($options['sound'])) {
            $payload['sound'] = $options['sound'];
        }
        if (!empty($options['rainbow']) && boolval($options['rainbow'])) {
            $payload['rainbow'] = true;
        }

        $this->apiRequest('notify', 'POST', $payload);
        $modeStr = ($hold ? 'FIJA (Hold)' : "duración {$durationSec}s") . " | [{$prioStr}]";
        log::add('awtrixng', 'info', "Notificación enviada a Awtrix [{$modeStr}]: '{$text}' (Icono: " . ($icon ?: 'ninguno') . ", Color: " . ($color ?: 'por defecto') . ")");
    }

    public function sendCustomApp($appName, $text, $options = array()) {
        $appName = trim($appName);
        if (empty($appName)) {
            throw new Exception(__('Debes especificar el nombre de la app (en el título de la orden).', __FILE__));
        }

        // Buscar si ya existe configurada en la lista del carrusel del panel para heredar icono, color y duración
        $appsList = $this->getConfiguration('custom_apps_list', array());
        if (is_string($appsList)) {
            $decoded = json_decode($appsList, true);
            $appsList = is_array($decoded) ? $decoded : array();
        }
        $existingApp = null;
        if (is_array($appsList)) {
            foreach ($appsList as $a) {
                if (is_array($a) && isset($a['name']) && strtolower(trim($a['name'])) === strtolower($appName)) {
                    $existingApp = $a;
                    break;
                }
            }
        }
        if ($existingApp !== null) {
            if (empty($options['icon']) && !empty($existingApp['icon'])) {
                $options['icon'] = $existingApp['icon'];
            }
            if (empty($options['color']) && !empty($existingApp['color'])) {
                $options['color'] = $existingApp['color'];
            }
            if (empty($options['duration']) && !empty($existingApp['duration'])) {
                $options['duration'] = $existingApp['duration'];
            }
            if ($text === '' && !empty($existingApp['text'])) {
                $text = $existingApp['text'];
            }
        }

        $text = trim($text);
        if (class_exists('jeedom') && method_exists('jeedom', 'evaluateExpression')) {
            $text = jeedom::evaluateExpression($text);
        }

        // Detección inteligente de orden de borrado de la app del carrusel
        $lowerText = strtolower(trim($text));
        $deleteKeywords = array('delete', 'del', 'supprimer', 'borrar', 'quitar', 'clear', 'off', 'stop');
        if (in_array($lowerText, $deleteKeywords) || ($text === '' && $existingApp === null)) {
            $this->deleteCustomApp($appName);
            log::add('awtrixng', 'info', $this->getLogPrefix() . "App carrusel '{$appName}' eliminada de la pantalla.");
            return;
        }

        $color = isset($options['color']) ? $options['color'] : $this->getConfiguration('default_color', '');
        $color = $this->normalizeColor($color);
        $durationSec = isset($options['duration']) && intval($options['duration']) > 0 ? intval($options['duration']) : 10;

        $icon = '';
        if (!empty($options['icon'])) {
            $icon = $options['icon'];
        } elseif (!empty($options['title']) && is_numeric($options['title'])) {
            $icon = $options['title'];
        }
        $icon = self::resolveIconName($icon);

        if ($this->isV1Api()) {
            $v1Payload = array(
                'text' => trim($text),
                'durationMs' => $durationSec * 1000
            );
            if (!empty($icon)) {
                $v1Payload['icon'] = $icon;
            }
            if (!empty($color)) {
                $v1Payload['textColor'] = $color;
            }
            try {
                $this->apiRequest('v1/apps/pushed/' . rawurlencode($appName), 'PUT', $v1Payload);
                log::add('awtrixng', 'info', "App carrusel v1 '{$appName}' actualizada en Awtrix con texto: '{$text}' (Color: " . ($color ?: 'por defecto') . ")");
                return;
            } catch (Exception $e) {
                log::add('awtrixng', 'debug', "Endpoint v1/apps/pushed falló (" . $e->getMessage() . "), recurriendo a fallback /api/custom");
            }
        }

        // Fallback Legacy API (/api/custom?name=...)
        $payload = array(
            'text' => trim($text),
            'duration' => $durationSec
        );
        if (!empty($icon)) {
            $payload['icon'] = $icon;
        }
        if (!empty($color)) {
            $payload['color'] = $color;
        }
        if (!empty($options['rainbow']) && boolval($options['rainbow'])) {
            $payload['rainbow'] = true;
        }

        $this->apiRequest('custom?name=' . urlencode($appName), 'POST', $payload);
        log::add('awtrixng', 'info', "App carrusel '{$appName}' actualizada en Awtrix con texto: '{$text}' (Color: " . ($color ?: 'por defecto') . ")");
    }

    public function deleteCustomApp($appName) {
        $appName = trim($appName);
        if (empty($appName)) return;

        $names = array($appName);
        $lower = strtolower($appName);
        if ($lower !== $appName) {
            $names[] = $lower;
        }

        foreach ($names as $name) {
            // 1. Endpoint Awtrix NG v1: DELETE /api/v1/apps/pushed/{name}
            if ($this->isV1Api()) {
                try {
                    $this->apiRequest('v1/apps/pushed/' . rawurlencode($name), 'DELETE');
                    log::add('awtrixng', 'info', $this->getLogPrefix() . "App carrusel v1 '{$name}' eliminada (DELETE /api/v1/apps/pushed)");
                } catch (Exception $e) {
                    // Ignore
                }
                try {
                    $this->apiRequest('v1/apps/' . rawurlencode($name), 'DELETE');
                } catch (Exception $e) {
                    // Ignore
                }
            }

            // 2. Endpoint universal Awtrix Light / Awtrix NG: POST /api/custom?name={name} con cuerpo '{}'
            try {
                $this->apiRequest('custom?name=' . urlencode($name), 'POST', '{}');
                log::add('awtrixng', 'info', $this->getLogPrefix() . "App carrusel '{$name}' eliminada (POST /api/custom)");
            } catch (Exception $e) {
                // Ignore
            }

            // 3. Fallback adicional con cuerpo vacío
            try {
                $this->apiRequest('custom?name=' . urlencode($name), 'POST', '');
            } catch (Exception $e) {
                // Ignore
            }
        }
    }

    /**
     * Sincroniza la información / pantallas del carrusel configuradas específicamente en este panel.
     * Evalúa expresiones de Jeedom (como #[Objeto][Equipo][Comando]#) para mostrar datos en tiempo real.
     */
    public function syncPanelApps() {
        $ip = trim($this->getConfiguration('ip', ''));
        if (empty($ip)) return 0;

        $appsList = $this->getConfiguration('custom_apps_list', array());
        if (is_string($appsList)) {
            $decoded = json_decode($appsList, true);
            $appsList = is_array($decoded) ? $decoded : array();
        }
        if (!is_array($appsList) || empty($appsList)) {
            return 0;
        }

        $syncedCount = 0;
        foreach ($appsList as $app) {
            if (!is_array($app)) continue;
            $appName = isset($app['name']) ? trim($app['name']) : '';
            if (empty($appName)) continue;

            $enabled = (isset($app['enable']) && ($app['enable'] === 1 || $app['enable'] === '1' || $app['enable'] === true));
            if (!$enabled) {
                try {
                    $this->deleteCustomApp($appName);
                    log::add('awtrixng', 'info', $this->getLogPrefix() . "App carrusel deshabilitada '{$appName}' eliminada de la pantalla.");
                } catch (Exception $e) {
                    log::add('awtrixng', 'warning', $this->getLogPrefix() . "Error al eliminar app deshabilitada '{$appName}': " . $e->getMessage());
                }
                continue;
            }

            $rawText = isset($app['text']) ? (string)$app['text'] : '';
            if (class_exists('jeedom') && method_exists('jeedom', 'evaluateExpression')) {
                $evaluatedText = jeedom::evaluateExpression($rawText);
            } else {
                $evaluatedText = $rawText;
            }

            $options = array(
                'icon' => isset($app['icon']) ? trim($app['icon']) : '',
                'color' => isset($app['color']) ? trim($app['color']) : $this->getConfiguration('default_color', '#FFFFFF'),
                'duration' => isset($app['duration']) && intval($app['duration']) > 0 ? intval($app['duration']) : 10
            );

            $this->sendCustomApp($appName, (string)$evaluatedText, $options);
            $syncedCount++;
        }

        return $syncedCount;
    }

    /**
     * Copia la configuración visual y/o la lista de pantallas de información de este panel a otro panel destino,
     * preservando SIEMPRE la dirección IP, puerto y credenciales propias del panel destino,
     * y respetando por defecto el tamaño/modelo propio de cada panel (salvo que se indique lo contrario).
     */
    public function copyConfigurationTo($targetEqLogic, $copyOptions = array()) {
        if (!is_object($targetEqLogic) || $targetEqLogic->getId() == $this->getId()) {
            return false;
        }

        $copyVisual = isset($copyOptions['copy_visual']) ? boolval($copyOptions['copy_visual']) : true;
        $copyApps = isset($copyOptions['copy_apps']) ? boolval($copyOptions['copy_apps']) : true;
        $copyModel = isset($copyOptions['copy_model']) ? boolval($copyOptions['copy_model']) : false;

        if ($copyVisual) {
            $targetEqLogic->setConfiguration('default_color', $this->getConfiguration('default_color', '#FFFFFF'));
            $targetEqLogic->setConfiguration('default_icon', $this->getConfiguration('default_icon', '2284'));
            $targetEqLogic->setConfiguration('auto_refresh', $this->getConfiguration('auto_refresh', 1));
        }

        if ($copyApps) {
            $targetEqLogic->setConfiguration('custom_apps_list', $this->getConfiguration('custom_apps_list', array()));
        }

        if ($copyModel) {
            $targetEqLogic->setConfiguration('model', $this->getConfiguration('model', '32x8'));
            $targetEqLogic->setConfiguration('matrix_width', $this->getConfiguration('matrix_width', 32));
            $targetEqLogic->setConfiguration('matrix_height', $this->getConfiguration('matrix_height', 8));
        }

        // La IP, puerto y contraseña de $targetEqLogic NO se modifican para que cada panel conserve su dirección de red
        $targetEqLogic->save();
        log::add('awtrixng', 'info', $this->getLogPrefix() . 'Configuración copiada hacia el panel ' . $targetEqLogic->getLogPrefix());

        if (!empty($copyOptions['sync_now']) && $targetEqLogic->getIsEnable()) {
            try {
                $targetEqLogic->syncPanelApps();
            } catch (Exception $e) {
                log::add('awtrixng', 'warning', 'Configuración copiada a ' . $targetEqLogic->getName() . ', pero no se pudo sincronizar en vivo: ' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Directorio donde se almacenan las copias de seguridad locales en el servidor Jeedom
     */
    public static function getBackupDir() {
        $dir = dirname(__FILE__) . '/../../data/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Exporta la configuración completa de ESTE panel (incluyendo sus apps de información y opcionalmente los settings del dispositivo físico)
     */
    public function exportPanelData($includeDeviceSettings = true) {
        $appsList = $this->getConfiguration('custom_apps_list', array());
        if (is_string($appsList)) {
            $decoded = json_decode($appsList, true);
            $appsList = is_array($decoded) ? $decoded : array();
        }

        $panelData = array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'object_id' => $this->getObject_id(),
            'isEnable' => intval($this->getIsEnable()),
            'isVisible' => intval($this->getIsVisible()),
            'category' => $this->getCategory(),
            'configuration' => array(
                'model' => $this->getConfiguration('model', '32x8'),
                'matrix_width' => intval($this->getConfiguration('matrix_width', 32)),
                'matrix_height' => intval($this->getConfiguration('matrix_height', 8)),
                'ip' => $this->getConfiguration('ip', ''),
                'port' => intval($this->getConfiguration('port', 80)),
                'auth_password' => $this->getConfiguration('auth_password', ''),
                'default_color' => $this->getConfiguration('default_color', '#FFFFFF'),
                'default_icon' => $this->getConfiguration('default_icon', '2284'),
                'auto_refresh' => intval($this->getConfiguration('auto_refresh', 1)),
                'custom_apps_list' => $appsList
            ),
            'device_firmware_settings' => null
        );

        if ($includeDeviceSettings && !empty($panelData['configuration']['ip'])) {
            try {
                $ep = $this->isV1Api() ? 'v1/settings' : 'settings';
                $fwSettings = $this->apiRequest($ep, 'GET');
                if (is_array($fwSettings)) {
                    $panelData['device_firmware_settings'] = $fwSettings;
                }
            } catch (Exception $e) {
                // El panel físico puede estar apagado o desconectado al hacer backup; se guarda igualmente toda la configuración de Jeedom
            }
        }

        return $panelData;
    }

    /**
     * Genera un paquete completo de Copia de Seguridad (de todos los paneles o de uno específico)
     * y lo guarda opcionalmente en el servidor Jeedom en data/backups/
     */
    public static function createBackupBundle($panelId = 'all', $saveOnServer = true) {
        $panelsData = array();
        if ($panelId === 'all' || empty($panelId)) {
            foreach (self::byType('awtrixng') as $eqLogic) {
                $panelsData[] = $eqLogic->exportPanelData(true);
            }
        } else {
            $eqLogic = self::byId(intval($panelId));
            if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'awtrixng') {
                throw new Exception(__('Panel no encontrado para realizar la copia de seguridad.', __FILE__));
            }
            $panelsData[] = $eqLogic->exportPanelData(true);
        }

        $bundle = array(
            'plugin' => 'awtrixng',
            'backup_version' => '1.1',
            'created_at' => date('Y-m-d H:i:s'),
            'scope' => ($panelId === 'all' || empty($panelId)) ? 'all_panels' : 'single_panel',
            'global_config' => array(
                'timeout' => config::byKey('timeout', 'awtrixng', 5),
                'default_icon' => config::byKey('default_icon', 'awtrixng', '31805')
            ),
            'panels_count' => count($panelsData),
            'panels' => $panelsData
        );

        $savedFilename = '';
        if ($saveOnServer) {
            $dir = self::getBackupDir();
            $suffix = ($bundle['scope'] === 'all_panels') ? 'all_panels' : ('panel_' . intval($panelId));
            $savedFilename = 'awtrixng_backup_' . $suffix . '_' . date('Ymd_His') . '.json';
            $fullPath = $dir . '/' . $savedFilename;
            @file_put_contents($fullPath, json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            log::add('awtrixng', 'info', "Copia de seguridad creada correctamente: {$savedFilename} (" . count($panelsData) . " panel(es))");
        }

        $bundle['saved_filename'] = $savedFilename;
        return $bundle;
    }

    /**
     * Lista las copias de seguridad almacenadas en el servidor Jeedom
     */
    public static function listServerBackups() {
        $dir = self::getBackupDir();
        $files = glob($dir . '/awtrixng_backup_*.json');
        if (!is_array($files)) return array();

        rsort($files); // Más recientes primero
        $result = array();
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            $decoded = json_decode($content, true);
            $result[] = array(
                'filename' => basename($file),
                'size_kb' => round(filesize($file) / 1024, 1),
                'created_at' => isset($decoded['created_at']) ? $decoded['created_at'] : date('Y-m-d H:i:s', filemtime($file)),
                'panels_count' => isset($decoded['panels_count']) ? intval($decoded['panels_count']) : (isset($decoded['panels']) ? count($decoded['panels']) : 0),
                'scope' => isset($decoded['scope']) ? $decoded['scope'] : 'all_panels'
            );
        }
        return $result;
    }

    /**
     * Restaura una copia de seguridad desde un array decodificado de JSON.
     * Permite:
     * - Restaurar todos los paneles del backup (actualizando por IP/nombre o creando los que no existan).
     * - O restaurar los datos sobre un panel específico ($targetPanelId) conservando opcionalmente su propia IP y tamaño.
     */
    public static function restoreFromBackupData($bundle, $options = array()) {
        if (!is_array($bundle) || !isset($bundle['panels']) || !is_array($bundle['panels'])) {
            throw new Exception(__('El archivo o datos de copia de seguridad no tienen un formato válido de Awtrix NG.', __FILE__));
        }

        $targetPanelId = isset($options['target_panel_id']) ? $options['target_panel_id'] : 'auto';
        $preserveIp = isset($options['preserve_ip']) ? boolval($options['preserve_ip']) : false;
        $preserveSize = isset($options['preserve_size']) ? boolval($options['preserve_size']) : false;
        $restoreFirmware = isset($options['restore_firmware']) ? boolval($options['restore_firmware']) : false;

        // Restaurar configuración global si está incluida y es restauración general
        if ($targetPanelId === 'auto' && isset($bundle['global_config']) && is_array($bundle['global_config'])) {
            if (isset($bundle['global_config']['timeout'])) {
                config::save('timeout', $bundle['global_config']['timeout'], 'awtrixng');
            }
            if (isset($bundle['global_config']['default_icon'])) {
                config::save('default_icon', $bundle['global_config']['default_icon'], 'awtrixng');
            }
        }

        $restoredNames = array();
        $existingPanels = self::byType('awtrixng');

        // Caso 1: Restaurar específicamente sobre un panel concreto seleccionado por el usuario
        if ($targetPanelId !== 'auto' && is_numeric($targetPanelId) && count($bundle['panels']) > 0) {
            $targetEq = self::byId(intval($targetPanelId));
            if (!is_object($targetEq) || $targetEq->getEqType_name() !== 'awtrixng') {
                throw new Exception(__('El panel destino seleccionado para la restauración no existe.', __FILE__));
            }
            $sourcePanel = $bundle['panels'][0];
            self::applyBackupPanelToEqLogic($targetEq, $sourcePanel, $preserveIp, $preserveSize, $restoreFirmware);
            $restoredNames[] = $targetEq->getName() . ' (' . $targetEq->getConfiguration('ip', 'Sin IP') . ')';
            log::add('awtrixng', 'info', "Restauración de copia de seguridad aplicada sobre el panel: " . $targetEq->getLogPrefix());
            return $restoredNames;
        }

        // Caso 2: Restauración automática de todos los paneles del backup (coincidencia por ID, IP o Nombre, o creación si no existe)
        foreach ($bundle['panels'] as $pData) {
            if (!is_array($pData)) continue;
            $matchedEq = null;
            $pIp = isset($pData['configuration']['ip']) ? trim($pData['configuration']['ip']) : '';
            $pName = isset($pData['name']) ? trim($pData['name']) : 'Pantalla Awtrix';

            foreach ($existingPanels as $eq) {
                $eqIp = trim($eq->getConfiguration('ip', ''));
                if ((!empty($pIp) && $eqIp === $pIp) || strtolower($eq->getName()) === strtolower($pName)) {
                    $matchedEq = $eq;
                    break;
                }
            }

            if (!is_object($matchedEq)) {
                $matchedEq = new awtrixng();
                $matchedEq->setEqType_name('awtrixng');
                $matchedEq->setName($pName);
                $matchedEq->setIsEnable(isset($pData['isEnable']) ? intval($pData['isEnable']) : 1);
                $matchedEq->setIsVisible(isset($pData['isVisible']) ? intval($pData['isVisible']) : 1);
                if (!empty($pData['object_id'])) {
                    $matchedEq->setObject_id($pData['object_id']);
                }
            }

            self::applyBackupPanelToEqLogic($matchedEq, $pData, $preserveIp, $preserveSize, $restoreFirmware);
            $restoredNames[] = $matchedEq->getName() . ' (' . $matchedEq->getConfiguration('ip', 'Sin IP') . ')';
        }

        log::add('awtrixng', 'info', "Copia de seguridad restaurada con éxito (" . count($restoredNames) . " panel(es)): " . implode(', ', $restoredNames));
        return $restoredNames;
    }

    protected static function applyBackupPanelToEqLogic($eqLogic, $pData, $preserveIp = false, $preserveSize = false, $restoreFirmware = false) {
        $conf = isset($pData['configuration']) && is_array($pData['configuration']) ? $pData['configuration'] : array();

        if (!$preserveIp) {
            if (isset($conf['ip']) && !empty($conf['ip'])) {
                $eqLogic->setConfiguration('ip', $conf['ip']);
            }
            if (isset($conf['port'])) {
                $eqLogic->setConfiguration('port', intval($conf['port']));
            }
            if (isset($conf['auth_password'])) {
                $eqLogic->setConfiguration('auth_password', $conf['auth_password']);
            }
        }

        if (!$preserveSize) {
            if (isset($conf['model'])) {
                $eqLogic->setConfiguration('model', $conf['model']);
            }
            if (isset($conf['matrix_width'])) {
                $eqLogic->setConfiguration('matrix_width', intval($conf['matrix_width']));
            }
            if (isset($conf['matrix_height'])) {
                $eqLogic->setConfiguration('matrix_height', intval($conf['matrix_height']));
            }
        }

        if (isset($conf['default_color'])) {
            $eqLogic->setConfiguration('default_color', $conf['default_color']);
        }
        if (isset($conf['default_icon'])) {
            $eqLogic->setConfiguration('default_icon', $conf['default_icon']);
        }
        if (isset($conf['auto_refresh'])) {
            $eqLogic->setConfiguration('auto_refresh', intval($conf['auto_refresh']));
        }
        if (isset($conf['custom_apps_list'])) {
            $eqLogic->setConfiguration('custom_apps_list', $conf['custom_apps_list']);
        }

        $eqLogic->save();

        // Si se solicita restaurar también ajustes en el reloj físico y tiene IP activa
        if ($restoreFirmware && !empty($pData['device_firmware_settings']) && is_array($pData['device_firmware_settings'])) {
            try {
                if ($eqLogic->isV1Api()) {
                    $eqLogic->apiRequest('v1/settings', 'PATCH', $pData['device_firmware_settings']);
                } else {
                    $eqLogic->apiRequest('settings', 'POST', $pData['device_firmware_settings']);
                }
            } catch (Exception $e) {
                log::add('awtrixng', 'warning', $eqLogic->getLogPrefix() . 'No se pudieron enviar los settings de firmware al reloj durante la restauración: ' . $e->getMessage());
            }
        }

        // Sincronizar las pantallas del carrusel restauradas si el panel está habilitado
        if ($eqLogic->getIsEnable()) {
            try {
                $eqLogic->syncPanelApps();
            } catch (Exception $e) {
                // Ignorar si el reloj está apagado en el momento de restaurar
            }
        }
    }

    /**
     * Biblioteca integrada de efectos de sonido y melodías RTTTL y MP3
     */
    public static function getBuiltinSoundLibrary() {
        return array(
            array(
                'id' => 'water',
                'name' => __('Agua / Riego de Plantas', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-tint',
                'color' => '#00BFFF',
                'rtttl' => 'water:d=4,o=5,b=140:16c,16e,16g,16c6,16e6,16g6,32p,16g6,16e6,16c6,16g,16e,16c',
                'mp3_file' => 'water.mp3',
                'description' => __('Arpegio acuático fluido ideal para electroválvulas y avisos de riego.', __FILE__)
            ),
            array(
                'id' => 'alarm',
                'name' => __('Alarma de Emergencia / Fuego', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-fire',
                'color' => '#FF0000',
                'rtttl' => 'alarm:d=4,o=5,b=160:8c,8e,8g,8c6,8g,8e,8c,8p,8c,8e,8g,8c6,8g,8e,8c',
                'mp3_file' => 'alarm.mp3',
                'description' => __('Alarma penetrante continua para humo, fuego o intrusión.', __FILE__)
            ),
            array(
                'id' => 'siren',
                'name' => __('Sirena de Policía / Intrusión', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-bullhorn',
                'color' => '#DC2626',
                'rtttl' => 'siren:d=4,o=5,b=120:16c6,16g6,16c6,16g6,16c6,16g6,16c6,16g6,16c6,16g6,16c6,16g6',
                'mp3_file' => 'siren.mp3',
                'description' => __('Tono oscilante bitonal de alta intensidad.', __FILE__)
            ),
            array(
                'id' => 'doorbell',
                'name' => __('Timbre de Casa (Ding-Dong)', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-bell',
                'color' => '#F59E0B',
                'rtttl' => 'doorbell:d=4,o=5,b=100:8e6,4c6',
                'mp3_file' => 'doorbell.mp3',
                'description' => __('Clásico sonido de llamada a la puerta de dos notas.', __FILE__)
            ),
            array(
                'id' => 'power_on',
                'name' => __('Encendido / Conexión (Power On)', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-plug',
                'color' => '#10B981',
                'rtttl' => 'power_on:d=4,o=5,b=180:16c,16e,16g,8c6',
                'mp3_file' => 'power_on.mp3',
                'description' => __('Acorde ascendente positivo para dispositivos que se encienden.', __FILE__)
            ),
            array(
                'id' => 'power_off',
                'name' => __('Apagado / Desconexión (Power Off)', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-power-off',
                'color' => '#64748B',
                'rtttl' => 'power_off:d=4,o=5,b=180:16c6,16g,16e,8c',
                'mp3_file' => 'power_off.mp3',
                'description' => __('Acorde descendente para fin de tarea o apagado.', __FILE__)
            ),
            array(
                'id' => 'chime',
                'name' => __('Notificación Suave (Chime)', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-comment-dots',
                'color' => '#3B82F6',
                'rtttl' => 'chime:d=4,o=5,b=160:16g6,16c7',
                'mp3_file' => 'chime.mp3',
                'description' => __('Campanilla discreta de dos notas para avisos normales.', __FILE__)
            ),
            array(
                'id' => 'success',
                'name' => __('Éxito / Tarea Completada', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-check-circle',
                'color' => '#22C55E',
                'rtttl' => 'success:d=4,o=5,b=160:16c,16e,16g,8b,4c6',
                'mp3_file' => 'success.mp3',
                'description' => __('Fanfarria triunfal para lavadora lista, recarga terminada, etc.', __FILE__)
            ),
            array(
                'id' => 'warning',
                'name' => __('Advertencia / Atento', __FILE__),
                'category' => 'home',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => '#EAB308',
                'rtttl' => 'warning:d=4,o=5,b=120:8c6,8p,8c6,8p,4f#5',
                'mp3_file' => 'warning.mp3',
                'description' => __('Doble tono seco para llamar la atención sin estridencias.', __FILE__)
            ),
            array(
                'id' => 'mario',
                'name' => 'Super Mario Bros',
                'category' => 'music',
                'icon' => 'fas fa-gamepad',
                'color' => '#EF4444',
                'rtttl' => 'mario:d=4,o=5,b=100:16e6,16e6,32p,8e6,16c6,8e6,8g6,8p,8g,8p',
                'mp3_file' => 'mario.mp3',
                'description' => __('Tema clásico de inicio de Nintendo Super Mario.', __FILE__)
            ),
            array(
                'id' => 'starwars',
                'name' => 'Star Wars (Marcha Imperial)',
                'category' => 'music',
                'icon' => 'fas fa-jedi',
                'color' => '#38BDF8',
                'rtttl' => 'Imperial:d=4,o=5,b=100:e,e,e,8c,16g,e,8c,16g,2e',
                'mp3_file' => 'starwars.mp3',
                'description' => __('Marcha imperial de Darth Vader.', __FILE__)
            ),
            array(
                'id' => 'mission_imp',
                'name' => 'Misión Imposible',
                'category' => 'music',
                'icon' => 'fas fa-user-secret',
                'color' => '#8B5CF6',
                'rtttl' => 'MissionImp:d=16,o=6,b=95:d,d#,d,d#,d,d#,d,d#,d,d#,d,d#,d,d#,d,d#,4d,8f,8g,4d,8c,8c#',
                'mp3_file' => 'mission_imp.mp3',
                'description' => __('Melodía de suspense y acción rápida.', __FILE__)
            ),
            array(
                'id' => 'tetris',
                'name' => 'Tetris (Korobeiniki)',
                'category' => 'music',
                'icon' => 'fas fa-th',
                'color' => '#EC4899',
                'rtttl' => 'tetris:d=4,o=5,b=160:e6,8b,8c6,8d6,16e6,16d6,8c6,8b,a,8a,8c6,e6,8d6,8c6,b,8b,8c6,d6,e6,c6,a,2a',
                'mp3_file' => 'tetris.mp3',
                'description' => __('Banda sonora retro de Tetris.', __FILE__)
            ),
            array(
                'id' => 'zelda',
                'name' => 'Zelda (Secret Found)',
                'category' => 'music',
                'icon' => 'fas fa-gem',
                'color' => '#14B8A6',
                'rtttl' => 'zelda_secret:d=4,o=5,b=140:16g,16f#,16d#,16a,16g#,16e,16g#,8c6',
                'mp3_file' => 'zelda.mp3',
                'description' => __('Sonido mítico al abrir un cofre o descubrir un secreto.', __FILE__)
            ),
            array(
                'id' => 'pacman',
                'name' => 'Pac-Man Arcade',
                'category' => 'music',
                'icon' => 'fas fa-ghost',
                'color' => '#FBBF24',
                'rtttl' => 'pacman:d=4,o=5,b=110:32b,32b6,32f#6,32d#6,32b6,32f#6,16d#6,32c,32c7,32g6,32e6,32c7,32g6,16e6',
                'mp3_file' => 'pacman.mp3',
                'description' => __('Tono arcade retro de Pacman.', __FILE__)
            )
        );
    }

    public function fetchAudioFiles() {
        $ip = trim($this->getConfiguration('ip', ''));
        $port = intval($this->getConfiguration('port', 80)) > 0 ? intval($this->getConfiguration('port', 80)) : 80;
        $password = trim($this->getConfiguration('auth_password', ''));
        if (empty($ip)) return array();

        $baseUrl = "http://{$ip}" . ($port != 80 ? ":{$port}" : "");
        $ch = curl_init("{$baseUrl}/api/v1/files");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $audioFiles = array();
        if ($code === 200 && !empty($res)) {
            $decoded = json_decode($res, true);
            $files = isset($decoded['files']) && is_array($decoded['files']) ? $decoded['files'] : array();
            foreach ($files as $f) {
                if (!empty($f['name'])) {
                    $name = (string)$f['name'];
                    if (preg_match('/\.(mp3|wav|ogg)$/i', $name)) {
                        $audioFiles[] = array(
                            'name' => $name,
                            'slug' => preg_replace('/\.(mp3|wav|ogg)$/i', '', $name),
                            'size' => isset($f['size']) ? intval($f['size']) : 0,
                            'size_str' => isset($f['size']) ? round(intval($f['size']) / 1024, 1) . ' KB' : ''
                        );
                    }
                }
            }
        }
        return $audioFiles;
    }

    public function uploadAudioFile($filePath, $fileName) {
        $ip = trim($this->getConfiguration('ip', ''));
        $port = intval($this->getConfiguration('port', 80)) > 0 ? intval($this->getConfiguration('port', 80)) : 80;
        $password = trim($this->getConfiguration('auth_password', ''));
        if (empty($ip)) throw new Exception(__('IP no configurada', __FILE__));
        if (!file_exists($filePath)) throw new Exception(__('Archivo no encontrado en el servidor', __FILE__));

        $baseUrl = "http://{$ip}" . ($port != 80 ? ":{$port}" : "");
        
        $postData = array('file' => new CURLFile($filePath, 'audio/mpeg', $fileName));
        $ch = curl_init("{$baseUrl}/api/v1/files");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            log::add('awtrixng', 'info', "Archivo de audio {$fileName} subido correctamente a Awtrix NG");
            return true;
        }

        $ch = curl_init("{$baseUrl}/edit");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('data' => new CURLFile($filePath, 'audio/mpeg', '/' . ltrim($fileName, '/'))));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            log::add('awtrixng', 'info', "Archivo de audio {$fileName} subido mediante /edit a Awtrix NG");
            return true;
        }

        throw new Exception(__("No se pudo subir el archivo de audio {$fileName} a la pantalla (HTTP {$code})", __FILE__));
    }

    public function deleteAudioFile($fileName) {
        $ip = trim($this->getConfiguration('ip', ''));
        $port = intval($this->getConfiguration('port', 80)) > 0 ? intval($this->getConfiguration('port', 80)) : 80;
        $password = trim($this->getConfiguration('auth_password', ''));
        if (empty($ip)) throw new Exception(__('IP no configurada', __FILE__));

        $baseUrl = "http://{$ip}" . ($port != 80 ? ":{$port}" : "");
        
        $ch = curl_init("{$baseUrl}/api/v1/files?file=" . rawurlencode($fileName));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            log::add('awtrixng', 'info', "Archivo de audio {$fileName} eliminado de Awtrix NG");
            return true;
        }

        $ch = curl_init("{$baseUrl}/edit?file=" . rawurlencode('/' . ltrim($fileName, '/')));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            log::add('awtrixng', 'info', "Archivo de audio {$fileName} eliminado de Awtrix NG mediante /edit");
            return true;
        }

        throw new Exception(__("No se pudo eliminar el archivo {$fileName} de la pantalla (HTTP {$code})", __FILE__));
    }

    public function getCustomRtttls() {
        $raw = $this->getConfiguration('custom_rtttls', '[]');
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($decoded) ? $decoded : array();
    }

    public function addCustomRtttl($name, $code) {
        $name = trim($name);
        $code = trim($code);
        if (empty($name) || empty($code)) throw new Exception(__('Nombre y código RTTTL son obligatorios', __FILE__));
        $list = $this->getCustomRtttls();
        $id = 'usr_' . substr(md5($name . microtime()), 0, 8);
        $list[] = array(
            'id' => $id,
            'name' => $name,
            'rtttl' => $code,
            'created_at' => date('Y-m-d H:i:s')
        );
        $this->setConfiguration('custom_rtttls', json_encode($list, JSON_UNESCAPED_UNICODE));
        $this->save();
        return $id;
    }

    public function removeCustomRtttl($id) {
        $list = $this->getCustomRtttls();
        $newList = array();
        foreach ($list as $item) {
            if ($item['id'] !== $id) {
                $newList[] = $item;
            }
        }
        $this->setConfiguration('custom_rtttls', json_encode($newList, JSON_UNESCAPED_UNICODE));
        $this->save();
        return true;
    }
}

class awtrixngCmd extends cmd {

    public function preSave() {
        $actionCmds = array(
            'refresh' => 'other',
            'power_on' => 'other',
            'power_off' => 'other',
            'toggle_power' => 'other',
            'set_brightness' => 'slider',
            'next_app' => 'other',
            'prev_app' => 'other',
            'notify' => 'message',
            'dismiss_notif' => 'other',
            'custom_app' => 'message',
            'delete_app' => 'message',
            'sync_apps' => 'other',
            'play_sound' => 'message',
            'reboot' => 'other',
            'tts' => 'message',
            'set_volume' => 'slider'
        );
        $infoCmds = array(
            'online' => 'binary',
            'battery' => 'numeric',
            'lux' => 'numeric',
            'temp' => 'numeric',
            'humidity' => 'numeric',
            'power_state' => 'binary',
            'brightness_state' => 'numeric',
            'current_app' => 'string',
            'wifi_signal' => 'numeric',
            'uptime_str' => 'string'
        );
        $logicalId = $this->getLogicalId();
        if (isset($actionCmds[$logicalId])) {
            $this->setType('action');
            if (empty($this->getSubType()) || in_array($this->getSubType(), array('string', 'numeric', 'binary', 'other'))) {
                $this->setSubType($actionCmds[$logicalId]);
            }
        } elseif (isset($infoCmds[$logicalId])) {
            $this->setType('info');
            if (empty($this->getSubType())) {
                $this->setSubType($infoCmds[$logicalId]);
            }
        } elseif ($this->getConfiguration('is_preset_notif') == 1 || strpos($logicalId, 'preset_notif_') === 0) {
            $this->setType('action');
            $this->setSubType('other');
        }
    }

    public function execute($options = array()) {
        $eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic)) {
            throw new Exception(__('Equipo no encontrado', __FILE__));
        }

        $logicalId = $this->getLogicalId();
        $title = isset($options['title']) ? $options['title'] : '';
        $message = isset($options['message']) ? $options['message'] : '';

        switch ($logicalId) {
            case 'refresh':
                $eqLogic->refresh();
                break;

            case 'power_on':
                $eqLogic->setPower(true);
                break;

            case 'power_off':
                $eqLogic->setPower(false);
                break;

            case 'toggle_power':
                $current = $eqLogic->getCmd(null, 'power_state');
                $state = is_object($current) ? $current->execCmd() : 0;
                $eqLogic->setPower(!$state);
                break;

            case 'set_brightness':
                $slider = isset($options['slider']) ? $options['slider'] : 50;
                $eqLogic->setBrightness($slider);
                break;

            case 'set_volume':
                $slider = isset($options['slider']) ? $options['slider'] : 50;
                $eqLogic->setVolume($slider);
                break;

            case 'next_app':
                $eqLogic->nextApp();
                break;

            case 'prev_app':
                $eqLogic->prevApp();
                break;

            case 'reboot':
                $eqLogic->reboot();
                break;

            case 'play_sound':
                $t = trim((string)$title);
                $m = trim((string)$message);
                if (!empty($t) && !empty($m)) {
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $t) && (strpos($m, 'd=') === 0 || strpos($m, 'b=') === 0 || strpos($m, 'o=') === 0)) {
                        $sound = $t . ':' . $m;
                    } else {
                        $sound = !empty($m) ? $m : $t;
                    }
                } else {
                    $sound = !empty($m) ? $m : $t;
                }
                $eqLogic->playSound($sound);
                break;

            case 'tts':
                $text = !empty($message) ? $message : $title;
                $eqLogic->sendTTS($text);
                break;

            case 'notify':
                $extraOptions = array();
                $targetJson = null;
                if (is_string($message) && substr(trim($message), 0, 1) === '{') {
                    $targetJson = json_decode($message, true);
                } elseif (is_string($title) && substr(trim($title), 0, 1) === '{') {
                    $targetJson = json_decode($title, true);
                }
                if (is_array($targetJson)) {
                    $msgText = isset($targetJson['text']) ? $targetJson['text'] : $message;
                    $msgIcon = isset($targetJson['icon']) ? $targetJson['icon'] : $title;
                    $eqLogic->sendNotification($msgIcon, $msgText, $targetJson);
                    break;
                }
                $eqLogic->sendNotification($title, $message, $extraOptions);
                break;

            case 'dismiss_notif':
            case 'dismiss_notification':
                $eqLogic->dismissNotification();
                break;

            case 'custom_app':
                $appName = trim((string)$title);
                $extraOptions = array();
                if (strpos($appName, '|') !== false) {
                    $rawParts = explode('|', $appName);
                    $appName = trim($rawParts[0]);
                    if (isset($rawParts[1]) && trim($rawParts[1]) !== '') {
                        $p1 = trim($rawParts[1]);
                        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $p1)) {
                            $extraOptions['color'] = $p1;
                        } else {
                            $extraOptions['icon'] = $p1;
                        }
                    }
                    if (isset($rawParts[2]) && trim($rawParts[2]) !== '') {
                        $p2 = trim($rawParts[2]);
                        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $p2)) {
                            $extraOptions['color'] = $p2;
                        } elseif (is_numeric($p2)) {
                            $extraOptions['duration'] = intval($p2);
                        } else {
                            $extraOptions['icon'] = $p2;
                        }
                    }
                    if (isset($rawParts[3]) && trim($rawParts[3]) !== '') {
                        $extraOptions['duration'] = intval($rawParts[3]);
                    }
                }
                if (empty($appName)) $appName = 'custom';
                if (is_string($message) && substr(trim($message), 0, 1) === '{') {
                    $json = json_decode($message, true);
                    if (is_array($json)) {
                        $msgText = isset($json['text']) ? $json['text'] : '';
                        $merged = array_merge($extraOptions, $json);
                        $eqLogic->sendCustomApp($appName, $msgText, $merged);
                        break;
                    }
                }
                $eqLogic->sendCustomApp($appName, $message, $extraOptions);
                break;

            case 'delete_app':
                $appName = !empty($title) ? $title : $message;
                $eqLogic->deleteCustomApp($appName);
                break;

            case 'sync_apps':
                $eqLogic->syncPanelApps();
                break;

            default:
                if ($this->getConfiguration('is_preset_notif') == 1 || strpos($logicalId, 'preset_notif_') === 0) {
                    $icon = $this->getConfiguration('preset_icon', '');
                    $text = $this->getConfiguration('preset_text', '');
                    $color = $this->getConfiguration('preset_color', '');
                    $duration = intval($this->getConfiguration('preset_duration', 12));
                    $hold = intval($this->getConfiguration('preset_hold', 0)) == 1;
                    $stack = intval($this->getConfiguration('preset_stack', 1)) == 1;
                    $eqLogic->sendNotification($icon, $text, array('color' => $color, 'duration' => $duration, 'hold' => $hold, 'stack' => $stack));
                    $modeStr = ($hold ? 'FIJA' : "{$duration}s") . ($stack ? '' : ' | URGENTE');
                    log::add('awtrixng', 'info', "Notificación preconfigurada ejecutada [{$modeStr}]: {$this->getName()} (Icono: {$icon}, Texto: {$text})");
                    break;
                }
                log::add('awtrixng', 'debug', "Comando {$logicalId} ejecutado sin acción específica");
                break;
        }
    }
}
?>
