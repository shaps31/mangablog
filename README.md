# MangaBlog — mini-blog Symfony (manga/anime)

Petit blog éditorial avec back-office : articles, catégories, tags, commentaires et un dashboard avec métriques + mini-graphes.

---

## ✨ Fonctionnalités

**Public**
- Liste des articles `/blog` avec :
    - recherche texte,
    - filtres par **catégorie** et **tag**,
    - **pagination** (conserve les filtres), indicateur **Page X / Y**,
    - **compteur** de résultats + **bouton Réinitialiser** quand filtres actifs.
- Bandeau “**Ce mois-ci**” (total publiés) + **répartition par catégorie** (badges).
- Page article :
    - cover (via URL) avec **fallback local** si vide,
    - **tags cliquables** (surbrillance du tag actif),
    - **articles de la même catégorie** en bas,
    - **commentaires** : envoi → `pending`, affichage quand `approved` (auth requis).
- **Animations douces** au scroll (reveal).

**Back-office**
- CRUD : **Post**, **Category**, **Tag**, **Comment**.
- **Sécurité** : accès restreint aux CRUD et au Dashboard.
- **Dashboard** `/admin` :
    - tuiles de **compteurs** (articles, catégories, tags, commentaires),
    - derniers articles publiés,
    - **commentaires en attente** (actions rapides : *Approuver / Voir*),
    - **Top catégories** (par nb d’articles),
    - mini-graphes (Chart.js) :
        - **articles publiés par mois** (année courante),
        - **top tags** (par nb d’articles publiés).

**Édition**
- Champ **tags** ergonomique (Tom Select) en multi-sélection.
- **Aperçu dynamique** de la cover pendant la saisie (JS simple, URL only).
- Slug auto (champ vide → sluggifié depuis le titre).
- Badge visuel **Publié / Brouillon** dans les listes.

---

## 🧰 Stack & choix techniques

- **Symfony** (PHP) + **Twig** + **Doctrine** (SQLite).
- **Bootstrap 5** (CDN) + **Tom Select** (CDN) + **Chart.js** (CDN).
- **Aucun build front** requis (pas de Webpack/Vite) — assets statiques :
    - `public/css/app.css`
    - `public/js/app.js`
- **Cover uniquement par URL** (pas d’upload de fichier pour rester dans le cadre du sujet).
- **Tom Select** : pas de création “à la volée” des tags (idée future).

---

## 🚀 Démarrage

### 1) Prérequis
- PHP 8.1+ (avec ext-sqlite3)
- Composer
- (facultatif) Symfony CLI

### 2) Dépendances
```bash
composer install
