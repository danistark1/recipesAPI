<?php

namespace App\Repository;

use App\Entity\RecipesEntity;
use App\Entity\RecipesSelectorEntity;
use App\Utils\RecipiesDateTime;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method RecipesSelectorEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecipesSelectorEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecipesSelectorEntity[]    findAll()
 * @method RecipesSelectorEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipesSelectorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecipesSelectorEntity::class);
    }

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
        $recipesSelectorEntity = new RecipesSelectorEntity();
        $recipesSelectorEntity->setName($params['name']);
        $recipesSelectorEntity->setRecipeId($params['recipeId']);

        $dt = RecipiesDateTime::dateNow('', true);
        $recipesSelectorEntity->setInsertDateTime($dt);
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($recipesSelectorEntity);
            $em->flush();
            // Try and commit the transaction
            $em->getConnection()->commit();

        } catch (ORMInvalidArgumentException | ORMException $e) {
//            $this->logger->log('test', [], Logger::CRITICAL);
        }
        return $recipesSelectorEntity->getId();

    }

    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQuery(array $params): array {
        return parent::findBy($params, [], 20);
    }

    /**
     * Delete records.
     */
    public function delete() {
        $query = $this->createQueryBuilder('e')
            ->delete()
            ->getQuery()
            ->execute();
        return $query;
    }

    // /**
    //  * @return RecipesSelectorEntity[] Returns an array of RecipesSelectorEntity objects
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
    public function findOneBySomeField($value): ?RecipesSelectorEntity
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
