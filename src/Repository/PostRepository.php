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



}
