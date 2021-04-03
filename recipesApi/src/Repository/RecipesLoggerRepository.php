<?php

namespace App\Repository;

use App\Entity\RecipesLoggerEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RecipesLoggerEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecipesLoggerEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecipesLoggerEntity[]    findAll()
 * @method RecipesLoggerEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipesLoggerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipesLoggerEntity::class);
    }

    // /**
    //  * @return RecipesLoggerEntity[] Returns an array of RecipesLoggerEntity objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RecipesLoggerEntity
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
