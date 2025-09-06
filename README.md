# MangaBlog

Blog d’actus & critiques manga (Symfony 7, Bootstrap, SQLite).

## Lancer le projet
- `composer install`
- `php -d opcache.enable_cli=0 bin/console doctrine:database:create`
- `symfony server:start -d`

## Sprints
- Sprint 0 : Bootstrap (layout + accueil) 
- Sprint 1 : Auth (register/login, rôles, routes protégées)
- Sprint 2 : Entités (Post, Category, Tag, Comment) + migrations
- Sprint 3 : CRUD admin + validations
- Sprint 4 : Liste publique + filtres + totaux
- Sprint 5 : Dashboard + bonus (Chart.js, export CSV)
