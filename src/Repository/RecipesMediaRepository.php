<?php

namespace App\Repository;

use App\Entity\RecipesMediaEntity;
use App\Utils\RecipiesDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RecipesMediaEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecipesMediaEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecipesMediaEntity[]    findAll()
 * @method RecipesMediaEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipesMediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipesMediaEntity::class);
    }

    // /**
    //  * @return RecipesMedia[] Returns an array of RecipesMedia objects
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
    /**
     * Save Recipe record to the database.
     *
     * @param array $params Post data.
     * @return integer $id is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save(array $params) {
        $em = $this->getEntityManager();
        $recipesMediaEntity = new RecipesMediaEntity();
        $recipesMediaEntity->setName($params['name']);
        $recipesMediaEntity->setImageHeight($params['imageHeight'] ?? null);
        $recipesMediaEntity->setImageWidth($params['imageWidth'] ?? null);
        $recipesMediaEntity->setPath($params['path']);
        $recipesMediaEntity->setType($params['type']);
        $recipesMediaEntity->setSize($params['size']);
        $recipesMediaEntity->setForeignID($params['foreignID']);
        $recipesMediaEntity->setForeignTable($params['foreignTable']);
        //$recipesMediaEntity->setInsertUserID();
        $dt = RecipiesDateTime::dateNow('');
        $recipesMediaEntity->setInsertDateTime($dt);

        $em->getConnection()->beginTransaction();
        try {
            $em->persist($recipesMediaEntity);
            $em->flush();
            // Try and commit the transaction
            $em->getConnection()->commit();

        } catch (ORMInvalidArgumentException | ORMException $e) {
//            $this->logger->log('test', [], Logger::CRITICAL);
        }
        $id = $recipesMediaEntity->getId();
        return $id;
    }

    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQuery(array $params): array {
        $recipeMediaData = parent::findBy($params,[], 1);
        return $recipeMediaData;
    }

    /*
    public function findOneBySomeField($value): ?RecipesMedia
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
