# Complete Installation & User Guide: Awtrix NG Plugin for Jeedom

Native integration for **Awtrix Light** and **Awtrix NG** LED pixel matrix smart displays (including the **Ulanzi TC001**, the high-definition **Ulanzi TC002**, and custom ESP32 WS2812B matrix projects).

Allows direct control from Jeedom using its **HTTP REST API** (no external MQTT broker needed), featuring popup notifications, carousel custom applications, brightness adjustment, battery/ambient light sensors, and full customization of **text colors and icons** adapted to each display resolution.

---

## 📺 1. Multi-Panel Management: Independent IPs, Sizes & Per-Panel or Cloned Information

The plugin manages **multiple Awtrix panels simultaneously**, each with its own **IP address** and **matrix size**:

1. **32×8 (TC001 & DIY)**:
   * **Matrix Resolution**: 32 columns × 8 rows of RGB LEDs.
   * **Color Icons**: **8×8 pixel** icons (thousands available via the LaMetric and Awtrix public icon repositories).
   * **Hardware**: Ulanzi TC001 and WS2812B matrices. Portable battery telemetry, ambient light LDR sensor, and RTTTL buzzer.

2. **52×16 (TC002)**:
   * **Matrix Resolution**: 52 columns × 16 rows of RGB LEDs.
   * **Color Icons**: Supports both 8×8 icons and new high-definition **16×16 pixel** icons.
   * **Hardware**: Ulanzi TC002. Built-in speaker for MP3 sound playback, web radio, melodies, Text-To-Speech (TTS), and audio volume controls.

3. **Awtrix NG Compatible DIY LED Matrices (32×8 up to 128×8 LEDs)**:
   * Following official Awtrix NG hardware specifications for ESP32 / ESP32-S3 DIY builds, supports **8-row height** matrices with configurable widths from **32 to 128 pixels** (e.g. `32×8`, `64×8`, `96×8`, `128×8`).

### 🔄 Different Information per Panel or Cloned Across Panels
* **Independent per-panel information**: In the **"Panel Information (Carousel)"** tab of each display, configure which Jeedom sensors (`#[Object][Equipment][Command]#`) or messages rotate on that specific panel.
* **One-click Configuration Copy between Panels**: Use the **"Copy Config Between Panels"** button to copy carousel screens and visual settings from one panel to another (or to all panels at once) while **preserving each target panel's own IP address and matrix size**.

---

## 🚀 2. Installation in Jeedom

1. Copy the `plugin-awtrixng` folder into your Jeedom server's plugin directory:
   ```bash
   sudo cp -r plugin-awtrixng /var/www/html/plugins/awtrixng
   sudo chown -R www-data:www-data /var/www/html/plugins/awtrixng
   ```
2. In the Jeedom web interface:
   * Navigate to **Plugins > Plugin Management**.
   * Locate **Awtrix NG / Light** and click **Activate**.

---

## ⚙️ 3. Adding and Configuring Your Screen

1. Navigate to **Plugins > Communication > Awtrix NG**.
2. Click **Add Screen**.
3. In the device settings tab:
   * **Model**: Choose either **32×8 (TC001 & DIY)** or **52×16 (TC002)**.
   * **IP Address**: Enter your Awtrix screen's local IP address (e.g. `192.168.1.150`).
   * **Text Color**: Select your default text color using the visual color picker.
   * Click **Test Connection**: Jeedom verifies communication and reports current battery and light level.
   * Click **Save**: All telemetry sensors and control commands will be created automatically.

---

## 🎨 4. Live Quick Test Studio

Inside your screen's device tab in Jeedom, click on the **Live Quick Test Studio** tab:
* Quickly test domestic presets:
  * 🌸 **Watering flowers**: Green text with plant/water icon.
  * 🌡️ **Water heater < 40°C**: Urgent red hot water warning.
  * 🧺 **Laundry finished**: Blue washing machine alert.
  * 🚪 **Door open**: Orange terrace door alert.
* Type any custom text, pick a color with the picker, and click **Send Popup Notification** to immediately see it live on your clock.

---

## 💡 5. Practical Home Automation Examples in Jeedom Scenarios

### Example 1: Terrace Water Valve Opened (Watering Flowers)
When the irrigation valve opens on the terrace, show a green alert on the display:
* **Action**: `#[Terrace][Awtrix Display][Notify (Popup)]#`
* **Title (Icon or Color)**: `2284` *(or `#00FF00`)*
* **Message**: `Watering flowers (Terrace water valve open)`

---

### Example 2: Water Heater Drops Below 40°C
When the water heater tank sensor drops below 40°C, display an urgent red notification:
* **Action**: `#[Living Room][Awtrix Display][Notify (Popup)]#`
* **Title**: `#FF0000`
* **Message**: `Alert: Water heater temperature dropped below 40°C (#[Bathroom][Heater][Temperature]#°C)`

---

### Example 3: Washing Machine Cycle Finished (Carousel App)
Add a persistent card to the carousel while clean clothes are in the drum:
* **Action**: `#[Living Room][Awtrix Display][Custom App (Publish)]#`
* **Title (App Name)**: `laundry`
* **Message**: `Washing machine cycle finished`

When the laundry door is opened:
* **Action**: `#[Living Room][Awtrix Display][Delete Custom App]#`
* **Title**: `laundry`

---

### Example 4: Automatic Bedtime Dimming
In your bedtime scenario:
* **Action**: `#[Bedroom][Awtrix Display][Set Brightness]#`
* **Value**: `10` *(or execute `[Power Off]` for total darkness)*

---

## 📚 6. Official Project Documentation & Build Guides

* 🚀 **Get Started**: [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* 🛠️ **How to build your own matrix LED**: [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)

