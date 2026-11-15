# Awtrix NG (Matrix LED) — Plugin Jeedom

[![Jeedom Compatibility](https://img.shields.io/badge/Jeedom-v4.2%2B-24b35b.svg)](https://www.jeedom.com)
[![Version](https://img.shields.io/badge/version-0.9.0-orange.svg)](https://github.com/FranciscoJoseLeon/plugin-awtrixng)
[![Awtrix NG](https://img.shields.io/badge/Awtrix%20NG-v1.2%2B-f59e0b.svg)](https://blueforcer.github.io/awtrix-ng/esp32/)
[![Languages](https://img.shields.io/badge/lang-fr__FR%20%7C%20en__US%20%7C%20es__ES-informational.svg)](#readme)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPLv3-blue.svg)](LICENSE)

🌐 **Languages / Langues / Idiomas :** [🇫🇷 Français](#-français) • [🇬🇧 English](#-english) • [🇪🇸 Español](#-español)

📚 **Documentation complète / Full Documentation / Documentación completa :**
- 🇫🇷 [Documentation (fr_FR)](docs/fr_FR/index.md) • [Changelog (fr_FR)](docs/fr_FR/changelog.md)
- 🇬🇧 [Documentation (en_US)](docs/en_US/index.md) • [Changelog (en_US)](docs/en_US/changelog.md)
- 🇪🇸 [Documentación (es_ES)](docs/es_ES/index.md) • [Changelog (es_ES)](docs/es_ES/changelog.md)

---

## 🇫🇷 Français

**plugin-awtrixng** est un plugin natif pour **Jeedom v4.2+** permettant de superviser, personnaliser et piloter vos horloges matricielles LED **Awtrix NG** et **Awtrix Light** (**Ulanzi TC001**, **Ulanzi TC002** et panneaux DIY **ESP32 / ESP32-S3 WS2812B**).

### 🌟 Fonctionnalités principales

- **API HTTP REST 100 % native** : Communication directe avec votre horloge (`/api/v1/notifications`), sans broker MQTT ni démon externe requis.
- **Détection matérielle automatique** : Détecte automatiquement la résolution (`32×8`, `52×16` ou DIY `32×8` à `128×8`), le modèle, la version du firmware et les capteurs (Batterie, Luminosité LDR, Température, Humidité, Buzzer/Audio).
- **Widget Dashboard dédié** : Chaque appareil Awtrix dispose de son propre widget visuel avec interrupteur d'alimentation, curseur de luminosité, capteurs en direct, app suivante/précédente, arrêt des notifications et envoi rapide de notifications ou sons.
- **Simulateur matériel en direct** : Aperçu pixel par pixel de l'écran et mode *Live* (`/api/v1/display/screen`).
- **Sélecteur d'icônes, couleurs et sons** : Listes déroulantes et sélecteur visuel avec filtrage automatique selon la résolution (`8×8` vs `16×16` HD).
- **Commandes en 1 clic (sans JSON)** : Créez des commandes Jeedom natives directement depuis le testeur et utilisez la syntaxe simplifiée `icone | #couleur | durée` dans vos scénarios (tout en gardant la rétrocompatibilité JSON).
- **Carrousel d'informations & Sauvegarde** : Applications rotatives avec évaluation dynamique des tags Jeedom et sauvegarde/restauration complète en JSON.

### 🚀 Installation depuis GitHub dans Jeedom

1. Dans Jeedom, allez dans **Plugins → Gestion des plugins → Ajouter un plugin depuis GitHub** :
   - **ID logique du plugin** : `awtrixng`
   - **Utilisateur ou organisation** : `FranciscoJoseLeon`
   - **Nom du dépôt** : `plugin-awtrixng`
   - **Branche** : `main` (ou `beta` pour les dernières nouveautés)
2. Activez le plugin et accédez à **Plugins → Communication → Awtrix NG**.

### ⚖️ Avertissement

Fourni « tel quel », sans garantie d'aucune sorte, sous licence **AGPL-3.0**. Projet communautaire indépendant, non affilié à Jeedom SAS, Ulanzi ou au projet Awtrix NG.

---

## 🇬🇧 English

**plugin-awtrixng** is a native **Jeedom v4.2+** plugin to monitor, customize, and control **Awtrix NG** and **Awtrix Light** connected LED pixel matrix clocks (**Ulanzi TC001**, **Ulanzi TC002**, and DIY **ESP32 / ESP32-S3 WS2812B** panels).

### 🌟 Key Features

- **100% Native HTTP REST API**: Direct communication with your clock (`/api/v1/notifications`) with **no MQTT broker or extra daemon required**.
- **Automatic Hardware & Sensor Detection**: Automatically detects screen resolution (`32×8`, `52×16`, or DIY `32×8` to `128×8`), hardware model, firmware version, and sensors (Battery, LDR Lux, Temperature, Humidity, Buzzer/Audio).
- **Dedicated Dashboard Widget**: Each Awtrix device gets its own styled Dashboard widget with power toggle, brightness slider, live sensors, next/previous app, notification stop, and quick notification/sound senders.
- **Pixel-Perfect Live Simulator**: Authentic LED grid preview and real-time *Live Mode* (`/api/v1/display/screen`).
- **Interactive Icon, Color & Sound Pickers**: Curated dropdowns and visual icon browser with automatic resolution filtering (`8×8` vs `16×16` HD).
- **1-Click Action Commands (Zero-JSON)**: Save alerts from the live tester as native Jeedom commands and use pipe syntax `icon | #color | duration` in scenarios (with full legacy JSON backward compatibility).
- **Persistent Carousel & Backup/Restore**: Auto-evaluated Jeedom tags in rotating apps and full JSON backup/restore.

### 🚀 Installation from GitHub in Jeedom

1. In Jeedom, go to **Plugins → Plugin Management → Add plugin from GitHub**:
   - **Plugin logical ID**: `awtrixng`
   - **User or organization**: `FranciscoJoseLeon`
   - **Repository name**: `plugin-awtrixng`
   - **Branch**: `main` (or `beta` for latest updates)
2. Activate the plugin and go to **Plugins → Communication → Awtrix NG**.

### ⚖️ Disclaimer

Provided "AS IS" without warranty of any kind under **AGPL-3.0**. Independent community development, not affiliated with Jeedom SAS, Ulanzi, or the Awtrix NG project.

---

## 🇪🇸 Español

**plugin-awtrixng** es un plugin nativo para **Jeedom v4.2+** diseñado para supervisar, personalizar y controlar pantallas matriciales LED **Awtrix NG** y **Awtrix Light** (**Ulanzi TC001**, **Ulanzi TC002** y matrices DIY con **ESP32 / ESP32-S3 WS2812B**).

### 🌟 Características principales

- **API HTTP REST 100 % nativa**: Comunicación directa con tu pantalla (`/api/v1/notifications`), **sin necesidad de broker MQTT ni demonios adicionales**.
- **Detección automática de hardware y sensores**: Detecta automáticamente la resolución (`32×8`, `52×16` o DIY de `32×8` a `128×8`), modelo, versión de firmware y sensores (Batería, Luz LDR, Temperatura, Humedad, Zumbador/Audio).
- **Widget dedicado en el Dashboard**: Cada dispositivo Awtrix cuenta con su propio widget visual con encendido/apagado, control de brillo, sensores en vivo, app siguiente/anterior, parada de notificaciones y envío rápido de avisos o sonidos.
- **Simulador hardware en vivo**: Vista previa píxel a píxel y modo *Live* (`/api/v1/display/screen`).
- **Selector visual de iconos, colores y sonidos**: Desplegables y selector gráfico con filtro automático por resolución (`8×8` frente a `16×16` HD).
- **Comandos en 1 clic (sin JSON)**: Guarda cualquier alerta del probador como comando nativo de Jeedom y usa la sintaxis `icono | #color | duración` en escenarios (manteniendo compatibilidad total con JSON clásico).
- **Carrusel persistente y copia de seguridad**: Evaluación automática de etiquetas de Jeedom en apps rotativas y exportación/restauración completa en JSON.

### 🚀 Instalación desde GitHub en Jeedom

1. En Jeedom, ve a **Plugins → Gestión de plugins → Añadir un plugin desde GitHub**:
   - **ID lógico del plugin**: `awtrixng`
   - **Usuario u organización**: `FranciscoJoseLeon`
   - **Nombre del repositorio**: `plugin-awtrixng`
   - **Rama**: `main` (o `beta` para las últimas novedades)
2. Activa el plugin y accede a **Plugins → Comunicación → Awtrix NG**.

### ⚖️ Exención de responsabilidad

Se proporciona «tal cual», sin garantía de ningún tipo, bajo licencia **AGPL-3.0**. Desarrollo comunitario independiente, sin afiliación oficial con Jeedom SAS, Ulanzi ni el proyecto Awtrix NG.

---

Developed by **Francisco José León** ([@FranciscoJoseLeon](https://github.com/FranciscoJoseLeon))
