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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight"});

// Clean up any previously bound delegated handlers on document to prevent duplicate triggers on SPA navigation
$(document).off('.awtrixng');
$(document).off('click', '.awtrix-model-card');
$(document).off('change', '.modelRadio');
$(document).off('click', '.btn-size-preset');
$(document).off('click', '.btn-sim-matrix-size');
$(document).off('click', '.btn-add-app-preset');
$(document).off('click', '.bt_removePanelApp');
$(document).off('input change', '.appAttr_picker');
$(document).off('input change', '.appAttr_color');
$(document).off('click', '.bt_selectAppJeedomCmd');

// Force browser cache refresh for plugin icon and update navbar label if cached by SPA
if (window.fetch) {
    fetch('plugins/awtrixng/plugin_info/awtrixng_icon.png', { cache: 'reload' }).then(function() {
        $('img[src*="awtrixng_icon.png"]').each(function() {
            var base = $(this).attr('src').split('?')[0];
            $(this).attr('src', base + '?v=' + Date.now());
        });
    }).catch(function() {});
}
$('a[href*="m=awtrixng"]').each(function() {
    var $img = $(this).find('img[src*="awtrixng_icon.png"]');
    if ($img.length) {
        $(this).contents().filter(function() { return this.nodeType === 3 && this.nodeValue.trim().length > 0; }).each(function() {
            this.nodeValue = ' Awtrix NG Matrix LED';
        });
        $(this).find('span').text('Awtrix NG Matrix LED');
    }
});

// --- Model & Matrix Size Selector Logic (32x8, 52x16, Custom DIY) ---
function updateModelCardUI(selectedModel) {
    $('.awtrix-model-card').css({
        'border-color': '#ddd',
        'background-color': '#fff',
        'box-shadow': '0 2px 8px rgba(0,0,0,0.06)'
    });
    $('.awtrix-model-card[data-model="' + selectedModel + '"]').css({
        'border-color': '#28a745',
        'background-color': '#f8fff9',
        'box-shadow': '0 4px 15px rgba(40,167,69,0.25)'
    });
    $('.modelRadio[value="' + selectedModel + '"]').prop('checked', true);
    $('#input_awtrix_model').val(selectedModel);

    var activeW = 32;
    var activeH = 8;
    if (selectedModel === 'custom') {
        $('#div_custom_dimensions').slideDown(150);
        var w = parseInt($('#input_matrix_width').val(), 10) || 64;
        if (w < 32) w = 32;
        if (w > 128) w = 128;
        $('#input_matrix_width').val(w);
        $('#input_matrix_height').val(8);
        activeW = w;
        activeH = 8;
        $('#preview_model_badge').text(w + '×8 (DIY)').removeClass('label-primary label-success').addClass('label-warning');
        $('#help_icon_res').text('{{Matriz DIY Awtrix NG}}: ' + w + '×8 — {{Alto de 8 filas con ancho configurable entre 32 y 128 píxeles. Utiliza iconos de 8×8 píxeles.}}');
    } else if (selectedModel === '52x16') {
        $('#div_custom_dimensions').slideUp(150);
        $('#input_matrix_width').val(52);
        $('#input_matrix_height').val(16);
        activeW = 52;
        activeH = 16;
        $('#preview_model_badge').text('52×16 (TC002)').removeClass('label-primary label-warning').addClass('label-success');
        $('#help_icon_res').text('{{Modelo 52×16 (TC002) seleccionado: Admite iconos estándar de 8×8 e iconos de alta definición de 16×16, además de voz TTS y reproducción MP3.}}');
    } else {
        $('#div_custom_dimensions').slideUp(150);
        $('#input_matrix_width').val(32);
        $('#input_matrix_height').val(8);
        activeW = 32;
        activeH = 8;
        $('#preview_model_badge').text('32×8 (TC001)').removeClass('label-success label-warning').addClass('label-primary');
        $('#help_icon_res').text('{{Modelo 32×8 (TC001 & DIY) seleccionado: Utiliza iconos estándar de 8×8 píxeles (biblioteca LaMetric).}}');
    }

    var currentId = $('.eqLogicAttr[data-l1key=id]').val();
    if (currentId) {
        var cardImg = 'plugins/awtrixng/plugin_info/images/tc001_32x8.jpg';
        if (selectedModel === '52x16') {
            cardImg = 'plugins/awtrixng/plugin_info/images/tc002_52x16.jpg';
        } else if (selectedModel === 'custom') {
            cardImg = 'plugins/awtrixng/plugin_info/images/diy_matrix.png';
        }
        $('.eqLogicDisplayCard[data-eqLogic_id="' + currentId + '"] img').attr('src', cardImg);
    }

    if (typeof drawSimulatedMatrixFrame === 'function') {
        drawSimulatedMatrixFrame();
    }
}

// Click on model card
$(document).on('click.awtrixng', '.awtrix-model-card', function() {
    var model = $(this).attr('data-model');
    updateModelCardUI(model);
});

// Change on radio
$(document).on('change.awtrixng', '.modelRadio', function() {
    updateModelCardUI($(this).val());
});

// Preset DIY dimensions buttons
$(document).on('click.awtrixng', '.btn-size-preset', function() {
    var w = $(this).attr('data-w');
    var h = $(this).attr('data-h');
    $('#input_matrix_width').val(w);
    $('#input_matrix_height').val(h);
    updateModelCardUI('custom');
});

$('#input_matrix_width, #input_matrix_height').on('input change', function() {
    if ($('#input_awtrix_model').val() === 'custom') {
        updateModelCardUI('custom');
    }
});

// --- Color Pickers Synchronization ---
// Default text color
$('#picker_default_color').on('input change', function() {
    var col = $(this).val().toUpperCase();
    $('#input_default_color').val(col);
});

$('#input_default_color').on('input change', function() {
    var col = $(this).val();
    if (/^#[0-9a-fA-F]{6}$/.test(col)) {
        $('#picker_default_color').val(col);
    }
});

// Color presets buttons
$('.btn-color-preset').on('click', function() {
    var col = $(this).attr('data-color');
    $('#picker_default_color').val(col);
    $('#input_default_color').val(col);
});

// Live Studio Color Sync & Exact Awtrix NG LED Matrix Simulator
var awtrixSimState = {
    mode: 'edit', // 'edit' (simulates message + real icon) or 'live' (/api/v1/display/screen)
    scrollOffset: 0,
    iconFrames: [], // array of { w, h, pixels }
    iconFrameIdx: 0,
    lastIconKey: '',
    animTimer: null,
    liveTimer: null,
    revertToEditTimeout: null,
    isScale1to1: false
};

// Built-in pixel definitions for standard Awtrix NG icons (allows instantaneous preview even offline)
var AWTRIX_BUILTIN_ICONS = {
    'plant-watering': { w: 8, h: 8, pixels: [8121864,0,8121864,15096072,12928000,8121864,0,8121864,0,8121864,0,15096072,0,0,8121864,0,8121864,0,8121864,15096072,12928000,8121864,0,8121864,0,8121864,8121864,15096072,0,8121864,8121864,0,15105288,15105288,1085926,1085926,1085926,1085926,15105288,15105288,0,15105288,1085926,1085926,1085926,1085926,15105288,0,0,15105288,1085926,1085926,1085926,1085926,15105288,0,0,15105288,15105288,15105288,15105288,15105288,15105288,0] },
    'thermometer2': { w: 8, h: 8, pixels: [0,16777215,16777215,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,16777215,0,0,0,16777215,0,0,0,16777215,0,0,0,16777215,0,0,0,0,16777215,16777215,16777215,0,0,0,0] },
    'water-leaked': { w: 8, h: 8, pixels: [16777215,0,0,0,0,0,0,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,16777215,4113404,16777215,16777215,16777215,16777215,16777215,16777215,4113404,4113404,16777215,16777215,16777215,16777215,0,0,4113404,0,0,0,16777215,0,0,0,4113404,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,4113404,0,0,0] },
    'outdoor-temp': { w: 8, h: 8, pixels: [0,16777215,16777215,16777215,0,16774656,16774656,16774656,0,16777215,16711680,16777215,0,0,16774656,16774656,0,16777215,16711680,16777215,0,0,0,16774656,0,16777215,16711680,16777215,0,0,0,0,0,16777215,16711680,16777215,0,0,0,0,16777215,16711680,16711680,16711680,16777215,0,0,0,16777215,16711680,16711680,16711680,16777215,0,0,0,0,16777215,16777215,16777215,0,0,0,0] },
    'battery-empty-2': { w: 8, h: 8, pixels: [0,0,6381921,6381921,6381921,0,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,0,0,0,6381921,8257547,8257547,8257547,6381921,0,0,0,6381921,6381921,6381921,6381921,6381921,0,0] },
    'temp-high': { w: 8, h: 8, pixels: [0,16777215,16777215,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,0,16777215,0,0,0,0,0,16777215,16711680,16777215,0,0,0,0,16777215,16711680,16711680,16711680,16777215,0,0,0,16777215,16711680,16711680,16711680,16777215,0,0,0,0,16777215,16777215,16777215,0,0,0,0] }
};
// Aliases for legacy numeric IDs
AWTRIX_BUILTIN_ICONS['2284'] = AWTRIX_BUILTIN_ICONS['plant-watering'];
AWTRIX_BUILTIN_ICONS['1425'] = AWTRIX_BUILTIN_ICONS['thermometer2'];
AWTRIX_BUILTIN_ICONS['962'] = AWTRIX_BUILTIN_ICONS['water-leaked'];
AWTRIX_BUILTIN_ICONS['2355'] = AWTRIX_BUILTIN_ICONS['outdoor-temp'];
AWTRIX_BUILTIN_ICONS['27229'] = AWTRIX_BUILTIN_ICONS['battery-empty-2'];
AWTRIX_BUILTIN_ICONS['1841'] = AWTRIX_BUILTIN_ICONS['outdoor-temp'];

// Official Awtrix NG 3x5 Pixel Font (uppercase ASCII & digits)
var AWTRIX_FONT_3X5 = {
    ' ': [0,0,0,0,0],
    '!': [2,2,2,0,2],
    '"': [5,5,0,0,0],
    '#': [5,7,5,7,5],
    '$': [3,6,2,3,6],
    '%': [5,1,2,4,5],
    '&': [2,5,2,5,3],
    '\'': [2,2,0,0,0],
    '(': [1,2,2,2,1],
    ')': [4,2,2,2,4],
    '*': [5,2,5,0,0],
    '+': [0,2,7,2,0],
    ',': [0,0,0,2,4],
    '-': [0,0,7,0,0],
    '.': [0,0,0,0,2],
    '/': [1,1,2,4,4],
    '0': [7,5,5,5,7],
    '1': [2,6,2,2,7],
    '2': [7,1,7,4,7],
    '3': [7,1,7,1,7],
    '4': [5,5,7,1,1],
    '5': [7,4,7,1,7],
    '6': [7,4,7,5,7],
    '7': [7,1,1,2,2],
    '8': [7,5,7,5,7],
    '9': [7,5,7,1,7],
    ':': [0,2,0,2,0],
    ';': [0,2,0,2,4],
    '<': [1,2,4,2,1],
    '=': [0,7,0,7,0],
    '>': [4,2,1,2,4],
    '?': [7,1,3,0,2],
    'A': [2,5,7,5,5],
    'B': [6,5,6,5,6],
    'C': [3,4,4,4,3],
    'D': [6,5,5,5,6],
    'E': [7,4,7,4,7],
    'F': [7,4,7,4,4],
    'G': [3,4,5,5,3],
    'H': [5,5,7,5,5],
    'I': [7,2,2,2,7],
    'J': [1,1,1,5,2],
    'K': [5,5,6,5,5],
    'L': [4,4,4,4,7],
    'M': [5,7,7,5,5],
    'N': [5,7,7,7,5],
    'O': [2,5,5,5,2],
    'P': [6,5,6,4,4],
    'Q': [2,5,5,7,3],
    'R': [6,5,6,5,5],
    'S': [3,4,2,1,6],
    'T': [7,2,2,2,2],
    'U': [5,5,5,5,7],
    'V': [5,5,5,5,2],
    'W': [5,5,7,7,5],
    'X': [5,5,2,5,5],
    'Y': [5,5,2,2,2],
    'Z': [7,1,2,4,7],
    '°': [6,6,0,0,0],
    'º': [6,6,0,0,0]
};

function normalizeAsciiText(str) {
    return String(str || '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toUpperCase();
}

function hexToRgb24(hex) {
    hex = String(hex || '#00FF00').replace(/^#/, '');
    if (hex.length !== 6) return 0x00FF00;
    var v = parseInt(hex, 16);
    return isNaN(v) ? 0x00FF00 : v;
}

// Exact Awtrix NG He() renderer: 12px per LED cell (11px cell + 1px black gap on #000)
function renderAwtrixCanvasPixels(pixels, width, height) {
    var canvas = document.getElementById('awtrix_matrix_canvas');
    if (!canvas) return;
    var cellSize = 12;
    var s = 11;
    if (canvas.width !== width * cellSize || canvas.height !== height * cellSize) {
        canvas.width = width * cellSize;
        canvas.height = height * cellSize;
        canvas.style.aspectRatio = width + ' / ' + height;
    }
    $('#preview_led_count').text('(' + width + '×' + height + ' · ' + (width * height) + ' LEDs)');

    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#000000';
    ctx.fillRect(0, 0, width * cellSize, height * cellSize);
    for (var o = 0; o < width * height && o < pixels.length; o++) {
        var a = pixels[o] || 0;
        if (a === 0) {
            ctx.fillStyle = '#141413';
        } else {
            ctx.fillStyle = 'rgb(' + ((a >> 16) & 255) + ',' + ((a >> 8) & 255) + ',' + (a & 255) + ')';
        }
        ctx.fillRect((o % width) * cellSize, ((o / width) | 0) * cellSize, s, s);
    }
}

function drawSimulatedMatrixFrame() {
    if (awtrixSimState.mode !== 'edit') return;
    var width = parseInt($('#input_matrix_width').val(), 10) || 32;
    var height = parseInt($('#input_matrix_height').val(), 10) || 8;
    var pixels = new Array(width * height).fill(0);
    var is16Row = (height >= 16);

    // 1. Draw icon frame onto left 8x8 (or 16x16 on 52x16 TC002) LEDs
    var iconSlug = $.trim($('#test_msg_icon').val() || '');
    var iconOffset = 0;
    if (iconSlug && awtrixSimState.iconFrames.length > 0) {
        var frame = awtrixSimState.iconFrames[awtrixSimState.iconFrameIdx % awtrixSimState.iconFrames.length];
        var iw = frame.w || 8;
        var ih = frame.h || 8;
        if (is16Row && iw <= 8 && ih <= 8) {
            // Scale 8x8 icon 2x2 onto 16x16 left area for 52x16 TC002 matrix
            for (var iy = 0; iy < ih; iy++) {
                for (var ix = 0; ix < iw; ix++) {
                    var c = frame.pixels[iy * iw + ix] || 0;
                    for (var dy = 0; dy < 2; dy++) {
                        for (var dx = 0; dx < 2; dx++) {
                            var ty = iy * 2 + dy;
                            var tx = ix * 2 + dx;
                            if (ty < height && tx < width) {
                                pixels[ty * width + tx] = c;
                            }
                        }
                    }
                }
            }
            iconOffset = (iw * 2) + 1;
        } else {
            for (var iy2 = 0; iy2 < ih && iy2 < height; iy2++) {
                for (var ix2 = 0; ix2 < iw && ix2 < width; ix2++) {
                    pixels[iy2 * width + ix2] = frame.pixels[iy2 * iw + ix2] || 0;
                }
            }
            iconOffset = iw + 1;
        }
    }

    // 2. Rasterize text using 3x5 bitmap font (1x for 8-row matrices, 2x for 16-row 52x16 TC002)
    var rawText = $('#test_msg_text').val() || '';
    var normText = normalizeAsciiText(rawText);
    var color24 = hexToRgb24($('#test_msg_color').val() || '#00FF00');
    var fontScale = is16Row ? 2 : 1;
    var textCols = [];
    for (var ci = 0; ci < normText.length; ci++) {
        var ch = normText.charAt(ci);
        var glyph = AWTRIX_FONT_3X5[ch] || AWTRIX_FONT_3X5[' '];
        for (var gx = 0; gx < 3; gx++) {
            var colBits = 0;
            for (var gy = 0; gy < 5; gy++) {
                if ((glyph[gy] >> (2 - gx)) & 1) {
                    colBits |= (1 << gy);
                }
            }
            for (var sx = 0; sx < fontScale; sx++) {
                textCols.push(colBits);
            }
        }
        for (var sp = 0; sp < fontScale; sp++) {
            textCols.push(0); // 1px (or 2px on 16-row) letter spacing
        }
    }

    var availWidth = width - iconOffset;
    var glyphHeight = 5 * fontScale;
    var topPad = Math.max(1, Math.floor((height - glyphHeight) / 2));
    var totalCols = textCols.length;

    if (totalCols <= availWidth) {
        // Fits on screen: center horizontally in available area
        var startX = iconOffset + Math.max(0, Math.floor((availWidth - totalCols) / 2));
        for (var tx2 = 0; tx2 < totalCols; tx2++) {
            var bits = textCols[tx2];
            for (var ty2 = 0; ty2 < 5; ty2++) {
                if ((bits >> ty2) & 1) {
                    for (var sy2 = 0; sy2 < fontScale; sy2++) {
                        var py = topPad + ty2 * fontScale + sy2;
                        var px = startX + tx2;
                        if (px >= iconOffset && px < width && py < height) {
                            pixels[py * width + px] = color24;
                        }
                    }
                }
            }
        }
    } else {
        // Scroll horizontally with gap
        var gapCols = 8 * fontScale;
        var cycle = totalCols + gapCols;
        var offset = (awtrixSimState.scrollOffset * fontScale) % cycle;
        for (var scx = 0; scx < availWidth; scx++) {
            var srcCol = (offset + scx) % cycle;
            if (srcCol < totalCols) {
                var sBits = textCols[srcCol];
                for (var scy = 0; scy < 5; scy++) {
                    if ((sBits >> scy) & 1) {
                        for (var scsy = 0; scsy < fontScale; scsy++) {
                            var spy = topPad + scy * fontScale + scsy;
                            var spx = iconOffset + scx;
                            if (spx >= iconOffset && spx < width && spy < height) {
                                pixels[spy * width + spx] = color24;
                            }
                        }
                    }
                }
            }
        }
    }

    renderAwtrixCanvasPixels(pixels, width, height);
}

function updatePreviewIconBox(icon) {
    icon = $.trim(icon || '').replace(/\.(gif|jpg|jpeg|png|bmp)$/i, '');
    var ip = $.trim($('#input_device_ip').val());
    var port = parseInt($('#input_device_port').val(), 10) || 80;
    var key = ip + ':' + port + ':' + icon;
    if (!icon) {
        awtrixSimState.iconFrames = [];
        awtrixSimState.lastIconKey = '';
        drawSimulatedMatrixFrame();
        return;
    }

    // Immediate fallback from built-in cache for instant responsiveness
    if (AWTRIX_BUILTIN_ICONS[icon]) {
        var b = AWTRIX_BUILTIN_ICONS[icon];
        awtrixSimState.iconFrames = [{
            w: b.w,
            h: b.h,
            pixels: b.pixels.slice()
        }];
        awtrixSimState.iconFrameIdx = 0;
        drawSimulatedMatrixFrame();
    }

    if (key === awtrixSimState.lastIconKey && awtrixSimState.iconFrames.length > 0) {
        drawSimulatedMatrixFrame();
        return;
    }
    awtrixSimState.lastIconKey = key;

    if (!ip) {
        if (!AWTRIX_BUILTIN_ICONS[icon]) {
            awtrixSimState.iconFrames = [];
            drawSimulatedMatrixFrame();
        }
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'getIconData',
            ip: ip,
            port: port,
            password: $.trim($('#input_device_password').val() || ''),
            icon: icon
        },
        dataType: 'json',
        global: false,
        success: function(data) {
            if (data.state !== 'ok' || !data.result) return;
            var res = data.result;
            if ($.isArray(res.pixels) && res.pixels.length > 0) {
                awtrixSimState.iconFrames = [{
                    w: res.width || 8,
                    h: res.height || 8,
                    pixels: res.pixels
                }];
                awtrixSimState.iconFrameIdx = 0;
                drawSimulatedMatrixFrame();
            }
            // If browser supports ImageDecoder and base64 GIF is present, decode all animation frames!
            if (res.base64 && typeof window.ImageDecoder === 'function') {
                try {
                    var binStr = atob(res.base64);
                    var bytes = new Uint8Array(binStr.length);
                    for (var i = 0; i < binStr.length; i++) bytes[i] = binStr.charCodeAt(i);
                    var decoder = new ImageDecoder({ data: bytes.buffer, type: 'image/gif' });
                    decoder.tracks.ready.then(function() {
                        var frameCount = decoder.tracks.selectedTrack.frameCount || 1;
                        if (frameCount <= 1) return;
                        var promises = [];
                        for (var f = 0; f < frameCount; f++) {
                            promises.push(decoder.decode({ frameIndex: f }));
                        }
                        Promise.all(promises).then(function(decodedFrames) {
                            var allFrames = [];
                            var offCanvas = document.createElement('canvas');
                            for (var k = 0; k < decodedFrames.length; k++) {
                                var img = decodedFrames[k].image;
                                var fw = img.displayWidth || 8;
                                var fh = img.displayHeight || 8;
                                offCanvas.width = fw;
                                offCanvas.height = fh;
                                var octx = offCanvas.getContext('2d');
                                octx.clearRect(0, 0, fw, fh);
                                octx.drawImage(img, 0, 0, fw, fh);
                                if (img.close) img.close();
                                var idata = octx.getImageData(0, 0, fw, fh).data;
                                var pxArr = [];
                                for (var p = 0; p < fw * fh; p++) {
                                    var alpha = idata[4 * p + 3] / 255;
                                    var r = Math.round(idata[4 * p] * alpha);
                                    var g = Math.round(idata[4 * p + 1] * alpha);
                                    var b = Math.round(idata[4 * p + 2] * alpha);
                                    pxArr.push((r << 16) | (g << 8) | b);
                                }
                                allFrames.push({ w: fw, h: fh, pixels: pxArr });
                            }
                            if (allFrames.length > 0) {
                                awtrixSimState.iconFrames = allFrames;
                            }
                        }).catch(function() {});
                    }).catch(function() {});
                } catch (e) {}
            }
        }
    });
}

function setSimulatorMode(mode) {
    awtrixSimState.mode = mode;
    if (mode === 'live') {
        $('#bt_simModeLive').removeClass('btn-default').addClass('btn-warning');
        $('#bt_simModeEdit').removeClass('btn-warning').addClass('btn-default');
        pollLivePanelScreen();
        if (!awtrixSimState.liveTimer) {
            awtrixSimState.liveTimer = setInterval(pollLivePanelScreen, 350);
        }
    } else {
        $('#bt_simModeEdit').removeClass('btn-default').addClass('btn-warning');
        $('#bt_simModeLive').removeClass('btn-warning').addClass('btn-default');
        if (awtrixSimState.liveTimer) {
            clearInterval(awtrixSimState.liveTimer);
            awtrixSimState.liveTimer = null;
        }
        drawSimulatedMatrixFrame();
    }
}

function pollLivePanelScreen() {
    if (awtrixSimState.mode !== 'live') return;
    if (!$('#awtrix_matrix_canvas').length) {
        if (awtrixSimState.liveTimer) {
            clearInterval(awtrixSimState.liveTimer);
            awtrixSimState.liveTimer = null;
        }
        return;
    }
    var ip = $.trim($('#input_device_ip').val());
    var port = parseInt($('#input_device_port').val(), 10) || 80;
    if (!ip) return;

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'getLiveScreen',
            ip: ip,
            port: port,
            password: $.trim($('#input_device_password').val() || '')
        },
        dataType: 'json',
        global: false,
        success: function(data) {
            if (data.state === 'ok' && data.result && $.isArray(data.result.pixels) && data.result.pixels.length > 0) {
                var w = data.result.width || parseInt($('#input_matrix_width').val(), 10) || 32;
                var h = data.result.height || parseInt($('#input_matrix_height').val(), 10) || 8;
                renderAwtrixCanvasPixels(data.result.pixels, w, h);
            }
        }
    });
}

// Start 120ms animation loop for scrolling text and animated icon frames in Edit mode
if (window.awtrixMatrixAnimInterval) {
    clearInterval(window.awtrixMatrixAnimInterval);
}
window.awtrixMatrixAnimInterval = setInterval(function() {
    if (!$('#awtrix_matrix_canvas').is(':visible')) return;
    if (awtrixSimState.mode === 'edit') {
        awtrixSimState.scrollOffset++;
        awtrixSimState.iconFrameIdx++;
        drawSimulatedMatrixFrame();
    }
}, 130);

$('#bt_simModeEdit').on('click', function() {
    if (awtrixSimState.revertToEditTimeout) clearTimeout(awtrixSimState.revertToEditTimeout);
    setSimulatorMode('edit');
});

$('#bt_simModeLive').on('click', function() {
    if (awtrixSimState.revertToEditTimeout) clearTimeout(awtrixSimState.revertToEditTimeout);
    setSimulatorMode('live');
});

// Control Bar Handlers (Power, Brightness, Auto, Prev, Pause, Next, Dismiss)
function sendSimulatorPanelControl(cmd, val) {
    var ip = $.trim($('#input_device_ip').val());
    var port = parseInt($('#input_device_port').val(), 10) || 80;
    if (!ip) return;
    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'panelControl',
            ip: ip,
            port: port,
            password: $.trim($('#input_device_password').val() || ''),
            cmd: cmd,
            val: val
        },
        dataType: 'json',
        global: false
    });
}

$('#sim_ctl_power').on('change', function() {
    sendSimulatorPanelControl('power', $(this).is(':checked') ? 1 : 0);
});
$('#sim_ctl_brightness').on('input change', function() {
    var v = $(this).val();
    $('#sim_ctl_brightness_val').text(v);
    sendSimulatorPanelControl('brightness', v);
});
$('#sim_ctl_autobri').on('change', function() {
    var checked = $(this).is(':checked');
    $('#sim_ctl_brightness').prop('disabled', checked);
    sendSimulatorPanelControl('autoBrightness', checked ? 1 : 0);
});
$('#sim_ctl_prev').on('click', function() {
    sendSimulatorPanelControl('prev', 1);
    setSimulatorMode('live');
});
$('#sim_ctl_next').on('click', function() {
    sendSimulatorPanelControl('next', 1);
    setSimulatorMode('live');
});
$('#sim_ctl_pause').on('click', function() {
    var paused = $(this).attr('data-paused') === '1';
    var nextPaused = !paused;
    $(this).attr('data-paused', nextPaused ? '1' : '0');
    $(this).find('i').attr('class', nextPaused ? 'fas fa-play' : 'fas fa-pause');
    sendSimulatorPanelControl('autoTransition', nextPaused ? 0 : 1);
});
$('#sim_ctl_dismiss').on('click', function() {
    sendSimulatorPanelControl('dismiss', 1);
});

// Download matrix screenshot as PNG
$('#sim_ctl_screenshot').on('click', function() {
    var canvas = document.getElementById('awtrix_matrix_canvas');
    if (!canvas) return;
    var link = document.createElement('a');
    link.download = 'awtrix_matrix_' + canvas.width + 'x' + canvas.height + '.png';
    link.href = canvas.toDataURL('image/png');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});

// Toggle 1:1 pixel view vs fit width
$('#sim_ctl_1to1').on('click', function() {
    var canvas = document.getElementById('awtrix_matrix_canvas');
    if (!canvas) return;
    awtrixSimState.isScale1to1 = !awtrixSimState.isScale1to1;
    if (awtrixSimState.isScale1to1) {
        $(this).removeClass('btn-default').addClass('btn-primary');
        canvas.style.width = 'auto';
        canvas.style.maxWidth = 'none';
    } else {
        $(this).removeClass('btn-primary').addClass('btn-default');
        canvas.style.width = '100%';
        canvas.style.maxWidth = '100%';
    }
});

function updateScenarioSnippet() {
    var eqName = $('.eqLogicAttr[data-l1key=name]').val() || 'Salon Awtrix NG';
    var icon = $.trim($('#test_msg_icon').val() || '');
    var color = $.trim($('#test_msg_color').val() || '#00FF00');
    var text = $('#test_msg_text').val() || '';
    var durMode = $('#test_msg_duration').val() || '12';

    var durStr = '';
    if (durMode === 'hold') {
        durStr = 'fija';
    } else if (parseInt(durMode, 10) > 12) {
        var sec = parseInt(durMode, 10);
        durStr = (sec >= 60) ? (sec / 60) + 'm' : sec + 's';
    }

    var parts = [];
    if (icon) parts.push(icon);
    if (color && color.toUpperCase() !== '#FFFFFF') parts.push(color);
    if (durStr) parts.push(durStr);

    var titleStr = parts.join(' | ');

    $('#snippet_scenario_cmd').text('#[...][' + eqName + '][{{Notificar (Popup)}}]#');
    $('#snippet_scenario_title').text(titleStr || '{{(icono o vacío)}}');
    $('#snippet_scenario_msg').text(text || '{{Tu mensaje aquí}}');
}

$(document).on('click', '#bt_copyScenarioSnippet', function() {
    var cmd = $('#snippet_scenario_cmd').text();
    var title = $('#snippet_scenario_title').text();
    var msg = $('#snippet_scenario_msg').text();
    var full = "Action: " + cmd + "\nTitle: " + title + "\nMessage: " + msg;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(full).then(function() {
            $('#div_alert').showAlert({message: '{{¡Copiado al portapapeles! Listo para pegar en tu escenario.}}', level: 'success', emptyBefore: true});
        });
    } else {
        var tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(full).select();
        document.execCommand('copy');
        tempInput.remove();
        $('#div_alert').showAlert({message: '{{¡Copiado al portapapeles! Listo para pegar en tu escenario.}}', level: 'success', emptyBefore: true});
    }
});

$('#test_msg_picker').on('input change', function() {
    var col = $(this).val().toUpperCase();
    $('#test_msg_color').val(col);
    setSimulatorMode('edit');
    updateScenarioSnippet();
});

$('#test_msg_color').on('input change', function() {
    var col = $(this).val();
    if (/^#[0-9a-fA-F]{6}$/.test(col)) {
        $('#test_msg_picker').val(col);
        setSimulatorMode('edit');
        updateScenarioSnippet();
    }
});

$('#test_msg_text').on('input', function() {
    awtrixSimState.scrollOffset = 0;
    setSimulatorMode('edit');
    updateScenarioSnippet();
});

$('#test_msg_icon').on('input change', function() {
    setSimulatorMode('edit');
    updatePreviewIconBox($(this).val());
    updateScenarioSnippet();
});

// Test Presets Click (Domestic examples: Flowers, Water heater, Laundry, Door)
$('.btn-test-preset').on('click', function() {
    var text = $(this).attr('data-text');
    var icon = $(this).attr('data-icon');
    var color = $(this).attr('data-color');

    $('#test_msg_text').val(text);
    $('#test_msg_icon').val(icon);
    $('#test_msg_color').val(color);
    $('#test_msg_picker').val(color);
    awtrixSimState.scrollOffset = 0;
    setSimulatorMode('edit');
    updatePreviewIconBox(icon);
    updateScenarioSnippet();
});

function clearJeedomToasts() {
    if (window.jeeDialog && typeof jeeDialog.clearToasts === 'function') {
        jeeDialog.clearToasts();
    }
    $('#jeeDialogToastContainer').empty();
    $('.jeeDialogToast, .toast').remove();
}
// Clear any stuck red toasts on load
clearJeedomToasts();

// --- Saved Notification Preset Commands Handler ---
function loadPresetNotificationCmds() {
    var eqId = $('.eqLogicAttr[data-l1key=id]').val();
    var $container = $('#div_saved_preset_commands_list');
    if (!eqId || !$container.length) return;

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'getPresetNotificationCmds',
            eqLogic_id: eqId
        },
        dataType: 'json',
        global: false,
        success: function(data) {
            if (data.state !== 'ok') return;
            var list = data.result || [];
            if (list.length === 0) {
                $container.html('<div style="text-align: center; color: #94a3b8; font-size: 11.5px; padding: 10px;"><i class="fas fa-info-circle"></i> {{Aún no has guardado notificaciones como comando. Diseña una arriba y pulsa "Guardar como Comando" para tener botones listos para tu Dashboard o Escenarios sin JSON.}}</div>');
                return;
            }

            var html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var colorDot = item.color ? '<span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: ' + item.color + '; vertical-align: middle; margin-right: 5px;"></span>' : '';
                var iconBadge = item.icon ? '<span class="label label-primary" style="font-size: 10px; margin-right: 6px;"><i class="fas fa-icons"></i> ' + item.icon + '</span>' : '';

                html += '<div style="background: #0f172a; border: 1px solid #334155; border-radius: 6px; padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">';
                html += '  <div style="flex: 1; min-width: 200px;">';
                html += '    <div style="font-weight: bold; color: #f8fafc; font-size: 12.5px; display: flex; align-items: center;">';
                html += colorDot + iconBadge + '<span>' + item.name + '</span>';
                html += '    </div>';
                html += '    <div style="color: #94a3b8; font-size: 11px; margin-top: 3px; font-style: italic;">';
                html += '      "' + (item.text || '') + '"';
                html += '    </div>';
                html += '    <div style="margin-top: 4px; font-family: monospace; font-size: 11px; color: #38bdf8;">';
                html += '      <code>' + item.human + '</code>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div style="display: flex; gap: 6px; align-items: center;">';
                html += '    <button type="button" class="btn btn-xs btn-primary bt_copyPresetHuman" data-human="' + item.human.replace(/"/g, '&quot;') + '" title="{{Copiar tag Jeedom}}"><i class="far fa-copy"></i> {{Copiar Tag}}</button>';
                html += '    <button type="button" class="btn btn-xs btn-success bt_execPresetCmd" data-cmd_id="' + item.id + '" title="{{Probar comando en la pantalla física}}"><i class="fas fa-play"></i> {{Probar}}</button>';
                html += '    <button type="button" class="btn btn-xs btn-danger bt_deletePresetCmd" data-cmd_id="' + item.id + '" data-name="' + item.name.replace(/"/g, '&quot;') + '" title="{{Eliminar este comando}}"><i class="fas fa-trash-alt"></i></button>';
                html += '  </div>';
                html += '</div>';
            }
            html += '</div>';
            $container.html(html);
        }
    });
}

$(document).on('click', '#bt_saveAsPresetCmd', function() {
    var eqId = $('.eqLogicAttr[data-l1key=id]').val();
    if (!eqId) {
        $('#div_alert').showAlert({message: '{{Debes guardar primero el panel antes de crear comandos}}', level: 'danger', emptyBefore: true});
        return;
    }
    var text = $('#test_msg_text').val();
    var icon = $('#test_msg_icon').val();
    var color = $('#test_msg_color').val();
    var durMode = $('#test_msg_duration').val() || '12';
    var isHold = (durMode === 'hold') ? 1 : 0;
    var durVal = (durMode === 'hold') ? 0 : (parseInt(durMode, 10) || 12);

    if (!text && !icon) {
        $('#div_alert').showAlert({message: '{{Escribe un mensaje o elige un icono antes de guardar}}', level: 'danger', emptyBefore: true});
        return;
    }

    var defaultName = text ? text.substring(0, 25) : (icon ? 'Alerta ' + icon : 'Notificación');
    if (isHold) defaultName += ' (Fija)';
    bootbox.prompt({
        title: '{{Nombre del nuevo comando de notificación (ej. "Regando Macetas", "Apagar luces", "Alerta termo"):}}',
        value: defaultName,
        callback: function(result) {
            if (result === null || $.trim(result) === '') return;
            var cmdName = $.trim(result);
            $.ajax({
                type: 'POST',
                url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                data: {
                    action: 'saveNotificationAsCmd',
                    eqLogic_id: eqId,
                    name: cmdName,
                    text: text,
                    icon: icon,
                    color: color,
                    duration: durVal,
                    hold: isHold
                },
                dataType: 'json',
                global: false,
                success: function(data) {
                    if (data.state !== 'ok') {
                        $('#div_alert').showAlert({message: data.result, level: 'danger', emptyBefore: true});
                        return;
                    }
                    $('#div_alert').showAlert({message: '{{¡Comando de notificación guardado con éxito!}} (' + data.result.human + ')', level: 'success', emptyBefore: true});
                    loadPresetNotificationCmds();
                }
            });
        }
    });
});

$(document).on('click', '.bt_copyPresetHuman', function() {
    var human = $(this).attr('data-human');
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(human).then(function() {
            $('#div_alert').showAlert({message: '{{¡Tag Jeedom copiado al portapapeles!}} (' + human + ')', level: 'success', emptyBefore: true});
        });
    }
});

$(document).on('click', '.bt_execPresetCmd', function() {
    var cmdId = $(this).attr('data-cmd_id');
    jeedom.cmd.execute({ id: cmdId });
    $('#div_alert').showAlert({message: '{{Comando enviado a la pantalla}}', level: 'success', emptyBefore: true});
});

$(document).on('click', '.bt_deletePresetCmd', function() {
    var cmdId = $(this).attr('data-cmd_id');
    var name = $(this).attr('data-name');
    bootbox.confirm('{{¿Estás seguro de que deseas eliminar el comando}} "' + name + '" ?', function(res) {
        if (!res) return;
        $.ajax({
            type: 'POST',
            url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
            data: {
                action: 'deletePresetNotificationCmd',
                cmd_id: cmdId
            },
            dataType: 'json',
            global: false,
            success: function() {
                loadPresetNotificationCmds();
            }
        });
    });
});

function loadInstalledPanelIcons(ip, port) {
    var $containers = $('.div_installed_panel_icons');
    if (!ip) return;
    var pwd = $.trim($('#input_device_password').val() || '');

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'getPanelIcons',
            ip: ip,
            port: port || 80,
            password: pwd
        },
        dataType: 'json',
        global: false,
        success: function(data) {
            if (data.state !== 'ok' || !$.isArray(data.result) || data.result.length === 0) return;
            awtrixInstalledIconsCache = data.result;
            if (!$containers.length) return;
            var html = '<span style="font-size: 11px; color: #706c65; margin-right: 4px;"><i class="fas fa-check-circle" style="color:#28a745;"></i> {{Iconos en tu reloj}}:</span>';
            for (var i = 0; i < data.result.length; i++) {
                var ic = data.result[i];
                html += '<button type="button" class="btn btn-xs btn-default btn-pick-installed-icon" data-slug="' + ic.slug.replace(/"/g, '&quot;') + '" style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 7px;" title="{{Usar icono}} ' + ic.slug + '">';
                html += '<img src="' + ic.url + '" style="width: 14px; height: 14px; image-rendering: pixelated;" /> ';
                html += '<span>' + ic.slug + '</span>';
                html += '</button>';
            }
            $containers.html(html);
        }
    });
}

$(document).off('click', '.btn-pick-installed-icon').on('click.awtrixng', '.btn-pick-installed-icon', function() {
    var slug = $(this).attr('data-slug');
    var target = $(this).closest('.div_installed_panel_icons').attr('data-target') || '#test_msg_icon';
    $(target).val(slug).trigger('change');
    if (target === '#test_msg_icon') {
        updatePreviewIconBox(slug);
    }
});

// --- Curated Catalog of Standard Icons for Awtrix NG ---
var AWTRIX_CATALOG_ICONS = [
    { slug: 'plant-watering', title: 'Riego Flores', cat: 'home', res: '8x8', desc: 'Riego de terraza o plantas' },
    { slug: 'thermometer2', title: 'Temperatura Termo', cat: 'home', res: '8x8', desc: 'Temperatura del agua / termo' },
    { slug: 'water-leaked', title: 'Lavadora / Agua', cat: 'home', res: '8x8', desc: 'Lavadora terminada / Fuga agua' },
    { slug: 'outdoor', title: 'Puerta Abierta', cat: 'home', res: '8x8', desc: 'Puerta o ventana abierta' },
    { slug: 'battery-empty-2', title: 'Batería / Energía', cat: 'home', res: '8x8', desc: 'Nivel de batería baja / consumo' },
    { slug: 'outdoor-temp', title: 'Clima / Sol', cat: 'weather', res: '8x8', desc: 'Temperatura exterior / Sol' },
    { slug: 'temp-high', title: 'Alerta Calor', cat: 'weather', res: '8x8', desc: 'Alerta temperatura alta' },
    { slug: 'temp-low2', title: 'Frío / Nieve', cat: 'weather', res: '8x8', desc: 'Alerta temperatura baja / frío' },
    { slug: 'christmas', title: 'Árbol Navidad', cat: 'status', res: '8x8', desc: 'Festivo / Navidad' }
];
var awtrixInstalledIconsCache = [];
var currentIconPickerTarget = null;

$(document).off('click', '.bt_openIconPicker').on('click.awtrixng', '.bt_openIconPicker', function() {
    var target = $(this).attr('data-target');
    if (target === 'this' || !target) {
        currentIconPickerTarget = $(this).closest('tr, div').find('.appAttr_icon, input[type=text]');
    } else {
        currentIconPickerTarget = $(target);
    }

    var matrixH = parseInt($('#input_matrix_height').val(), 10) || 8;
    var is16Row = (matrixH >= 16);

    if (is16Row) {
        $('#icon_picker_badge_size').text('16×16 HD & 8×8 LEDs').removeClass('label-primary').addClass('label-success');
        $('#icon_picker_notice_text').text('{{Pantalla 52×16 (TC002): Admite iconos HD de 16×16 píxeles e iconos estándar de 8×8. Haz clic en cualquiera para seleccionarlo.}}');
    } else {
        $('#icon_picker_badge_size').text('8×8 LEDs').removeClass('label-success').addClass('label-primary');
        $('#icon_picker_notice_text').text('{{Pantalla de 8 filas (TC001 / DIY): Utiliza iconos de 8×8 píxeles estándar. Haz clic en cualquiera para seleccionarlo.}}');
    }

    renderIconPickerGrid('all', '');
    $('#icon_picker_search_input').val('');
    $('#icon_picker_category_filter button').removeClass('btn-primary active').addClass('btn-default');
    $('#icon_picker_category_filter button[data-cat=all]').removeClass('btn-default').addClass('btn-primary active');
    $('#modal_awtrix_icon_picker').modal('show');
});

function renderIconPickerGrid(category, search) {
    category = category || 'all';
    search = (search || '').toLowerCase().trim();
    var matrixH = parseInt($('#input_matrix_height').val(), 10) || 8;
    var $container = $('#icon_picker_grid_container');
    $container.empty();

    var cards = [];

    // 1. Installed icons from clock
    if (awtrixInstalledIconsCache && awtrixInstalledIconsCache.length > 0) {
        for (var i = 0; i < awtrixInstalledIconsCache.length; i++) {
            var ic = awtrixInstalledIconsCache[i];
            cards.push({
                slug: ic.slug,
                title: ic.slug,
                cat: 'clock',
                res: '8x8',
                imgUrl: ic.url,
                isClock: true,
                desc: '{{Instalado en tu reloj}}'
            });
        }
    }

    // 2. Curated standard catalog icons
    for (var j = 0; j < AWTRIX_CATALOG_ICONS.length; j++) {
        var catIcon = AWTRIX_CATALOG_ICONS[j];
        if (matrixH < 16 && catIcon.res === '16x16') continue;
        var exists = cards.some(function(c) { return c.slug === catIcon.slug; });
        if (!exists) {
            cards.push({
                slug: catIcon.slug,
                title: catIcon.title,
                cat: catIcon.cat,
                res: catIcon.res,
                imgUrl: '',
                isClock: false,
                desc: catIcon.desc
            });
        }
    }

    if (cards.length === 0) {
        $container.html('<div style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #888;">{{No se encontraron iconos para esta búsqueda}}</div>');
        return;
    }

    for (var k = 0; k < cards.length; k++) {
        var c = cards[k];
        if (category !== 'all' && c.cat !== category) continue;
        if (search) {
            var hay = (c.slug + ' ' + c.title + ' ' + (c.desc || '')).toLowerCase();
            if (hay.indexOf(search) === -1) continue;
        }

        var imgHtml = '';
        if (c.imgUrl) {
            imgHtml = '<img src="' + c.imgUrl + '" style="max-height: 28px; max-width: 28px; image-rendering: pixelated;" onerror="this.style.display=\'none\'" />';
        } else if (AWTRIX_BUILTIN_ICONS[c.slug]) {
            imgHtml = '<canvas class="icon-thumb-cvs" data-slug="' + c.slug + '" width="24" height="24" style="image-rendering: pixelated; width: 24px; height: 24px; border-radius: 2px;"></canvas>';
        } else {
            imgHtml = '<i class="fas fa-icons" style="font-size: 20px; color: #f59e0b;"></i>';
        }

        var badgeClass = c.isClock ? 'label-success' : 'label-primary';
        var badgeText = c.isClock ? '{{En Reloj}}' : c.res;

        var cardHtml = '<div class="icon-picker-card" data-slug="' + c.slug.replace(/"/g, '&quot;') + '" style="background: #ffffff; border: 2px solid #e2e8f0; border-radius: 9px; padding: 10px 8px; cursor: pointer; text-align: center; transition: all 0.15s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
        cardHtml += '<div style="height: 32px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px;">' + imgHtml + '</div>';
        cardHtml += '<div style="font-size: 11px; font-weight: bold; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' + c.title + '">' + c.title + '</div>';
        cardHtml += '<div style="font-size: 10px; color: #64748b; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + c.slug + '</div>';
        cardHtml += '<div style="margin-top: 5px;"><span class="label ' + badgeClass + '" style="font-size: 9px; padding: 2px 5px;">' + badgeText + '</span></div>';
        cardHtml += '</div>';

        $container.append(cardHtml);
    }

    $container.find('.icon-thumb-cvs').each(function() {
        var slug = $(this).attr('data-slug');
        var b = AWTRIX_BUILTIN_ICONS[slug];
        if (b && b.pixels) {
            var ctx = this.getContext('2d');
            var s = Math.floor(24 / b.w);
            ctx.fillStyle = '#141413';
            ctx.fillRect(0, 0, 24, 24);
            for (var py = 0; py < b.h; py++) {
                for (var px = 0; px < b.w; px++) {
                    var rgb = b.pixels[py * b.w + px];
                    if (rgb) {
                        ctx.fillStyle = 'rgb(' + ((rgb >> 16) & 255) + ',' + ((rgb >> 8) & 255) + ',' + (rgb & 255) + ')';
                        ctx.fillRect(px * s, py * s, s - 1, s - 1);
                    }
                }
            }
        }
    });
}

$(document).on('input', '#icon_picker_search_input', function() {
    var search = $(this).val();
    var activeCat = $('#icon_picker_category_filter button.active').attr('data-cat') || 'all';
    renderIconPickerGrid(activeCat, search);
});

$(document).on('click', '.btn-icon-cat', function() {
    $('#icon_picker_category_filter button').removeClass('btn-primary active').addClass('btn-default');
    $(this).removeClass('btn-default').addClass('btn-primary active');
    var cat = $(this).attr('data-cat');
    var search = $('#icon_picker_search_input').val();
    renderIconPickerGrid(cat, search);
});

$(document).on('click', '.icon-picker-card', function() {
    var slug = $(this).attr('data-slug');
    if (currentIconPickerTarget) {
        $(currentIconPickerTarget).val(slug).trigger('change');
    }
    if ($('#test_msg_icon').length && ($(currentIconPickerTarget).is('#test_msg_icon') || currentIconPickerTarget === '#test_msg_icon')) {
        updatePreviewIconBox(slug);
    }
    $('#modal_awtrix_icon_picker').modal('hide');
});

// --- Render Auto-Detected Hardware & Sensors Summary ---
function renderDetectedHardwareSummary(det) {
    if (!det) {
        $('#div_detected_hardware_summary').hide();
        return;
    }

    var fwHost = '';
    if (det.firmware) fwHost += 'Firmware: ' + det.firmware;
    if (det.hostname) fwHost += (fwHost ? ' | ' : '') + 'Host: ' + det.hostname;
    $('#span_detected_fw_host').text(fwHost ? '(' + fwHost + ')' : '');

    var sensors = det.sensors || {};
    var tel = det.telemetry || {};
    var w = det.matrix_width || 32;
    var h = det.matrix_height || 8;

    var badges = '';
    badges += '<span class="label label-primary" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-th"></i> {{Matriz}}: ' + w + '×' + h + ' LEDs</span>';

    if (sensors.has_battery == 1) {
        var bTxt = (tel.battery !== undefined && tel.battery !== null) ? ' (' + tel.battery + '%)' : '';
        badges += '<span class="label label-success" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-battery-three-quarters"></i> {{Batería}}: {{Sí}}' + bTxt + '</span>';
    } else {
        badges += '<span class="label label-default" style="font-size: 11px; padding: 5px 8px; opacity: 0.75;"><i class="fas fa-plug"></i> {{Sin Batería (Corriente)}}</span>';
    }

    if (sensors.has_lux == 1) {
        var lTxt = (tel.lux !== undefined && tel.lux !== null) ? ' (' + tel.lux + ' lux)' : '';
        badges += '<span class="label label-success" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-sun"></i> {{Sensor Luz (LDR)}}: {{Sí}}' + lTxt + '</span>';
    } else {
        badges += '<span class="label label-default" style="font-size: 11px; padding: 5px 8px; opacity: 0.75;">{{Sin Sensor Luz}}</span>';
    }

    if (sensors.has_temp == 1) {
        var tTxt = (tel.temp !== undefined && tel.temp !== null) ? ' (' + tel.temp + '°C)' : '';
        badges += '<span class="label label-success" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-thermometer-half"></i> {{Temperatura}}: {{Sí}}' + tTxt + '</span>';
    } else {
        badges += '<span class="label label-default" style="font-size: 11px; padding: 5px 8px; opacity: 0.75;">{{Sin Sensor Temp}}</span>';
    }

    if (sensors.has_humidity == 1) {
        var hTxt = (tel.humidity !== undefined && tel.humidity !== null) ? ' (' + tel.humidity + '%)' : '';
        badges += '<span class="label label-success" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-tint"></i> {{Humedad}}: {{Sí}}' + hTxt + '</span>';
    } else {
        badges += '<span class="label label-default" style="font-size: 11px; padding: 5px 8px; opacity: 0.75;">{{Sin Sensor Humedad}}</span>';
    }

    if (sensors.has_audio_tts == 1) {
        badges += '<span class="label label-info" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-volume-up"></i> {{Audio MP3 & TTS}}</span>';
    } else {
        badges += '<span class="label label-info" style="font-size: 11px; padding: 5px 8px;"><i class="fas fa-music"></i> {{Zumbador RTTTL}}</span>';
    }

    $('#div_detected_badges').html(badges);
    $('#div_detected_hardware_summary').slideDown(150);
}

// --- Open Panel Webserver in Browser ---
$('#bt_openPanelWebserver, #bt_openPanelWebserverTop').on('click', function() {
    var ip = $.trim($('#input_device_ip').val());
    var port = parseInt($('#input_device_port').val(), 10) || 80;

    if (!ip) {
        $('#div_alert').showAlert({message: '{{Debes introducir primero la dirección IP o hostname de la pantalla}}', level: 'warning'});
        return;
    }

    var url = 'http://' + ip + (port !== 80 ? ':' + port : '') + '/';
    window.open(url, '_blank');
});

// --- Connect & Auto-Detect Hardware Button ---
$('#bt_testAwtrix').on('click', function() {
    var ip = $('#input_device_ip').val();
    var port = $('#input_device_port').val() || 80;
    var password = $('#input_device_password').val() || '';
    var eqId = $('.eqLogicAttr[data-l1key=id]').val() || 0;

    if (!ip) {
        $('#div_alert').showAlert({message: '{{Debes introducir primero la IP o hostname de tu panel Awtrix}}', level: 'danger'});
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'testConnection',
            ip: ip,
            port: port,
            password: password,
            eqLogic_id: eqId
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var det = data.result.detected || null;
            if (det) {
                $('#input_matrix_width').val(det.matrix_width || 32);
                $('#input_matrix_height').val(det.matrix_height || 8);
                $('#input_awtrix_model').val(det.model || '32x8');
                $('#input_hw_auto_detected').val(1);
                $('#input_detected_firmware').val(det.firmware || '');
                $('#input_detected_hostname').val(det.hostname || '');

                if (det.sensors) {
                    $('#input_has_battery').val(det.sensors.has_battery);
                    $('#input_has_lux').val(det.sensors.has_lux);
                    $('#input_has_temp').val(det.sensors.has_temp);
                    $('#input_has_humidity').val(det.sensors.has_humidity);
                    $('#input_has_audio_tts').val(det.sensors.has_audio_tts);
                }

                updateModelCardUI(det.model || '32x8');
                renderDetectedHardwareSummary(det);

                var msg = '{{¡Comunicación establecida y hardware auto-detectado!}} ';
                msg += '{{Matriz}}: ' + det.matrix_width + '×' + det.matrix_height + ' (' + det.size_label + ')';
                if (det.firmware) msg += ' | {{Firmware}}: ' + det.firmware;
                $('#div_alert').showAlert({message: msg, level: 'success'});
            } else {
                $('#div_alert').showAlert({message: '{{¡Conexión establecida con éxito con Awtrix NG!}}', level: 'success'});
            }
        }
    });
});

// --- Send Quick Notification Button (Single Panel) ---
$('#bt_sendQuickNotification').on('click', function() {
    clearJeedomToasts();
    var ip = $('#input_device_ip').val();
    var port = $('#input_device_port').val() || 80;
    var text = $('#test_msg_text').val();
    var icon = $('#test_msg_icon').val();
    var color = $('#test_msg_color').val();
    var durMode = $('#test_msg_duration').val() || '12';
    var isHold = (durMode === 'hold') ? 1 : 0;
    var durVal = (durMode === 'hold') ? 0 : (parseInt(durMode, 10) || 12);

    if (!ip) {
        $('#div_alert').showAlert({message: '{{Debes introducir primero la IP de este panel Awtrix}}', level: 'danger', emptyBefore: true});
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'sendQuickNotification',
            ip: ip,
            port: port,
            password: $('#input_device_password').val() || '',
            eqLogic_id: $('.eqLogicAttr[data-l1key=id]').val() || 0,
            text: text,
            icon: icon,
            color: color,
            duration: durVal,
            hold: isHold
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger', emptyBefore: true});
                return;
            }
            var infoMode = isHold ? ' [{{Fija / Permanente}}]' : ' [' + durVal + 's]';
            $('#div_alert').showAlert({message: '{{¡Notificación enviada correctamente al panel Awtrix!}} (' + ip + ')' + infoMode, level: 'success', emptyBefore: true});
            var cfgW = parseInt($('#input_matrix_width').val(), 10) || 32;
            var cfgH = parseInt($('#input_matrix_height').val(), 10) || 8;
            if (cfgW === 32 && cfgH === 8) {
                setSimulatorMode('live');
                if (awtrixSimState.revertToEditTimeout) clearTimeout(awtrixSimState.revertToEditTimeout);
                if (!isHold) {
                    awtrixSimState.revertToEditTimeout = setTimeout(function() {
                        setSimulatorMode('edit');
                    }, (durVal > 0 ? durVal * 1000 + 500 : 12500));
                }
            } else {
                awtrixSimState.scrollOffset = 0;
                setSimulatorMode('edit');
            }
        }
    });
});

// --- Dismiss Notification Button ---
$('#bt_dismissNotification').on('click', function() {
    clearJeedomToasts();
    var ip = $('#input_device_ip').val();
    var port = $('#input_device_port').val() || 80;
    var eqId = $('.eqLogicAttr[data-l1key=id]').val() || 0;

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'dismissNotification',
            ip: ip,
            port: port,
            password: $('#input_device_password').val() || '',
            eqLogic_id: eqId
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger', emptyBefore: true});
                return;
            }
            $('#div_alert').showAlert({message: '{{¡Notificación descartada/borrada correctamente de la pantalla!}}', level: 'success', emptyBefore: true});
            if (awtrixSimState.revertToEditTimeout) clearTimeout(awtrixSimState.revertToEditTimeout);
            setSimulatorMode('edit');
        }
    });
});

$('#test_msg_duration').on('change', function() {
    updateScenarioSnippet();
});

// --- Send Broadcast Notification Button (All Active Panels) ---
$('#bt_sendBroadcastNotification').on('click', function() {
    clearJeedomToasts();
    var text = $('#test_msg_text').val();
    var icon = $('#test_msg_icon').val();
    var color = $('#test_msg_color').val();

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'sendBroadcastNotification',
            text: text,
            icon: icon,
            color: color
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger', emptyBefore: true});
                return;
            }
            var res = data.result || {};
            $('#div_alert').showAlert({message: '{{¡Notificación enviada simultáneamente a los paneles Awtrix!}} (' + (res.sent || 0) + ')', level: 'success', emptyBefore: true});
        }
    });
});

// --- Panel-Specific Carousel Information Builder (#table_panel_apps) ---
function addPanelAppRow(_app) {
    _app = _app || {};
    var enabled = (_app.enable === undefined || _app.enable == 1) ? 'checked' : '';
    var name = _app.name || '';
    var icon = _app.icon || 'plant-watering';
    var color = (_app.color || '#00FF00').toUpperCase();
    var text = _app.text || '';
    var duration = _app.duration || 10;

    var tr = '<tr class="panelAppRow">';
    tr += '<td style="text-align: center; vertical-align: middle;">';
    tr += '<input type="checkbox" class="appAttr_enable" ' + enabled + ' />';
    tr += '</td>';
    tr += '<td>';
    tr += '<input type="text" class="form-control input-sm appAttr_name" placeholder="ej. termo" value="' + name.replace(/"/g, '&quot;') + '" />';
    tr += '</td>';
    tr += '<td>';
    tr += '<div style="display: flex; gap: 4px; align-items: center;">';
    tr += '<input type="text" class="form-control input-sm appAttr_icon" placeholder="ej. plant-watering" title="{{Descarga el icono desde https://awtrix.de/icons/ en la pantalla del Awtrix NG (Hint: renombra el icono a un máximo de 12 caracteres)}}" value="' + icon.replace(/"/g, '&quot;') + '" style="min-width: 80px;" />';
    tr += '<button type="button" class="btn btn-xs btn-primary bt_openIconPicker" data-target="this" title="{{Elegir Icono}}"><i class="fas fa-icons"></i></button>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<div class="input-group input-group-sm">';
    tr += '<input type="color" class="form-control appAttr_picker" value="' + color + '" style="width: 38px; padding: 1px; cursor: pointer;" />';
    tr += '<input type="text" class="form-control appAttr_color" value="' + color + '" style="font-family: monospace; text-transform: uppercase;" />';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<div class="input-group input-group-sm">';
    tr += '<input type="text" class="form-control appAttr_text" placeholder="{{Texto o #[Objeto][Equipo][Comando]#}}" value="' + text.replace(/"/g, '&quot;') + '" />';
    tr += '<span class="input-group-btn">';
    tr += '<button type="button" class="btn btn-default bt_selectAppJeedomCmd" title="{{Insertar valor de un sensor o comando de Jeedom}}"><i class="fas fa-list-alt"></i></button>';
    tr += '</span>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<input type="number" class="form-control input-sm appAttr_duration" min="2" max="120" value="' + duration + '" />';
    tr += '</td>';
    tr += '<td style="text-align: center; vertical-align: middle;">';
    tr += '<button type="button" class="btn btn-xs btn-danger bt_removePanelApp"><i class="fas fa-trash"></i></button>';
    tr += '</td>';
    tr += '</tr>';

    $('#tbody_panel_apps').append(tr);
}

function collectPanelAppsFromTable() {
    var list = [];
    $('#tbody_panel_apps tr.panelAppRow').each(function() {
        var $tr = $(this);
        var name = $.trim($tr.find('.appAttr_name').val());
        if (!name) return;
        list.push({
            enable: $tr.find('.appAttr_enable').is(':checked') ? 1 : 0,
            name: name,
            icon: $.trim($tr.find('.appAttr_icon').val()),
            color: $.trim($tr.find('.appAttr_color').val()) || '#FFFFFF',
            text: $tr.find('.appAttr_text').val() || '',
            duration: parseInt($tr.find('.appAttr_duration').val(), 10) || 10
        });
    });
    return list;
}

$('#bt_addPanelApp').off('click').on('click', function() {
    addPanelAppRow({
        enable: 1,
        name: 'info_' + ($('#tbody_panel_apps tr').length + 1),
        icon: $('#input_awtrix_model').val() === '52x16' ? '2284' : '2284',
        color: $('#input_default_color').val() || '#00FF00',
        text: '',
        duration: 10
    });
});

$(document).on('click.awtrixng', '.btn-add-app-preset', function() {
    addPanelAppRow({
        enable: 1,
        name: $(this).attr('data-name'),
        icon: $(this).attr('data-icon'),
        color: $(this).attr('data-color'),
        text: $(this).attr('data-text'),
        duration: 10
    });
});

$(document).on('click.awtrixng', '.bt_removePanelApp', function() {
    $(this).closest('tr').remove();
});

$(document).on('input.awtrixng change.awtrixng', '.appAttr_picker', function() {
    var col = $(this).val().toUpperCase();
    $(this).closest('td').find('.appAttr_color').val(col);
});

$(document).on('input.awtrixng change.awtrixng', '.appAttr_color', function() {
    var col = $(this).val();
    if (/^#[0-9a-fA-F]{6}$/.test(col)) {
        $(this).closest('td').find('.appAttr_picker').val(col);
    }
});

// Jeedom Command Selector for Panel App Row
$(document).on('click.awtrixng', '.bt_selectAppJeedomCmd', function() {
    var $input = $(this).closest('.input-group').find('.appAttr_text');
    if (typeof jeedom !== 'undefined' && jeedom.cmd && typeof jeedom.cmd.getSelectModal === 'function') {
        jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
            var currentVal = $input.val();
            $input.val(currentVal + (currentVal ? ' ' : '') + result.human);
        });
    }
});

// Sync Panel Apps Now button
$('#bt_syncPanelAppsNow').on('click', function() {
    var eqId = $('.eqLogicAttr[data-l1key=id]').val();
    if (!eqId) {
        $('#div_alert').showAlert({message: '{{Guarda primero el equipo antes de sincronizar}}', level: 'warning'});
        return;
    }
    var apps = collectPanelAppsFromTable();
    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: {
            action: 'syncPanelApps',
            id: eqId,
            custom_apps_list: JSON.stringify(apps)
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var count = (data.result && data.result.synced_count) ? data.result.synced_count : 0;
            $('#div_alert').showAlert({message: '{{¡Información actualizada en este panel Awtrix!}} (' + count + ')', level: 'success'});
        }
    });
});

// --- Multi-Panel Copy Configuration Modal ---
function openCopyPanelModal(direction) {
    var currentId = $('.eqLogicAttr[data-l1key=id]').val();
    var currentName = $('.eqLogicAttr[data-l1key=name]').val() || '{{Este Panel}}';

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: { action: 'listPanels' },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var panels = data.result || [];
            var otherPanels = [];
            for (var i = 0; i < panels.length; i++) {
                if (String(panels[i].id) !== String(currentId)) {
                    otherPanels.push(panels[i]);
                }
            }

            if (otherPanels.length === 0) {
                $('#div_alert').showAlert({
                    message: '{{Solo tienes un panel creado actualmente. Añade otro panel Awtrix (con su propia dirección IP y tamaño) para poder copiar configuraciones entre ellos.}}',
                    level: 'warning'
                });
                return;
            }

            var isFromOther = (direction === 'from');
            var optionsHtml = '';
            if (!isFromOther) {
                optionsHtml += '<option value="all">🌐 {{TODOS los demás paneles}} (' + otherPanels.length + ')</option>';
            }
            for (var j = 0; j < otherPanels.length; j++) {
                var p = otherPanels[j];
                var ipLabel = p.ip ? p.ip : '{{Sin IP}}';
                optionsHtml += '<option value="' + p.id + '">📺 ' + p.name + ' — [' + p.size_label + ' | IP: ' + ipLabel + ']</option>';
            }

            var dialogTitle = isFromOther
                ? '<i class="fas fa-download"></i> {{Traer configuración DESDE otro panel a}} "' + currentName + '"'
                : '<i class="fas fa-share-square"></i> {{Copiar configuración de}} "' + currentName + '" {{a otro(s) panel(es)}}';

            var selectorLabel = isFromOther
                ? '{{Selecciona el panel de ORIGEN del que quieres copiar}}:'
                : '{{Selecciona el panel (o paneles) DESTINO}}:';

            var html = '<form class="form-horizontal" style="padding: 5px 10px;">';
            html += '<div class="alert alert-info" style="font-size: 12px; margin-bottom: 12px;">';
            html += '<i class="fas fa-shield-alt"></i> <strong>{{Protección Multi-Panel}}:</strong> {{La dirección IP y el puerto de cada panel NO se sobrescriben nunca. Cada panel conservará su propia IP y su propio tamaño de matriz.}}';
            html += '</div>';
            html += '<div class="form-group">';
            html += '<label class="col-sm-12" style="margin-bottom: 5px;">' + selectorLabel + '</label>';
            html += '<div class="col-sm-12"><select id="sel_copy_panel_peer" class="form-control">' + optionsHtml + '</select></div>';
            html += '</div>';
            html += '<div class="form-group" style="margin-bottom: 6px;">';
            html += '<div class="col-sm-12">';
            html += '<label style="font-weight: bold; display: block; margin-bottom: 6px;">{{¿Qué deseas copiar?}}</label>';
            html += '<div class="checkbox"><label><input type="checkbox" id="chk_copy_apps" checked/> <strong>{{Información en Pantalla (Carrusel de Apps y Sensores)}}</strong></label></div>';
            html += '<div class="checkbox"><label><input type="checkbox" id="chk_copy_visual" checked/> <strong>{{Personalización Visual}}</strong> ({{Color de texto por defecto, icono por defecto y auto-refresco}})</label></div>';
            html += '<div class="checkbox"><label><input type="checkbox" id="chk_copy_model"/> {{Copiar también el Modelo / Tamaño de Matriz (desmarcado por defecto si los paneles son de distinto tamaño)}}</label></div>';
            html += '<hr style="margin: 8px 0;"/>';
            html += '<div class="checkbox"><label><input type="checkbox" id="chk_copy_sync_now" checked/> <span class="text-success"><strong>{{Sincronizar y mostrar inmediatamente la información en el panel destino}}</strong></span></label></div>';
            html += '</div>';
            html += '</div>';
            html += '</form>';

            bootbox.dialog({
                title: dialogTitle,
                message: html,
                buttons: {
                    cancel: {
                        label: '{{Cancelar}}',
                        className: 'btn-default'
                    },
                    confirm: {
                        label: '<i class="fas fa-check"></i> {{Copiar Configuración Ahora}}',
                        className: 'btn-warning',
                        callback: function() {
                            var peerId = $('#sel_copy_panel_peer').val();
                            var copyApps = $('#chk_copy_apps').is(':checked') ? 1 : 0;
                            var copyVisual = $('#chk_copy_visual').is(':checked') ? 1 : 0;
                            var copyModel = $('#chk_copy_model').is(':checked') ? 1 : 0;
                            var syncNow = $('#chk_copy_sync_now').is(':checked') ? 1 : 0;

                            var sourceId = isFromOther ? peerId : currentId;
                            var targetId = isFromOther ? currentId : peerId;

                            $.ajax({
                                type: 'POST',
                                url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                                data: {
                                    action: 'copyPanelConfig',
                                    source_id: sourceId,
                                    target_id: targetId,
                                    copy_apps: copyApps,
                                    copy_visual: copyVisual,
                                    copy_model: copyModel,
                                    sync_now: syncNow
                                },
                                dataType: 'json',
                                error: function(request, status, error) {
                                    handleAjaxError(request, status, error);
                                },
                                success: function(res) {
                                    if (res.state != 'ok') {
                                        $('#div_alert').showAlert({message: res.result, level: 'danger'});
                                        return;
                                    }
                                    var info = res.result || {};
                                    var names = (info.copied_panels || []).join(', ');
                                    $('#div_alert').showAlert({
                                        message: '{{¡Configuración copiada con éxito a: }} ' + names + ' {{(conservando cada panel su propia IP)!}}',
                                        level: 'success'
                                    });
                                    if (isFromOther) {
                                        // Reload current eqLogic view to reflect copied apps/colors
                                        $('.li_eqLogic[data-eqLogic_id="' + currentId + '"]').click();
                                    }
                                }
                            });
                        }
                    }
                }
            });
        }
    });
}

$('#bt_openCopyConfigModal, #bt_copyToOtherPanel, #bt_copyAppsToOtherPanel').on('click', function() {
    openCopyPanelModal('to');
});

$('#bt_copyFromOtherPanel').on('click', function() {
    openCopyPanelModal('from');
});

// --- Logs Viewer Modal ---
$('#bt_openPluginLogs, #bt_openPluginLogsDetail').on('click', function() {
    if ($('#md_modal').length) {
        $('#md_modal').dialog({title: '{{Registros (Logs) del Plugin Awtrix NG}}'});
        $('#md_modal').load('index.php?v=d&modal=log.display&log=awtrixng').dialog('open');
    } else {
        window.open('index.php?v=d&p=log&logfile=awtrixng', '_blank');
    }
});

// --- Helper to download JSON bundle in the browser ---
function triggerJsonDownload(bundleData, filename) {
    var jsonStr = typeof bundleData === 'string' ? bundleData : JSON.stringify(bundleData, null, 2);
    var blob = new Blob([jsonStr], {type: 'application/json;charset=utf-8'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename || ('awtrixng_backup_' + new Date().toISOString().slice(0, 10) + '.json');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

// --- Backup & Restore Manager Modal ---
function openBackupRestoreModal(defaultScope) {
    var currentId = $('.eqLogicAttr[data-l1key=id]').val() || '';

    $.ajax({
        type: 'POST',
        url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
        data: { action: 'listPanels' },
        dataType: 'json',
        success: function(pRes) {
            var panels = (pRes.state === 'ok' && pRes.result) ? pRes.result : [];

            $.ajax({
                type: 'POST',
                url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                data: { action: 'listBackups' },
                dataType: 'json',
                success: function(bRes) {
                    var backups = (bRes.state === 'ok' && bRes.result) ? bRes.result : [];

                    var scopeOptions = '<option value="all">🌐 {{TODOS los paneles configurados en el plugin}} (' + panels.length + ')</option>';
                    var targetOptions = '<option value="auto">🔄 {{Restaurar automáticamente todos los paneles incluidos en la copia}}</option>';

                    for (var i = 0; i < panels.length; i++) {
                        var p = panels[i];
                        var sel = (defaultScope === 'single' && String(p.id) === String(currentId)) ? 'selected' : '';
                        var ipStr = p.ip ? p.ip : '{{Sin IP}}';
                        scopeOptions += '<option value="' + p.id + '" ' + sel + '>📺 {{Solo el panel}}: ' + p.name + ' [' + p.size_label + ' | ' + ipStr + ']</option>';
                        targetOptions += '<option value="' + p.id + '">🎯 {{Aplicar sobre el panel}}: ' + p.name + ' [' + p.size_label + ' | ' + ipStr + ']</option>';
                    }

                    var backupsRows = '';
                    if (backups.length === 0) {
                        backupsRows = '<tr><td colspan="4" style="text-align:center; color:#777;">{{Aún no hay copias de seguridad guardadas en el servidor.}}</td></tr>';
                    } else {
                        for (var j = 0; j < backups.length; j++) {
                            var b = backups[j];
                            backupsRows += '<tr>';
                            backupsRows += '<td><strong>' + b.created_at + '</strong><br><small style="color:#666;">' + b.filename + '</small></td>';
                            backupsRows += '<td style="text-align:center; vertical-align:middle;"><span class="label label-info">' + b.panels_count + ' {{panel(es)}}</span></td>';
                            backupsRows += '<td style="text-align:center; vertical-align:middle;">' + b.size_kb + ' KB</td>';
                            backupsRows += '<td style="text-align:right; vertical-align:middle; white-space:nowrap;">';
                            backupsRows += '<button type="button" class="btn btn-xs btn-warning bt_restoreServerBackup" data-filename="' + b.filename + '" title="{{Restaurar esta copia}}"><i class="fas fa-undo"></i> {{Restaurar}}</button> ';
                            backupsRows += '<button type="button" class="btn btn-xs btn-default bt_downloadServerBackup" data-filename="' + b.filename + '" title="{{Descargar archivo .json}}"><i class="fas fa-download"></i></button> ';
                            backupsRows += '<button type="button" class="btn btn-xs btn-danger bt_deleteServerBackup" data-filename="' + b.filename + '" title="{{Eliminar del servidor}}"><i class="fas fa-trash"></i></button>';
                            backupsRows += '</td>';
                            backupsRows += '</tr>';
                        }
                    }

                    var html = '<div style="padding: 5px 10px;">';
                    // 1. Create Backup Section
                    html += '<div class="panel panel-primary" style="margin-bottom: 15px;">';
                    html += '<div class="panel-heading"><i class="fas fa-download"></i> <strong>{{1. Crear Copia de Seguridad (Backup)}}</strong></div>';
                    html += '<div class="panel-body">';
                    html += '<p style="font-size: 12px; margin-bottom: 8px;">{{Guarda en un archivo JSON la configuración de tus paneles (IP, modelo/tamaño, colores, iconos, pantallas del carrusel y ajustes internos del reloj):}}</p>';
                    html += '<div class="row" style="display:flex; align-items:center; flex-wrap:wrap; gap:8px;">';
                    html += '<div class="col-sm-7"><select id="sel_backup_scope" class="form-control input-sm">' + scopeOptions + '</select></div>';
                    html += '<div class="col-sm-4"><button type="button" class="btn btn-sm btn-primary" id="bt_doCreateBackup"><i class="fas fa-archive"></i> {{Crear y Descargar Backup (.json)}}</button></div>';
                    html += '</div>';
                    html += '</div></div>';

                    // 2. Restore Backup Section
                    html += '<div class="panel panel-warning" style="margin-bottom: 5px;">';
                    html += '<div class="panel-heading"><i class="fas fa-upload"></i> <strong>{{2. Restaurar Copia de Seguridad}}</strong></div>';
                    html += '<div class="panel-body">';
                    html += '<div class="form-group" style="margin-bottom: 8px;">';
                    html += '<label style="font-size: 12px;">{{Destino de la restauración}}:</label>';
                    html += '<select id="sel_restore_target" class="form-control input-sm">' + targetOptions + '</select>';
                    html += '</div>';
                    html += '<div style="margin-bottom: 10px; font-size: 12px;">';
                    html += '<label class="checkbox-inline"><input type="checkbox" id="chk_restore_preserve_ip"/> {{Conservar la IP actual del panel destino}}</label> ';
                    html += '<label class="checkbox-inline"><input type="checkbox" id="chk_restore_preserve_size"/> {{Conservar el tamaño actual del panel}}</label> ';
                    html += '<label class="checkbox-inline"><input type="checkbox" id="chk_restore_firmware" checked/> {{Restaurar también ajustes en el reloj físico}}</label>';
                    html += '</div>';

                    // Upload JSON file
                    html += '<div class="well well-sm" style="margin-bottom: 12px; background: #f8fafc;">';
                    html += '<label style="font-size: 12px; display:block;"><i class="fas fa-file-upload"></i> {{Restaurar subiendo un archivo .json desde tu ordenador}}:</label>';
                    html += '<div style="display:flex; gap:8px; align-items:center; margin-top:5px;">';
                    html += '<input type="file" id="input_restore_file" accept=".json,application/json" class="form-control input-sm" style="max-width: 340px;" />';
                    html += '<button type="button" class="btn btn-sm btn-warning" id="bt_doRestoreFromFile"><i class="fas fa-upload"></i> {{Restaurar Archivo Subido}}</button>';
                    html += '</div>';
                    html += '</div>';

                    // Server Backups Table
                    html += '<label style="font-size: 12px; display:block; margin-bottom:4px;"><i class="fas fa-server"></i> {{Copias de seguridad guardadas en el servidor Jeedom}}:</label>';
                    html += '<div style="max-height: 190px; overflow-y: auto;">';
                    html += '<table class="table table-bordered table-condensed" style="font-size: 12px; margin-bottom:0;">';
                    html += '<thead><tr><th>{{Fecha y Archivo}}</th><th style="text-align:center;">{{Paneles}}</th><th style="text-align:center;">{{Tamaño}}</th><th style="text-align:right;">{{Acciones}}</th></tr></thead>';
                    html += '<tbody id="tbody_server_backups">' + backupsRows + '</tbody>';
                    html += '</table>';
                    html += '</div>';

                    html += '</div></div>';
                    html += '</div>';

                    var dialog = bootbox.dialog({
                        title: '<i class="fas fa-archive"></i> {{Copia de Seguridad y Restauración — Awtrix NG}}',
                        message: html,
                        size: 'large',
                        buttons: {
                            close: {
                                label: '{{Cerrar}}',
                                className: 'btn-default'
                            }
                        }
                    });

                    // Handler: Create & Download Backup
                    dialog.on('click', '#bt_doCreateBackup', function() {
                        var scopeVal = $('#sel_backup_scope').val();
                        $.ajax({
                            type: 'POST',
                            url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                            data: { action: 'createBackup', panel_id: scopeVal, save_on_server: 1 },
                            dataType: 'json',
                            success: function(res) {
                                if (res.state !== 'ok') {
                                    $('#div_alert').showAlert({message: res.result, level: 'danger'});
                                    return;
                                }
                                var bundle = res.result;
                                triggerJsonDownload(bundle, bundle.saved_filename);
                                $('#div_alert').showAlert({
                                    message: '{{¡Copia de seguridad creada en el servidor y descargada con éxito!}} (' + bundle.saved_filename + ')',
                                    level: 'success'
                                });
                                bootbox.hideAll();
                            }
                        });
                    });

                    // Handler: Restore from uploaded file
                    dialog.on('click', '#bt_doRestoreFromFile', function() {
                        var fileInput = document.getElementById('input_restore_file');
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            $('#div_alert').showAlert({message: '{{Selecciona primero un archivo .json de copia de seguridad}}', level: 'warning'});
                            return;
                        }
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var jsonContent = e.target.result;
                            $.ajax({
                                type: 'POST',
                                url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                                data: {
                                    action: 'restoreBackup',
                                    backup_json: jsonContent,
                                    target_panel_id: $('#sel_restore_target').val(),
                                    preserve_ip: $('#chk_restore_preserve_ip').is(':checked') ? 1 : 0,
                                    preserve_size: $('#chk_restore_preserve_size').is(':checked') ? 1 : 0,
                                    restore_firmware: $('#chk_restore_firmware').is(':checked') ? 1 : 0
                                },
                                dataType: 'json',
                                success: function(res) {
                                    if (res.state !== 'ok') {
                                        $('#div_alert').showAlert({message: res.result, level: 'danger'});
                                        return;
                                    }
                                    var info = res.result || {};
                                    bootbox.hideAll();
                                    $('#div_alert').showAlert({
                                        message: '{{¡Copia de seguridad restaurada correctamente en: }} ' + (info.restored_panels || []).join(', ') + '!',
                                        level: 'success'
                                    });
                                    setTimeout(function() { window.location.reload(); }, 1000);
                                }
                            });
                        };
                        reader.readAsText(fileInput.files[0]);
                    });

                    // Handler: Restore from Server Backup
                    dialog.on('click', '.bt_restoreServerBackup', function() {
                        var fname = $(this).attr('data-filename');
                        $.ajax({
                            type: 'POST',
                            url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                            data: {
                                action: 'restoreBackup',
                                filename: fname,
                                target_panel_id: $('#sel_restore_target').val(),
                                preserve_ip: $('#chk_restore_preserve_ip').is(':checked') ? 1 : 0,
                                preserve_size: $('#chk_restore_preserve_size').is(':checked') ? 1 : 0,
                                restore_firmware: $('#chk_restore_firmware').is(':checked') ? 1 : 0
                            },
                            dataType: 'json',
                            success: function(res) {
                                if (res.state !== 'ok') {
                                    $('#div_alert').showAlert({message: res.result, level: 'danger'});
                                    return;
                                }
                                var info = res.result || {};
                                bootbox.hideAll();
                                $('#div_alert').showAlert({
                                    message: '{{¡Copia de seguridad restaurada correctamente en: }} ' + (info.restored_panels || []).join(', ') + '!',
                                    level: 'success'
                                });
                                setTimeout(function() { window.location.reload(); }, 1000);
                            }
                        });
                    });

                    // Handler: Download Server Backup
                    dialog.on('click', '.bt_downloadServerBackup', function() {
                        var fname = $(this).attr('data-filename');
                        $.ajax({
                            type: 'POST',
                            url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                            data: { action: 'getServerBackupContent', filename: fname },
                            dataType: 'json',
                            success: function(res) {
                                if (res.state === 'ok' && res.result) {
                                    triggerJsonDownload(res.result.bundle, res.result.filename);
                                }
                            }
                        });
                    });

                    // Handler: Delete Server Backup
                    dialog.on('click', '.bt_deleteServerBackup', function() {
                        var $row = $(this).closest('tr');
                        var fname = $(this).attr('data-filename');
                        $.ajax({
                            type: 'POST',
                            url: 'plugins/awtrixng/core/ajax/awtrixng.ajax.php',
                            data: { action: 'deleteBackup', filename: fname },
                            dataType: 'json',
                            success: function(res) {
                                if (res.state === 'ok') {
                                    $row.fadeOut(200, function() { $(this).remove(); });
                                }
                            }
                        });
                    });
                }
            });
        }
    });
}

$('#bt_openBackupRestoreGlobal').on('click', function() {
    openBackupRestoreModal('all');
});

$('#bt_openBackupRestorePanel, #bt_openBackupRestoreSection').on('click', function() {
    openBackupRestoreModal('single');
});

// Hook called by Jeedom before saving an eqLogic
function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    _eqLogic.configuration.custom_apps_list = collectPanelAppsFromTable();
    return _eqLogic;
}

// When an eqLogic is loaded, initialize model UI & panel-specific apps table
function printEqLogic(_eqLogic) {
    var model = '32x8';
    if (_eqLogic && _eqLogic.configuration && _eqLogic.configuration.model) {
        model = _eqLogic.configuration.model;
    }
    if (_eqLogic && _eqLogic.configuration) {
        if (_eqLogic.configuration.matrix_width) {
            $('#input_matrix_width').val(_eqLogic.configuration.matrix_width);
        }
        if (_eqLogic.configuration.matrix_height) {
            $('#input_matrix_height').val(_eqLogic.configuration.matrix_height);
        }
    }
    updateModelCardUI(model);

    if (_eqLogic && _eqLogic.configuration && _eqLogic.configuration.default_color) {
        var col = _eqLogic.configuration.default_color;
        $('#picker_default_color').val(col);
        $('#input_default_color').val(col);
    }

    if (_eqLogic && _eqLogic.configuration && _eqLogic.configuration.ip) {
        loadInstalledPanelIcons(_eqLogic.configuration.ip, _eqLogic.configuration.port || 80);
        loadPresetNotificationCmds();
        setTimeout(function() {
            updatePreviewIconBox($('#test_msg_icon').val());
        }, 100);
    }

    // Show auto-detected hardware summary if already detected for this panel
    if (_eqLogic && _eqLogic.configuration && _eqLogic.configuration.hardware_auto_detected == 1) {
        var c = _eqLogic.configuration;
        renderDetectedHardwareSummary({
            model: c.model || '32x8',
            matrix_width: c.matrix_width || 32,
            matrix_height: c.matrix_height || 8,
            firmware: c.detected_firmware || 'Awtrix NG',
            hostname: c.detected_hostname || '',
            sensors: {
                has_battery: c.has_battery !== undefined ? c.has_battery : 1,
                has_lux: c.has_lux !== undefined ? c.has_lux : 1,
                has_temp: c.has_temp !== undefined ? c.has_temp : 1,
                has_humidity: c.has_humidity !== undefined ? c.has_humidity : 1,
                has_audio_tts: c.has_audio_tts !== undefined ? c.has_audio_tts : 0
            },
            telemetry: {}
        });
    } else {
        $('#div_detected_hardware_summary').hide();
    }

    // Load panel-specific custom apps list
    $('#tbody_panel_apps').empty();
    if (_eqLogic && _eqLogic.configuration && _eqLogic.configuration.custom_apps_list) {
        var apps = _eqLogic.configuration.custom_apps_list;
        if (typeof apps === 'string') {
            try {
                apps = JSON.parse(apps);
            } catch (e) {
                apps = [];
            }
        }
        if ($.isArray(apps)) {
            for (var i = 0; i < apps.length; i++) {
                addPanelAppRow(apps[i]);
            }
        }
    }
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td style="width: 80px;">';
    tr += '<span class="cmdAttr" data-l1key="id"></span>';
    tr += '</td>';
    tr += '<td style="width: 300px;">';
    tr += '<div class="row">';
    tr += '<div class="col-xs-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nombre}}">';
    tr += '</div>';
    tr += '<div class="col-xs-6">';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</div>';
    tr += '</div>';
    tr += '</td>';
    tr += '<td>';
    tr += '<span class="cmdAttr" data-l1key="type" style="display:none;"></span>';
    tr += '<span class="cmdAttr" data-l1key="subType" style="display:none;"></span>';
    tr += '<span class="cmdAttr" data-l1key="logicalId" style="display:none;"></span>';
    tr += '<span class="badge badge-info">' + init(_cmd.logicalId) + '</span> ';
    if (init(_cmd.unite)) {
        tr += '<span class="badge badge-default">' + init(_cmd.unite) + '</span>';
    }
    tr += '</td>';
    tr += '<td>';
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Mostrar}}</label> ';
    if (_cmd.subType == 'numeric' || _cmd.subType == 'binary') {
        tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/>{{Historizar}}</label> ';
    }
    tr += '</td>';
    tr += '<td style="width: 150px;">';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Probar}}</a>';
    }
    tr += '</td>';
    tr += '</tr>';

    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

// Ensure matrix simulation and installed icons load when entering Quick Test tab
$(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function(e) {
    if ($(e.target).attr('href') === '#quicktesttab') {
        var ip = $.trim($('#input_device_ip').val());
        var port = parseInt($('#input_device_port').val(), 10) || 80;
        if (ip) {
            loadInstalledPanelIcons(ip, port);
        }
        updateModelCardUI();
        drawSimulatedMatrixFrame();
        updateScenarioSnippet();
        loadPresetNotificationCmds();
    }
});

$(function() {
    if (window.location.hash === '#quicktesttab') {
        setTimeout(function() {
            var ip = $.trim($('#input_device_ip').val());
            var port = parseInt($('#input_device_port').val(), 10) || 80;
            if (ip) {
                loadInstalledPanelIcons(ip, port);
            }
            updateModelCardUI();
            drawSimulatedMatrixFrame();
            updateScenarioSnippet();
            loadPresetNotificationCmds();
        }, 300);
    }
});
