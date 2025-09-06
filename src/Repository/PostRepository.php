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

    /**
     * Recherche des articles publiés avec filtres optionnels.
     *
     * @param string $q         Mot-clé pour chercher dans le titre ou le contenu (vide = pas de filtre)
     * @param int    $categoryId Id de la catégorie à filtrer (0 = pas de filtre)
     * @param int    $tagId      Id du tag à filtrer (0 = pas de filtre)
     *
     * @return array Liste des articles trouvés
     */
    public function searchPublished(string $q = '', int $categoryId = 0, int $tagId = 0): array
    {
        // On commence une requête sur Post (alias "p")
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c') // jointure pour récupérer la catégorie
            ->leftJoin('p.tags', 't')->addSelect('t')     // jointure pour récupérer les tags
            ->andWhere('p.status = :pub')                 // on ne garde que les articles publiés
            ->setParameter('pub', 'published')
            ->orderBy('p.publishedAt', 'DESC');           // tri : les plus récents d’abord

        // Si on a un mot-clé, on cherche dans le titre ou le contenu
        if ($q !== '') {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%'); // % = recherche "contient"
        }

        // Si on a une catégorie précise, on filtre par son id
        if ($categoryId > 0) {
            $qb->andWhere('c.id = :cid')
                ->setParameter('cid', $categoryId);
        }

        // Si on a un tag précis, on filtre aussi par son id
        if ($tagId > 0) {
            $qb->andWhere('t.id = :tid')
                ->setParameter('tid', $tagId);
        }

        // On exécute la requête et on renvoie la liste d’articles
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

    /**
     * Recherche paginée des articles publiés avec filtres.
     *
     * @param string|null $q         Mot-clé (recherche dans titre ou contenu)
     * @param int|null    $categoryId Id de la catégorie (null = pas de filtre)
     * @param int|null    $tagId      Id du tag (null = pas de filtre)
     * @param int         $page       Numéro de page (commence à 1)
     * @param int         $perPage    Nombre d’articles par page (par défaut 5)
     *
     * @return array{
     *   items: array,   // liste des articles de la page courante
     *   total: int,     // nombre total d’articles trouvés
     *   page: int,      // page actuelle
     *   perPage: int    // combien d’articles par page
     * }
     */
    public function searchPublishedPaginated(
        ?string $q,
        ?int $categoryId,
        ?int $tagId,
        int $page,
        int $perPage = 5
    ): array {
        // 1) Construire la requête de base (tous les articles publiés, sans pagination)
        $baseQb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c') // jointure catégorie
            ->leftJoin('p.tags', 't')->addSelect('t')     // jointure tags
            ->andWhere('p.status = :pub')                 // uniquement "published"
            ->setParameter('pub', 'published');

        // Filtre par mot-clé (titre ou contenu)
        if ($q) {
            $baseQb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        // Filtre par catégorie
        if ($categoryId) {
            $baseQb->andWhere('c.id = :cid')
                ->setParameter('cid', $categoryId);
        }

        // Filtre par tag
        if ($tagId) {
            $baseQb->andWhere('t.id = :tid')
                ->setParameter('tid', $tagId);
        }

        // 2) Requête de comptage (total d’articles correspondant aux filtres)
        $countQb = clone $baseQb;
        $total = (int) $countQb->select('COUNT(DISTINCT p.id)') // DISTINCT car jointures peuvent dupliquer
        ->resetDQLPart('orderBy') // on enlève le tri, inutile pour COUNT
        ->getQuery()
            ->getSingleScalarResult();

        // 3) Requête finale pour récupérer uniquement la page demandée
        $items = (clone $baseQb)
            ->orderBy('p.publishedAt', 'DESC')            // tri : les plus récents en premier
            ->setFirstResult(max(0, $page - 1) * $perPage) // OFFSET : où commencer
            ->setMaxResults($perPage)                      // LIMIT : combien d’articles max
            ->getQuery()
            ->getResult();

        // 4) On retourne les infos utiles au contrôleur/vue
        return [
            'items'   => $items,   // les articles de cette page
            'total'   => $total,   // nombre total de résultats trouvés
            'page'    => $page,    // la page actuelle
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
