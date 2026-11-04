# Jeedom Plugin Awtrix NG (Matrix LED)

[![Jeedom Compatibility](https://img.shields.io/badge/Jeedom-v4.2%2B-24b35b.svg)](https://www.jeedom.com)
[![Version: 0.9.0 Beta](https://img.shields.io/badge/version-0.9.0%20Beta-orange.svg)](https://github.com/FranciscoJoseLeon/Jeedom-Awtrixng)
[![Awtrix NG](https://img.shields.io/badge/Awtrix%20NG-v1.2%2B-f59e0b.svg)](https://blueforcer.github.io/awtrix-ng/esp32/)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/FranciscoJoseLeon/Jeedom-Awtrixng/pulls)

**Jeedom-Awtrixng** is an advanced, native Jeedom plugin to monitor, customize, and control **Awtrix NG** and **Awtrix Light** connected LED pixel matrix clocks (including **Ulanzi TC001**, **Ulanzi TC002**, and custom **ESP32 / ESP32-S3 WS2812B** DIY panels).

---

## 🌟 Key Features

* **⚡ 100% Native HTTP REST API**: Direct, lightning-fast communication with your clock (`/api/v1/notifications`). **No MQTT broker or extra daemons required!**
* **🔍 Automatic Hardware & Sensor Detection**: Automatically queries your clock upon entering its IP to detect:
  * Exact LED matrix dimensions (`32×8`, `52×16`, or custom DIY from `32×8` to `128×8`).
  * Hardware model (`Ulanzi TC001`, `TC002`, `DIY ESP32`).
  * Available physical sensors (Battery %, LDR Ambient Light lux, Temperature, Humidity, Audio/TTS).
  * Firmware version and network hostname.
* **🖥️ Pixel-Perfect Hardware Simulator**:
  * Simulates the exact configured screen size with authentic LED grid spacing (11px cell + 1px black gap).
  * Real-time hardware control chassis: Power toggle, Brightness slider, Auto-brightness toggle, player controls, and screenshot export.
  * **"Live Mode" streaming**: Displays the real physical matrix pixels in real-time directly from `/api/v1/display/screen`.
* **🎨 Interactive Visual Icon Picker**:
  * Automatically fetches icons installed in your clock's flash memory (`/api/v1/files`).
  * Curated catalog with category filters (*All*, *On Clock*, *Home / Automation*, *Weather*, *Status / Alerts*).
  * **Automatic Resolution Filter**: Intelligently hides 16×16 HD icons on 8-row matrices to guarantee 100% visual compatibility.
* **📑 1-Click Action Commands (Zero-JSON)**:
  * Hate typing tedious, error-prone JSON like `{"icon": 909, "duration": 50, "text": "REGANDO"}`?
  * Design any alert in the live tester and click **`Guardar como Comando`** to instantly create a native Jeedom action command ready for your Dashboard, scenarios, or voice assistants!
* **🔄 Persistent Carousel Information Builder (No Scenario Loops)**:
  * Define apps rotating continuously on your clock (Power consumption, Water heater temp, Solar production, Washing machine...).
  * Live evaluation of Jeedom tags (`#[Room][Sensor]#`) refreshed automatically.
* **📋 Scenario Code Generator**:
  * Copy-ready snippet generator for your Jeedom scenarios with direct 1-click clipboard copy.
  * Supports friendly pipe syntax: `plant-watering | #00FF00 | 15s`.
* **💾 Multi-Panel Management & Backup/Restore**:
  * Manage multiple independent clocks in your home with unique IPs and screen sizes.
  * One-click configuration sync between panels (without overwriting IP or size).
  * Full JSON backup and restore.

---

## 🚀 Quick Installation

### Method 1: Direct GitHub install in Jeedom
1. In your Jeedom interface, navigate to: **Plugins -> Gestion des plugins**.
2. Click **Ajouter un plugin depuis GitHub** (or install via Jeedom Market / custom plugin upload).
3. Set the repository URL:
   ```text
   https://github.com/FranciscoJoseLeon/Jeedom-Awtrixng.git
   ```
4. Activate the plugin and go to **Plugins -> Communication -> Awtrix NG**.

### Method 2: Manual Installation via SSH / Docker
```bash
cd /var/www/html/plugins/
git clone https://github.com/FranciscoJoseLeon/Jeedom-Awtrixng.git awtrixng
chown -R www-data:www-data awtrixng/
```

---

## 💡 How to Use in Jeedom Scenarios (No JSON Required!)

When building automation scenarios in Jeedom, select the action:
```text
#[Living Room][Salon Awtrix NG][Notificar (Popup)]#
```

### 1. Title Field (Icon & Formatting Options)
* **Simple**: Enter just the icon name (e.g., `plant-watering`, `thermometer2`, or leave blank for default).
* **Advanced (Pipe syntax)**: `icon | text_color | duration_seconds`
  ```text
  plant-watering | #00FF00 | 15
  ```

### 2. Message Field (Plain Text & Jeedom Tags)
Type plain text with any Jeedom tags. They are automatically evaluated before sending:
```text
Terrace watering activated. Tank temperature: #[Rooftop][Water Heater]# °C (Grid: #2997# W)
```

> **Backward Compatibility**: If you have legacy MQTT scenarios passing raw JSON (`{"icon": 909, "text": "ALERT"}`), the plugin detects and parses it automatically!

---

## 🛠️ Supported Hardware

| Hardware | Matrix Resolution | Icon Support | Sensors & Features |
| :--- | :---: | :---: | :--- |
| **Ulanzi TC001** | `32×8` (256 LEDs) | 8×8 Standard | Battery, Lux (LDR), Temp, Humidity, RTTTL Buzzer |
| **Ulanzi TC002** | `52×16` (832 LEDs) | 16×16 HD & 8×8 | Battery, Lux, Temp, Humidity, MP3 Audio, TTS Speech |
| **DIY ESP32 / ESP32-S3** | `32×8` to `128×8` | 8×8 Standard | WS2812B addressable LEDs, configurable width |

* **Official Awtrix NG Documentation**: [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* **Official DIY Build Guide**: [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)
* **Icon Gallery**: [https://awtrix.de/icons/](https://awtrix.de/icons/)

---

## 🌍 Languages

* 🇬🇧 **English** (en_US)
* 🇪🇸 **Español** (es_ES)
* 🇫🇷 **Français** (fr_FR)

---

## ⚠️ Disclaimer / Avertissement / Exención de Responsabilidad

* **Disclaimer (EN)**: This plugin is provided "AS IS", without warranty of any kind, express or implied. In no event shall the author be liable for any claim, damages, data loss, hardware malfunctions, or other liability arising from the use of this software. This plugin is an independent community development and is not officially affiliated with, maintained by, or endorsed by Jeedom SAS, Ulanzi, or the Awtrix NG project.
* **Avertissement (FR)**: Ce plugin est fourni « tel quel », sans garantie d'aucune sorte, expresse ou implicite. En aucun cas l'auteur ne pourra être tenu responsable des réclamations, dommages directs ou indirects, pertes de données ou dysfonctionnements matériels découlant de l'utilisation de ce logiciel. Ce plugin est un développement communautaire indépendant et n'est pas affilié, maintenu ni approuvé par Jeedom SAS, Ulanzi ou le projet Awtrix NG.
* **Exención de Responsabilidad (ES)**: Este plugin se proporciona «tal cual», sin garantía de ningún tipo, expresa o implícita. En ningún caso el autor será responsable de reclamaciones, daños directos o indirectos, pérdidas de datos o averías de hardware derivadas del uso de este software. Este plugin es un desarrollo comunitario independiente y no está afiliado ni respaldado oficialmente por Jeedom SAS, Ulanzi o el proyecto Awtrix NG.

---

## 📄 License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)** - see the [LICENSE](LICENSE) file for details.

Developed with ❤️ by **Francisco José León** ([@FranciscoJoseLeon](https://github.com/FranciscoJoseLeon)).
