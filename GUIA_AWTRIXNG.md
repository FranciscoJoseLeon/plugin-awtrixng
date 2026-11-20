# Guía Completa de Instalación y Uso: Plugin Awtrix NG para Jeedom

Integración nativa para pantallas matriciales LED **Awtrix Light** y **Awtrix NG** (relojes inteligentes **Ulanzi TC001**, **Ulanzi TC002** y matrices ESP32 caseras WS2812B).

Permite controlar la pantalla directamente desde Jeedom a través de su **API HTTP REST** (sin requerir broker MQTT externo), con notificaciones emergentes en color, carrusel de apps, ajuste de brillo, sensores de batería/luz y soporte completo para las dos resoluciones de pantalla del mercado.

---

## 📺 1. Gestión Multi-Panel: Distintas IPs, Distintos Tamaños e Información Independiente o Copiada

El plugin está diseñado para gestionar **tantos paneles como quieras simultáneamente**, cada uno como un equipo independiente con su propia **dirección IP** y su propio **tamaño de matriz LED**:

1. **32×8 (TC001 & DIY)**:
   * **Resolución**: Matriz de 32 columnas × 8 filas de LEDs.
   * **Iconos en Color**: Iconos de **8×8 píxeles** (biblioteca pública de LaMetric y Awtrix con miles de iconos).
   * **Hardware**: Ulanzi TC001 y matrices WS2812B. Telemetría de batería portátil, sensor de luz LDR y zumbador RTTTL.

2. **52×16 (TC002)**:
   * **Resolución**: Matriz de 52 columnas × 16 filas de LEDs.
   * **Iconos en Color**: Admite tanto iconos de 8×8 como nuevos iconos de alta definición de **16×16 píxeles**.
   * **Hardware**: Ulanzi TC002. Altavoz para reproducción de audio MP3, radio web, melodías, síntesis de voz (TTS) y comandos de volumen.

3. **Matriz DIY Compatible con Awtrix NG (de 32×8 hasta 128×8 LEDs)**:
   * Según las especificaciones oficiales de Awtrix NG para montajes caseros ESP32 / ESP32-S3, admite matrices de **8 filas de alto** y un **ancho configurable de 32 a 128 píxeles** (por ejemplo `32×8`, `64×8`, `96×8`, `128×8`).

### 🔄 ¿Información distinta en cada panel o la misma en todos?
* **Información independiente por panel**: En la pestaña **"Información de este Panel (Carrusel)"** de cada equipo puedes definir qué sensores de Jeedom (`#[Objeto][Equipo][Comando]#`) o avisos muestra ese panel en particular (por ejemplo, en la cocina mostrar el termo y la lavadora, y en el salón mostrar la temperatura exterior y el riego).
* **Copiar configuración de un panel a otro**: Si quieres que dos o más paneles muestren exactamente lo mismo, pulsa el botón **"Copiar Config entre Paneles"**. El plugin copiará las pantallas de información y los colores al panel destino (o a todos los paneles) **conservando siempre la dirección IP propia y el tamaño de matriz de cada panel**.

### 💾 Copia de Seguridad (Backup / Restauración) y Registro de Logs
* **Copia de Seguridad y Restauración**: Desde el icono **Backup / Restaurar** (tanto en la pantalla principal del plugin como dentro de cada panel) puedes exportar un archivo `.json` con todos tus paneles o con un panel individual (incluyendo sus apps del carrusel, colores, IPs, tamaños y ajustes internos `/api/settings` del reloj), guardarlo automáticamente en el servidor Jeedom (`data/backups/`) y restaurarlo en cualquier momento con un clic o subiendo tu archivo `.json`.
* **Sistema de Logs Integrado (`awtrixng`)**: Todas las operaciones, peticiones HTTP, copias entre paneles, backups y alertas quedan registradas en el motor de logs de Jeedom identificando cada panel por su nombre, IP y tamaño (`[NombrePanel | IP | Tamaño]`). Puedes abrir el visor de logs directamente con el botón **Ver Logs** del plugin o desde **Análisis > Logs > awtrixng**.

---

## 🚀 2. Instalación en Jeedom

1. Copia la carpeta `plugin-awtrixng` en el directorio de plugins de tu servidor Jeedom:
   ```bash
   sudo cp -r plugin-awtrixng /var/www/html/plugins/awtrixng
   sudo chown -R www-data:www-data /var/www/html/plugins/awtrixng
   ```
2. En la interfaz web de Jeedom:
   * Ve a **Plugins > Gestión de Plugins**.
   * Localiza **Awtrix NG / Light** y haz clic en **Activar**.

---

## ⚙️ 3. Añadir tu Pantalla con Auto-Detección Automática de Matriz y Sensores

1. Ve a **Plugins > Comunicación > Awtrix NG** y haz clic en **Añadir Pantalla**.
2. Introduce únicamente la **Dirección IP** (o hostname `awtrixng-xxxxxx.local`) de tu panel y pulsa **Conectar y Auto-Detectar Matriz y Sensores** (o directamente **Guardar**):
   * **El tamaño de la matriz LED y el modelo vienen dados automáticamente**: El plugin interroga al panel (`/api/v1/device` y `/api/stats`) y detecta por sí solo si es un `32×8 (TC001)`, un `52×16 (TC002)` o una matriz DIY de entre `32×8` y `128×8` LEDs, seleccionándolo sin que tengas que configurarlo a mano.
   * **Detección automática de sensores instalados**: El plugin detecta qué sensores físicos tiene realmente ese panel concreto (**Batería**, **Luminosidad LDR**, **Temperatura**, **Humedad**, **Audio/TTS**) y muestra en Jeedom únicamente los comandos de los sensores presentes en ese dispositivo.
   * Muestra además un cuadro resumen con el **Firmware**, **Hostname**, **Resolución de Matriz** y **Sensores detectados**.

---

## 🎨 4. Probador en Vivo (Quick Test Studio)

Dentro de la ficha de tu pantalla en Jeedom tienes una pestaña llamada **Probador en Vivo**:
* Puedes pulsar directamente sobre las plantillas preparadas:
  * 🌸 **Regando las flores**: Texto verde con icono de flor/agua.
  * 🌡️ **Termo < 40ºC**: Alerta roja de agua caliente.
  * 🧺 **Lavadora**: Aviso azul de colada terminada.
  * 🚪 **Puerta abierta**: Alerta naranja de puerta de terraza abierta.
* Puedes escribir cualquier mensaje, elegir un color con el selector y hacer clic en **Enviar Notificación Emergente** para verlo al segundo en tu pantalla real.

---

## 💡 5. Ejemplos Prácticos en Escenarios de Jeedom

### Ejemplo 1: Al abrir la válvula de agua de la terraza (Riego)
Cuando se abra la electroválvula de riego en tu terraza, muestra un aviso verde en la pantalla:
* **Acción**: `#[Terraza][Pantalla Awtrix][Notificar (Popup)]#`
* **Título (Icono o Color)**: `2284` *(o `#00FF00`)*
* **Mensaje**: `Regando las flores (Válvula terraza abierta)`

---

### Ejemplo 2: Alerta cuando el termo de agua caliente baja de 40ºC
Si la sonda de temperatura del termo eléctrico mide menos de 40ºC, muestra una alerta roja urgente:
* **Acción**: `#[Salón][Pantalla Awtrix][Notificar (Popup)]#`
* **Título**: `#FF0000`
* **Mensaje**: `Alerta: Temperatura del termo ha bajado de 40ºC (#[Baño][Termo][Temperatura]#ºC)`

---

### Ejemplo 3: Aviso de lavadora terminada (App del Carrusel)
Añade una pantalla temporal en el carrusel de Awtrix mientras la ropa esté en el tambor:
* **Acción**: `#[Salón][Pantalla Awtrix][App Carrusel (Publicar)]#`
* **Título (Nombre de la App)**: `lavadora`
* **Mensaje**: `Lavadora lista para tender`

Cuando el sensor magnético detecte que se ha abierto la puerta de la lavadora:
* **Acción**: `#[Salón][Pantalla Awtrix][Eliminar App Carrusel]#`
* **Título**: `lavadora`

---

### Ejemplo 4: Bajar el brillo por la noche
En tu escenario nocturno:
* **Acción**: `#[Dormitorio][Pantalla Awtrix][Ajustar Brillo]#`
* **Valor**: `10` *(o `[Apagar Pantalla]` para dejarla apagada)*

---

## 📚 6. Documentación Oficial y Construcción DIY

* 🚀 **Guía de Inicio (Get Started)**: [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* 🛠️ **Cómo Construir tu Propia Pantalla Matriz LED (How to build your own matrix LED)**: [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)

