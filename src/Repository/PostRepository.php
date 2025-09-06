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

    public function searchPublished(?string $q = null, ?int $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->where('p.status = :status')->setParameter('status', 'published')
            ->orderBy('p.publishedAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }
        if ($categoryId) {
            $qb->andWhere('c.id = :cat')->setParameter('cat', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

}
