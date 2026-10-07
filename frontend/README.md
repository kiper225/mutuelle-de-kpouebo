# Frontend Vue.js – Mutuelle de Développement de Kpouèbo

Application Vue 3 (Vite) qui consomme l'API Laravel du dossier `backend`. Aucune bibliothèque de style : tout est dans `src/styles.css`, aux couleurs du logo.

> Ce code a été écrit sans pouvoir être compilé (pas de réseau dans l'environnement de rédaction). Lancez `npm run dev`, essayez chaque page, et signalez-moi toute erreur affichée dans le terminal ou dans la console du navigateur (F12).

## Installation

```bash
cd frontend
npm install
copy .env.example .env      # puis vérifier VITE_API_URL
npm run dev                 # http://localhost:5173
```

Côté backend, dans `.env` : `FRONTEND_URL=http://localhost:5173` (autorise les appels depuis le navigateur), puis `php artisan config:clear`.

## Pages

| Adresse | Accès | Contenu |
|---|---|---|
| `/` | Public | Accueil : bandeau, mot du président, axes, dernières actualités, valeurs |
| `/la-mutuelle` | Public | Présentation, vidéo, histoire, mot du président, valeurs, bureau (ancres `#histoire`, `#president`, `#bureau`) |
| `/chantiers` | Public | Projets en cours et réalisations (publications des catégories correspondantes) |
| `/don` | Public | Page de don (paiement en ligne pas encore disponible) |
| `/actualites`, `/actualites/:slug` | Public | Liste avec recherche et catégories, détail ; commentaires pour les membres connectés |
| `/inscription`, `/connexion` | Visiteurs | Demande d'adhésion, connexion par téléphone ou email |
| `/espace` | Membre | Profil, paiement de la cotisation, historique |
| `/admin` | Admin | Statistiques, validation / suspension des membres |
| `/admin/publications` | Admin | Créer, modifier, publier en brouillon, supprimer (image de couverture) |
| `/admin/cotisations` | Admin | Retards du mois, paiements hors ligne, historique |

Les commentaires se modèrent directement sous chaque publication (boutons Masquer / Supprimer visibles par l'admin).

## Personnaliser les textes et les images

Tous les textes du site vitrine (slogan, mot du président, axes d'action, contact) sont dans `src/content.js`. Remplacez les `[crochets]`. Pour les photos (fond du bandeau, président, membres du bureau, carte du village), déposez les fichiers dans `public/img/` puis indiquez leur chemin dans `content.js` (ex. `'/img/president.jpg'`). Pour la vidéo, collez le lien d'intégration YouTube dans `videoUrl`. Tant qu'un champ est vide, un emplacement gris s'affiche.

## Mise en ligne (Apache)

```bash
npm run build    # produit le dossier dist/
```

Copiez le contenu de `dist/` sur le serveur. Le fichier `.htaccess` fourni renvoie toutes les adresses vers `index.html` : si le site est dans un sous-dossier, adaptez `RewriteBase` et la dernière règle. Avant le build, mettez dans `.env` l'adresse publique de l'API (`VITE_API_URL=https://…/api/v1`), et dans le `.env` du backend l'adresse publique du site (`FRONTEND_URL`).

## Pas encore inclus

- **Dons** et **messagerie privée entre membres** : ils n'existent pas encore dans l'API (le formulaire de paiement ne gère que les cotisations). La page `/don` explique pour l'instant comment donner hors ligne.
- Page « Mot de passe oublié ».
- Le jeton de connexion est conservé dans le navigateur (`localStorage`) : n'affichez jamais de contenu non fiable en HTML (le code actuel affiche tous les textes en texte brut, ce qui l'évite).
