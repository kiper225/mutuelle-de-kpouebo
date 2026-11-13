# Mutuelle de Développement de Kpouèbo

Site web et application mobile de la **Mutuelle de Développement de Kpouèbo** (Côte d'Ivoire) : vitrine du village, adhésion et espace des membres, cotisations et dons par Mobile Money, levées de fonds, suivi des chantiers, messagerie privée, notifications, et administration complète.

> Dernière mise à jour : novembre 2026

## Sommaire

1. [Présentation](#1-présentation)
2. [Fonctionnalités](#2-fonctionnalités)
3. [Technologies](#3-technologies)
4. [Structure du dépôt](#4-structure-du-dépôt)
5. [Prérequis](#5-prérequis)
6. [Installation](#6-installation)
7. [Configuration](#7-configuration)
8. [Lancer le projet en développement](#8-lancer-le-projet-en-développement)
9. [Paiements (Paystack)](#9-paiements-paystack)
10. [Notifications et Web Push](#10-notifications-et-web-push)
11. [Messagerie](#11-messagerie)
12. [Documents imprimables](#12-documents-imprimables)
13. [API : vue d'ensemble](#13-api--vue-densemble)
14. [Modèle de données](#14-modèle-de-données)
15. [Rôles et sécurité](#15-rôles-et-sécurité)
16. [Tests](#16-tests)
17. [Application Android (Capacitor)](#17-application-android-capacitor)
18. [Déploiement en production](#18-déploiement-en-production)
19. [Guide rapide de l'administrateur](#19-guide-rapide-de-ladministrateur)
20. [Dépannage](#20-dépannage)
21. [Pistes d'évolution](#21-pistes-dévolution)
22. [Conventions du code](#22-conventions-du-code)
23. [Licence et contact](#23-licence-et-contact)

---

## 1. Présentation

Le projet se compose de deux applications indépendantes qui communiquent par une API JSON :

| Dossier | Rôle |
|---|---|
| `backend/` | API Laravel (données, authentification, paiements, notifications, tâches planifiées) |
| `frontend/` | Application Vue 3 (site public, espace membre, administration), également empaquetée en application Android avec Capacitor |

Identité visuelle : vert `#17753F`, vert foncé `#0F5A2E`, orange `#F0891A`, encre `#1B1F1C`, fond `#F3F5F2`. Le logo est `frontend/public/logo.jpg`. Toute l'interface est en français.

---

## 2. Fonctionnalités

### Site public
- Page d'accueil : carrousel, mot du président, vidéo, histoire, visages emblématiques, axes d'action, valeurs, bureau, actualités.
- Page « La mutuelle » : présentation, bureau, valeurs.
- Fil d'actualités façon Facebook : publications, j'aime, commentaires, partage, page de détail.
- Chantiers et projets (en cours, à venir, réalisés), avec barre d'avancement et page de détail.
- Levées de fonds : collectes exceptionnelles avec image, explication, objectif et progression.
- Dons en ligne (Mobile Money) ouverts à tous.
- Recherche globale dans l'en-tête (activités, publications, messages, membres).
- Formulaire d'adhésion avec état civil complet et photo recadrable.
- Connexion, mot de passe oublié et réinitialisation par email.

### Espace membre
- Tableau de bord personnel, cotisations mensuelles payées en ligne, historique.
- Contributions aux levées de fonds.
- Profil modifiable (informations, photo, mot de passe, affichage du statut « en ligne »).
- **Messagerie** façon WhatsApp : conversations privées, emojis, autocollants, voyant « en ligne », « Actif il y a X min », profil du membre, blocage et déblocage, suppression d'une discussion (pour soi seulement).
- **Notifications** : cloche avec compteur, bulles dans la page, alertes du navigateur et **Web Push** (même site fermé), rappels de cotisation.

### Administration
- Tableau de bord : membres, cotisations et dons du mois, graphiques sur 12 mois, **entrées d'argent** du mois (en ligne et en espèces, par source), demandes d'adhésion en attente.
- Membres : validation ou refus, dossier complet modifiable, photo, réinitialisation du mot de passe (mot de passe provisoire affiché une fois), numéro de membre automatique (`MBR-AAAA-NNNN`).
- Publications : création, modification, image de couverture, modération des commentaires.
- Cotisations et dons : suivi, enregistrement des paiements **en espèces**.
- **Messagerie groupée** : message à tous les membres actifs, aux membres en retard de cotisation, ou à une sélection ; suivi du nombre de lectures.
- **Levées de fonds** : collectes, contributions, enregistrement des contributions en espèces.
- **Chantiers et projets** : statut, avancement, budget, dates, lien avec une collecte.
- **Contenu de l'accueil** : carrousel, visages emblématiques, bureau, axes d'action et valeurs (avec image ou icône).
- **Documents imprimables** : fiche d'adhésion (2 pages, avec QR code), carte de membre (format carte bancaire), carton annuel de cotisations.

---

## 3. Technologies

| Couche | Technologie |
|---|---|
| API | Laravel (PHP), authentification par jetons Laravel Sanctum, préfixe `/api/v1` |
| Base de données | MySQL ou MariaDB (`utf8mb4`, indispensable pour les emojis) |
| Frontend | Vue 3 (`<script setup>`), Vite, vue-router |
| Styles | CSS simple dans `src/styles.css` (la première ligne est `@import "tailwindcss";`) |
| Paiements | Paystack (Orange Money, MTN MoMo, Wave) |
| Web Push | `minishlink/web-push`, service worker `public/sw.js` |
| QR code | paquet npm `qrcode` |
| Application mobile | Capacitor (Android) |

---

## 4. Structure du dépôt

```
mutuelle-de-kpouebo/
├── README.md
├── backend/
│   ├── app/
│   │   ├── Console/Commands/        # commandes artisan (rappels, Web Push)
│   │   ├── Http/Controllers/        # contrôleurs de l'API
│   │   │   └── Admin/               # contrôleurs réservés à l'administration
│   │   ├── Models/
│   │   ├── Notifications/           # emails (réinitialisation du mot de passe)
│   │   └── Services/                # paiements, notifications, Web Push
│   ├── config/mutuelle.php          # réglages propres à la mutuelle
│   ├── database/migrations/
│   ├── database/seeders/            # AdminSeeder
│   ├── routes/api.php               # routes de l'API
│   ├── routes/console.php           # tâches planifiées
│   └── tests/Feature/
└── frontend/
    ├── public/                      # logo.jpg, sw.js, manifest.webmanifest, icônes
    ├── capacitor.config.json        # application Android
    └── src/
        ├── main.js, router.js, api.js, auth.js, push.js
        ├── content.js               # textes et coordonnées modifiables
        ├── utils.js, sections.js, axisIcons.js, emojis.js, stickers.js
        ├── composables/             # homeContent.js, notifications.js
        ├── components/              # composants réutilisables
        │   └── print/               # documents imprimables
        ├── layouts/                 # AdminLayout.vue
        ├── views/                   # pages publiques et membres
        │   └── admin/               # pages d'administration
        └── styles.css               # tous les styles
```

---

## 5. Prérequis

- **PHP** 8.2 ou plus (selon la version de Laravel installée, à vérifier dans `composer.json`) avec les extensions `pdo_mysql`, `gd`, `mbstring`, `openssl`, `curl`, `fileinfo` (et `gmp` ou `bcmath`, recommandées pour le Web Push).
- **Composer** 2.
- **MySQL ou MariaDB**.
- **Node.js** 20 ou plus et **npm**.
- Pour l'application Android : **Android Studio** et le JDK qu'il fournit.
- Environnement de développement actuel : Windows, avec Apache 2.4 sous `C:\Serveur\Apache24\htdocs\web\mutuelle-de-kpouebo\`.

---

## 6. Installation

### 6.1 Backend

```bash
cd backend
composer install
cp .env.example .env          # Windows : copy .env.example .env
php artisan key:generate
```

Créez une base de données vide (jeu de caractères `utf8mb4`), renseignez-la dans `.env` (voir [Configuration](#7-configuration)), puis :

```bash
php artisan migrate
php artisan storage:link      # rend les images envoyées accessibles sous /storage
php artisan db:seed --class=AdminSeeder
```

Le compte administrateur est créé à partir de `ADMIN_PHONE` et `ADMIN_PASSWORD` du fichier `.env`.

### 6.2 Frontend

```bash
cd frontend
npm install
```

Créez `frontend/.env` :

```
VITE_API_URL=http://localhost:8000/api/v1
```

---

## 7. Configuration

### 7.1 Backend : `backend/.env`

| Variable | Rôle |
|---|---|
| `APP_URL` | adresse de l'API (`http://localhost:8000` en local) |
| `DB_*` | connexion à la base de données |
| `ADMIN_PHONE`, `ADMIN_PASSWORD` | compte administrateur créé par `AdminSeeder` |
| `MEMBER_NUMBER_PREFIX` | préfixe du numéro de membre (`MBR` par défaut) |
| `PAYSTACK_*` | clés Paystack (voir [Paiements](#9-paiements-paystack)) |
| `MAIL_*` | envoi des emails (mot de passe oublié) |
| `CORS_ALLOWED_ORIGINS` | origines autorisées à appeler l'API (site, et `https://localhost` pour Android) |
| `MUTUELLE_REMINDER_DAYS` | jours du mois où le rappel de cotisation est envoyé (`1,15,25` par défaut) |
| `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT` | clés du Web Push |

Les réglages propres à la mutuelle (devise, préfixe du numéro de membre, montant minimal d'un don, clés de paiement, jours de rappel, clés Web Push) sont lus dans **`backend/config/mutuelle.php`**. Vérifiez-y les noms exacts des variables d'environnement.

### 7.2 Frontend : `frontend/.env`

| Variable | Rôle |
|---|---|
| `VITE_API_URL` | adresse de l'API, avec `/api/v1` |
| `VITE_PUBLIC_URL` | adresse publique du site, utilisée pour les liens de partage (indispensable dans l'application Android) |

### 7.3 Textes, coordonnées et personnalisation

- **`frontend/src/content.js`** : nom de la mutuelle, coordonnées (téléphone, email, adresse), pied de page, signataire des documents, avertissement de la carte de membre, texte de la déclaration d'adhésion, textes d'exemple de l'accueil.
- **Depuis l'administration** : carrousel, visages emblématiques, bureau, axes d'action, valeurs (ce contenu remplace les textes d'exemple de `content.js` dès qu'un élément existe).
- **Couleurs** : variables au début de `frontend/src/styles.css` (`--green`, `--orange`, etc.).
- **Logo** : remplacez `frontend/public/logo.jpg` (et créez `icon-192.png` et `icon-512.png` dans `public/` pour l'installation sur téléphone).

---

## 8. Lancer le projet en développement

Dans deux terminaux :

```bash
# Terminal 1 : API
cd backend
php artisan serve          # http://localhost:8000

# Terminal 2 : site
cd frontend
npm run dev                # http://localhost:5173
```

Pour tester les **rappels automatiques** et les publications programmées, laissez aussi ouvert un troisième terminal :

```bash
cd backend
php artisan schedule:work
```

Connexion administrateur : le téléphone et le mot de passe définis dans `.env` (`ADMIN_PHONE`, `ADMIN_PASSWORD`), sur `/connexion`.

---

## 9. Paiements (Paystack)

Les cotisations, les dons et les contributions aux levées de fonds passent par **Paystack**. Les montants sont envoyés en XOF multipliés par 100, comme l'exige Paystack.

- Chaque paiement a une référence : `COT-…` (cotisation), `DON-…` (don), `COL-…` (contribution à une collecte).
- **Confirmation** : Paystack appelle le webhook `POST /api/v1/webhooks/paystack` (signature HMAC-SHA512 vérifiée). La page de retour du site interroge aussi `GET /api/v1/payments/{reference}`, qui vérifie directement auprès de Paystack si le paiement est encore « en attente ». Cela fonctionne même en local, où Paystack ne peut pas joindre votre ordinateur.
- Le montant payé est comparé au montant attendu avant de valider un paiement.
- Sans clé Paystack, une passerelle neutre (`NullPaymentGateway`) enregistre la demande sans transaction réelle : utile en développement.
- Paiements **en espèces** : enregistrés par l'administrateur depuis les pages Cotisations, Dons et Collectes. Ils sont comptés dans la colonne « Espèces » du tableau de bord.
- Webhook de production à déclarer dans Paystack : `https://api.votre-domaine.ci/api/v1/webhooks/paystack`.

---

## 10. Notifications et Web Push

### Types de notifications
| Événement | Notification |
|---|---|
| Nouvelle information publiée | « Nouvelle information : … » |
| Nouveau communiqué | « Nouveau communiqué : … » |
| Nouvelle activité (publication ou chantier) | « Nouvelle activité : … » |
| Nouvelle levée de fonds | « Nouvelle levée de fonds : … » |
| Rappel de cotisation | les jours configurés, aux membres qui n'ont pas payé le mois |
| Message privé ou groupé | bulle et alerte avec le nom et le début du message (en plus de la pastille de la messagerie) |

Chaque contenu notifie **une seule fois** (colonne `notified_at`). Le contenu publié avant la mise en place des notifications n'en déclenche pas.

### Où les membres les voient
1. **Cloche** dans l'en-tête, avec compteur et page « Toutes les notifications ».
2. **Bulles** en haut à droite de la page.
3. **Alertes du navigateur**, quand le site est ouvert dans un onglet.
4. **Web Push** : l'alerte arrive même quand le site est fermé.

L'activation est demandée au membre par un encadré, et enregistrée **par membre** (un ordinateur partagé ne reçoit pas les alertes du compte précédent). À la déconnexion, l'appareil est détaché du compte.

### Tâches planifiées (`backend/routes/console.php`)
| Tâche | Fréquence |
|---|---|
| `notifications:cotisation-reminders` | chaque jour à 8 h (envoie seulement aux jours configurés) |
| `notifications:publish-pending` | toutes les 10 minutes (publications programmées) |
| nettoyage des notifications de plus de 90 jours | chaque jour |

Le planificateur doit tourner : `php artisan schedule:work` en développement, une ligne `cron` en production (voir [Déploiement](#18-déploiement-en-production)).

### Configurer le Web Push

```bash
cd backend
composer require minishlink/web-push
php artisan webpush:keys          # affiche les trois lignes à copier dans .env
php artisan config:clear
php artisan migrate
```

Dans `.env` :

```
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:contact@votre-domaine.ci
```

**À ne faire qu'une fois** : si les clés sont régénérées, tous les appareils doivent se réabonner.

Test : un membre active les alertes depuis le site, puis :

```bash
php artisan webpush:test 83       # 83 = identifiant du membre
```

Particularités :
- Le site doit être en **HTTPS** (ou `localhost`).
- **iPhone** : le Web Push ne fonctionne que si le site est ajouté à l'écran d'accueil (iOS 16.4 ou plus).
- L'**application Android Capacitor** n'a pas de Web Push (voir [Pistes d'évolution](#21-pistes-dévolution)).
- Sous **Windows**, si `webpush:keys` affiche « Unable to create the key », voir [Dépannage](#20-dépannage).

---

## 11. Messagerie

- Conversations privées entre membres actifs, rafraîchies automatiquement (5 s dans une conversation ouverte, 15 s pour la liste).
- **Présence** : le site envoie un signal toutes les 45 secondes tant qu'il est visible. Un membre vu depuis moins de 90 secondes est « en ligne » ; ensuite « Actif il y a X min » jusqu'à 24 h. Si l'un des deux membres masque son statut, aucun des deux ne voit celui de l'autre. L'API n'envoie jamais l'heure exacte.
- **Profil du membre** : photo, poste dans la mutuelle, année d'adhésion (jamais le téléphone ni la date de naissance).
- **Blocage** : le membre bloqué ne peut plus écrire et ne voit plus la présence. L'administration ne peut pas être bloquée par un membre.
- **Suppression d'une discussion** : pour soi seulement ; l'autre membre garde la sienne. Si l'on vous écrit de nouveau, la discussion réapparaît avec les nouveaux messages.
- **Emojis et autocollants** : 7 catégories d'emojis (avec récents), 12 autocollants prédéfinis (Bonjour, Bravo, Merci, Félicitations, Courage, Solidarité, Paix, Haha, Je vous aime, D'accord, On fête ça, Unis pour Kpouèbo). La liste des autocollants doit rester identique dans `Message::STICKERS` (API) et `src/stickers.js` (site).
- **Messages groupés** de l'administration : un message privé est créé pour chaque destinataire, avec la mention « Message groupé ».

---

## 12. Documents imprimables

Accessibles aux administrateurs, depuis le dossier d'un membre :

| Document | Contenu |
|---|---|
| **Fiche d'adhésion** | 2 pages A4 : fiche (QR code, matricule, photo, informations personnelles et professionnelles, visa du bureau) et déclaration d'adhésion « à conserver par l'association », avec pré-remplissage facultatif |
| **Carte de membre** | recto et verso au format carte bancaire (85,6 × 54 mm), effet 3D à l'écran, panneau de personnalisation (date d'émission, signataire, signature écrite facultative) |
| **Carton annuel** | grille des 12 mois de cotisation |

Impression : **A4, échelle 100 %, sans en-têtes ni pieds de page**. Les textes (signataire, déclaration, avertissement) se modifient dans `src/content.js`.

---

## 13. API : vue d'ensemble

Préfixe : `/api/v1`. Authentification : en-tête `Authorization: Bearer <jeton>` (Sanctum). La liste exacte et à jour des routes :

```bash
php artisan route:list --path=api/v1
```

| Domaine | Exemples de routes |
|---|---|
| Public | `POST /register`, `POST /login`, `POST /forgot-password`, `POST /reset-password`, `GET /home-content`, `GET /projects`, `GET /fundraisers`, `GET /fundraisers/{id}` |
| Paiements | `POST /webhooks/paystack`, `POST /webhooks/payments`, `GET /payments/{reference}` |
| Membre (compte actif) | `GET /me`, `POST /me/profile`, `POST /me/password`, `POST /logout`, `POST /presence`, `GET /search` |
| Messagerie | `GET /conversations`, `GET\|POST\|DELETE /conversations/{user}`, `POST\|DELETE /conversations/{user}/block`, `GET /directory`, `GET /members/{user}`, `GET /messages/unread-count` |
| Notifications | `GET /notifications`, `POST /notifications/read-all`, `POST /notifications/{id}/read`, `GET /push/key`, `POST /push/subscribe`, `POST /push/unsubscribe` |
| Collectes | `POST /fundraisers/{id}/contribute` |
| Administration | tout sous `/admin/...` : `stats`, `stats/monthly`, `stats/income`, `members`, `publications`, `cotisations`, `donations`, `hero-slides`, `faces`, `sections/{section}`, `fundraisers`, `projects`, `group-messages` |

Les erreurs de validation suivent le format Laravel (`422` avec un objet `errors`), géré côté site par `src/api.js` (`e.errors`, `e.field('nom')`).

---

## 14. Modèle de données

| Table | Contenu |
|---|---|
| `users` | membres et administrateurs : rôle, statut (`pending`, `active`, `suspended`), état civil, photo, numéro de membre, présence |
| `cotisations` | cotisations mensuelles (période `AAAA-MM`, montant, statut, référence, mode de paiement) |
| `donations` | dons (donateur, montant, statut, référence) |
| `publications`, `comments`, `likes` | fil d'actualités |
| `messages`, `group_messages`, `chat_states` | messages privés (texte ou autocollant), messages groupés, blocages et suppressions de discussion |
| `fundraisers`, `contributions` | levées de fonds et contributions |
| `projects` | chantiers et projets |
| `hero_slides`, `faces`, `section_items` | contenu de l'accueil géré depuis l'administration (bureau, axes, valeurs) |
| `notifications` | notifications des membres (table standard de Laravel) |
| `push_subscriptions` | appareils abonnés au Web Push |

Un paiement est **« en ligne »** s'il vient de Paystack, et **« en espèces »** s'il a été enregistré à la main par l'administration (colonne `recorded_by` renseignée).

---

## 15. Rôles et sécurité

| Rôle | Droits |
|---|---|
| Visiteur | contenus publics, adhésion, dons |
| Membre en attente (`pending`) | ne peut pas se connecter avant la validation |
| Membre actif | espace membre, cotisations, messagerie, collectes, notifications |
| Administrateur | toute l'administration, documents imprimables |

Mesures en place :
- Jetons Sanctum ; middlewares `active` (compte validé) et `admin` déclarés dans `bootstrap/app.php`.
- Limitation de débit sur la connexion, la réinitialisation du mot de passe, l'envoi de messages groupés, les contributions et les abonnements push.
- Webhooks de paiement signés ; montants contrôlés avant validation.
- Envois d'images limités (type et taille) ; fichiers dans `storage/app/public`.
- Les données personnelles sensibles ne sont jamais exposées entre membres (annuaire : nom et photo ; profil : poste et année d'adhésion).
- Le contenu des alertes Web Push est chiffré de bout en bout.
- Ne jamais committer le fichier `.env`. Ne jamais régénérer les clés VAPID sans nécessité.

---

## 16. Tests

```bash
cd backend
php artisan test
php artisan test --filter=PresenceTest    # un seul groupe de tests
```

Les tests couvrent notamment l'inscription et la validation des membres, les paiements et les webhooks, les cotisations et dons, les publications, les messages privés et groupés, la présence, le blocage, les collectes, les chantiers, les notifications et le Web Push (envoi simulé : aucun appel réseau réel).

Le frontend n'a pas de tests automatisés ; vérifiez les pages principales à la main aux largeurs 360, 768, 1024 et 1440 px (outils de développement du navigateur).

---

## 17. Application Android (Capacitor)

Identifiant de l'application : `ci.kpouebo.mutuelle` (à adapter avant publication). Configuration dans `frontend/capacitor.config.json`.

1. Créez `frontend/.env.android` :
   ```
   VITE_API_URL=https://api.votre-domaine.ci/api/v1
   VITE_PUBLIC_URL=https://votre-domaine.ci
   ```
   L'API doit être en ligne, en **HTTPS**.
2. Autorisez l'application dans `CORS_ALLOWED_ORIGINS` du backend : ajoutez `https://localhost`.
3. Construisez et ouvrez le projet Android :
   ```bash
   cd frontend
   npm run build:android      # vite build --mode android, puis npx cap sync android
   npx cap open android       # ouvre Android Studio
   ```
4. Dans Android Studio : *Sync*, puis lancez sur un téléphone ou un émulateur.

Limites connues : l'impression des documents n'est pas disponible dans l'application ; le Web Push ne fonctionne pas dans la fenêtre web de Capacitor ; le retour de paiement Paystack doit être testé sur un vrai téléphone.

Publication sur Google Play : une **politique de confidentialité** en ligne est obligatoire ; un compte personnel exige un test fermé de 12 testeurs pendant 14 jours.

---

## 18. Déploiement en production

Exemple pour un serveur **Ubuntu + Apache** avec deux sites :

| Site | Racine du site |
|---|---|
| `api.votre-domaine.ci` | `backend/public` |
| `votre-domaine.ci` | `frontend/dist` |

### Backend
```bash
cd /var/www/mutuelle/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env            # puis renseigner APP_ENV=production, APP_DEBUG=false, la base, Paystack, VAPID, MAIL…
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan db:seed --class=AdminSeeder
php artisan config:cache && php artisan route:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Planificateur (rappels, publications programmées)
Ajoutez une ligne à `crontab -e` :
```
* * * * * cd /var/www/mutuelle/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Frontend
```bash
cd /var/www/mutuelle/frontend
npm ci
npm run build                   # produit dist/
```
Le routeur utilise des adresses « propres » : Apache doit renvoyer `index.html` pour les chemins inconnus.
```apache
<Directory /var/www/mutuelle/frontend/dist>
    Require all granted
    FallbackResource /index.html
</Directory>
<Files "sw.js">
    Header set Cache-Control "no-cache"
</Files>
```
(`sudo a2enmod headers rewrite`.)

### HTTPS et sauvegardes
- HTTPS avec Certbot (`sudo certbot --apache -d votre-domaine.ci -d api.votre-domaine.ci`). Le HTTPS est obligatoire pour le Web Push et l'installation sur téléphone.
- Sauvegardes quotidiennes : un export de la base (`mysqldump`) et le dossier `backend/storage/app/public` (photos et images).
- Déclarez le webhook Paystack : `https://api.votre-domaine.ci/api/v1/webhooks/paystack`.
- Après chaque mise à jour : `git pull`, `composer install --no-dev`, `php artisan migrate --force`, `php artisan config:cache`, puis `npm ci && npm run build`.

---

## 19. Guide rapide de l'administrateur

1. **Valider les adhésions** : *Membres*, filtre « en attente », ouvrir le dossier, *Valider*. Le numéro de membre est attribué automatiquement.
2. **Publier une information ou un communiqué** : *Publications*, nouvelle publication avec sa catégorie et son image. Les membres sont notifiés automatiquement.
3. **Enregistrer un paiement en espèces** : *Cotisations* ou *Dons*, bouton d'enregistrement manuel. Il apparaît dans la colonne « Espèces » du tableau de bord.
4. **Lever des fonds pour un chantier** : *Levées de fonds → Collectes*, créer la collecte (texte, image, objectif), puis la lier au chantier depuis *Chantiers et projets*.
5. **Écrire à tous les membres** : *Messagerie*, choisir les destinataires (tous, en retard de cotisation, sélection) et envoyer.
6. **Personnaliser l'accueil** : menu *Site* (carrousel, visages, bureau, axes d'action, valeurs).
7. **Imprimer** une fiche d'adhésion, une carte de membre ou un carton annuel depuis le dossier du membre.
8. **Réinitialiser un mot de passe** : dossier du membre, le mot de passe provisoire s'affiche **une seule fois** : communiquez-le au membre.

---

## 20. Dépannage

| Symptôme | Cause et solution |
|---|---|
| `Unknown column …` ou `Table … doesn't exist` | migrations non appliquées : `php artisan migrate` |
| `Failed to open stream` pour une classe, ou commande artisan introuvable | liste de classes de Composer périmée : `composer dump-autoload` puis `php artisan optimize:clear` |
| `cURL error 60` (Paystack, Web Push) | indiquez le fichier `cacert.pem` dans `php.ini` (`curl.cainfo` et `openssl.cafile`) |
| Erreur sur les images ou les photos | activez l'extension `gd` dans `php.ini` |
| Erreur 500 de Vite sur un fichier `.vue` | le message est dans le terminal de `npm run dev` : cherchez `[plugin:vite:vue]` (balise non fermée, import manquant) |
| `Failed to resolve import "qrcode"` | `npm install qrcode` dans `frontend` |
| `Incorrect string value` à l'envoi d'un emoji | la base n'est pas en `utf8mb4` : `ALTER TABLE messages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` |
| `Unable to create the key` (`webpush:keys`, Windows) | OpenSSL ne trouve pas son `openssl.cnf` : créez `backend/openssl.cnf` (sections `[ req ]` et `[ req_distinguished_name ]` vides) et indiquez-le avec `set OPENSSL_CONF=…\openssl.cnf` ; ce problème n'existe pas sous Ubuntu |
| Les alertes n'arrivent pas | vérifiez : notifications autorisées dans le navigateur (cadenas), `sw.js` actif (outils de développement, onglet Application), clés VAPID dans `.env` après `php artisan config:clear`, et `storage/logs/laravel.log` |
| Les rappels de cotisation ne partent pas | le planificateur ne tourne pas : `php artisan schedule:work` (développement) ou `cron` (production) ; test immédiat : `php artisan notifications:cotisation-reminders --force` |
| L'application Android ne joint pas l'API | l'API doit être en HTTPS, et `https://localhost` doit figurer dans `CORS_ALLOWED_ORIGINS` |
| Le paiement reste « en attente » en local | Paystack ne peut pas joindre votre ordinateur : la page de retour vérifie directement auprès de Paystack ; en production, vérifiez l'adresse du webhook |
| Message `Vite manifest not found` dans un test | supprimez le test `ExampleTest` généré par défaut |

---

## 21. Pistes d'évolution

- **Politique de confidentialité** en ligne (obligatoire pour Google Play).
- Changement de mot de passe **obligatoire** à la première connexion après une réinitialisation par l'administrateur.
- Rendre modifiables depuis l'administration les textes encore présents dans `content.js` (mot du président, présentation, vidéo, histoire, village, contact).
- Notifications par **email** et par **SMS**, et réglage des types d'alertes par membre.
- Web Push dans l'application Android (module Capacitor et Firebase Cloud Messaging).
- Section « Nos chantiers en cours » et collectes en cours sur la page d'accueil ; chantiers dans les résultats de recherche.
- Page de **vérification** des membres ouverte par le QR code de la fiche d'adhésion.
- Autocollants en image gérés depuis l'administration.
- Version installable (PWA) complète, avec fonctionnement hors ligne.

---

## 22. Conventions du code

- Interface, messages d'erreur et commentaires en **français**.
- Une seule feuille de style (`src/styles.css`). Les classes sont préfixées par fonctionnalité : `wa-` (messagerie), `fb-` (fil d'actualités), `fa-` (fiche d'adhésion), `bd-` (carte de membre), `fund-` (collectes), `notif-` et `toast` (notifications), `kpi` et `admin-` (administration).
- Les écrans sont conçus pour tous les appareils : points de rupture à 480, 640, 800, 900, 1000 et 1150 px.
- Côté API : un contrôleur par domaine, les contrôleurs d'administration dans `Admin/`, la logique métier dans `app/Services/`, une migration par évolution.
- Chaque nouvelle fonction de l'API s'accompagne d'un test dans `tests/Feature/`.
- Les listes partagées entre l'API et le site doivent rester identiques : autocollants (`Message::STICKERS` et `src/stickers.js`), icônes des axes (`SectionItem::ICONS` et `src/axisIcons.js`).

---

## 23. Licence et contact

Projet de la **Mutuelle de Développement de Kpouèbo**. Licence à préciser par la mutuelle (par défaut : tous droits réservés).

Contact : à compléter (responsable du projet, téléphone, email).