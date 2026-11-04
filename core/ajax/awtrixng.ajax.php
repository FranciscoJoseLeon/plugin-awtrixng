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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Acceso no autorizado', __FILE__));
    }

    ajax::init();

    $action = init('action');

    if ($action == 'testConnection') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        $password = trim(init('password', ''));
        $eqLogicId = intval(init('eqLogic_id', 0));

        if (empty($ip)) {
            throw new Exception(__('Debes introducir una dirección IP válida', __FILE__));
        }

        $detected = awtrixng::probeAndDetectHardware($ip, $port, $password);

        if ($eqLogicId > 0) {
            $eqLogic = eqLogic::byId($eqLogicId);
            if (is_object($eqLogic) && $eqLogic->getEqType_name() === 'awtrixng') {
                $eqLogic->setConfiguration('ip', $ip);
                $eqLogic->setConfiguration('port', $port);
                if (!empty($password)) {
                    $eqLogic->setConfiguration('auth_password', $password);
                }
                $eqLogic->autoDetectAndConfigure(true);
            }
        }

        ajax::success(array(
            'status' => 'ok',
            'detected' => $detected,
            'data' => $detected['raw']
        ));
    }

    if ($action == 'getIconData') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        if ($port <= 0) $port = 80;
        $icon = awtrixng::resolveIconName(init('icon', ''));
        $password = trim(init('password', ''));
        if (empty($password) && !empty($ip)) {
            foreach (eqLogic::byType('awtrixng') as $eq) {
                if ($eq->getConfiguration('ip') === $ip) {
                    $password = trim($eq->getConfiguration('auth_password', ''));
                    if (!empty($password)) break;
                }
            }
        }
        if (empty($ip) || empty($icon)) {
            ajax::success(array('pixels' => array()));
        }
        $ch = curl_init("http://{$ip}:{$port}/ICONS/" . rawurlencode($icon . '.gif'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }
        $rawBytes = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && !empty($rawBytes)) {
            $pixels = array();
            $w = 8;
            $h = 8;
            if (function_exists('imagecreatefromstring')) {
                $im = @imagecreatefromstring($rawBytes);
                if ($im !== false) {
                    $w = imagesx($im);
                    $h = imagesy($im);
                    for ($y = 0; $y < $h; $y++) {
                        for ($x = 0; $x < $w; $x++) {
                            $rgb = imagecolorat($im, $x, $y);
                            $colors = imagecolorsforindex($im, $rgb);
                            $alpha = isset($colors['alpha']) ? (127 - $colors['alpha']) / 127.0 : 1.0;
                            $r = intval(round($colors['red'] * $alpha));
                            $g = intval(round($colors['green'] * $alpha));
                            $b = intval(round($colors['blue'] * $alpha));
                            $pixels[] = ($r << 16) | ($g << 8) | $b;
                        }
                    }
                    imagedestroy($im);
                }
            }
            ajax::success(array(
                'width' => $w,
                'height' => $h,
                'pixels' => $pixels,
                'base64' => base64_encode($rawBytes)
            ));
        }
        ajax::success(array('pixels' => array()));
    }

    if ($action == 'panelControl') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        if ($port <= 0) $port = 80;
        $cmd = trim(init('cmd'));
        $val = init('val');
        $password = trim(init('password', ''));
        if (empty($password) && !empty($ip)) {
            foreach (eqLogic::byType('awtrixng') as $eq) {
                if ($eq->getConfiguration('ip') === $ip) {
                    $password = trim($eq->getConfiguration('auth_password', ''));
                    if (!empty($password)) break;
                }
            }
        }
        if (empty($ip)) {
            throw new Exception(__('IP requerida', __FILE__));
        }
        $baseUrl = "http://{$ip}:{$port}";
        $method = 'POST';
        $ep = '';
        $payload = null;
        if ($cmd === 'power') {
            $method = 'PATCH';
            $ep = '/api/v1/display';
            $payload = array('power' => intval($val) === 1);
        } elseif ($cmd === 'brightness') {
            $method = 'PATCH';
            $ep = '/api/v1/settings';
            $payload = array('brightness' => max(0, min(255, intval($val))));
        } elseif ($cmd === 'autoBrightness') {
            $method = 'PATCH';
            $ep = '/api/v1/settings';
            $payload = array('autoBrightness' => intval($val) === 1);
        } elseif ($cmd === 'autoTransition') {
            $method = 'PATCH';
            $ep = '/api/v1/settings';
            $payload = array('autoTransition' => intval($val) === 1);
        } elseif ($cmd === 'prev') {
            $method = 'POST';
            $ep = '/api/v1/apps/previous';
            $payload = array();
        } elseif ($cmd === 'next') {
            $method = 'POST';
            $ep = '/api/v1/apps/next';
            $payload = array();
        } elseif ($cmd === 'dismiss') {
            $method = 'DELETE';
            $ep = '/api/v1/notifications/active';
        }
        if (!empty($ep)) {
            $ch = curl_init($baseUrl . $ep);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            if (!empty($password)) {
                curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
            }
            curl_exec($ch);
            curl_close($ch);
        }
        ajax::success(array('ok' => true));
    }

    if ($action == 'getPanelIcons') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        $password = trim(init('password', ''));
        $icons = awtrixng::fetchPanelIcons($ip, $port, $password);
        ajax::success($icons);
    }

    if ($action == 'getLiveScreen') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        if ($port <= 0) $port = 80;
        $password = trim(init('password', ''));
        if (empty($ip)) {
            ajax::success(array('pixels' => array()));
        }
        $ch = curl_init("http://{$ip}:{$port}/api/v1/display/screen");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && !empty($res)) {
            $decoded = json_decode($res, true);
            if (is_array($decoded)) {
                ajax::success($decoded);
            }
        }
        ajax::success(array('pixels' => array()));
    }

    if ($action == 'sendQuickNotification') {
        $ip = trim(init('ip'));
        $port = intval(init('port', 80));
        if ($port <= 0) $port = 80;
        $text = trim(init('text', 'Regando las flores'));
        $icon = awtrixng::resolveIconName(init('icon', ''));
        $color = trim(init('color', '#00FF00'));
        $duration = intval(init('duration', 12));
        $hold = intval(init('hold', 0)) == 1;
        $stack = (init('priority') === 'urgent' || intval(init('stack', 1)) == 0) ? false : true;

        $eqId = intval(init('eqLogic_id', 0));
        if ($eqId > 0) {
            $eq = eqLogic::byId($eqId);
            if (is_object($eq)) {
                $eq->sendNotification($icon, $text, array('color' => $color, 'duration' => $duration, 'hold' => $hold, 'stack' => $stack));
                ajax::success(array('status' => 'ok', 'message' => __('Notificación enviada correctamente a tu pantalla Awtrix NG', __FILE__)));
            }
        }
        $password = trim(init('password', ''));
        $eqId = intval(init('eqLogic_id', 0));

        if (empty($password) && $eqId > 0) {
            $eq = eqLogic::byId($eqId);
            if (is_object($eq)) {
                $password = trim($eq->getConfiguration('auth_password', ''));
            }
        }
        if (empty($password) && !empty($ip)) {
            foreach (eqLogic::byType('awtrixng') as $eq) {
                if ($eq->getConfiguration('ip') === $ip) {
                    $password = trim($eq->getConfiguration('auth_password', ''));
                    if (!empty($password)) break;
                }
            }
        }

        if (empty($ip)) {
            throw new Exception(__('Debes introducir la dirección IP o hostname de tu pantalla Awtrix NG', __FILE__));
        }

        // Native Awtrix NG v1 API (/api/v1/notifications)
        $v1Url = "http://{$ip}:{$port}/api/v1/notifications";
        $v1Payload = array(
            'text' => $text,
            'repeat' => 2,
            'wakeup' => true,
            'stack' => false
        );
        if ($hold) {
            $v1Payload['hold'] = true;
        } else {
            $v1Payload['durationMs'] = $duration * 1000;
        }
        if (!empty($icon)) {
            $v1Payload['icon'] = $icon;
        }
        if (!empty($color)) {
            $v1Payload['textColor'] = '#' . strtoupper(ltrim($color, '#'));
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $v1Url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($v1Payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (!empty($password)) {
            curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        }
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            ajax::success(array('status' => 'ok', 'message' => __('Notificación enviada correctamente a tu pantalla Awtrix NG', __FILE__)));
        }

        $detail = !empty($curlErr) ? $curlErr : (!empty($res) ? $res : "HTTP {$code}");
        throw new Exception(__("Error al comunicar con la pantalla Awtrix NG en {$v1Url}: {$detail}", __FILE__));
    }

    if ($action == 'sendBroadcastNotification') {
        $text = trim(init('text', 'Regando las flores'));
        $icon = trim(init('icon', ''));
        $color = trim(init('color', '#00FF00'));

        $eqLogics = eqLogic::byType('awtrixng', true);
        $sent = 0;
        $errors = array();
        foreach ($eqLogics as $eqLogic) {
            $ip = trim($eqLogic->getConfiguration('ip', ''));
            if (empty($ip)) continue;
            try {
                $eqLogic->sendNotification($icon, $text, array('color' => $color));
                $sent++;
            } catch (Exception $e) {
                $errors[] = $eqLogic->getName() . ': ' . $e->getMessage();
            }
        }

        if ($sent == 0 && !empty($errors)) {
            throw new Exception(implode(' | ', $errors));
        }
        ajax::success(array(
            'status' => 'ok',
            'sent' => $sent,
            'errors' => $errors
        ));
    }

    if ($action == 'saveNotificationAsCmd') {
        $eqId = intval(init('eqLogic_id'));
        $name = trim(init('name'));
        $text = trim(init('text'));
        $icon = awtrixng::resolveIconName(init('icon', ''));
        $color = trim(init('color', '#00FF00'));

        if (empty($name)) {
            throw new Exception(__('Debes indicar un nombre para el comando', __FILE__));
        }
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }

        // Check if command already exists with this name for this eqLogic
        $cmd = null;
        foreach ($eqLogic->getCmd('action') as $existingCmd) {
            if ($existingCmd->getName() === $name) {
                $cmd = $existingCmd;
                break;
            }
        }
        if (!is_object($cmd)) {
            $cmd = new awtrixngCmd();
            $cmd->setEqLogic_id($eqId);
            $cmd->setLogicalId('preset_notif_' . substr(md5($name . microtime()), 0, 8));
            $cmd->setName($name);
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->setIsVisible(1);
            $cmd->setDisplay('icon', 'fas fa-bell');
        }
        $cmd->setConfiguration('is_preset_notif', 1);
        $cmd->setConfiguration('preset_text', $text);
        $cmd->setConfiguration('preset_icon', $icon);
        $cmd->setConfiguration('preset_color', $color);
        $cmd->setConfiguration('preset_duration', intval(init('duration', 12)));
        $cmd->setConfiguration('preset_hold', intval(init('hold', 0)));
        $cmd->setConfiguration('preset_stack', (init('priority') === 'urgent' || intval(init('stack', 1)) == 0) ? 0 : 1);
        $cmd->save();

        ajax::success(array(
            'id' => $cmd->getId(),
            'name' => $cmd->getName(),
            'human' => $cmd->getHumanName(),
            'text' => $text,
            'icon' => $icon,
            'color' => $color,
            'duration' => intval(init('duration', 12)),
            'hold' => intval(init('hold', 0)),
            'stack' => (init('priority') === 'urgent' || intval(init('stack', 1)) == 0) ? 0 : 1
        ));
    }

    if ($action == 'dismissNotification') {
        $eqId = intval(init('eqLogic_id'));
        $ip = trim(init('ip', ''));
        $port = intval(init('port', 80));
        $password = trim(init('password', ''));

        if ($eqId > 0) {
            $eq = eqLogic::byId($eqId);
            if (is_object($eq)) {
                $eq->dismissNotification();
                ajax::success(array('status' => 'ok', 'message' => __('Notificación descartada/borrada correctamente', __FILE__)));
            }
        }

        if (empty($ip)) {
            throw new Exception(__('Debes indicar la dirección IP o seleccionar un panel', __FILE__));
        }

        $v1Url = "http://{$ip}:{$port}/api/v1/notifications/dismiss";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $v1Url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            $v1Url2 = "http://{$ip}:{$port}/api/v1/notifications";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $v1Url2);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('text' => '', 'dismiss' => true)));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            if (!empty($password)) curl_setopt($ch, CURLOPT_USERPWD, 'admin:' . $password);
            curl_exec($ch);
            curl_close($ch);
        }

        ajax::success(array('status' => 'ok', 'message' => __('Notificación descartada/borrada correctamente', __FILE__)));
    }

    if ($action == 'getPresetNotificationCmds') {
        $eqId = intval(init('eqLogic_id'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            ajax::success(array());
        }
        $list = array();
        foreach ($eqLogic->getCmd('action') as $cmd) {
            if ($cmd->getConfiguration('is_preset_notif') == 1 || strpos($cmd->getLogicalId(), 'preset_notif_') === 0) {
                $list[] = array(
                    'id' => $cmd->getId(),
                    'name' => $cmd->getName(),
                    'human' => $cmd->getHumanName(),
                    'text' => $cmd->getConfiguration('preset_text', ''),
                    'icon' => $cmd->getConfiguration('preset_icon', ''),
                    'color' => $cmd->getConfiguration('preset_color', '#00FF00')
                );
            }
        }
        ajax::success($list);
    }

    if ($action == 'deletePresetNotificationCmd') {
        $cmdId = intval(init('cmd_id'));
        $cmd = cmd::byId($cmdId);
        if (is_object($cmd)) {
            $cmd->remove();
            ajax::success(array('deleted' => true));
        }
        ajax::success(array('deleted' => false));
    }

    if ($action == 'listPanels') {
        $eqLogics = eqLogic::byType('awtrixng');
        $panels = array();
        foreach ($eqLogics as $eqLogic) {
            $panels[] = array(
                'id' => $eqLogic->getId(),
                'name' => $eqLogic->getHumanName(false),
                'short_name' => $eqLogic->getName(),
                'ip' => $eqLogic->getConfiguration('ip', ''),
                'model' => $eqLogic->getConfiguration('model', '32x8'),
                'size_label' => method_exists($eqLogic, 'getMatrixSizeLabel') ? $eqLogic->getMatrixSizeLabel() : $eqLogic->getConfiguration('model', '32x8'),
                'isEnable' => $eqLogic->getIsEnable()
            );
        }
        ajax::success($panels);
    }

    if ($action == 'copyPanelConfig') {
        $sourceId = intval(init('source_id'));
        $targetId = init('target_id'); // integer ID or 'all'
        $copyVisual = intval(init('copy_visual', 1)) === 1;
        $copyApps = intval(init('copy_apps', 1)) === 1;
        $copyModel = intval(init('copy_model', 0)) === 1;
        $syncNow = intval(init('sync_now', 1)) === 1;

        $sourceEq = eqLogic::byId($sourceId);
        if (!is_object($sourceEq) || $sourceEq->getEqType_name() !== 'awtrixng') {
            throw new Exception(__('No se encontró el panel de origen seleccionado.', __FILE__));
        }

        $targets = array();
        if ($targetId === 'all') {
            foreach (eqLogic::byType('awtrixng') as $eq) {
                if ($eq->getId() != $sourceEq->getId()) {
                    $targets[] = $eq;
                }
            }
        } else {
            $targetEq = eqLogic::byId(intval($targetId));
            if (!is_object($targetEq) || $targetEq->getEqType_name() !== 'awtrixng') {
                throw new Exception(__('No se encontró el panel de destino seleccionado.', __FILE__));
            }
            $targets[] = $targetEq;
        }

        if (empty($targets)) {
            throw new Exception(__('No hay otros paneles destino disponibles para copiar la configuración.', __FILE__));
        }

        $copiedNames = array();
        foreach ($targets as $tEq) {
            $sourceEq->copyConfigurationTo($tEq, array(
                'copy_visual' => $copyVisual,
                'copy_apps' => $copyApps,
                'copy_model' => $copyModel,
                'sync_now' => $syncNow
            ));
            $copiedNames[] = $tEq->getName() . ' (' . ($tEq->getConfiguration('ip', 'Sin IP')) . ')';
        }

        ajax::success(array(
            'status' => 'ok',
            'copied_count' => count($copiedNames),
            'copied_panels' => $copiedNames
        ));
    }

    if ($action == 'syncPanelApps') {
        $eqLogicId = intval(init('id'));
        $eqLogic = eqLogic::byId($eqLogicId);
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() !== 'awtrixng') {
            throw new Exception(__('Panel Awtrix no encontrado.', __FILE__));
        }

        $appsJson = init('custom_apps_list', '');
        if (!empty($appsJson)) {
            $decoded = json_decode($appsJson, true);
            if (is_array($decoded)) {
                $eqLogic->setConfiguration('custom_apps_list', $decoded);
                $eqLogic->save();
            }
        }

        $count = $eqLogic->syncPanelApps();
        ajax::success(array('status' => 'ok', 'synced_count' => $count));
    }

    if ($action == 'createBackup') {
        $panelId = init('panel_id', 'all');
        $saveOnServer = intval(init('save_on_server', 1)) === 1;
        $bundle = awtrixng::createBackupBundle($panelId, $saveOnServer);
        ajax::success($bundle);
    }

    if ($action == 'listBackups') {
        $backups = awtrixng::listServerBackups();
        ajax::success($backups);
    }

    if ($action == 'getServerBackupContent') {
        $filename = basename(init('filename', ''));
        $filePath = awtrixng::getBackupDir() . '/' . $filename;
        if (empty($filename) || !file_exists($filePath)) {
            throw new Exception(__('El archivo de copia de seguridad solicitado no existe en el servidor.', __FILE__));
        }
        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);
        ajax::success(array(
            'filename' => $filename,
            'bundle' => $decoded
        ));
    }

    if ($action == 'deleteBackup') {
        $filename = basename(init('filename', ''));
        $filePath = awtrixng::getBackupDir() . '/' . $filename;
        if (!empty($filename) && file_exists($filePath)) {
            @unlink($filePath);
            log::add('awtrixng', 'info', "Copia de seguridad eliminada del servidor: {$filename}");
        }
        ajax::success(array('status' => 'ok'));
    }

    if ($action == 'restoreBackup') {
        $filename = basename(init('filename', ''));
        $backupJson = init('backup_json', '');
        $targetPanelId = init('target_panel_id', 'auto');
        $preserveIp = intval(init('preserve_ip', 0)) === 1;
        $preserveSize = intval(init('preserve_size', 0)) === 1;
        $restoreFirmware = intval(init('restore_firmware', 0)) === 1;

        $bundle = null;
        if (!empty($backupJson)) {
            $bundle = json_decode($backupJson, true);
        } elseif (!empty($filename)) {
            $filePath = awtrixng::getBackupDir() . '/' . $filename;
            if (!file_exists($filePath)) {
                throw new Exception(__('No se encontró el archivo de copia de seguridad en el servidor.', __FILE__));
            }
            $bundle = json_decode(file_get_contents($filePath), true);
        }

        if (!is_array($bundle)) {
            throw new Exception(__('Los datos de la copia de seguridad no son un JSON válido.', __FILE__));
        }

        $restoredPanels = awtrixng::restoreFromBackupData($bundle, array(
            'target_panel_id' => $targetPanelId,
            'preserve_ip' => $preserveIp,
            'preserve_size' => $preserveSize,
            'restore_firmware' => $restoreFirmware
        ));

        ajax::success(array(
            'status' => 'ok',
            'restored_count' => count($restoredPanels),
            'restored_panels' => $restoredPanels
        ));
    }

    if ($action == 'getSoundLibrary') {
        $eqId = intval(init('eqLogic_id'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }

        $builtin = awtrixng::getBuiltinSoundLibrary();
        $customRtttls = $eqLogic->getCustomRtttls();
        $panelMp3s = array();
        try {
            $panelMp3s = $eqLogic->fetchAudioFiles();
        } catch (Exception $e) {}

        $model = $eqLogic->getConfiguration('model', '32x8');
        $hasAudio = ($model === '52x16' || intval($eqLogic->getConfiguration('has_audio_tts', 0)) == 1);

        ajax::success(array(
            'builtin' => $builtin,
            'custom_rtttls' => $customRtttls,
            'panel_mp3s' => $panelMp3s,
            'has_audio' => $hasAudio,
            'model' => $model
        ));
    }

    if ($action == 'playAudioSound') {
        $eqId = intval(init('eqLogic_id'));
        $sound = trim(init('sound'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        if (empty($sound)) {
            throw new Exception(__('Debes indicar un sonido o código RTTTL', __FILE__));
        }
        $eqLogic->playSound($sound);
        ajax::success(array('status' => 'ok', 'message' => __('Sonido enviado al reloj', __FILE__)));
    }

    if ($action == 'saveCustomRtttl') {
        $eqId = intval(init('eqLogic_id'));
        $name = trim(init('name'));
        $code = trim(init('code'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        $newId = $eqLogic->addCustomRtttl($name, $code);
        ajax::success(array('status' => 'ok', 'id' => $newId, 'name' => $name, 'code' => $code));
    }

    if ($action == 'deleteCustomRtttl') {
        $eqId = intval(init('eqLogic_id'));
        $id = trim(init('id'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        $eqLogic->removeCustomRtttl($id);
        ajax::success(array('status' => 'ok'));
    }

    if ($action == 'uploadMP3ToPanel') {
        $eqId = intval(init('eqLogic_id'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
            throw new Exception(__('No se ha seleccionado ningún archivo', __FILE__));
        }
        $fileName = basename($_FILES['file']['name']);
        if (!preg_match('/\.(mp3|wav|ogg)$/i', $fileName)) {
            throw new Exception(__('Solo se admiten archivos de audio .mp3 o .wav', __FILE__));
        }
        $eqLogic->uploadAudioFile($_FILES['file']['tmp_name'], $fileName);
        ajax::success(array('status' => 'ok', 'filename' => $fileName));
    }

    if ($action == 'deleteMP3FromPanel') {
        $eqId = intval(init('eqLogic_id'));
        $fileName = trim(init('filename'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        if (empty($fileName)) {
            throw new Exception(__('Nombre de archivo no válido', __FILE__));
        }
        $eqLogic->deleteAudioFile($fileName);
        ajax::success(array('status' => 'ok', 'filename' => $fileName));
    }

    if ($action == 'installAllBuiltinMP3s') {
        $eqId = intval(init('eqLogic_id'));
        $eqLogic = eqLogic::byId($eqId);
        if (!is_object($eqLogic)) {
            throw new Exception(__('Panel no encontrado', __FILE__));
        }
        $audioDir = dirname(__FILE__) . '/../../resources/audio';
        if (!is_dir($audioDir)) {
            throw new Exception(__('Carpeta de audios integrada no encontrada', __FILE__));
        }
        $files = scandir($audioDir);
        $uploaded = 0;
        $errors = array();
        foreach ($files as $f) {
            if (preg_match('/\.(mp3|wav)$/i', $f)) {
                try {
                    $eqLogic->uploadAudioFile($audioDir . '/' . $f, $f);
                    $uploaded++;
                } catch (Exception $e) {
                    $errors[] = "{$f}: " . $e->getMessage();
                }
            }
        }
        ajax::success(array('status' => 'ok', 'uploaded_count' => $uploaded, 'errors' => $errors));
    }

    throw new Exception(__('Acción desconocida : ', __FILE__) . $action);
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
