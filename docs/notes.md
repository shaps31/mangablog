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
-“Correction filtres category/tag (+ pagination) – Bug ‘variable inexistante’ corrigé.”
- Ajout d'un compteur de résultats sur /blog + bouton Réinitialiser si des filtres sont actifs.
- Objectif : meilleure UX, facile à expliquer (Twig uniquement).

