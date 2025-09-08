# MangaBlog (Symfony)

Petit blog de mangas : actus, classements et critiques.
**Stack :** Symfony 6/7, Twig, Doctrine ORM (SQLite par défaut), Bootstrap 5, Chart.js, Tom Select.

## ✨ Fonctionnalités

- Front office :
    - Filtrage (recherche, catégories, tags), pagination
    - Tags cliquables, estimation du temps de lecture
    - Page article avec suggestions “dans la même catégorie”
    - Accueil enrichi : héros, catégories populaires, tendances, tags
- Back office :
    - Dashboard (compteurs, “posts par mois”, top tags)
    - CRUD Articles / Catégories / Tags / Commentaires
    - Modération rapide en liste (Approuver / Annuler / Supprimer)
- UX/UI :
    - Thème orange/violet, cartes modernes, animations “reveal”
    - Aperçu instantané de la couverture (URL uniquement)

## 🚀 Démarrer

```bash
# 1) Dépendances
composer install

# 2) DB (SQLite par défaut)
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n

# 3) (Optionnel) Fixtures de démo — ⚠️ écrase la base
# php bin/console doctrine:fixtures:load -n

# 4) Serveur
symfony serve -d   # ou  php -S 127.0.0.1:8000 -t public
