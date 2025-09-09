<?php

namespace App\Repository;

use App\Entity\Reaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reaction::class);
    }

    public function totalsForPostIds(array $ids): array
    {
        if (!$ids) return [];
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.post) AS id, COUNT(r.id) AS total')
            ->where('r.post IN (:ids)')->setParameter('ids', $ids)
            ->groupBy('r.post')
            ->getQuery()->getArrayResult();
        return array_column($rows, 'total', 'id'); // [postId => total]
    }

}
