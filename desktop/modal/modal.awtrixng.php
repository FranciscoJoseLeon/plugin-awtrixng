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
?>

<div id="div_modalAwtrixngAlert"></div>
<div style="padding: 10px;">
    <h4><i class="fas fa-info-circle"></i> {{Ayuda de Comandos e Iconos Awtrix NG}}</h4>
    <div class="alert alert-warning" style="padding: 8px 12px; font-size: 12px;">
        <i class="fas fa-icons"></i> <strong>{{Iconos en Awtrix NG}}:</strong>
        {{Los iconos deben ser descargados en la pantalla del Awtrix NG a través de la web}}
        <a href="https://awtrix.de/icons/" target="_blank" style="font-weight: bold; text-decoration: underline;">https://awtrix.de/icons/</a>.<br/>
        <i class="fas fa-lightbulb" style="color: #d97706;"></i> <strong>{{Hint}}:</strong> {{Renombra el icono a un}} <strong>{{máximo de 12 caracteres}}</strong>.
    </div>
    <p>{{Para enviar notificaciones o apps desde tus escenarios:}}</p>
    <ul>
        <li><strong>{{Notificar (Popup):}}</strong> {{En el campo 'Título' introduce el nombre o ID del icono (máx. 12 caracteres). En el campo 'Mensaje' introduce el texto.}}</li>
        <li><strong>{{App Carrusel:}}</strong> {{En 'Título' pon el identificador de la app (ej. vuelos, tiempo). En 'Mensaje' pon el texto a mostrar.}}</li>
        <li><strong>{{Sonidos / RTTTL:}}</strong> {{Introduce 'beep', 'alarm', o el código de melodía RTTTL estándar.}}</li>
    </ul>

    <hr/>
    <h4><i class="fas fa-book-reader"></i> {{Documentación Oficial y Galería de Iconos Awtrix NG}}</h4>
    <p>{{Accede directamente a la galería de iconos y a las guías oficiales de Awtrix:}}</p>
    <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
        <a href="https://awtrix.de/icons/" target="_blank" class="btn btn-primary btn-sm">
            <i class="fas fa-icons"></i> {{Galería de Iconos (https://awtrix.de/icons/)}}
        </a>
        <a href="https://blueforcer.github.io/awtrix-ng/esp32/" target="_blank" class="btn btn-info btn-sm">
            <i class="fas fa-rocket"></i> {{Get Started (Guía de Inicio)}}
        </a>
        <a href="https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/" target="_blank" class="btn btn-default btn-sm">
            <i class="fas fa-tools"></i> {{How to build your own matrix LED}}
        </a>
    </div>
</div>
