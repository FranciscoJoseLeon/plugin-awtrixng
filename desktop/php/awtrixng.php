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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Acceso no autorizado}}');
}

$plugin = plugin::byId('awtrixng');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <!-- Sidebar / Equipment List -->
    <div class="col-xs-12 col-md-3 col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add">
                    <i class="fas fa-plus-circle"></i> {{Añadir Pantalla Awtrix}}
                </a>
                <li class="filter" style="margin-bottom: 5px;">
                    <input class="filter form-control input-sm" placeholder="{{Buscar}}" style="width: 100%"/>
                </li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                    $ipBadge = $eqLogic->getConfiguration('ip', '');
                    $ipInfo = !empty($ipBadge) ? ' <small style="opacity:0.75;">(' . htmlspecialchars($ipBadge) . ')</small>' : '';
                    echo '<li class="cursor li_eqLogic ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . $ipInfo . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- Main Panel: Thumbnail Display -->
    <div class="col-xs-12 col-md-9 col-lg-10 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend><i class="fas fa-cog"></i> {{Gestión del Plugin Multi-Panel}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Añadir Pantalla}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br>
                <span>{{Configuración}}</span>
            </div>
            <div class="cursor logoSecondary" id="bt_openBackupRestoreGlobal">
                <i class="fas fa-archive"></i>
                <br>
                <span>{{Backup / Restaurar}}</span>
            </div>
            <div class="cursor logoSecondary" id="bt_openPluginLogs">
                <i class="far fa-file-alt"></i>
                <br>
                <span>{{Ver Logs}}</span>
            </div>
            <div class="cursor logoSecondary" onclick="window.open('https://blueforcer.github.io/awtrix-ng/esp32/', '_blank')">
                <i class="fas fa-book-reader"></i>
                <br>
                <span>{{Get Started}}</span>
            </div>
            <div class="cursor logoSecondary" onclick="window.open('https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/', '_blank')">
                <i class="fas fa-tools"></i>
                <br>
                <span>{{Build Matrix LED}}</span>
            </div>
        </div>

        <legend><i class="fas fa-desktop"></i> {{Mis Pantallas Awtrix NG}} <small>({{Cada panel gestiona su propia IP, tamaño e información de forma independiente o sincronizada}})</small></legend>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                $model = $eqLogic->getConfiguration('model', '32x8');
                if (method_exists($eqLogic, 'getMatrixSizeLabel')) {
                    $modelBadge = $eqLogic->getMatrixSizeLabel();
                } else {
                    $modelBadge = ($model === '52x16') ? '52×16 (TC002)' : '32×8 (TC001)';
                }
                $badgeClass = ($model === '52x16') ? 'label-success' : (($model === 'custom') ? 'label-warning' : 'label-info');
                $ipAddr = trim($eqLogic->getConfiguration('ip', ''));
                $portNum = intval($eqLogic->getConfiguration('port', 80));
                if ($portNum <= 0) $portNum = 80;
                $ipDisplay = !empty($ipAddr) ? htmlspecialchars($ipAddr) : '{{Sin IP}}';
                $webUrl = !empty($ipAddr) ? ('http://' . htmlspecialchars($ipAddr) . ($portNum != 80 ? ':' . $portNum : '') . '/') : '';
                $panelImg = method_exists($eqLogic, 'getImage') ? $eqLogic->getImage() : $plugin->getPathImgIcon();
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '" data-model="' . htmlspecialchars($model) . '">';
                echo '<img src="' . htmlspecialchars($panelImg) . '" style="border-radius: 8px; object-fit: cover;"/>';
                echo '<br>';
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '<br><span class="label ' . $badgeClass . '" style="font-size: 10px;">' . htmlspecialchars($modelBadge) . '</span> ';
                if (!empty($webUrl)) {
                    echo '<span class="label label-default" style="font-size: 10px; cursor: pointer;" onclick="event.stopPropagation(); window.open(\'' . $webUrl . '\', \'_blank\');" title="{{Abrir Webserver del panel en}} ' . $webUrl . '"><i class="fas fa-external-link-alt"></i> ' . $ipDisplay . '</span>';
                } else {
                    echo '<span class="label label-default" style="font-size: 10px;"><i class="fas fa-network-wired"></i> ' . $ipDisplay . '</span>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Detail Form -->
    <div class="col-xs-12 col-md-9 col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px; display: none;">
        <a class="btn btn-sm btn-default pull-right eqLogicAction" data-action="copy" title="{{Crea un nuevo panel duplicando este}}"><i class="fas fa-clone"></i> {{Duplicar Equipo}}</a>
        <a class="btn btn-sm btn-warning pull-right" id="bt_openCopyConfigModal" style="margin-right: 5px;" title="{{Copia la configuración e información de este panel a otro(s) panel(es) sin sobrescribir su IP ni su tamaño}}"><i class="fas fa-exchange-alt"></i> {{Copiar Config entre Paneles}}</a>
        <a class="btn btn-sm btn-primary pull-right" id="bt_openBackupRestorePanel" style="margin-right: 5px;" title="{{Crear o restaurar una copia de seguridad de este panel o de todos los paneles}}"><i class="fas fa-archive"></i> {{Backup / Restaurar}}</a>
        <a class="btn btn-sm btn-info pull-right" id="bt_openPanelWebserverTop" style="margin-right: 5px;" title="{{Abrir la interfaz web interna de esta pantalla Awtrix a través de su IP}}"><i class="fas fa-external-link-alt"></i> {{Webserver Pantalla}}</a>
        <a class="btn btn-sm btn-default pull-right" id="bt_openPluginLogsDetail" style="margin-right: 5px;" title="{{Ver registros y logs del plugin Awtrix NG}}"><i class="far fa-file-alt"></i> {{Logs}}</a>
        <a class="btn btn-sm btn-default pull-right eqLogicAction" data-action="configure"><i class="fas fa-cogs"></i> {{Configuración Avanzada}}</a>
        <a class="btn btn-sm btn-success pull-right eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Guardar}}</a>
        <a class="btn btn-sm btn-danger pull-right eqLogicAction" data-action="remove"><i class="fas fa-minus-circle"></i> {{Eliminar}}</a>
        <a class="btn btn-sm btn-info pull-right" href="https://blueforcer.github.io/awtrix-ng/esp32/" target="_blank" style="margin-right: 5px;"><i class="fas fa-book-reader"></i> {{Get Started}}</a>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipo, IP y Tamaño}}</a></li>
            <li role="presentation"><a href="#panelinfotab" aria-controls="panelinfo" role="tab" data-toggle="tab"><i class="fas fa-layer-group"></i> {{Información de este Panel (Carrusel)}}</a></li>
            <li role="presentation"><a href="#quicktesttab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-paper-plane"></i> {{Probador en Vivo}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Comandos}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <!-- Tab 1: Equipment Configuration -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br/>
                <form class="form-horizontal">
                    <fieldset>
                        <!-- General Info -->
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nombre del Equipo}}</label>
                            <div class="col-sm-4">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nombre de la pantalla (ej. Reloj Salón)}}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objeto Padre}}</label>
                            <div class="col-sm-4">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Ninguno}}</option>
                                    <?php
                                    $options = '';
                                    foreach ((jeeObject::buildTree(null, false)) as $object) {
                                        $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                                    }
                                    echo $options;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Categoría}}</label>
                            <div class="col-sm-9">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Opciones}}</label>
                            <div class="col-sm-4">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activar}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                            </div>
                        </div>

                        <!-- Network Connection & Automatic Hardware Discovery -->
                        <legend><i class="fas fa-network-wired"></i> {{Conexión de Red y Auto-Detección del Panel (HTTP REST)}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Dirección IP o Hostname}}</label>
                            <div class="col-sm-4">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ip" placeholder="192.168.1.150 o awtrixng-xxxxxx.local" id="input_device_ip"/>
                            </div>
                            <div class="col-sm-5" style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <a class="btn btn-primary" id="bt_testAwtrix"><i class="fas fa-magic"></i> {{Conectar y Auto-Detectar}}</a>
                                <a class="btn btn-info" id="bt_openPanelWebserver" title="{{Abre el servidor web integrado del reloj Awtrix en una nueva pestaña}}"><i class="fas fa-external-link-alt"></i> {{Abrir Webserver de la Pantalla}}</a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Puerto HTTP / Contraseña}}</label>
                            <div class="col-sm-2">
                                <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="port" placeholder="80" value="80" id="input_device_port" title="{{Puerto HTTP (por defecto 80)}}"/>
                            </div>
                            <div class="col-sm-3">
                                <input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="auth_password" id="input_device_password" placeholder="{{Contraseña Web (Opcional)}}" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Auto-Configuración al Conectar}}</label>
                            <div class="col-sm-8">
                                <label class="checkbox-inline">
                                    <input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="auto_detect_hardware" id="chk_auto_detect_hardware" checked/>
                                    <strong>{{Obtener automáticamente el tamaño de la matriz LED, el modelo y los sensores instalados cuando se establezca comunicación}}</strong>
                                </label>
                                <div id="div_detected_hardware_summary" class="well well-sm" style="margin-top: 10px; margin-bottom: 0; background: #f0fdf4; border: 1px solid #86efac; display: none;">
                                    <div style="font-size: 12px; color: #166534; margin-bottom: 6px;">
                                        <i class="fas fa-check-circle"></i> <strong>{{Hardware Detectado Automáticamente desde el Panel}}:</strong>
                                        <span id="span_detected_fw_host" style="margin-left: 6px; color: #15803d;"></span>
                                    </div>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;" id="div_detected_badges">
                                    </div>
                                </div>
                                <!-- Hidden attributes populated automatically upon connection -->
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="hardware_auto_detected" id="input_hw_auto_detected" value="0" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="detected_firmware" id="input_detected_firmware" value="" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="detected_hostname" id="input_detected_hostname" value="" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="has_battery" id="input_has_battery" value="1" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="has_lux" id="input_has_lux" value="1" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="has_temp" id="input_has_temp" value="1" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="has_humidity" id="input_has_humidity" value="1" />
                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="has_audio_tts" id="input_has_audio_tts" value="0" />
                            </div>
                        </div>

                        <!-- Hardware Model Selector with Real Photos & Custom DIY Sizes (Auto-Selected on Connect or Manual Override) -->
                        <legend><i class="fas fa-th"></i> {{Modelo y Tamaño del Panel Awtrix NG}} <small>({{Se selecciona automáticamente al conectar con la IP, o puedes elegirlo manualmente}})</small></legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Tipo y Tamaño de Matriz}}</label>
                            <div class="col-sm-9">
                                <div class="row">
                                    <!-- 32x8 Card (TC001 & DIY) with Real Photo -->
                                    <div class="col-xs-12 col-sm-4">
                                        <div class="panel panel-default awtrix-model-card" data-model="32x8" style="cursor: pointer; border: 2px solid #ddd; border-radius: 10px; overflow: hidden; margin-bottom: 15px; transition: all 0.25s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                                            <div style="width: 100%; height: 140px; background: #e9ecef; overflow: hidden; position: relative;">
                                                <img src="plugins/awtrixng/plugin_info/images/tc001_32x8.jpg" alt="Ulanzi TC001 32x8" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.src='plugins/awtrixng/plugin_info/awtrixng_icon.png'">
                                                <span class="label label-primary" style="position: absolute; top: 10px; right: 10px; font-size: 11px; padding: 4px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">{{Iconos 8×8}}</span>
                                            </div>
                                            <div style="padding: 12px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label style="font-size: 15px; font-weight: bold; margin: 0; cursor: pointer;">
                                                        <input type="radio" name="awtrix_model_radio" value="32x8" class="modelRadio" style="margin-right: 6px;"> 32×8 (TC001 & DIY)
                                                    </label>
                                                </div>
                                                <p style="margin: 6px 0 0; font-size: 12px; color: #666; line-height: 1.4;">
                                                    {{Reloj Ulanzi TC001 y matrices WS2812B estándar de 32×8 LEDs. Compatible con miles de iconos 8×8.}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 52x16 Card (TC002) with Real Photo -->
                                    <div class="col-xs-12 col-sm-4">
                                        <div class="panel panel-default awtrix-model-card" data-model="52x16" style="cursor: pointer; border: 2px solid #ddd; border-radius: 10px; overflow: hidden; margin-bottom: 15px; transition: all 0.25s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                                            <div style="width: 100%; height: 140px; background: #e9ecef; overflow: hidden; position: relative;">
                                                <img src="plugins/awtrixng/plugin_info/images/tc002_52x16.jpg" alt="Ulanzi TC002 52x16" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.src='plugins/awtrixng/plugin_info/awtrixng_icon.png'">
                                                <span class="label label-success" style="position: absolute; top: 10px; right: 10px; font-size: 11px; padding: 4px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">{{Iconos 16×16 HD & Audio}}</span>
                                            </div>
                                            <div style="padding: 12px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label style="font-size: 15px; font-weight: bold; margin: 0; cursor: pointer;">
                                                        <input type="radio" name="awtrix_model_radio" value="52x16" class="modelRadio" style="margin-right: 6px;"> 52×16 (TC002)
                                                    </label>
                                                </div>
                                                <p style="margin: 6px 0 0; font-size: 12px; color: #666; line-height: 1.4;">
                                                    {{Reloj Ulanzi TC002 de 52×16 LEDs. Iconos HD 16×16, altavoz MP3, Web radio y voz TTS.}}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Custom Size DIY Card (Official Awtrix NG DIY: 32x8 to 128x8) -->
                                    <div class="col-xs-12 col-sm-4">
                                        <div class="panel panel-default awtrix-model-card" data-model="custom" style="cursor: pointer; border: 2px solid #ddd; border-radius: 10px; overflow: hidden; margin-bottom: 15px; transition: all 0.25s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                                            <div style="width: 100%; height: 140px; background: #0f172a; overflow: hidden; position: relative;">
                                                <img src="plugins/awtrixng/plugin_info/images/diy_matrix.png" alt="Matriz DIY 32x8 a 128x8" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.src='plugins/awtrixng/plugin_info/awtrixng_icon.png'">
                                                <span class="label label-warning" style="position: absolute; top: 10px; right: 10px; font-size: 11px; padding: 4px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">{{Ancho 32–128 × 8}}</span>
                                            </div>
                                            <div style="padding: 12px;">
                                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                                    <label style="font-size: 15px; font-weight: bold; margin: 0; cursor: pointer;">
                                                        <input type="radio" name="awtrix_model_radio" value="custom" class="modelRadio" style="margin-right: 6px;"> {{Matriz DIY (32×8 a 128×8)}}
                                                    </label>
                                                </div>
                                                <p style="margin: 6px 0 0; font-size: 12px; color: #666; line-height: 1.4;">
                                                    {{Matrices WS2812B ESP32/ESP32-S3 compatibles con Awtrix NG: alto fijo de 8 filas y ancho de 32 a 128 LEDs (ej. 64×8, 96×8, 128×8).}}
                                                </p>
                                                <div style="margin-top: 6px;">
                                                    <a href="https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/" target="_blank" class="btn btn-xs btn-default" style="font-size: 11px;"><i class="fas fa-tools"></i> {{Guía Oficial DIY Build}}</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Custom Matrix Dimensions Box (Official Awtrix NG DIY: Width 32..128, Height 8) -->
                                <div id="div_custom_dimensions" class="well well-sm" style="display: none; margin-top: 5px; margin-bottom: 10px; background: #f8fafc; border: 1px solid #cbd5e1;">
                                    <div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
                                        <div class="col-sm-4">
                                            <label><i class="fas fa-arrows-alt-h"></i> {{Ancho Compatible Awtrix NG (32 a 128 LEDs)}}:</label>
                                            <input type="number" class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="matrix_width" id="input_matrix_width" value="64" min="32" max="128" />
                                        </div>
                                        <div class="col-sm-3">
                                            <label><i class="fas fa-arrows-alt-v"></i> {{Alto (Filas LED)}}:</label>
                                            <input type="number" class="eqLogicAttr form-control input-sm" data-l1key="configuration" data-l2key="matrix_height" id="input_matrix_height" value="8" min="8" max="8" readonly title="{{Las matrices DIY en Awtrix NG utilizan 8 filas de alto (32×8 a 128×8)}}" />
                                        </div>
                                        <div class="col-sm-5" style="margin-top: 18px;">
                                            <span style="font-size: 11px; color: #555; display: block; margin-bottom: 3px;">{{Tamaños DIY oficiales en Awtrix NG (32–128 × 8)}}:</span>
                                            <button type="button" class="btn btn-xs btn-default btn-size-preset" data-w="32" data-h="8">32×8</button>
                                            <button type="button" class="btn btn-xs btn-default btn-size-preset" data-w="64" data-h="8">64×8 (2 paneles)</button>
                                            <button type="button" class="btn btn-xs btn-default btn-size-preset" data-w="96" data-h="8">96×8 (3 paneles)</button>
                                            <button type="button" class="btn btn-xs btn-default btn-size-preset" data-w="128" data-h="8">128×8 (4 paneles)</button>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="model" id="input_awtrix_model" value="32x8" />
                            </div>
                        </div>

                        <!-- Colors and Icons Defaults -->
                        <legend><i class="fas fa-palette"></i> {{Personalización de Texto e Iconos en Color}}</legend>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Color de Texto por Defecto}}</label>
                            <div class="col-sm-4">
                                <div class="input-group">
                                    <input type="color" class="form-control" id="picker_default_color" value="#FFFFFF" style="width: 50px; padding: 2px; cursor: pointer;"/>
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="default_color" id="input_default_color" placeholder="#FFFFFF" value="#FFFFFF" style="text-transform: uppercase; font-family: monospace; font-weight: bold;"/>
                                </div>
                                <div style="margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-xs btn-default btn-color-preset" data-color="#00FF00" style="color: #00aa00; font-weight: bold;"><i class="fas fa-tint"></i> {{Verde (Riego)}}</button>
                                    <button type="button" class="btn btn-xs btn-default btn-color-preset" data-color="#FF3333" style="color: #dd2222; font-weight: bold;"><i class="fas fa-fire"></i> {{Rojo (Termo <40ºC)}}</button>
                                    <button type="button" class="btn btn-xs btn-default btn-color-preset" data-color="#00BFFF" style="color: #0088cc; font-weight: bold;"><i class="fas fa-soap"></i> {{Azul (Lavadora)}}</button>
                                    <button type="button" class="btn btn-xs btn-default btn-color-preset" data-color="#FFA500" style="color: #cc8800; font-weight: bold;"><i class="fas fa-door-open"></i> {{Naranja (Puerta)}}</button>
                                    <button type="button" class="btn btn-xs btn-default btn-color-preset" data-color="#FFFFFF" style="font-weight: bold;">{{Blanco}}</button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Icono por Defecto}}</label>
                            <div class="col-sm-8">
                                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; max-width: 600px;">
                                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="default_icon" id="input_default_icon" placeholder="{{Nombre o ID (máx. 12 caract.)}}" style="max-width: 200px;" />
                                    <button type="button" class="btn btn-sm btn-primary bt_openIconPicker" data-target="#input_default_icon" title="{{Abrir catálogo de iconos compatibles con esta pantalla}}">
                                        <i class="fas fa-icons"></i> {{Elegir Icono}}
                                    </button>
                                    <a href="https://awtrix.de/icons/" target="_blank" class="btn btn-sm btn-default" title="{{Abrir galería oficial de iconos para Awtrix NG}}">
                                        <i class="fas fa-external-link-alt"></i> {{awtrix.de/icons}}
                                    </a>
                                </div>
                                <div class="div_installed_panel_icons" data-target="#input_default_icon" style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center;"></div>
                                <div class="alert alert-warning" style="margin-top: 8px; margin-bottom: 6px; padding: 8px 12px; font-size: 12px; max-width: 680px;">
                                    <i class="fas fa-download"></i> <strong>{{Descarga de Iconos}}:</strong>
                                    {{Los iconos deben ser descargados en la pantalla del Awtrix NG a través de la web}}
                                    <a href="https://awtrix.de/icons/" target="_blank" style="font-weight: bold; text-decoration: underline;">https://awtrix.de/icons/</a>.
                                    <br/>
                                    <i class="fas fa-lightbulb" style="color: #d97706;"></i> <strong>{{Hint}}:</strong> {{Renombra el icono a un}} <strong>{{máximo de 12 caracteres}}</strong> {{antes de subirlo a la pantalla.}}
                                </div>
                                <span class="help-block" id="help_icon_res" style="margin-bottom: 0;">
                                    {{Introduce el nombre o ID del icono cargado en tu Awtrix NG (máximo 12 caracteres). Para 32×8 se usan iconos de 8×8 píxeles; para 52×16 se admiten también iconos HD de 16×16.}}
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Auto-refrescar Telemetría e Info}}</label>
                            <div class="col-sm-5">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="auto_refresh" checked/>{{Consultar sensores y actualizar información de este panel cada minuto (cron)}}</label>
                            </div>
                        </div>

                        <!-- Multi-Panel Copy Configuration Section -->
                        <legend><i class="fas fa-exchange-alt"></i> {{Gestión Multi-Panel: Copiar Configuración a / desde otro Panel}}</legend>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Clonar Configuración entre Paneles}}</label>
                            <div class="col-sm-8">
                                <div class="well well-sm" style="margin-bottom: 0; background: #fcfcfc; border-left: 4px solid #f0ad4e;">
                                    <p style="font-size: 12px; margin-bottom: 10px;">
                                        <i class="fas fa-info-circle"></i> {{Cada panel tiene su propia <strong>dirección IP</strong> y su propio <strong>tamaño de pantalla</strong>, y puede mostrar información distinta. Si quieres que otro panel (o todos) muestre exactamente la misma configuración e información que este, cópiala con un clic <strong>sin perder la IP ni el tamaño de cada panel</strong>:}}
                                    </p>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                        <button type="button" class="btn btn-sm btn-warning" id="bt_copyToOtherPanel">
                                            <i class="fas fa-share-square"></i> {{Copiar la configuración de ESTE panel a otro(s)...}}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" id="bt_copyFromOtherPanel">
                                            <i class="fas fa-download"></i> {{Traer configuración DESDE otro panel a este...}}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary" id="bt_openBackupRestoreSection">
                                            <i class="fas fa-archive"></i> {{Copia de Seguridad / Restaurar (JSON)...}}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </fieldset>
                </form>
            </div>

            <!-- Tab 2: Panel-Specific Information / Carousel Apps -->
            <div role="tabpanel" class="tab-pane" id="panelinfotab">
                <br/>
                <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <i class="fas fa-layer-group"></i> <strong>{{Información Mostrada en ESTE Panel}}</strong>: 
                        {{Define qué datos o sensores de Jeedom rotan en el carrusel de este panel específico. Cada panel puede mostrar información totalmente distinta, o puedes copiarla de un panel a otro si quieres que muestren lo mismo.}}
                    </div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm btn-primary" id="bt_addPanelApp">
                            <i class="fas fa-plus-circle"></i> {{Añadir Pantalla de Información}}
                        </button>
                        <button type="button" class="btn btn-sm btn-success" id="bt_syncPanelAppsNow" title="{{Evalúa los sensores de Jeedom y envía ahora mismo estas pantallas a la IP de este panel}}">
                            <i class="fas fa-cloud-upload-alt"></i> {{Enviar Ahora a este Panel}}
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" id="bt_copyAppsToOtherPanel" title="{{Copia estas pantallas de información a otro panel conservando su IP y su tamaño}}">
                            <i class="fas fa-exchange-alt"></i> {{Copiar a otro Panel}}
                        </button>
                    </div>
                </div>

                <!-- Quick Presets for Panel Carousel Information -->
                <div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: bold; color: #555;"><i class="fas fa-magic"></i> {{Añadir plantilla rápida a este panel}}:</span>
                    <button type="button" class="btn btn-xs btn-default btn-add-app-preset" data-name="termo" data-icon="thermometer2" data-color="#FF3333" data-text="Termo: 45ºC">
                        🌡️ {{Temperatura Termo}}
                    </button>
                    <button type="button" class="btn btn-xs btn-default btn-add-app-preset" data-name="riego" data-icon="plant-watering" data-color="#00FF00" data-text="Riego: Terraza OK">
                        🌸 {{Estado Riego Flores}}
                    </button>
                    <button type="button" class="btn btn-xs btn-default btn-add-app-preset" data-name="lavadora" data-icon="water-leaked" data-color="#00BFFF" data-text="Lavadora: Lista">
                        🧺 {{Estado Lavadora}}
                    </button>
                    <button type="button" class="btn btn-xs btn-default btn-add-app-preset" data-name="exterior" data-icon="outdoor-temp" data-color="#FFD700" data-text="Exterior: 21.5ºC">
                        ☀️ {{Clima / Temp. Exterior}}
                    </button>
                    <button type="button" class="btn btn-xs btn-default btn-add-app-preset" data-name="energia" data-icon="battery-empty-2" data-color="#FFA500" data-text="Consumo: 420W">
                        ⚡ {{Consumo / Solar}}
                    </button>
                </div>

                <div class="alert alert-warning" style="padding: 8px 12px; font-size: 12px; margin-bottom: 12px;">
                    <i class="fas fa-icons"></i> <strong>{{Iconos de la Pantalla}}:</strong>
                    {{Los iconos deben ser descargados en la pantalla del Awtrix NG a través de la web}}
                    <a href="https://awtrix.de/icons/" target="_blank" style="font-weight: bold; text-decoration: underline;">https://awtrix.de/icons/</a>
                    &nbsp;|&nbsp; <i class="fas fa-lightbulb" style="color: #d97706;"></i> <strong>{{Hint}}:</strong> {{Renombra el icono a un}} <strong>{{máximo de 12 caracteres}}</strong>.
                </div>

                <table class="table table-bordered table-condensed" id="table_panel_apps">
                    <thead>
                        <tr>
                            <th style="width: 70px; text-align: center;">{{Activa}}</th>
                            <th style="width: 150px;">{{ID App (ej. termo)}}</th>
                            <th style="width: 140px;">{{Icono (máx. 12 car.)}}</th>
                            <th style="width: 160px;">{{Color Texto}}</th>
                            <th>{{Texto o Sensor Jeedom (ej. Termo: #[Baño][Termo][Temperatura]# ºC)}}</th>
                            <th style="width: 100px;">{{Duración (s)}}</th>
                            <th style="width: 70px; text-align: center;">{{Borrar}}</th>
                        </tr>
                    </thead>
                    <tbody id="tbody_panel_apps">
                    </tbody>
                </table>
                <input type="hidden" class="eqLogicAttr" data-l1key="configuration" data-l2key="custom_apps_list" id="input_custom_apps_list" />
            </div>

            <!-- Tab 3: Live Quick Test Studio -->
            <div role="tabpanel" class="tab-pane" id="quicktesttab">
                <br/>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>{{Probador en Vivo}}</strong>: {{Comprueba al instante cómo se ve tu mensaje en este panel Awtrix NG (o envíalo a todos tus paneles a la vez) sin necesidad de crear un escenario.}}
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <form class="form-horizontal">
                            <!-- Preset Buttons for Quick Testing -->
                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Plantillas Rápidas}}</label>
                                <div class="col-sm-9">
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <button type="button" class="btn btn-sm btn-default btn-test-preset" 
                                                data-text="{{Regando las flores (Válvula terraza abierta)}}" 
                                                data-icon="plant-watering" 
                                                data-color="#00FF00">
                                            🌸 {{Regando flores}}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-default btn-test-preset" 
                                                data-text="{{Alerta: Temperatura del termo ha bajado de 40ºC}}" 
                                                data-icon="thermometer2" 
                                                data-color="#FF2222">
                                            🌡️ {{Termo < 40ºC}}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-default btn-test-preset" 
                                                data-text="{{Lavadora completada con éxito}}" 
                                                data-icon="water-leaked" 
                                                data-color="#00D2FF">
                                            🧺 {{Lavadora}}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-default btn-test-preset" 
                                                data-text="{{Puerta de la terraza abierta}}" 
                                                data-icon="outdoor-temp" 
                                                data-color="#FF9900">
                                            🚪 {{Puerta abierta}}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Mensaje}}</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="test_msg_text" value="{{Regando las flores}}" placeholder="{{Escribe el texto a mostrar en la pantalla}}"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Color del Texto}}</label>
                                <div class="col-sm-8">
                                    <div class="input-group" style="width: 220px;">
                                        <input type="color" class="form-control" id="test_msg_picker" value="#00FF00" style="width: 50px; padding: 2px; cursor: pointer;"/>
                                        <input type="text" class="form-control" id="test_msg_color" value="#00FF00" style="text-transform: uppercase; font-family: monospace; font-weight: bold;"/>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Icono (Opcional)}}</label>
                                <div class="col-sm-8">
                                    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                        <input type="text" class="form-control" id="test_msg_icon" value="plant-watering" placeholder="{{Máx. 12 caracteres}}" style="max-width: 200px;"/>
                                        <button type="button" class="btn btn-sm btn-primary bt_openIconPicker" data-target="#test_msg_icon" title="{{Abrir catálogo interactivo de iconos según tamaño de pantalla}}">
                                            <i class="fas fa-icons"></i> {{Elegir Icono}}
                                        </button>
                                        <a href="https://awtrix.de/icons/" target="_blank" class="btn btn-sm btn-default" title="{{Abrir galería oficial de iconos}}">
                                            <i class="fas fa-external-link-alt"></i> {{awtrix.de/icons}}
                                        </a>
                                    </div>
                                    <div id="div_installed_panel_icons" class="div_installed_panel_icons" data-target="#test_msg_icon" style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; align-items: center;"></div>
                                    <span class="help-block" style="font-size: 11px; margin-bottom: 0;">
                                        {{Solo iconos compatibles con la resolución de esta pantalla. Descarga más en}} <a href="https://awtrix.de/icons/" target="_blank">https://awtrix.de/icons/</a> ({{máximo 12 caracteres}}).
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">{{Duración / Modo}}</label>
                                <div class="col-sm-8">
                                    <select class="form-control" id="test_msg_duration" style="max-width: 280px;">
                                        <option value="12">{{Normal (12 segundos)}}</option>
                                        <option value="30">{{30 segundos}}</option>
                                        <option value="60">{{1 minuto}}</option>
                                        <option value="300">{{5 minutos}}</option>
                                        <option value="600">{{10 minutos}}</option>
                                        <option value="hold">{{Fija / Permanente (Hold - hasta descartar)}}</option>
                                    </select>
                                    <span class="help-block" style="font-size: 11px; margin-bottom: 0;">
                                        {{En modo 'Fija', el aviso se queda en pantalla hasta que se envíe 'Descartar Notificación' o termine el riego.}}
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-success" id="bt_sendQuickNotification">
                                        <i class="fas fa-bell"></i> {{Enviar a ESTE Panel}}
                                    </button>
                                    <button type="button" class="btn btn-danger" id="bt_dismissNotification" title="{{Elimina inmediatamente cualquier notificación activa en pantalla}}">
                                        <i class="fas fa-bell-slash"></i> {{Descartar Notificación}}
                                    </button>
                                    <button type="button" class="btn btn-primary" id="bt_sendBroadcastNotification" title="{{Envía esta notificación emergente simultáneamente a todos los paneles Awtrix activos}}">
                                        <i class="fas fa-broadcast-tower"></i> {{Enviar a TODOS los Paneles}}
                                    </button>
                                    <button type="button" class="btn btn-warning" id="bt_saveAsPresetCmd" title="{{Crea un botón o comando de acción en Jeedom para enviar esta notificación sin escribir JSON}}">
                                        <i class="fas fa-plus-circle"></i> {{Guardar como Comando}}
                                    </button>
                                </div>
                            </div>

                            <!-- Scenario Helper Box: Copy without any JSON -->
                            <div class="well well-sm" style="margin-top: 18px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #e2e8f0; padding: 12px 14px;">
                                <div style="font-weight: 600; font-size: 12px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                                    <span style="color: #f8fafc;"><i class="fas fa-magic" style="color: #f59e0b;"></i> <strong>{{Cómo usar en Escenarios Jeedom}}</strong> <small style="color: #94a3b8; font-weight: normal;">({{100% directo, sin escribir JSON}})</small></span>
                                    <button type="button" class="btn btn-xs btn-primary" id="bt_copyScenarioSnippet" title="{{Copiar valores al portapapeles}}">
                                        <i class="far fa-copy"></i> {{Copiar}}
                                    </button>
                                </div>
                                <div style="font-family: monospace; font-size: 12px; background: #0f172a; padding: 8px 12px; border-radius: 6px; border-left: 3px solid #10b981; line-height: 1.7;">
                                    <div><strong style="color: #94a3b8;">{{Acción}}:</strong> <span id="snippet_scenario_cmd" style="color: #38bdf8;">#[...][Notificar (Popup)]#</span></div>
                                    <div><strong style="color: #94a3b8;">{{Título}}:</strong> <span id="snippet_scenario_title" style="color: #fbbf24;">plant-watering</span></div>
                                    <div><strong style="color: #94a3b8;">{{Mensaje}}:</strong> <span id="snippet_scenario_msg" style="color: #4ade80;">Regando las macetas</span></div>
                                </div>
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
                                    <i class="fas fa-info-circle" style="color: #38bdf8;"></i> {{En el campo Mensaje puedes usar tags de Jeedom como}} <code>#[Azotea][Temp]# °C</code> {{o}} <code>#2997# W</code> {{y se evalúan automáticamente.}}
                                </div>
                            </div>

                            <!-- Saved Preset Notification Commands List -->
                            <div style="margin-top: 18px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="font-weight: 600; font-size: 13px; color: #f8fafc;">
                                        <i class="fas fa-bookmark" style="color: #38bdf8;"></i> {{Tus Comandos de Notificación Guardados}}
                                        <small style="color: #94a3b8; font-weight: normal;">({{Comandos listos para tu Dashboard o Escenarios sin JSON}})</small>
                                    </span>
                                </div>
                                <div id="div_saved_preset_commands_list" style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 10px; min-height: 40px;">
                                    <div style="color: #94a3b8; font-size: 12px; text-align: center; padding: 8px;">
                                        <i class="fas fa-spinner fa-spin"></i> {{Cargando comandos...}}
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Live Matrix Simulation Preview (Exact Awtrix NG LED Matrix) -->
                    <div class="col-md-5">
                        <style>
                            .awtrix-device-chassis {
                                background: #ffffff;
                                border: 1px solid #dedcd6;
                                border-bottom: 5px solid #10100f;
                                border-radius: 16px;
                                box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                                padding: 12px 14px 10px;
                                color: #242321;
                                position: relative;
                                margin-bottom: 20px;
                            }
                            .awtrix-toggle-switch {
                                position: relative;
                                display: inline-flex;
                                align-items: center;
                                cursor: pointer;
                                margin: 0;
                            }
                            .awtrix-toggle-switch input {
                                position: absolute;
                                opacity: 0;
                                width: 0;
                                height: 0;
                            }
                            .awtrix-toggle-slider {
                                position: relative;
                                display: inline-block;
                                width: 34px;
                                height: 18px;
                                background-color: #c9c5bd;
                                transition: 0.2s ease;
                                border-radius: 18px;
                            }
                            .awtrix-toggle-slider:before {
                                position: absolute;
                                content: "";
                                height: 14px;
                                width: 14px;
                                left: 2px;
                                bottom: 2px;
                                background-color: #ffffff;
                                transition: 0.2s ease;
                                border-radius: 50%;
                                box-shadow: 0 1px 3px rgba(0,0,0,0.25);
                            }
                            .awtrix-toggle-switch input:checked + .awtrix-toggle-slider {
                                background-color: #b55a1d;
                            }
                            .awtrix-toggle-switch input:checked + .awtrix-toggle-slider:before {
                                transform: translateX(16px);
                            }
                        </style>

                        <div class="awtrix-device-chassis">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #706c65; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
                                <span><i class="fas fa-tv" style="color: #b55a1d;"></i> <strong>{{Simulación de Pantalla}}</strong> <small id="preview_led_count" style="color: #888; text-transform: none; font-weight: normal;">(32×8 · 256 LEDs)</small></span>
                                <div style="display: flex; gap: 4px; align-items: center;">
                                    <button type="button" class="btn btn-xs btn-warning" id="bt_simModeEdit" title="{{Simula en la matriz de LEDs el icono real y el texto de este panel}}"><i class="fas fa-edit"></i> {{Vista Previa}}</button>
                                    <button type="button" class="btn btn-xs btn-default" id="bt_simModeLive" title="{{Muestra la pantalla física en tiempo real (/api/v1/display/screen)}}"><i class="fas fa-sync-alt"></i> {{En Vivo}}</button>
                                    <span id="preview_model_badge" class="label label-primary">32×8 (TC001)</span>
                                </div>
                            </div>
                            <div id="matrix_preview_box" style="background: #000000; border-radius: 8px; padding: 6px; border: 1px solid #141413; box-shadow: inset 0 3px 12px #000; position: relative; overflow-x: auto; text-align: center;">
                                <canvas id="awtrix_matrix_canvas" width="384" height="96" style="width: 100%; height: auto; aspect-ratio: 32 / 8; image-rendering: pixelated; display: block; margin: 0 auto; background: #000000; border-radius: 4px;"></canvas>
                            </div>
                            <!-- Awtrix NG Hero Control Bar (Matching Official Web UI Chassis) -->
                            <div style="margin-top: 10px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11.5px; color: #555;">
                                <label class="awtrix-toggle-switch" title="{{Encender / Apagar matriz LED}}">
                                    <input type="checkbox" id="sim_ctl_power" checked />
                                    <span class="awtrix-toggle-slider"></span>
                                    <span style="margin-left: 6px; font-weight: 500; color: #444;">Power</span>
                                </label>
                                <span style="margin-left: 6px; color: #666; font-weight: 500;">Brightness</span>
                                <input type="range" id="sim_ctl_brightness" min="0" max="255" value="114" style="width: 85px; display: inline-block; vertical-align: middle; accent-color: #b55a1d; cursor: pointer;" />
                                <span id="sim_ctl_brightness_val" style="font-family: monospace; font-size: 11px; min-width: 24px; color: #666;">114</span>
                                <label class="awtrix-toggle-switch" title="{{Brillo automático por sensor LDR}}" style="margin-left: 4px;">
                                    <input type="checkbox" id="sim_ctl_autobri" />
                                    <span class="awtrix-toggle-slider"></span>
                                    <span style="margin-left: 6px; font-weight: 500; color: #444;">Auto brightness</span>
                                </label>
                                <div style="margin-left: auto; display: inline-flex; gap: 4px; align-items: center;">
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_prev" title="{{App Anterior}}"><i class="fas fa-step-backward"></i></button>
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_pause" data-paused="0" title="{{Pausar / Reanudar rotación}}"><i class="fas fa-pause"></i></button>
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_next" title="{{Siguiente App}}"><i class="fas fa-step-forward"></i></button>
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_dismiss" title="{{Descartar notificación activa}}"><i class="fas fa-bell-slash"></i></button>
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_screenshot" title="{{Descargar captura de pantalla (PNG)}}"><i class="fas fa-download"></i></button>
                                    <button type="button" class="btn btn-xs btn-default" id="sim_ctl_1to1" data-scale1="0" title="{{Modo escala 1:1 real / Ajustar al ancho}}"><strong style="font-size: 10px;">1:1</strong></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Commands List -->
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <br/>
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Id}}</th>
                            <th>{{Nombre}}</th>
                            <th>{{Tipo}}</th>
                            <th>{{Opciones}}</th>
                            <th>{{Acciones}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Selector Visual de Iconos Awtrix NG (Filtro Inteligente según Resolución de Matriz) -->
<div class="modal fade" id="modal_awtrix_icon_picker" tabindex="-1" role="dialog" aria-labelledby="modal_awtrix_icon_picker_label" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 860px; max-width: 95vw;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.35);">
            <div class="modal-header" style="background: #1e293b; color: #fff; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between;">
                <h4 class="modal-title" id="modal_awtrix_icon_picker_label" style="font-size: 16px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-icons" style="color: #f59e0b;"></i>
                    <span>{{Selector de Iconos Awtrix NG}}</span>
                    <span id="icon_picker_badge_size" class="label label-primary" style="font-size: 11px; margin-left: 6px;">8×8 LEDs</span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="color: #fff; opacity: 0.8; font-size: 22px;">&times;</button>
            </div>
            <div class="modal-body" style="background: #f8fafc; padding: 16px;">
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 14px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 220px; position: relative;">
                        <input type="text" id="icon_picker_search_input" class="form-control" placeholder="{{Buscar icono (ej. riego, termo, lavadora, sol, batería, puerta, alerta...)}}" style="border-radius: 20px; padding-left: 36px;" />
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 11px; color: #94a3b8;"></i>
                    </div>
                    <div class="btn-group" role="group" id="icon_picker_category_filter">
                        <button type="button" class="btn btn-sm btn-primary active btn-icon-cat" data-cat="all">{{Todos}}</button>
                        <button type="button" class="btn btn-sm btn-default btn-icon-cat" data-cat="clock">{{En tu Reloj}}</button>
                        <button type="button" class="btn btn-sm btn-default btn-icon-cat" data-cat="home">{{Hogar / Domótica}}</button>
                        <button type="button" class="btn btn-sm btn-default btn-icon-cat" data-cat="weather">{{Clima}}</button>
                        <button type="button" class="btn btn-sm btn-default btn-icon-cat" data-cat="status">{{Estado / Alertas}}</button>
                    </div>
                </div>

                <div id="icon_picker_notice" class="alert alert-info" style="padding: 8px 12px; font-size: 12px; margin-bottom: 12px; border-radius: 8px;">
                    <i class="fas fa-info-circle"></i> <span id="icon_picker_notice_text">{{Mostrando iconos compatibles con esta pantalla (8×8 píxeles estándar para TC001 y DIY). Haz clic en cualquiera para seleccionarlo al instante.}}</span>
                </div>

                <!-- Grid de Iconos -->
                <div id="icon_picker_grid_container" style="max-height: 420px; overflow-y: auto; padding: 4px; display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px;">
                </div>
            </div>
            <div class="modal-footer" style="background: #f1f5f9; padding: 10px 18px; display: flex; align-items: center; justify-content: space-between;">
                <div style="font-size: 11.5px; color: #64748b;">
                    <i class="fas fa-lightbulb" style="color: #d97706;"></i> {{¿Buscas más iconos? Descárgalos en}}:
                    <a href="https://awtrix.de/icons/" target="_blank" style="font-weight: 600; color: #0284c7; text-decoration: underline;">https://awtrix.de/icons/</a>
                </div>
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">{{Cerrar}}</button>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'awtrixng', 'js', 'awtrixng'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
