<?php

namespace App\Repository;

use App\Entity\Watch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Watch::class); }

    /** @return int[] post ids */
    public function findPostIdsForUser(int $userId): array
    {
        $rows = $this->createQueryBuilder('w')
            ->select('IDENTITY(w.post) AS id')
            ->where('w.user = :u')->setParameter('u', $userId)
            ->getQuery()->getArrayResult();

        return array_column($rows, 'id');
    }
}
