# Registro de Cambios (Changelog) - Plugin Awtrix NG / Light

>**IMPORTANTE**
>
>Si no hay información específica sobre una actualización, se debe únicamente a mejoras de documentación, traducciones o textos menores.

# Versión 0.9.0 (04/11/2026) - Versión Pre-Lanzamiento (Beta / RC)

- Versión preliminar para pruebas y validación comunitaria del plugin Awtrix NG para Jeedom v4.
- Compatibilidad nativa con pantallas matriciales LED 32×8 (Ulanzi TC001 y matrices DIY WS2812B), 52×16 (Ulanzi TC002) y DIY configurables (32×8 a 128×8).
- Detección automática de hardware, resolución y sensores físicos al conectar a la IP (Batería, LDR Lux, Temperatura, Humedad, Audio/TTS).
- Simulador de pantalla 1:1 adaptado estrictamente al tamaño configurado, con chasis interactivo y modo streaming "En Vivo" (/api/v1/display/screen).
- Catálogo visual interactivo de iconos con filtro automático de resolución (oculta iconos 16×16 HD en pantallas de 8 líneas).
- Gestión simplificada de notificaciones (Zero-JSON): sintaxis limpia para escenarios y botón "Guardar como Comando" para crear botones de acción con 1 clic.
- Carrusel continuo de información para rotar datos de sensores Jeedom sin necesidad de bucles en escenarios.
- Herramienta de copia de configuración entre pantallas y sistema de copias de seguridad (Backup / Restore) en formato JSON.
- Comunicación 100% nativa por HTTP REST API (sin necesidad de broker MQTT).
- Documentación y traducción completa en español, inglés y francés.
