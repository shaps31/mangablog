# MangaBlog — Symfony
Blog d’actus & critiques manga (Symfony 7, Bootstrap, SQLite).

Petit blog manga : articles, catégories, tags, commentaires avec modération, pagination, filtres…  
**Back-office** léger + **Dashboard** (stats, mini-graphes) + **UX propre** (prévisualisation cover, Tom Select multi-tags, etc.).

## 🔧 Installation rapide

```bash
git clone <repo>
cd mangablog
composer install

# Démarrer le serveur de dev
symfony server:start -d
# ou php -S 127.0.0.1:8001 -t public
