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
     * Recherche des articles publiés avec filtres optionnels (liste complète, non paginée).
     *
     * @param string $q          Mot-clé (titre/contenu). Vide = pas de filtre
     * @param int    $categoryId Id de catégorie. 0 = pas de filtre
     * @param int    $tagId      Id de tag. 0 = pas de filtre
     *
     * @return Post[]
     */
    public function searchPublished(string $q = '', int $categoryId = 0, int $tagId = 0): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.tags', 't')->addSelect('t')
            ->andWhere('p.status = :pub')->setParameter('pub', 'published')
            ->orderBy('p.publishedAt', 'DESC');

        if ($q !== '') {
            // NOTE : la plupart des DB sont déjà case-insensitive avec LIKE.
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        if ($categoryId > 0) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', $categoryId);
        }

        if ($tagId > 0) {
            $qb->andWhere('t.id = :tid')->setParameter('tid', $tagId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre d’articles publiés entre 2 dates.
     */
    public function countPublishedBetween(\DateTimeInterface $from, \DateTimeInterface $to): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->andWhere('p.publishedAt BETWEEN :from AND :to')
            ->setParameter('status', 'published')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre d’articles publiés par catégorie entre 2 dates.
     * @return array[] ex: [['category' => 'Actus', 'total' => 5], ...]
     */
    public function countByCategoryBetween(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('p')
            ->select('c.name AS category, COUNT(p.id) AS total')
            ->leftJoin('p.category', 'c')
            ->where('p.status = :status')
            ->andWhere('p.publishedAt BETWEEN :from AND :to')
            ->setParameter('status', 'published')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('c.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Recherche **paginée** des articles publiés avec filtres.
     *
     * @param string|null $q          Mot-clé (recherche titre/contenu)
     * @param int|null    $categoryId Id de catégorie (null = pas de filtre)
     * @param int|null    $tagId      Id de tag (null = pas de filtre)
     * @param int         $page       Page courante (1..N)
     * @param int         $perPage    Nombre d’articles par page
     *
     * @return array{
     *   items: array,
     *   total: int,
     *   page: int,
     *   perPage: int,
     *   pages: int
     * }
     */

    public function searchPublishedPaginated(
        ?string $q,
        ?int $categoryId,
        ?int $tagId,
        int $page,
        int $perPage = 5
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :published')
            ->setParameter('published', 'published');

        if ($q) {
            $qb->andWhere('(LOWER(p.title) LIKE :q OR LOWER(p.content) LIKE :q)')
                ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        if ($categoryId) {
            $qb->andWhere('p.category = :cid')->setParameter('cid', $categoryId);
        }

        if ($tagId) {
            // JOIN uniquement pour filtrer, sans fetch-join ni addSelect()
            $qb->join('p.tags', 't')->andWhere('t.id = :tid')->setParameter('tid', $tagId);
        }

        // Ordre stable pour la pagination
        $qb->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->distinct(); // très important quand il y a des JOIN

        // Total AVANT pagination
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT p.id)')->getQuery()->getSingleScalarResult();

        // Résultats paginés
        $offset = max(0, ($page - 1) * $perPage);
        $items = $qb->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $perPage),
        ];
    }


    /**
     * Articles publiés pour l’export (avec catégorie & auteur).
     *
     * @return Post[]
     */
    public function findPublishedForExport(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->where('p.status = :st')->setParameter('st', 'published')
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRelated(Post $post, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.status = :s')->setParameter('s', 'published')
            ->andWhere('p != :post')->setParameter('post', $post)
            ->setMaxResults($limit);

        if ($post->getTags()->count() > 0) {
            $qb->leftJoin('p.tags', 't')
                ->andWhere('p.category = :cat OR t IN (:tags)')
                ->setParameter('cat', $post->getCategory())
                ->setParameter('tags', $post->getTags())
                ->groupBy('p.id')
                ->addOrderBy('COUNT(t)', 'DESC')
                ->addOrderBy('p.publishedAt', 'DESC');
        } else {
            $qb->andWhere('p.category = :cat')->setParameter('cat', $post->getCategory())
                ->orderBy('p.publishedAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
    public function findPublishedHot(string $q=null, ?int $catId=null, ?int $tagId=null, int $page=1, int $perPage=12): array
    {
        $since = new \DateTimeImmutable('-30 days');

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.comments','c','WITH','c.status = :ok AND c.createdAt >= :since')
            ->leftJoin('p.tags','t')
            ->leftJoin('App\Entity\Reaction','r','WITH','r.post = p AND r.createdAt >= :since')
            ->addSelect('(COUNT(DISTINCT r.id) + COUNT(DISTINCT c.id)) AS HIDDEN score')
            ->andWhere('p.status = :pub')
            ->setParameter('pub','published')
            ->setParameter('ok','approved')
            ->setParameter('since',$since);

        if ($q)     { $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')->setParameter('q','%'.$q.'%'); }
        if ($catId) { $qb->andWhere('p.category = :cat')->setParameter('cat',$catId); }
        if ($tagId) { $qb->andWhere('t.id = :tag')->setParameter('tag',$tagId); }

        $qb->groupBy('p.id')
            ->orderBy('score','DESC')
            ->addOrderBy('p.publishedAt','DESC');

        // total
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()->getSingleScalarResult();

        $items = $qb->setFirstResult(($page-1)*$perPage)
            ->setMaxResults($perPage)
            ->getQuery()->getResult();

        return ['items'=>$items,'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/$perPage))];
    }

    public function relatedByTags(Post $post, int $limit = 4): array
    {
        // Récupère les id des tags de l’article courant
        $tagIds = array_map(fn($t) => $t->getId(), $post->getTags()->toArray());

        // Pas de tag ? on propose juste les plus récents (autres posts)
        if (!$tagIds) {
            return $this->createQueryBuilder('p')
                ->where('p != :post')
                ->setParameter('post', $post)
                ->orderBy('p.publishedAt', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()->getResult();
        }

        return $this->createQueryBuilder('p')
            ->innerJoin('p.tags', 't')
            ->andWhere('p != :post')
            ->andWhere('t.id IN (:tagIds)')
            ->setParameter('post', $post)
            ->setParameter('tagIds', $tagIds)
            ->groupBy('p.id')
            // d’abord ceux qui partagent le plus de tags, puis les plus récents
            ->orderBy('COUNT(t.id)', 'DESC')
            ->addOrderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
    #[Route('/rss.xml', name: 'rss')]
    public function rss(PostRepository $repo): Response {
        $posts = $repo->findBy([], ['publishedAt'=>'DESC'], 20);
        return $this->render('feed/rss.xml.twig', ['posts'=>$posts]);
    }

}
