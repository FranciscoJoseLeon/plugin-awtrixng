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

function awtrixng_install() {
    log::add('awtrixng', 'info', 'Instalación de plugin Awtrix NG completada.');
}

function awtrixng_update() {
    log::add('awtrixng', 'info', 'Actualización de plugin Awtrix NG completada.');
    foreach (eqLogic::byType('awtrixng') as $eqLogic) {
        if (is_object($eqLogic)) {
            $eqLogic->fixCommandTypes();
            $eqLogic->createCommands();
            $eqLogic->save();
        }
    }
}

function awtrixng_remove() {
    log::add('awtrixng', 'info', 'Desinstalación de plugin Awtrix NG completada.');
}
?>
