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
  Commit: feat(bo/posts): pagination de la liste + compteur résultats (page X/Y)
