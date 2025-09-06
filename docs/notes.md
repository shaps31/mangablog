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
