<?php

namespace App\Repository;

use App\Entity\RecipesEntity;
use App\Utils\RecipiesDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RecipesEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecipesEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecipesEntity[]    findAll()
 * @method RecipesEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipesRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, RecipesEntity::class);
    }



    /**
     * Save Recipe record to the database.
     *
     * @param array $params Post data.
     * @return bool True is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $params) {
        $em = $this->getEntityManager();
        $recipesEntity = new RecipesEntity();
        $recipesEntity->setName($params['name']);
        $recipesEntity->setCategory($params['category']);
        $recipesEntity->setDirections($params['directions']);
        $recipesEntity->setIngredients($params['ingredients']);
        $dt = RecipiesDateTime::dateNow();
        $recipesEntity->setInsertDateTime($dt);
        $recipesEntity->setFavourites($params['favourites']);
        $recipesEntity->setAddedBy($params['added_by']);
        $recipesEntity->setPrepTime($params['prep_time']);
        $recipesEntity->setCookingTime($params['cooking_time']);
        $recipesEntity->setCalories($params['calories']);
        $recipesEntity->setCuisine($params['cuisine']);
        $result = true;
        try {
            $em->persist($recipesEntity);
        } catch (ORMInvalidArgumentException | ORMException $e) {
            $result = false;
            //$this->logger->log('test', [], Logger::CRITICAL);
        }
        $em->flush();
        return $result;
    }

    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQuery(array $params): array {
        $recipeData = parent::findBy($params,[], 20);
        return $recipeData;
    }

    // /**
    //  * @return RecipesEntity[] Returns an array of RecipesEntity objects
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
    public function findOneBySomeField($value): ?RecipesEntity
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
