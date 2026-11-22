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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect('admin')) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    <fieldset>
        <div class="alert alert-info">
            <i class="fas fa-book-reader"></i> <strong>{{Documentación oficial de Awtrix NG}}:</strong>
            <ul style="margin-top: 5px; margin-bottom: 0;">
                <li><a href="https://blueforcer.github.io/awtrix-ng/esp32/" target="_blank"><strong>{{Get Started (Guía de inicio)}}</strong>: https://blueforcer.github.io/awtrix-ng/esp32/</a></li>
                <li><a href="https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/" target="_blank"><strong>{{How to build your own matrix LED (Fabricar pantalla propia)}}</strong>: https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/</a></li>
            </ul>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{Tiempo de espera HTTP (segundos)}}</label>
            <div class="col-sm-3">
                <input type="number" class="configKey form-control" data-l1key="timeout" placeholder="5" value="5" min="1" max="60" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Tiempo de conexión HTTP (segundos)}}</label>
            <div class="col-sm-3">
                <input type="number" class="configKey form-control" data-l1key="connect_timeout" placeholder="2" value="2" min="1" max="15" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Silenciar errores en Centro de Mensajes}}</label>
            <div class="col-sm-6">
                <input type="checkbox" class="configKey" data-l1key="silence_offline_errors" checked />
                <span class="help-block">{{Evita inundar el Centro de Mensajes de Jeedom cuando la pantalla esté apagada o fuera de cobertura WiFi. Los avisos se registran como advertencia en el log del plugin sin interrumpir los escenarios.}}</span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Icono por defecto}}</label>
            <div class="col-sm-3">
                <input type="text" class="configKey form-control" data-l1key="default_icon" placeholder="31805" value="31805" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Copias de Seguridad y Logs}}</label>
            <div class="col-sm-8" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a class="btn btn-sm btn-primary" id="bt_cfg_backupAll"><i class="fas fa-download"></i> {{Descargar Copia de Seguridad (Todos los Paneles)}}</a>
                <a class="btn btn-sm btn-default" onclick="$('#md_modal').dialog({title: '{{Logs Awtrix NG}}'}).load('index.php?v=d&modal=log.display&log=awtrixng').dialog('open');"><i class="far fa-file-alt"></i> {{Ver Registro de Logs (awtrixng)}}</a>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend><i class="fas fa-tools"></i> {{Modo Mantenimiento}}</legend>
        <div class="form-group">
            <label class="col-sm-3 control-label">{{Acciones Globales de Mantenimiento}}</label>
            <div class="col-sm-8">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                    <a class="btn btn-sm btn-info" id="bt_cfg_reloadAllCommands"><i class="fas fa-terminal"></i> {{Recargar comandos (Todos los Paneles)}}</a>
                    <a class="btn btn-sm btn-warning" id="bt_cfg_refreshAllPanels"><i class="fas fa-sync-alt"></i> {{Refrescar y Re-detectar Firmware (Todos)}}</a>
                    <a class="btn btn-sm btn-success" id="bt_cfg_syncAllApps"><i class="fas fa-layer-group"></i> {{Sincronizar Carrusel (Todos los Paneles)}}</a>
                </div>
                <span class="help-block" style="margin-bottom: 0;">{{Recarga los comandos por defecto sin perder escenarios ni configuraciones, fuerza la detección de firmware (AWTRIX NG / AWTRIX 3) o sincroniza las apps de carrusel en todos los paneles habilitados.}}</span>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-11 col-sm-offset-1">
                <div class="alert alert-warning" style="margin-top: 8px; margin-bottom: 0; font-size: 12px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>{{Aviso Legal y Exención de Responsabilidad}}</strong>:
                    {{Este plugin es un desarrollo comunitario independiente y se ofrece «TAL CUAL», sin garantías de ningún tipo. Puede contener errores o comportamientos inesperados (tanto con firmware AWTRIX NG como con AWTRIX 3). Los autores no se hacen responsables de posibles fallos de funcionamiento, pérdida de datos, bloqueos de pantalla ni de ningún daño directo o indirecto derivado de su uso. Úsalo bajo tu propia responsabilidad y realiza copias de seguridad periódicas.}}
                </div>
            </div>
        </div>
    </fieldset>
</form>

<script>
$('#bt_cfg_backupAll').on('click', function() {
    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: { action: 'createBackup', panel_id: 'all', save_on_server: 1 },
        dataType: 'json',
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var bundle = data.result;
            var filename = bundle.saved_filename || ('awtrixng_backup_all_' + new Date().toISOString().slice(0,10) + '.json');
            var blob = new Blob([JSON.stringify(bundle, null, 2)], {type: 'application/json;charset=utf-8'});
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            $('#div_alert').showAlert({message: '{{Copia de seguridad guardada en el servidor y descargada correctamente: }} ' + filename, level: 'success'});
        }
    });
});

function awtrixRunMaintenanceAction($btn, action, okPrefix) {
    var oldHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + '{{Ejecutando...}}');
    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: { action: action },
        dataType: 'json',
        error: function(request, status, error) {
            $btn.prop('disabled', false).html(oldHtml);
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            $btn.prop('disabled', false).html(oldHtml);
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var res = data.result || {};
            var count = res.updated !== undefined ? res.updated : (res.refreshed !== undefined ? res.refreshed : (res.synced || 0));
            var msg = okPrefix + ' (' + count + ')';
            if (res.errors && res.errors.length > 0) {
                msg += ' — {{Errores}}: ' + res.errors.join(' | ');
                $('#div_alert').showAlert({message: msg, level: 'warning'});
            } else {
                $('#div_alert').showAlert({message: msg, level: 'success'});
            }
        }
    });
}

$('#bt_cfg_reloadAllCommands').on('click', function() {
    awtrixRunMaintenanceAction($(this), 'reloadAllCommands', '{{Comandos recargados correctamente en paneles}}');
});

$('#bt_cfg_refreshAllPanels').on('click', function() {
    awtrixRunMaintenanceAction($(this), 'refreshAllPanels', '{{Paneles refrescados y re-detectados}}');
});

$('#bt_cfg_syncAllApps').on('click', function() {
    awtrixRunMaintenanceAction($(this), 'syncAllApps', '{{Carrusel sincronizado en paneles}}');
});
</script>
