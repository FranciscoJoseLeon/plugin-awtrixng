# Journal des Modifications (Changelog) - Plugin Awtrix NG / Awtrix 3

> **IMPORTANT**
>
> S'il n'y a pas d'information spécifique sur une mise à jour, il s'agit uniquement d'améliorations de documentation, de traductions ou de textes mineurs.

## Version 0.9.0 (15/11/2026) - Version Bêta

- **Compatibilité complète AWTRIX NG et AWTRIX 3 / Legacy** : détection automatique du firmware et de la matrice, routage audio intelligent (`/api/sound` pour MP3 et `/api/rtttl` pour RTTTL sur AWTRIX 3), capture d'écran en direct (`/api/v1/display/screen` et `/api/screen`) et message clair pour `tts` (exclusif AWTRIX NG).
- **Widget personnalisé sur le Dashboard** (thème sombre + accent ambre LED) : lien direct vers le panneau, statut, badge firmware/résolution, télémétrie disponible, app active, contrôles rapides (app précédente, allumer/éteindre, fermer notification, app suivante), luminosité et sélecteur de toutes les commandes avec liste des sons.
- **Testeur interactif de commandes et Visualiseur en direct par panneau** : adapté au firmware et à la résolution exacte (`32×8`, `52×16`, `DIY`), avec rafraîchissement fluide et listes déroulantes pour les sons disponibles, les icônes installées et les couleurs.
- **Sélecteur de modèle simplifié** : n'affiche que le modèle correspondant au panneau avec le bouton **Changer de modèle**.
- **Mode Maintenance et Clause de non-responsabilité** : rechargement global des commandes, re-détection du firmware et synchronisation du carrousel sur tous les panneaux depuis la configuration du plugin.
- **Traductions et manuels complets** en espagnol, français et anglais.
