<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    // Recherche les articles publiés, avec filtres optionnels :
    // - $q : mot-clé à chercher dans le titre ou le contenu
    // - $categoryId : limiter à une catégorie précise
    public function searchPublished(?string $q = null, ?int $categoryId = null): array
    {
        // On prépare une requête de base sur les posts (alias "p")
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c') // on relie Post à Category pour pouvoir filtrer
            ->where('p.status = :status')                 // seulement les articles publiés
            ->setParameter('status', 'published')
            ->orderBy('p.publishedAt', 'DESC');           // tri du plus récent au plus ancien

        // Si un mot-clé ($q) est fourni,
        // on filtre en cherchant ce mot dans le titre OU le contenu
        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%');           // % = recherche partielle (contient le mot)
        }

        // Si un id de catégorie est fourni ($categoryId),
        // on limite les résultats à cette catégorie
        if ($categoryId) {
            $qb->andWhere('c.id = :cat')
                ->setParameter('cat', $categoryId);
        }

        // On exécute la requête et on récupère tous les résultats
        return $qb->getQuery()->getResult();
    }


    // Compte le nombre total de posts publiés
    // entre deux dates données ($from et $to).
    public function countPublishedBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('p')   // "p" = alias pour Post
        ->select('COUNT(p.id)')                   // on veut juste le nombre de posts
        ->where('p.status = :status')             // condition : seulement les "published"
        ->andWhere('p.publishedAt BETWEEN :from AND :to') // condition : date de publication entre les 2 dates
        ->setParameter('status', 'published')     // valeur du paramètre status
        ->setParameter('from', $from)             // date début
        ->setParameter('to', $to)                 // date fin
        ->getQuery()                              // on génère la requête SQL
        ->getSingleScalarResult();                // on récupère UN nombre (COUNT)
    }

    // Compte le nombre de posts publiés par catégorie
    // entre deux dates données ($from et $to).
    public function countByCategoryBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('p')          // "p" = Post
        ->select('c.name AS category, COUNT(p.id) AS total') // on veut le nom de la catégorie + le nombre de posts
        ->leftJoin('p.category', 'c')              // on relie Post à sa Category
        ->where('p.status = :status')              // uniquement les "published"
        ->andWhere('p.publishedAt BETWEEN :from AND :to') // publiés entre les 2 dates
        ->setParameter('status', 'published')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('c.id')                          // on groupe par catégorie
            ->orderBy('total', 'DESC')                 // on trie du + grand au + petit
            ->getQuery()
            ->getArrayResult();                        // on récupère un tableau
    }

    // Recherche les articles publiés avec filtres (mot-clé + catégorie)
    // mais en ajoutant la pagination (on divise les résultats en pages).
       /**
     * Recherche paginée des articles publiés.
     *
     * @param string|null $q         Mot-clé à chercher (dans titre ou contenu)
     * @param int|null    $categoryId Id de la catégorie à filtrer (facultatif)
     * @param int         $page       Numéro de la page demandée (>=1)
     * @param int         $perPage    Nombre d’articles par page (par défaut 5)
     *
     * @return array{
     *   items: array,   // les articles de la page courante
     *   total: int,     // nombre total d’articles trouvés
     *   page: int,      // numéro de la page actuelle
     *   pages: int,     // nombre total de pages
     *   perPage: int    // nombre d’articles par page
     * }
     */
    public function searchPublishedPaginated(?string $q, ?int $categoryId, int $page, int $perPage = 5): array
    {
        // On s’assure que la page est au minimum 1
        $page = max(1, $page);

        // 1) Base de la requête : on sélectionne uniquement les articles publiés
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c') // on joint la catégorie
            ->where('p.status = :status')
            ->setParameter('status', 'published');

        // Si un mot-clé est fourni, on cherche dans le titre OU le contenu
        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%'); // % = recherche "contient"
        }

        // Si une catégorie est précisée, on ajoute un filtre sur son id
        if ($categoryId) {
            $qb->andWhere('c.id = :cat')
                ->setParameter('cat', $categoryId);
        }

        // 2) Compter le nombre total de résultats (COUNT)
        // On clone le QueryBuilder pour réutiliser les mêmes filtres
        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // 3) Récupérer uniquement les résultats de la page demandée
        $items = $qb->orderBy('p.publishedAt', 'DESC')   // tri du + récent au + ancien
        ->setFirstResult(($page - 1) * $perPage)     // OFFSET (ex: page 2 → on saute les 5 premiers)
        ->setMaxResults($perPage)                    // LIMIT (combien d’articles max par page)
        ->getQuery()
            ->getResult();

        // Nombre total de pages (arrondi vers le haut)
        $pages = (int) max(1, ceil($total / $perPage));

        // On renvoie toutes les infos utiles pour l’affichage
        return [
            'items'   => $items,   // articles de la page actuelle
            'total'   => $total,   // combien au total
            'page'    => $page,    // page actuelle
            'pages'   => $pages,   // combien de pages en tout
            'perPage' => $perPage, // combien d’articles par page
        ];
    }

    /**
     * Articles publiés pour l'export CSV.
     *
     * Cette méthode récupère tous les articles publiés,
     * avec leur catégorie et leur auteur,
     * pour préparer une exportation (ex: CSV ou Excel).
     *
     * @return array Liste d’objets Post avec relations chargées (catégorie + auteur)
     */
    public function findPublishedForExport(): array
    {
        return $this->createQueryBuilder('p')          // on part de l’entité Post (alias "p")
        ->leftJoin('p.category', 'c')->addSelect('c') // on ajoute la relation "category" (jointure)
        ->leftJoin('p.author', 'a')->addSelect('a')   // on ajoute aussi la relation "author"
        ->where('p.status = :st')                     // on ne prend que les articles publiés
        ->setParameter('st', 'published')
            ->orderBy('p.publishedAt', 'DESC')            // tri du plus récent au plus ancien
            ->getQuery()
            ->getResult();                                // on récupère tous les résultats
    }







}
