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
if (!isConnect()) {
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
                <input type="number" class="configKey form-control" data-l1key="timeout" placeholder="5" value="5" />
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
</script>
