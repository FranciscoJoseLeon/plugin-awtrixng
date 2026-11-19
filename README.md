# Awtrix NG (Matrix LED) — Plugin Jeedom

[![Jeedom Compatibility](https://img.shields.io/badge/Jeedom-v4.2%2B-24b35b.svg)](https://www.jeedom.com)
[![Version](https://img.shields.io/badge/version-0.9.0-orange.svg)](https://github.com/FranciscoJoseLeon/plugin-awtrixng)
[![Awtrix NG](https://img.shields.io/badge/Awtrix%20NG-v1.2%2B-f59e0b.svg)](https://blueforcer.github.io/awtrix-ng/esp32/)
[![Languages](https://img.shields.io/badge/lang-fr__FR%20%7C%20en__US%20%7C%20es__ES-informational.svg)](#readme)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)
[![Ko-fi](https://img.shields.io/badge/Ko--fi-Support_My_Work-FF5E5B?style=flat&logo=ko-fi&logoColor=white)](https://ko-fi.com/fjleon)

🌐 **Languages / Langues / Idiomas :** [🇫🇷 Français](#-français) • [🇬🇧 English](#-english) • [🇪🇸 Español](#-español)

📚 **Documentation complète / Full Documentation / Documentación completa :**
- 🇫🇷 [Documentation (fr_FR)](docs/fr_FR/index.md) • [Changelog (fr_FR)](docs/fr_FR/changelog.md)
- 🇬🇧 [Documentation (en_US)](docs/en_US/index.md) • [Changelog (en_US)](docs/en_US/changelog.md)
- 🇪🇸 [Documentación (es_ES)](docs/es_ES/index.md) • [Changelog (es_ES)](docs/es_ES/changelog.md)

---

## 🇫🇷 Français

**plugin-awtrixng** est une intégration native pour **Jeedom v4.2+** dédiée aux écrans matriciels LED **Awtrix Light** et **Awtrix NG** (**Ulanzi TC001**, **Ulanzi TC002** et matrices DIY **ESP32 / ESP32-S3 WS2812B**). Il pilote vos horloges directement via leur **API HTTP REST** (sans broker MQTT ni démon externe).

### 🌟 Fonctionnalités principales
- **API HTTP REST 100 % native** : communication directe avec l'horloge (`/api/v1/notifications`).
- **Détection matérielle & capteurs automatique** : saisissez uniquement l'IP (ou `awtrixng-xxxxxx.local`) ; le plugin détecte la résolution (`32×8`, `52×16` ou DIY `32×8` à `128×8`), le modèle, le firmware et les capteurs présents (Batterie, Luminosité LDR, Température, Humidité, Audio/TTS).
- **Widget Dashboard dédié** : chaque appareil dispose de son propre widget avec marche/arrêt, curseur de luminosité, capteurs en direct, app suivante/précédente, arrêt des notifications et envoi rapide de messages/sons.
- **Simulateur matériel & Mode Live** : aperçu pixel par pixel de l'écran et streaming temps réel (`/api/v1/display/screen`).
- **Sélecteur visuel d'icônes, couleurs et sons** : listes déroulantes et navigateur d'icônes avec filtrage automatique selon la résolution (`8×8` vs `16×16` HD).
- **Commandes en 1 clic (zéro JSON)** : créez des commandes Jeedom natives depuis le **Testeur en direct** et utilisez la syntaxe simplifiée `icone | #couleur | durée` dans vos scénarios (rétrocompatibilité JSON conservée).
- **Gestion multi-panneaux, Carrousel & Sauvegarde** : informations indépendantes par panneau ou clonage en 1 clic entre panneaux (en conservant IP et taille), sauvegarde/restauration `.json` complète et logs détaillés (`awtrixng`).

### 📺 Matériel pris en charge
| Matériel | Résolution | Icônes | Capteurs & Audio |
| :--- | :---: | :---: | :--- |
| **Ulanzi TC001** | `32×8` (256 LED) | 8×8 | Batterie, LDR, Température, Humidité, Buzzer RTTTL |
| **Ulanzi TC002** | `52×16` (832 LED) | 16×16 HD & 8×8 | Batterie, LDR, Température, Humidité, MP3, Web Radio, TTS |
| **Matrice DIY ESP32 / ESP32-S3** | `32×8` à `128×8` | 8×8 | LED WS2812B, largeur configurable (32 à 128 px) |

### 🚀 Installation dans Jeedom
1. **Depuis GitHub** : **Plugins → Gestion des plugins → Ajouter un plugin depuis GitHub** :
   - **ID logique** : `awtrixng` | **Utilisateur** : `FranciscoJoseLeon` | **Dépôt** : `plugin-awtrixng` | **Branche** : `main` (ou `beta`).
2. **Manuelle (SSH/Docker)** :
   ```bash
   cd /var/www/html/plugins/
   git clone https://github.com/FranciscoJoseLeon/plugin-awtrixng.git awtrixng
   chown -R www-data:www-data awtrixng/
   ```
3. Activez le plugin et allez dans **Plugins → Communication → Awtrix NG**.

### 💡 Exemples d'utilisation dans les scénarios Jeedom
- **Notification simple ou avancée** (`#[Salon][Awtrix NG][Notificar (Popup)]#`) :
  - **Titre** : `plant-watering | #00FF00 | 15` *(ou simplement un ID/nom d'icône ou `#FF0000`)*
  - **Message** : `Arrosage activé. Chauffe-eau : #[Salle de bain][Chauffe-eau][Température]# °C`
- **App du carrousel** (`Custom App (Publicar)`) : Titre `lave_linge`, Message `Cycle terminé` (puis `Eliminar App Carrusel` avec le titre `lave_linge` à l'ouverture).
- **Mode nuit** : `[Définir la luminosité]` à `10` (ou `[Éteindre l'écran]`).

### ⚖️ Avertissement
Fourni « tel quel », sans garantie, sous licence **AGPL-3.0**. Projet communautaire indépendant, non affilié à Jeedom SAS, Ulanzi ou Awtrix NG.

---

## 🇬🇧 English

**plugin-awtrixng** is a native **Jeedom v4.2+** integration for **Awtrix Light** and **Awtrix NG** LED pixel matrix displays (**Ulanzi TC001**, **Ulanzi TC002**, and DIY **ESP32 / ESP32-S3 WS2812B** panels). It communicates directly over **HTTP REST API** with **no external MQTT broker or daemon required**.

### 🌟 Key Features
- **100% Native HTTP REST API**: Direct, fast communication with your clock (`/api/v1/notifications`).
- **Automatic Hardware & Sensor Detection**: Enter only the IP (or `awtrixng-xxxxxx.local`); the plugin auto-detects matrix size (`32×8`, `52×16`, or DIY `32×8` to `128×8`), hardware model, firmware, and physical sensors (Battery, LDR Light, Temperature, Humidity, Audio/TTS).
- **Dedicated Dashboard Widget**: Each Awtrix device gets its own widget with power switch, brightness slider, live sensor readings, next/previous app, notification stop, and quick alert/sound sender.
- **Pixel-Perfect Live Simulator**: Authentic LED grid preview and real-time *Live Mode* (`/api/v1/display/screen`).
- **Visual Icon, Color & Sound Pickers**: Curated dropdowns and icon browser with automatic resolution filtering (`8×8` vs `16×16` HD).
- **1-Click Action Commands (Zero-JSON)**: Create native Jeedom commands straight from the **Live Quick Test Studio** and use pipe syntax `icon | #color | duration` in scenarios (legacy raw JSON still supported).
- **Multi-Panel Management, Carousel & Backup/Restore**: Independent carousels per panel or 1-click config cloning across panels (preserving each panel's IP and size), full `.json` backup/restore, and integrated Jeedom logs (`awtrixng`).

### 📺 Supported Hardware
| Hardware | Resolution | Icons | Sensors & Audio |
| :--- | :---: | :---: | :--- |
| **Ulanzi TC001** | `32×8` (256 LEDs) | 8×8 | Battery, LDR Lux, Temperature, Humidity, RTTTL Buzzer |
| **Ulanzi TC002** | `52×16` (832 LEDs) | 16×16 HD & 8×8 | Battery, LDR Lux, Temperature, Humidity, MP3, Web Radio, TTS |
| **DIY ESP32 / ESP32-S3** | `32×8` to `128×8` | 8×8 | WS2812B LEDs, configurable width (32 to 128 px) |

### 🚀 Installation in Jeedom
1. **From GitHub**: **Plugins → Plugin Management → Add plugin from GitHub**:
   - **Logical ID**: `awtrixng` | **User**: `FranciscoJoseLeon` | **Repository**: `plugin-awtrixng` | **Branch**: `main` (or `beta`).
2. **Manual (SSH/Docker)**:
   ```bash
   cd /var/www/html/plugins/
   git clone https://github.com/FranciscoJoseLeon/plugin-awtrixng.git awtrixng
   chown -R www-data:www-data awtrixng/
   ```
3. Activate the plugin and open **Plugins → Communication → Awtrix NG**.

### 💡 Practical Jeedom Scenario Examples
- **Popup Notification** (`#[Living Room][Awtrix NG][Notificar (Popup)]#`):
  - **Title**: `plant-watering | #00FF00 | 15` *(or just an icon name/ID or `#FF0000`)*
  - **Message**: `Terrace watering active. Water heater: #[Bathroom][Heater][Temperature]# °C`
- **Carousel App** (`Custom App (Publish)`): Title `laundry`, Message `Washing machine finished` (and `Delete Custom App` with Title `laundry` when emptied).
- **Bedtime Dimming**: `[Set Brightness]` to `10` (or `[Power Off]`).

### ⚖️ Disclaimer
Provided "AS IS" without warranty under **AGPL-3.0**. Independent community project, not affiliated with Jeedom SAS, Ulanzi, or Awtrix NG.

---

## 🇪🇸 Español

**plugin-awtrixng** es una integración nativa para **Jeedom v4.2+** diseñada para pantallas matriciales LED **Awtrix Light** y **Awtrix NG** (**Ulanzi TC001**, **Ulanzi TC002** y matrices DIY con **ESP32 / ESP32-S3 WS2812B**). Controla tus paneles directamente mediante su **API HTTP REST** (**sin broker MQTT externo ni demonios adicionales**).

### 🌟 Características principales
- **API HTTP REST 100 % nativa**: comunicación directa con tu pantalla (`/api/v1/notifications`).
- **Auto-detección automática de matriz y sensores**: introduce solo la IP (o `awtrixng-xxxxxx.local`) y el plugin detecta resolución (`32×8`, `52×16` o DIY de `32×8` a `128×8`), modelo, firmware y sensores reales (**Batería**, **Luminosidad LDR**, **Temperatura**, **Humedad**, **Audio/TTS**).
- **Widget dedicado en el Dashboard**: cada panel Awtrix tiene su propio widget con encendido/apagado, control de brillo, sensores en vivo, app siguiente/anterior, parada de notificaciones y envío rápido de avisos o sonidos.
- **Simulador hardware en vivo**: vista previa píxel a píxel y modo *Live* en tiempo real (`/api/v1/display/screen`).
- **Selector visual de iconos, colores y sonidos**: desplegables y galería visual con filtro automático según la resolución (`8×8` frente a `16×16` HD).
- **Comandos en 1 clic (sin JSON)**: crea comandos nativos desde el **Probador en Vivo** y usa la sintaxis simple `icono | #color | duración` en escenarios (con retrocompatibilidad total para JSON clásico).
- **Gestión multi-panel, Carrusel y Backup/Restauración**: información independiente por panel o copia en 1 clic entre paneles (conservando la IP y el tamaño de cada uno), exportación/restauración `.json` completa y registro integrado en logs de Jeedom (`awtrixng`).

### 📺 Hardware compatible
| Hardware | Resolución | Iconos | Sensores y Audio |
| :--- | :---: | :---: | :--- |
| **Ulanzi TC001** | `32×8` (256 LEDs) | 8×8 | Batería, Luz LDR, Temperatura, Humedad, Zumbador RTTTL |
| **Ulanzi TC002** | `52×16` (832 LEDs) | 16×16 HD y 8×8 | Batería, Luz LDR, Temperatura, Humedad, MP3, Radio Web, TTS |
| **Matriz DIY ESP32 / ESP32-S3** | `32×8` a `128×8` | 8×8 | LEDs WS2812B, ancho configurable (32 a 128 px) |

### 🚀 Instalación en Jeedom
1. **Desde GitHub**: **Plugins → Gestión de plugins → Añadir un plugin desde GitHub**:
   - **ID lógico**: `awtrixng` | **Usuario**: `FranciscoJoseLeon` | **Repositorio**: `plugin-awtrixng` | **Rama**: `main` (o `beta`).
2. **Manual (SSH/Docker)**:
   ```bash
   cd /var/www/html/plugins/
   git clone https://github.com/FranciscoJoseLeon/plugin-awtrixng.git awtrixng
   chown -R www-data:www-data awtrixng/
   ```
3. Activa el plugin y accede a **Plugins → Comunicación → Awtrix NG**.

### 💡 Ejemplos prácticos en escenarios de Jeedom
- **Notificación emergente** (`#[Salón][Pantalla Awtrix][Notificar (Popup)]#`):
  - **Título**: `plant-watering | #00FF00 | 15` *(o solo el nombre/ID de icono o `#FF0000`)*
  - **Mensaje**: `Riego activado. Termo: #[Baño][Termo][Temperatura]# °C`
- **App del carrusel** (`App Carrusel (Publicar)`): Título `lavadora`, Mensaje `Lavadora lista para tender` (y `Eliminar App Carrusel` con Título `lavadora` al abrir la puerta).
- **Modo nocturno**: `[Ajustar Brillo]` a `10` (o `[Apagar Pantalla]`).

### ⚖️ Exención de responsabilidad
Se entrega «tal cual», sin garantía, bajo licencia **AGPL-3.0**. Proyecto comunitario independiente, sin afiliación oficial con Jeedom SAS, Ulanzi ni Awtrix NG.

---

📚 **Official Awtrix NG Resources**: [Get Started](https://blueforcer.github.io/awtrix-ng/esp32/) • [DIY Build Guide](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/) • [Icon Gallery](https://awtrix.de/icons/)

Developed by **Francisco José León** ([@FranciscoJoseLeon](https://github.com/FranciscoJoseLeon))
