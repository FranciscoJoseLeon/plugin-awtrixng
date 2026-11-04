# Awtrix NG / Light Plugin for Jeedom

Native integration for **Awtrix Light** and **Awtrix NG** LED pixel matrix smart displays (such as the popular **Ulanzi TC001**, the high-definition **Ulanzi TC002**, and custom ESP32 WS2812B builds).

Control your display directly from Jeedom using its built-in **HTTP REST API**, featuring popup notifications, carousel custom applications, brightness adjustment, battery/ambient light sensors, and full customization of **text colors and icons** tailored for each resolution.

---

## 1. Supported Devices & Resolutions

The plugin supports both Awtrix NG hardware architectures:

| Device | Matrix Resolution | Icon Resolution | Key Features |
| :--- | :---: | :---: | :--- |
| **32×8 (TC001 & DIY)** | **32 × 8 LEDs** | **8 × 8 pixels** | Ulanzi TC001 and DIY WS2812B displays. Battery telemetry, ambient light LDR sensor, and RTTTL buzzer. |
| **52×16 (TC002)** | **52 × 16 LEDs** | **16 × 16 (HD) & 8 × 8** | Ulanzi TC002. Built-in speaker for MP3 sound, Web radio, melodies, Text-To-Speech (TTS), and high-resolution layouts. |

Inside the device configuration in Jeedom, **real product photos** of both clocks are displayed so you can instantly recognize and select your hardware model.


---

## 2. Color Icons & Text Color Customization

### Color Icons Tailored to Screen Resolution
* **For 32×8 screens (TC001 / DIY)**: Uses **8×8 pixel** color icons (thousands available on the public LaMetric or Awtrix icon library). Simply provide the icon ID (e.g. `2284` for plants/watering, `1425` for temperature).
* **For 52×16 screens (TC002)**: Supports both 8×8 icons and new high-definition **16×16 pixel** icons with rich colors.

### Text Color Customization
Change the display text color at any time:
* Using standard Hexadecimal codes: `#00FF00` (green), `#FF0000` (red), `#00BFFF` (cyan/blue), `#FFA500` (orange), `#FFD700` (yellow).
* Or by typing common color names in the Title field: `green`, `red`, `blue`, `yellow`, `orange`.

---

## 3. Practical Home Automation Examples in Jeedom

### Example 1: Terrace Water Valve Opened (Watering Flowers)
When the garden or terrace irrigation valve opens, trigger a green notification with a flower/water icon:
* **Action**: `#[Terrace][Awtrix Display][Notify (Popup)]#`
* **Title (Icon or Color)**: `2284` *(or `#00FF00`)*
* **Message**: `Watering flowers (Terrace water valve open)`

---

### Example 2: Water Heater Drops Below 40°C Alert
When the water heater tank temperature sensor falls below 40°C, display an urgent red notification with a warning sound:
* **Action**: `#[Living Room][Awtrix Display][Notify (Popup)]#`
* **Title**: `#FF0000`
* **Message**: `Alert: Water heater temperature dropped below 40°C (#[Bathroom][Heater][Temperature]#°C)`

---

### Example 3: Washing Machine Cycle Finished (Carousel App)
Add a persistent card to the Awtrix carousel while clean laundry is waiting in the machine:
* **Action**: `#[Living Room][Awtrix Display][Custom App (Publish)]#`
* **Title (App Name)**: `laundry`
* **Message**: `Washing machine cycle finished`

When the laundry door is opened, remove the app from the carousel:
* **Action**: `#[Living Room][Awtrix Display][Delete Custom App]#`
* **Title**: `laundry`

---

### Example 4: Automatic Night-time Dimming
In your bedtime scenario:
* **Action**: `#[Bedroom][Awtrix Display][Set Brightness]#`
* **Value**: `10` *(or execute `[Power Off]` for complete darkness)*

---

## 4. Live Quick Test Studio
Inside your device configuration tab in Jeedom, use the **Live Quick Test Studio**:
* Test ready-to-use presets (*Watering flowers*, *Water heater < 40°C*, *Laundry*, *Open door*).
* Experiment with text colors using the interactive HTML5 color picker.
* Click **Send Popup Notification** to immediately preview the result on your physical clock.

---

## 5. Official Documentation & Build Guides

To configure your clock's firmware or build your own custom DIY LED pixel matrix, consult the official Awtrix NG project documentation:
* 🚀 **Get Started**: [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* 🛠️ **How to build your own matrix LED**: [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)

