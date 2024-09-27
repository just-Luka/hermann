<?php

namespace App\Repository;

use App\Entity\CapitalSecurity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CapitalSecurity>
 */
class CapitalSecurityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CapitalSecurity::class);
    }

    //    /**
    //     * @return CapitalSecurity[] Returns an array of CapitalSecurity objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CapitalSecurity
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Get the most recent CapitalSecurity entity.
     */
    public function findLatest(): ?CapitalSecurity
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.created_at', 'DESC') // Change 'createdAt' to the relevant field
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
