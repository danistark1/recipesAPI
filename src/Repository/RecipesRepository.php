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
    /**
     * Valid recipe fields.
     */
    private const VALID_FIELDS = [
        'id',
        'name',
        'prep_time',
        'cooking_time',
        'ingredients',
        'servings',
        'category',
        'directions',
        'favourites',
        'added_by',
        'calories',
        'cuisine',
        'url'
    ];

    /**
     * Valid recipe post fields.
     */
    private const VALID_POST_FIELDS = [
        'name',
        'prep_time',
        'cooking_time',
        'ingredients',
        'servings',
        'category',
        'directions',
        'favourites',
        'added_by',
        'calories',
        'cuisine',
        'url'
    ];

    /**
     * RecipesRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, RecipesEntity::class);
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
        $recipesEntity = new RecipesEntity();
        $recipesEntity->setName($params['name']);
        $recipesEntity->setCategory($params['category']);
        $recipesEntity->setDirections($params['directions']);
        $recipesEntity->setIngredients($params['ingredients']);
        $dt = RecipiesDateTime::dateNow('', true);
        $recipesEntity->setInsertDateTime($dt);
        $recipesEntity->setFavourites($params['favourites']);
        $recipesEntity->setAddedBy($params['added_by']);
        $recipesEntity->setPrepTime($params['prep_time']);
        $recipesEntity->setCookingTime($params['cooking_time']);
        $recipesEntity->setCalories($params['calories']);
        $recipesEntity->setCuisine($params['cuisine']);
        try {
            $em->persist($recipesEntity);
        } catch (ORMInvalidArgumentException | ORMException $e) {
            //$this->logger->log('test', [], Logger::CRITICAL);
        }
        $em->flush();

        $id = $recipesEntity->getId();
        return $id;
    }

    /**
     * Update a recipe.
     *
     * @param $recipe
     * @return mixed
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateRecipe($recipe) {
        $em = $this->getEntityManager();
        $em->persist($recipe);
        $em->flush();
        return $recipe;
    }


    /**
     * Delete a record.
     *
     * @param int $id
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(int $id) {

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $result = $qb->select('p')
            ->from(RecipesEntity::class, 'p')
            ->where('p.'.'id'. '= :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
        if (!empty($result) && isset($result[0])) {
            $em->remove($result[0]);
            $em->flush();
            $deleted = true;
        } else {
            $deleted = false;
        }
        return $deleted;
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

    public function search($keyword) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $results = $qb->select('re')
            ->from(RecipesEntity::class, 're')
            ->where($qb->expr()->like('re.name', ':name'))
            ->orwhere($qb->expr()->like('re.directions', ':directions'))
            ->orwhere($qb->expr()->like('re.category', ':category'))
            ->orwhere($qb->expr()->like('re.cuisine', ':cuisine'))
            ->orwhere($qb->expr()->like('re.ingredients', ':ingredients'))
            ->setParameter('ingredients', $keyword . '%')
            ->setParameter('cuisine', $keyword . '%')
            ->setParameter('category', $keyword . '%')
            ->setParameter('directions', $keyword . '%')
            ->setParameter('name', $keyword . '%')
            ->getQuery()
            ->execute();
        return $results;
    }

    /**
     * Return valid recipe fields.
     *
     * @return string[]
     */
    public function getValidFields(): array {
        return self::VALID_FIELDS;
    }

    /**
     * Return valid post recipe fields.
     *
     * @return string[]
     */
    public function getValidPostFields(): array {
        return self::VALID_POST_FIELDS;
    }

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
