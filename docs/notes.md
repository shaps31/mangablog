# Journal de développement

## 2025-09-06 — Sprint 0
- Création du projet Symfony (webapp)
- Configuration SQLite (.env.local)
- README réécrit avec titre et sections

Commits:
- tache(env): initialiser le projet Symfony + configurer SQLite
- doc: corriger README (ajout du titre et des sections en français)

Prochaine étape : Sprint 1 (Auth)

## 2025-09-06 — Sprint 1
- Génération User + LoginFormAuthenticator (/login)
- Formulaire d’inscription (/register) avec hash du mot de passe
- Navbar: liens Register/Login/Logout
- Sécurité: access_control sur /admin, /post, /comment

Commits:
- ajout(auth): entité User et formulaire de connexion (/login)
- ajout(auth): page d’inscription (/register) avec hash du mot de passe
- ajout(ui): liens navbar Register/Login/Logout avec état connecté
- tache(securite): protéger les préfixes /admin, /post, /comment

## 2025-09-06 — Sprint 2
- Entités créées (Category, Tag, Post, Comment) + migrations
- CRUD générés
- Règles Post (author, slug, publishedAt)
- Validations Post/Comment
- Sécurité des routes CRUD

Commits:
- ajout(db): migrations initiales …
- ajout(crud): Category, Tag, Post, Comment …
- amelioration(post): …
- amelioration(validation): …
- tache(securite): …

## 2025-09-06 — Sprint 3
- Contrôleur public Blog (index + show)
- Recherche texte et filtre par catégorie (published only)
- Vues Bootstrap simples
- Lien "Blog" dans la navbar

Commits:
- ajout(blog): liste publique avec recherche et filtre catégorie + page article

## 2025-09-06 — Sprint 3 (suite)
- Totaux du mois (articles publiés) + répartition par catégorie
- Affichage en haut de /blog

Commit:
- ajout(blog): totaux du mois (global + par catégorie) sur la liste publique

## 2025-09-06 — Sprint 4
- Commentaires publics : formulaire simple (contenu seul), sauvegarde en "pending"
- Affichage des commentaires "approved" sous l'article
- Modération via CRUD des commentaires

Commit:
- ajout(comments): formulaire public (auth requis) + affichage approuvés

-“Commentaires publics : envoi → pending, modération via CRUD, affichage quand approved”.

## 2025-09-06 — Sprint 5
- Pagination /blog (Doctrine setFirstResult + setMaxResults)
- Conserve recherche et catégorie dans les liens
  Commit : feat(blog): pagination simple (page=X) avec conservation des filtres


## 2025-09-06 — Sprint 7
- Ajout d'un menu Back-office dans la navbar (CRUD Articles/Catégories/Tags/Commentaires)
- Visible uniquement si l'utilisateur est connecté
Commit: ui(nav): menu Back-office (liens CRUD) visible pour l’utilisateur connecté

## 2025-09-06 — Sprint 5 (petit plus)
- Ajout d'un indicateur "Page X / Y" sur /blog (Twig uniquement)
Commit: ui(blog): afficher 'Page X / Y' au-dessus de la pagination

## 2025-09-06 — Sprint 7 (plus)
- Prévisualisation de la cover pendant la saisie (JS simple). 

- Objectif : améliorer l’UX sans complexifier (pas d’upload, juste URL).
Commit: ui(post): aperçu visuel de la cover pendant la saisie (prévisualisation de l’URL)

- “Correction filtres category/tag (+ pagination) – Bug ‘variable inexistante’ corrigé.”

- Ajout d'un compteur de résultats sur /blog + bouton Réinitialiser si des filtres sont actifs.

- Objectif : meilleure UX, facile à expliquer (Twig uniquement).

- Ajout d'une confirmation avant suppression (onsubmit confirm).

- Fallback de cover: image locale par défaut si le champ est vide.

- Navbar: bouton + Créer un article (visible si connecté)

- Admin: badge visuel du statut (Publié/Brouillon) dans la liste des articles

- Accueil: ajout des 3 derniers articles publiés (findBy + tri publishedAt DESC).

- Vue: cartes avec cover (fallback), date, extrait, bouton Lire.
## 2025-09-06 — Sprint 8 (UX Tags)
- Champ **Tags** (Post) rendu **recherchable** et **multi-sélection** avec Tom Select (CDN).
- Objectif : rester simple côté Symfony (EntityType) tout en supportant des centaines de tags.

Commit:
- ui(post): tag picker with search (Tom Select) – manageable with large tag lists

## 2025-09-07 — Sprint 8

- Fix pagination /blog : doublons entre pages corrigés
→ DISTINCT côté DQL, COUNT(DISTINCT p.id) pour le total, ORDER BY publishedAt DESC, id DESC.

- Amélioration sélection des tags (Back-office) :
- Tom Select + recherche asynchrone (endpoint JSON), multi-sélection ergonomique.

- UI /blog : les liens de pagination préservent tous les filtres (q, category, tag).

- Divers : prévisualisation cover (JS) gardée, sans bloquer l’édition.

- Commits:

- fix(blog): pagination stable (DISTINCT + COUNT DISTINCT + ORDER BY)

- feat(bo/tags): sélecteur de tags Tom Select avec recherche async

- ui(blog): conserve les filtres dans la pagination

-docs: note d’avancement

## 2025-09-07 — Dashboard (mini)
- Dashboard admin enrichi :
    - Compteurs (articles, catégories, tags, commentaires)
    - Derniers articles publiés (5) + liens Voir/Éditer
    - Commentaires en attente (5) + Approuver/Voir
    - Top catégories (par nb d’articles publiés)
      Commit: feat(admin): dashboard enrichi (derniers posts, pending comments, top catégories)

- Tableau de bord : graphiques Chart.js (posts/mois, balises top). Requêtes compatibles SQLite ( strftime).

- Modération : actions rapides « Approuver » (et option « Supprimer » possible).

## 2025-09-07 — Admin pagination
- Back-office Articles (/post) : ajout d’une pagination serveur + compteur “N articles — page X/Y”.
- Conserve les paramètres `page` et `size`, liens accessibles (Précédent/Suivant).
 - Commit: feat(bo/posts): pagination de la liste + compteur résultats (page X/Y)

2025-09-07 — Sprint UI/UX
Thème adouci (Inter + Oswald), navbar en dégradé + bordure plus sombre

Boutons arrondis (dont “Lire”), couleurs moins agressives

Cartes : zoom léger de la cover au survol, ombres plus subtiles

Badges & breadcrumbs en violet secondaire (brand-alt)

Reveal CSS/JS (respecte prefers-reduced-motion)

README mis à jour (installation, URLs, choix, limites)


# Notes / backlog – MangaBlog

## ✅ Fait
- Hero page d’accueil : image = cover du dernier article publié, fallback `public/img/hero-cover.jpg`.
- Sélecteur d’image hero dans Twig : évite la regex, test par `starts with 'http'`.
- Section “Catégories populaires” sous forme de cartes (icône + compteur).
- Section “En tendance 🔥” (carousel) basée sur nb de commentaires approuvés.
- Blog index : badges plus lisibles, bouton “Lire” adouci, fil d’ariane.
- Navbar : dégradé léger + fine bordure basse, hover propre.
- Fix `CommentController::toggle()` : variable `$request` correctement injectée.

## 🧩 À envisager (post-soutenance / améliorations)
- Upload de cover (UX Dropzone) en plus de l’URL (garder l’URL comme fallback).
- Carrousel d’images en accueil (Swiper/Bootstrap) pour “À découvrir”.
- Nuage de tags plus visuel (tailles pondérées par fréquence).
- Module newsletter (formulaire simple + stockage).
- Accessibilité: contrastes, `aria-label`, focus visibles.
- Tests fonctionnels minimes (homepage, blog list, ajout commentaire).

## ℹ️ Tech/ops
- Ne pas relancer `doctrine:fixtures:load` en prod (écrase le contenu).
- Images: mettre le fallback dans `public/img/hero-cover.jpg`.
- Chemins Twig : toujours relatifs à `/public` + `asset()`.
- Déploiement prod: `composer install --no-dev --optimize-autoloader && bin/console cache:warmup`.

✨ Nouvelles fonctionnalités

Profil utilisateur : prénom, nom, bio, avatarUrl (URL absolue ou chemin relatif).
Fallback automatique Gravatar si aucun avatar.

Menu utilisateur : affiche displayName (prénom+nom sinon préfixe d’email) + accès rapide au profil.

Page d’accueil : Hero immersif (cover dynamique avec fallback), cartes “Catégories populaires”.

Section En tendance : carousel basé sur le volume de commentaires approuvés récents.

Blog : cartes modernisées, badges tags cliquables, estimation temps de lecture.

🛠 Corrections / Technique

Comments : fix de l’Undefined $request dans l’action d’approbation.

Robustesse affichage : compatibilité legacy avatar ↔ avatarUrl.

Accessibilité & perf : lazy images, alt cohérents.

🎨 UI/UX

Navbar plus douce (bordure + hover), boutons “Lire” arrondis.

Effet reveal on scroll (désactivé si prefers-reduced-motion).

Breadcrumb propre.

Upload avatar (fichier → /public/uploads/avatars, crop 256×256).

Page “Mes articles” pour l’auteur connecté (pagination, actions).

Accueil enrichi : section “À découvrir”.

Implémentations légères, sans bundle lourd (OK pour un rendu d’examen), possibilité d’ajouter Symfony UX Dropzone plus tard pour le drag & drop.
Fix : lien “Mes articles” dans le menu profil → utilise la route app_profile_app_my_posts (préfixe de nom de route du contrôleur).

## 2025-09-06 — Branding & thème

- Identité & logo
    - Nouveau logo SVG “Otaku Eye × Enso × Kanji” (sparkle animée, lignes de vitesse, sceau 神).
    - Partial Twig réutilisable: `templates/_partials/logo_mix.svg.twig`.
    - Variables CSS pour la taille : `--logo-h` (header) / `--footer-logo-h` (footer).

- Navbar & Header
    - Dégradé `--nav-from` → `--nav-to`, logo en contraste (en blanc).
    - Boutons pill Accueil/Blog (états filled/outline harmonisés).
    - Zone utilisateur: avatar (gravatar/URL), `displayName`, dropdown (Profil, Modifier, Écrire, Logout).
    - Ombre au scroll.

- Thème CSS
    - Palette: `--brand`, `--brand-600`, `--brand-700`, `--brand-alt`, `--ink`, `--muted`, `--bg`, `--card`.
    - Hero: dégradé radial doux + bordure subtile.
    - Cards: arrondis, ombres, zoom léger des covers au hover.
    - Badges/tags “chips” lisibles (hover/cohérence couleurs).
    - Fil d’Ariane léger (diviseur ›).
    - Pagination arrondie, colorée.
    - Tag cloud stylé.
    - Avatars utilitaires (.avatar-sm, .avatar-xxl, fallback initiales).
    - Micro-UX: reveal au scroll, transitions globales.

- Footer
    - Logo + wordmark, liens rapides, “Made with ❤️ by Shabadine”.
    - Prévu pour `.footer-dark` (logo passe en blanc).

- Utilitaires
    - Autohide alertes.
    - Preview dynamique de cover (URL http/https).
    - Fonts: Oswald (titres), Inter (texte), Dancing Script (citations).

> Où toucher quoi :
> - Taille logo : `--logo-h`, `--footer-logo-h` (dans `app.css`).
> - Couleur logo en header : `.navbar .brand-logo { --ink:#fff; --accent:#fff; }`.
> - Couleurs du thème : `:root { --brand … }` dans `app.css`.

Fix: injection repo dans Dashboard, suppression des imports erronés.

Fix: Post référence le bon repository.

Feat: amélioration page article (cover 16:9, SEO, réactions, watchlist).

Feat: spoilers dans commentaires avec flou + message d’aide persistant.

Refactor: routes et templates Post admin harmonisés.
