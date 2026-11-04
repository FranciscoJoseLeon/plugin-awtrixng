# Changelog Plugin Awtrix NG / Light

>**IMPORTANT**
>
>S'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.

# Version 0.9.0 (04/11/2026) - Version Bêta / RC

- Version préliminaire pour tests et validation communautaire du plugin Awtrix NG pour Jeedom v4.
- Compatibilité native avec horloges matricielles LED 32×8 (Ulanzi TC001 et matrices DIY WS2812B), 52×16 (Ulanzi TC002) et DIY (32×8 à 128×8).
- Auto-détection du matériel, de la taille d'écran et des capteurs physiques (Batterie, Lux LDR, Température, Humidité, Audio/TTS).
- Simulateur de matrice LED 1:1 fidèle au panneau configuré avec châssis de commandes et mode streaming "En Direct" (/api/v1/display/screen).
- Sélecteur visuel d'icônes avec filtrage intelligent de résolution (masque les icônes 16×16 HD sur les écrans à 8 lignes).
- Publication simplifiée de notifications (Zero-JSON) et bouton "Sauvegarder comme Commande" pour créer des actions en 1 clic.
- Carrousel continu pour faire défiler les capteurs Jeedom sans scénarios complexes.
- Sauvegarde et restauration (Backup / Restore) au format JSON et copie de configuration entre panneaux.
- Communication 100% directe en HTTP REST API (sans broker MQTT requis).
- Documentation complète en français, espagnol et anglais.
