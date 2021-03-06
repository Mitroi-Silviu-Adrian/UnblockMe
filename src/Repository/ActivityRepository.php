<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }


    public function findByBlocker(string $licensePlate): ?array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.blocker = :val')
            ->setParameter('val', $licensePlate)
            ->getQuery()
            ->execute()
            ;
    }
    public function findByBlockee(string $licensePlate): ?array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.blockee = :val')
            ->setParameter('val', $licensePlate)
            ->getQuery()
            ->execute()
            ;
    }

// /**
    //  * @return LicensePlates[] Returns an array of Activity objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Activity
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

}
