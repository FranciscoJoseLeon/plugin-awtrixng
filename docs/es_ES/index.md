# Plugin Awtrix NG / Light para Jeedom

Integración nativa para pantallas matriciales LED inteligentes **Awtrix Light** y **Awtrix NG** (como el popular reloj **Ulanzi TC001**, el nuevo **Ulanzi TC002** o matrices ESP32 caseras con WS2812B).

Controla tu pantalla directamente desde Jeedom a través de su **API HTTP REST**, con soporte para notificaciones emergentes, carrusel de aplicaciones, ajuste de brillo, sensores de batería/luz y personalización completa de **colores de texto e iconos** adaptados a cada resolución.

---

## 1. Gestión Multi-Panel: Distintas IPs, Distintos Tamaños e Información Independiente o Copiada

El plugin permite gestionar **múltiples paneles simultáneamente**, cada uno con su propia **dirección IP** y su propio **tamaño de pantalla**:

| Dispositivo / Tamaño | Resolución Matriz | Resolución Iconos | Características Especiales |
| :--- | :---: | :---: | :--- |
| **32×8 (TC001 & DIY)** | **32 × 8 LEDs** | **8 × 8 píxeles** | Ulanzi TC001 y matrices WS2812B. Telemetría de batería portátil, sensor de luz LDR y zumbador RTTTL. |
| **52×16 (TC002)** | **52 × 16 LEDs** | **16 × 16 (HD) y 8 × 8** | Ulanzi TC002. Altavoz para sonido MP3, Web radio, melodías, síntesis de voz (TTS) y alta resolución. |
| **Matriz DIY Awtrix NG (32×8 a 128×8)** | **32 × 8 hasta 128 × 8 LEDs** *(ej. 64×8, 96×8, 128×8)* | **8 × 8 píxeles** | Matrices WS2812B con ESP32/ESP32-S3 compatibles con Awtrix NG (alto fijo de 8 filas y ancho de 32 a 128 columnas). |

* **Información distinta en cada panel**: En la pestaña **Información de este Panel (Carrusel)** puedes configurar qué sensores de Jeedom (`#[Objeto][Equipo][Comando]#`) o mensajes muestra ese panel concreto.
* **Copiar configuración de un panel a otro**: Con el botón **Copiar Config entre Paneles**, puedes copiar la configuración y las pantallas de información de un panel a otro (o a todos los paneles a la vez) si quieres que muestren lo mismo, **sin sobrescribir nunca la dirección IP ni el tamaño propio de cada panel**.


---

## 2. Iconos en Color y Personalización del Texto

### Iconos en Color según la Resolución
* **Para pantallas 32×8 (TC001 / DIY)**: Utiliza iconos en color de **8×8 píxeles** (los miles de iconos de la biblioteca pública de LaMetric o Awtrix). Solo necesitas su número ID (ej. `2284` para flores/riego, `1425` para termómetro).
* **Para pantallas 52×16 (TC002)**: Admite tanto iconos estándar 8×8 como nuevos iconos de alta definición de **16×16 píxeles** con gran detalle de color.

### Colores de Texto
Puedes cambiar el color del texto en cualquier momento de forma muy sencilla:
* Con código Hexadecimal: `#00FF00` (verde), `#FF0000` (rojo), `#00BFFF` (azul), `#FFA500` (naranja), `#FFD700` (amarillo).
* O escribiendo el nombre del color en el campo Título: `verde`, `rojo`, `azul`, `amarillo`, `naranja`.

---

## 3. Ejemplos Prácticos en Escenarios de Jeedom

### Ejemplo 1: Al abrir la válvula de agua de la terraza (Riego)
Cuando se abra la electroválvula de riego de la terraza, envía una notificación verde con icono de flor/agua:
* **Acción**: `#[Terraza][Pantalla Awtrix][Notificar (Popup)]#`
* **Título (Icono o Color)**: `2284` *(o `#00FF00`)*
* **Mensaje**: `Regando las flores (Válvula terraza abierta)`

---

### Ejemplo 2: Alerta cuando el termo de agua caliente baja de 40ºC
Si la sonda de temperatura del termo eléctrico mide menos de 40ºC, muestra una alerta roja urgente con pitido:
* **Acción**: `#[Salón][Pantalla Awtrix][Notificar (Popup)]#`
* **Título**: `#FF0000`
* **Mensaje**: `Alerta: Temperatura del termo ha bajado de 40ºC (#[Baño][Termo][Temperatura]#ºC)`

---

### Ejemplo 3: Aviso de lavadora terminada (App del carrusel)
Añade una pantalla temporal en el carrusel de Awtrix mientras la ropa esté en el tambor:
* **Acción**: `#[Salón][Pantalla Awtrix][App Carrusel (Publicar)]#`
* **Título (Nombre de la App)**: `lavadora`
* **Mensaje**: `Lavadora lista para tender`

Cuando se abra la puerta de la lavadora, retira la app del carrusel:
* **Acción**: `#[Salón][Pantalla Awtrix][Eliminar App Carrusel]#`
* **Título**: `lavadora`

---

### Ejemplo 4: Regulación automática del brillo por la noche
Al activarse el escenario de buenas noches:
* **Acción**: `#[Dormitorio][Pantalla Awtrix][Ajustar Brillo]#`
* **Valor**: `10` *(o `[Apagar Pantalla]` si prefieres oscuridad total)*

---

## 4. Probador en Vivo (Quick Test Studio)
Dentro de la pestaña del equipo en Jeedom encontrarás el **Probador en Vivo**:
* Puedes elegir plantillas directas (*Regando las flores*, *Termo < 40ºC*, *Lavadora*, *Puerta abierta*).
* Probar cualquier color con el selector cromático interactivo.
* Pulsar en **Enviar Notificación** para ver el resultado de inmediato en tu pantalla física.

---

## 5. Documentación Oficial y Guías de Construcción

Para configurar el firmware de tu reloj o fabricar tu propia matriz LED personalizada, consulta la documentación oficial del proyecto Awtrix NG:
* 🚀 **Guía de Inicio Rápido (Get Started)**: [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* 🛠️ **Cómo Construir tu Propia Pantalla Matriz LED (How to build your own matrix LED)**: [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)

---

## 6. Aviso Legal y Limitación de Responsabilidad

Este plugin es un desarrollo comunitario independiente de código abierto distribuido bajo licencia **GNU AGPL v3**.

* **Sin garantía («Tal cual / As Is»)**: El software se proporciona sin garantía de ningún tipo, explícita o implícita, incluyendo, sin limitación, garantías de comerciabilidad o idoneidad para un propósito determinado.
* **Exención de responsabilidad**: El autor declina toda responsabilidad ante cualquier daño directo, indirecto, material o inmaterial derivado del uso o imposibilidad de uso de este software (pérdida de datos, caídas del sistema Jeedom, problemas de red o daños en las pantallas matriciales conectadas).
* **Marcas registradas**: Este proyecto no está patrocinado, afiliado ni respaldado oficialmente por la empresa **Jeedom SAS**, la marca **Ulanzi**, ni por los desarrolladores del firmware **Awtrix NG**. Todas las marcas registradas pertenecen a sus respectivos propietarios.


