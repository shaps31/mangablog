<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\WatchlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WatchlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchlistItem::class);
    }

    public function isInList(User $user, Post $post): bool
    {
        return (bool) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :u')->setParameter('u', $user)
            ->andWhere('w.post = :p')->setParameter('p', $post)
            ->getQuery()->getSingleScalarResult();
    }

    /** @return WatchlistItem[] */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->addSelect('p', 'c')
            ->join('w.post', 'p')
            ->leftJoin('p.category', 'c')
            ->andWhere('w.user = :u')->setParameter('u', $user)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
