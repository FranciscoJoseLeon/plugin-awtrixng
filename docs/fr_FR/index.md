# Plugin Awtrix NG / Light pour Jeedom

Intégration native pour horloges matricielles LED intelligentes **Awtrix Light** et **Awtrix NG** (comme l'horloge populaire **Ulanzi TC001**, la nouvelle **Ulanzi TC002** ou les projets ESP32 WS2812B personnalisés).

Contrôlez votre écran directement depuis Jeedom via son **API HTTP REST**, avec prise en charge des notifications instantanées, du carrousel d'applications, de l'ajustement de la luminosité, des capteurs de batterie/lumière et de la personnalisation complète des **couleurs de texte et icônes** adaptées à chaque résolution.

---

## 1. Appareils et Résolutions Compatibles

Le plugin prend en charge les deux architectures matérielles d'Awtrix NG :

| Appareil | Résolution Matrice | Résolution Icônes | Fonctionnalités Clés |
| :--- | :---: | :---: | :--- |
| **32×8 (TC001 & DIY)** | **32 × 8 LEDs** | **8 × 8 pixels** | Ulanzi TC001 et matrices WS2812B. Télémétrie de batterie portable, capteur de lumière LDR et buzzer RTTTL. |
| **52×16 (TC002)** | **52 × 16 LEDs** | **16 × 16 (HD) & 8 × 8** | Ulanzi TC002. Haut-parleur pour sons MP3, Web radio, mélodies, synthèse vocale (TTS) et haute résolution. |

Dans la configuration de l'équipement dans Jeedom, des **photographies réelles** de chaque modèle vous permettent d'identifier votre appareil immédiatement en un seul clic.


---

## 2. Icônes en Couleur et Couleurs de Texte

### Icônes en Couleur Adaptées à la Résolution
* **Pour écrans 32×8 (TC001 / DIY)** : Utilisez des icônes en couleur de **8×8 pixels** (des milliers d'icônes disponibles sur la bibliothèque LaMetric ou Awtrix). Indiquez simplement l'ID numérique (ex. `2284` pour arrosage/fleurs, `1425` pour température).
* **Pour écrans 52×16 (TC002)** : Prend en charge les icônes standard 8×8 ainsi que les nouvelles icônes haute définition de **16×16 pixels** avec des couleurs riches.

### Personnalisation des Couleurs de Texte
Modifiez la couleur du texte à tout moment :
* Avec un code hexadécimal standard : `#00FF00` (vert), `#FF0000` (rouge), `#00BFFF` (bleu), `#FFA500` (orange), `#FFD700` (jaune).
* Ou en écrivant le nom de la couleur dans le champ Titre : `vert`, `rouge`, `bleu`, `jaune`, `orange`.

---

## 3. Exemples Pratiques dans les Scénarios Jeedom

### Exemple 1 : Ouverture de la vanne d'arrosage de la terrasse
Lorsque la vanne d'arrosage de la terrasse s'ouvre, affichez une notification verte avec icône de fleur/eau :
* **Action** : `#[Terrasse][Écran Awtrix][Notifier (Popup)]#`
* **Titre (Icône ou Couleur)** : `2284` *(ou `#00FF00`)*
* **Message** : `Arrosage des fleurs (Vanne terrasse ouverte)`

---

### Exemple 2 : Alerte chauffe-eau inférieur à 40°C
Si la sonde de température du chauffe-eau passe sous les 40°C, affichez une alerte rouge urgente avec bip sonore :
* **Action** : `#[Salon][Écran Awtrix][Notifier (Popup)]#`
* **Titre** : `#FF0000`
* **Message** : `Alerte : Température du chauffe-eau inférieure à 40°C (#[SDB][Chauffe-eau][Température]#°C)`

---

### Exemple 3 : Notification de machine à laver terminée (Carrousel)
Ajoutez un écran persistant dans le carrousel Awtrix tant que le linge propre attend dans le tambour :
* **Action** : `#[Salon][Écran Awtrix][App Carrousel (Publier)]#`
* **Titre (Nom de l'App)** : `lessive`
* **Message** : `Lavage terminé`

Lorsque la porte de la machine est ouverte, supprimez l'application du carrousel :
* **Action** : `#[Salon][Écran Awtrix][Supprimer App Carrousel]#`
* **Titre** : `lessive`

---

### Exemple 4 : Réglage automatique de la luminosité la nuit
Dans votre scénario de nuit :
* **Action** : `#[Chambre][Écran Awtrix][Ajuster Luminosité]#`
* **Valeur** : `10` *(ou `[Éteindre Écran]` pour l'obscurité totale)*

---

## 4. Studio de Test en Direct
Dans l'onglet de configuration de l'appareil dans Jeedom, profitez du **Studio de Test en Direct** :
* Essayez directement les modèles préenregistrés (*Arrosage des fleurs*, *Chauffe-eau < 40°C*, *Machine à laver*, *Porte ouverte*).
* Testez les couleurs grâce au sélecteur chromatique interactif.
* Cliquez sur **Envoyer Notification** pour prévisualiser instantanément le résultat sur votre horloge physique.

---

## 5. Documentation Officielle et Guides de Construction

Pour configurer le firmware de votre horloge ou fabriquer votre propre écran matriciel LED personnalisé, consultez la documentation officielle du projet Awtrix NG :
* 🚀 **Guide de Démarrage (Get Started)** : [https://blueforcer.github.io/awtrix-ng/esp32/](https://blueforcer.github.io/awtrix-ng/esp32/)
* 🛠️ **Fabriquer sa Propre Matrice LED (How to build your own matrix LED)** : [https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/](https://blueforcer.github.io/awtrix-ng/esp32/advanced/diy-build/)

---

## 6. Avertissement Légal et Limitation de Responsabilité

Ce plugin est un développement logiciel communautaire indépendant sous licence libre **GNU AGPL v3**.

* **Absence de garantie (« En l'état / As Is »)** : Le logiciel est fourni sans garantie d'aucune sorte, expresse ou implicite, y compris, mais sans s'y limiter, les garanties implicites de qualité marchande ou d'adéquation à un usage particulier.
* **Limitation de responsabilité** : L'auteur décline toute responsabilité en cas de dommages directs, indirects, matériels ou immatériels résultant de l'utilisation ou de l'impossibilité d'utiliser ce plugin (perte de données, indisponibilité du système domotique Jeedom, défaillances de réseaux ou détérioration du matériel connecté).
* **Marques de tiers** : Ce projet n'est ni sponsorisé, ni affilié, ni officiellement soutenu par la société **Jeedom SAS**, la marque **Ulanzi**, ni par les auteurs du micrologiciel **Awtrix NG**. Toutes les marques commerciales et marques déposées citées restent la propriété exclusive de leurs détenteurs respectifs.


