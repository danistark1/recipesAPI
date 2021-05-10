<?php

namespace App\Repository;

use App\Entity\RecipesEntity;
use App\Entity\RecipesTags;
use App\Utils\RecipiesDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RecipesTags|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecipesTags|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecipesTags[]    findAll()
 * @method RecipesTags[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecipesTagsRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, RecipesTags::class);
    }


    /**
     * Save Recipe record to the database.
     *
     * @param  $params Post data.
     * @return integer $id is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save($tags, $params) {

        foreach($tags as $tag) {
            $em = $this->getEntityManager();
            $recipesTagsEntity = new RecipesTags();
            $recipesTagsEntity->setName($tag['name']);
            $recipesTagsEntity->setRecipe($params);
            $recipesTagsEntity->setDescription($tag['description'] ?? '');
            $dt = RecipiesDateTime::dateNow('');
            $recipesTagsEntity->setInsertDateTime($dt);
            $em->getConnection()->beginTransaction();
            try {
                $em->persist($recipesTagsEntity);
                $em->flush();
                // Try and commit the transaction
                $em->getConnection()->commit();


            } catch (ORMInvalidArgumentException | ORMException $e) {
//            $this->logger->log('test', [], Logger::CRITICAL);
            }
        }

       // $id = $recipesTagsEntity->getId();
     //   return $id;
    }

    // /**
    //  * @return RecipesTags[] Returns an array of RecipesTags objects
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
    public function findOneBySomeField($value): ?RecipesTags
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
