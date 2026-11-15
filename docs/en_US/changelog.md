# Changelog - Awtrix NG / Awtrix 3 Plugin

> **IMPORTANT**
>
> If there is no specific information about an update, it is solely due to documentation, translation, or minor text improvements.

## Version 0.9.0 (2026-11-15) - Beta Release

- **Full AWTRIX NG & AWTRIX 3 / Legacy Compatibility**: automatic firmware and matrix detection, smart audio routing (`/api/sound` for MP3 and `/api/rtttl` for RTTTL on AWTRIX 3), live screen capture (`/api/v1/display/screen` and `/api/screen`), and clear notice for `tts` (AWTRIX NG only).
- **Custom Dashboard Widget** (dark chassis + amber LED accent): direct link to panel, online status, firmware/resolution badge, available telemetry, current app, quick controls (previous app, display power toggle, dismiss notification, next app), brightness slider, and universal command selector with sound dropdown.
- **Per-Panel Interactive Command Tester & Live Visualizer**: tailored to each panel's firmware and exact pixel resolution (`32×8`, `52×16`, `DIY`), featuring smooth non-overlapping live refresh and dropdowns for available sounds, installed icons, and colors.
- **Streamlined Model Selector**: shows only the active panel model with a **Change model** toggle button.
- **Maintenance Mode & Disclaimer**: global command reload, firmware re-detection, and carousel sync across all panels from plugin configuration.
- **Complete trilingual translations & manuals** in Spanish, French, and English.
