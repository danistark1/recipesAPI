<?php

namespace App\Controller;

use App\CategorySchema;
use App\Entity\CategoriesEntity;
use App\Entity\RecipesEntity;
use App\RecipesPostSchema;
use App\RecipesUpdateSchema;
use App\Repository\RecipesRepository;
use App\RecipesLogger;
use App\Repository\RecipesTagsRepository;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Class RecipesController
 *
 * @package App\Controller
 */
class RecipesController extends AbstractController {
    // Status Codes
    const STATUS_OK = 200;
    const STATUS_NO_CONTENT = 204;
    const STATUS_VALIDATION_FAILED = 400;
    const STATUS_NOT_FOUND = 404;
    const STATUS_EXCEPTION = 500;

    const VALIDATION_FAILED = "Validation failed.";
    const VALIDATION_NO_RECORD = "No record found.";
    const VALIDATION_STATION_PARAMS = "Invalid post parameters.";
    const VALIDATION_INVALID_SEARCH_QUERY = "Invalid search query provided, query should be search?q={searchTerm}";

    const CATEGORY_DESSERT = 'dessert';
    const CATEGORY_SALAD = 'salad';
    const CATEGORY_APPETIZER = 'appetizer';
    const CATEGORY_MAIN_DISH = 'main dish';
    const CATEGORY_HOLIDAYS = 'holidays';
    const CATEGORY_BREAKFAST = 'breakfast';
    const CATEGORY_SIDE_DISH = 'side dish';
    const CATEGORY_BEVERAGE = 'beverage';
    const CATEGORY_BREAD = 'bread';
    const CATEGORY_SOUP = 'soup';

    public static $categories = [
        self::CATEGORY_APPETIZER,
        self::CATEGORY_BEVERAGE,
        self::CATEGORY_BREAD,
        self::CATEGORY_BREAKFAST,
        self::CATEGORY_DESSERT,
        self::CATEGORY_HOLIDAYS,
        self::CATEGORY_MAIN_DISH,
        self::CATEGORY_SALAD,
        self::CATEGORY_SIDE_DISH,
        self::CATEGORY_SOUP
        ];

    /** @var RecipesLogger  */
    private $logger;

    /** @var Response  */
    private $response;

    /** @var RecipesRepository|null  */
    private $recipesRepository;

    /** @var RecipesTagsRepository  */
    private $recipesTagsRepository;

    /** @var float|string Capture response execution time */
    private $time_start;

    /** @var Serializer  */
    private $serializer;

    /**
     * SensorController constructor.
     *
     * @param RecipesRepository $recipesRepository
     * @param RecipesLogger $logger
     * @param ObjectNormalizer $objectNormalizer
     */
    public function __construct(
        RecipesRepository $recipesRepository,
        RecipesTagsRepository $recipesTagsRepository,
        RecipesLogger $logger,
        ObjectNormalizer $objectNormalizer) {
        $this->response  = new Response();

        $encoders = [new JsonEncoder()];
        $normalizers = [$objectNormalizer];

        $this->serializer = new Serializer($normalizers, $encoders);
        $this->response->headers->set('Content-Type', 'application/json');
        $this->time_start = microtime(true);
        $this->request  = new Request();
        $this->logger = $logger;
        $this->recipesRepository = $recipesRepository;
        $this->recipesTagsRepository = $recipesTagsRepository;
        $this->time_start = microtime(true);
    }

    /**
     * Get all recipes.
     * 
     * @Route("/recipes", methods={"GET", "OPTIONS"}, name="get_all_recipies")
     */
    public function index(): Response {
        //TODO Paginate.
        $results = $this->recipesRepository->findAll();
        $this->normalize($results);
        $this->validateResponse($results);
        return $this->response;
    }

    /**
     * Get recipe by name.
     *
     * @param string $name Recipe name
     * @param Request $request
     * @return Response
     * @Route("recipes/name/{name}", methods={"GET"}, name="get_by_name")
     */
    public function getByName(string $name): Response {
        $name = strtolower($name);
        $valid = !empty($name);
        if ($valid) {
            $results = $this->recipesRepository->findByQuery(['name' => $name]);
            $this->normalize($results);
            $this->validateResponse($results, $name);
        }
        $this->updateResponseHeader();
        return $this->response;
    }

    /**
     * Post a category.
     *
     * @Route("recipes/category",  methods={"POST", "OPTIONS"}, name="post_category")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function postCategory(Request $request, ValidatorInterface $validator, CategorySchema $categorySchema): Response {
        $input = (array)json_decode($request->getContent());
        $violations = $validator->validate($input, $categorySchema::$schema);
        // TODO Category Post
        $this->validateRequest($violations);
        return $this->response;
    }

    /**
     * Normalize ingredients & directions.
     *
     * @param array $results
     */
    private function normalize(array &$results) {
        foreach($results as $result) {
            $parsedIngredients = $this->parseArray($result->getIngredients());
            $parsedDirections = $this->parseArray($result->getDirections());
            $result->setIngredients($parsedIngredients);
            $result->setDirections($parsedDirections);
        }
    }

    /**
     * Get recipe by id internally.
     *
     * @param $id
     */
    private function getByIdInternal($id, $return = false) {
        $results = $this->recipesRepository->findByQuery(['id' => $id]);
        if ($return) {
            return $results;
        }
        $this->normalize($results);
        $this->validateResponse($results, $id);
    }

    /**
     * Delete a recipes.
     *
     * @Route("recipes/delete/{id}", methods={"DELETE"}, requirements={"id"="\d+"}, name="delete_recipe")
     * @param $id
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($id): Response {
        if (is_numeric($id)) {
            $result = $this->recipesRepository->delete($id);
            if (!$result) {
                $this->response->setStatusCode(self::STATUS_NOT_FOUND);
                $this->response->setContent(self::VALIDATION_NO_RECORD);
            } else {
                $this->response->setStatusCode(self::STATUS_OK);
            }
        }
        $this->updateResponseHeader();
        return $this->response;

    }

    /**
     * Toggle favourites.
     *
     * @Route("recipes/favourites/{id}", methods={"PATCH", "OPTIONS"}, name="update_recipe_favourites")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchFavourites($id, Request $request): Response {
            $recipe = $this->recipesRepository->findOneBy(['id' => $id]);
            if (!empty($recipe)) {
                $recipe = $this->recipesRepository->toggleField('Favourites', $recipe);
                if ($recipe instanceof RecipesEntity) {
                    $this->validateResponse($recipe);
                }
            } else {
                $this->validateResponse($recipe);
            }
        return $this->response;
    }

    /**
     * Toggle featured.
     *
     * @Route("recipes/featured/{id}", methods={"PATCH", "OPTIONS"}, name="update_recipe_featured")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patchFeatured($id, Request $request): Response {
        $recipe = $this->recipesRepository->findOneBy(['id' => $id]);
        if (!empty($recipe)) {
            $recipe = $this->recipesRepository->toggleField('Featured', $recipe);
            if ($recipe instanceof RecipesEntity) {
                $this->validateResponse($recipe);
            }
        } else {
            $this->validateResponse($recipe);
        }
        return $this->response;
    }

    /**
     * Update a recipe.
     *
     * @Route("recipes/update/{id}", methods={"PATCH", "OPTIONS"}, name="update_recipe")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patch($id, Request $request, ValidatorInterface $validator, RecipesUpdateSchema $recipesUpdateSchema): Response {
        $data = json_decode($request->getContent(), true);
        $violations = $validator->validate($data, $recipesUpdateSchema::$schema);
        $valid = $this->validateRequest($violations);
        if ($valid) {
            $recipe = $this->recipesRepository->findOneBy(['id' => $id]);
            if (!empty($recipe)) {
                // TODO Refactor this nonsense.
                empty($data['name']) ? true : $recipe->setName($data['name']);
                empty($data['prepTime']) ? true : $recipe->setPrepTime($data['prepTime']);
                empty($data['cookingTime']) ? true : $recipe->setCookingTime($data['cookingTime']);
                empty($data['category']) ? true : $recipe->setCategory($data['category']);
                empty($data['directions']) ? true : $recipe->setDirections($data['directions']);
                empty($data['ingredients']) ? true : $recipe->setIngredients($data['ingredients']);
                $data['favourites'] ?  $recipe->setFavourites(1): $recipe->setFavourites(0);
                empty($data['calories']) ? true : $recipe->setCalories($data['calories']);
                empty($data['cuisine']) ? true : $recipe->setCuisine($data['cuisine']);
                empty($data['addedBy']) ? true : $recipe->setAddedBy($data['addedBy']);
                empty($data['url']) ? true : $recipe->setUrl($data['url']);
                if (!empty($data['category'])) {
                    $valid = $this->validateCategory($data['category']);
                }
                if ($valid) {
                    $updatedRecipe = $this->recipesRepository->updateRecipe($recipe);
                    if (!empty($updatedRecipe)) {
                        $this->getByIdInternal($id);
                    } else {
                        $this->response->setStatusCode(self::STATUS_NO_CONTENT);
                        $this->logger->log(self::VALIDATION_FAILED, ['fields' => $updatedRecipe], Logger::ALERT);
                    }
                } else {
                    $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                    $this->logger->log(self::VALIDATION_FAILED, ['fields' => $data], Logger::ALERT);
                }
            } else {
                $this->response->setStatusCode(self::STATUS_NOT_FOUND);
            }
        }
        return $this->response;
    }

    /**
     * Get recipe with a condition.
     * ex. recipes/where?id=1
     *
     * @param Request $request
     * @return Response
     * @Route("recipes/where", methods={"GET", "OPTIONS", "HEAD"}, name="get_where")
     */
    public function getWhere(Request $request): Response {
        // TODO Validate request.
        $page = $request->get('page');
        $params = $request->query->all();
        unset($params['page']);
        $value = array_values($params);
        $key = array_keys($params);
        $query = array_merge($key, $value);

        $query['page'] = $page ?? 1;
        $params = array_change_key_case ($params, CASE_LOWER );
        $valid = $this->validateRecipeFields($params);
        if ($valid) {
            $resultsAll = $this->recipesRepository->findByQueryBuilder($query);
            $results = $resultsAll['results'] ?? [];
            $pagesCount = $resultsAll['pagesCount'] ?? 0;
            $totalItems = $resultsAll['totalItems'] ?? 0;

            if (!empty($results)) {
                $this->normalize($results);
                $this->response->headers->set('recipes-totalItems', $totalItems);
                $this->response->headers->set('recipes-pagesCount', $pagesCount);
            }
            $this->validateResponse($results);
            $this->updateResponseHeader();
        } else {
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
        }
        return $this->response;
    }

    /**
     * Left/Right wildcard search a recipe.
     * Ex. - GET /recipes/search?q=pizza&page=1&filter=category&category=Main Dish
     *
     *
     * @param Request $request
     * @param string $keyword
     * @return Response
     * @Route("recipes/search", methods={"GET", "OPTIONS", "HEAD"}, name="get_search")
     */
    public function getSearchPager(Request $request) {
        // TODO Validate request.
        $query = $request->query->get('q');
        $page = $request->query->get('page') ?? 1;
        //TODO Sanitize query remove special chars.
        $filter = $request->query->get('filter');
        $category = null;
        if ($filter) {
            $category = $request->query->get('category');
        }
        $query = str_replace('%20',' ', $query);
        if (!empty(trim($query))) {
            $filter = ($category  && $filter) ?  ['category' => $category, 'filter' => $filter] : [];
            $resultsAll = $this->recipesRepository->getSearchByPage($query, $filter, $page) ?? [];
            $results = $resultsAll['results'];
            $pagesCount = $resultsAll['pagesCount'] ?? 0;
            $totalItems = $resultsAll['totalItems'] ?? 0;
            if (!empty($results)) {
                $this->normalize($results);
                $this->response->headers->set('recipes-totalItems', $totalItems);
                $this->response->headers->set('recipes-pagesCount', $pagesCount);
            }
            $this->validateResponse($results);
            $this->updateResponseHeader();
        } else {
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->response->setContent(self::VALIDATION_INVALID_SEARCH_QUERY);
        }
        return $this->response;
    }

    /**
     * Parse string into a usable array.
     *
     * @param string $data
     * @return array
     */
    private function parseArray(string $data, $seperator = '#'): array {
        // Make sure the string doesn't start and end with '#'.
        $data = rtrim(ltrim($data, $seperator), $seperator);
        // should be received as "place in oven#mix with water"
        $data = explode($seperator, $data);
        $parsedArray = [];
        foreach($data as $item) {
            $parsedArray[] =
                 trim($item);
        }
        return $parsedArray;
    }

    /**
     * Validate API response.
     *
     * @param array|RecipesEntity $result
     * @param string $recipeIdentifier
     */
    private function validateResponse($result, $recipeIdentifier = '') {
        $response = !empty($result) ? $this->serializer->serialize($result, 'json') : '';
        if (empty($response)) {
            $this->response->setStatusCode(404);
            $this->response->headers->set('recipes-totalItems', 0);
            $this->response->headers->set('recipes-pagesCount', 0);
            $this->response->setContent(self::VALIDATION_NO_RECORD);
            $this->logger->log(self::VALIDATION_NO_RECORD, ['id' => $recipeIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($response);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Post a recipe.
     *
     * @Route("recipes",  methods={"POST", "OPTIONS"}, name="post_recipes")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function post(Request $request, ValidatorInterface $validator, RecipesPostSchema $recipesPostSchema): Response {
        $pascalEm = (array)json_decode($request->getContent(), true);
        $violations = $validator->validate($pascalEm, $recipesPostSchema::$schema);
        $valid = $this->validateRequest($violations);
        if ($valid) {
            $pascalEm = $this->normalizeRecipeData($pascalEm);
            $recipeID = $this->recipesRepository->save($pascalEm);
            if ($recipeID) {
                if (isset($pascalEm['tags'])) {
                    $recipe = $this->getByIdInternal($recipeID, true)[0];
                    $this->postTagsInternal($pascalEm['tags'], $recipe);
                }
                $this->response->setStatusCode(self::STATUS_OK);
                // Return posted data back.(use get to normalize ingredients array).
                $this->getByIdInternal($recipeID);
            } else {
                $this->response->setStatusCode(self::STATUS_EXCEPTION);
                $this->response->setContent(self::VALIDATION_NO_RECORD);
            }
            $this->updateResponseHeader();
        }
        return $this->response;
    }

    /**
     * Post recipe tags.
     *
     */
    private function postTagsInternal(array $tags, $recipe) {
        $this->recipesTagsRepository->save($tags, $recipe);
    }

    /**
     * Validate incoming request.
     *
     * @param Request $request
     * @return bool $valid If request is valid.
     */
    private function validateRequest($violations = []): bool {
        $valid = true;
        if ($violations instanceof  ConstraintViolationListInterface && $violations->count() > 0) {
            $formatedViolationList = [];
            for ($i = 0; $i < $violations->count(); $i++) {
                $violation = $violations->get($i);
                $formatedViolationList[] = array($violation->getPropertyPath() => $violation->getMessage());
            }
            $this->logger->log('Schema validation failed', $formatedViolationList, Logger::CRITICAL);
            $this->response->setContent('Schema validation failed');
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $valid = false;
        }
        return $valid;
    }

    /**
     * Validate recipe fields.
     *
     * @param array $params
     * @param string $type Type of request.
     * @return bool
     */
    private function validateRecipeFields(array $params, $type = 'GET'): bool {
        $valid = true;
        foreach($params as $key => $value) {
            if (!in_array($key, $type === 'GET' ? $this->recipesRepository->getValidFields() : $this->recipesRepository->getValidPostFields())) {
                $this->logger->log(self::VALIDATION_FAILED, ['field' => $key], self::STATUS_VALIDATION_FAILED);
                $valid = false;
                break;
            }
        }
        return $valid;
    }

    /**
     * Adds execution time to the response.
     */
    private function updateResponseHeader() {
        $time_end = microtime(true);
        $execution_time = ($time_end - $this->time_start);
        $this->response->headers->set('recipes-responseTime', $execution_time);
        // TODO Get version from config.
        $this->response->headers->set('recipeApi-version', '1.0');
    }

    /**
     * Normalize Post data
     *
     * @param array $parameters
     * @return array Normalized Data
     */
    private function normalizeRecipeData(array $parameters): array {
        // TODO this shouldn't be needed with Schema validation.
        $normalizedData = [];
        foreach($parameters as $param => $value) {
            if ($param === 'name' || $param === 'addedBy') {
                $value =  ucwords($value);
            }
            $normalizedData += [$param => $value];
        }
        $normalizedData['favourites'] = $normalizedData['favourites'] ?? 0;
        $normalizedData['addedBy'] = $normalizedData['addedBy'] ?? '';
        $normalizedData['prepTime'] = $normalizedData['prepTime'] ?? null;
        $normalizedData['cookingTime'] = $normalizedData['cookingTime'] ?? null;
        $normalizedData['calories'] = $normalizedData['calories'] ?? 'NA';
        $normalizedData['cuisine'] = $normalizedData['cuisine'] ?? '';
        $normalizedData['url'] = $normalizedData['url'] ?? '';
        $normalizedData['servings'] = $normalizedData['servings'] ?? '';
        $normalizedData['featured'] = $normalizedData['featured'] ?? false;
        return $normalizedData;
    }

    /**
     * Validate station post data.
     *
     * @param array $parameters
     * @param $sender
     * @return bool
     */
    private function validateRequiredFields(array $parameters, $sender): bool {
        // TODO This should be needed with Schema validation.
        $valid = isset($parameters['name']) && (isset($parameters['category']) && $this->validateCategory($parameters['category'])) && isset($parameters['directions']) && isset($parameters['ingredients']);
        if (!$valid) {
            $this->response->setContent(self::VALIDATION_FAILED);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $parameters['sender'] = $sender;
            $this->logger->log(self::VALIDATION_FAILED, $parameters, Logger::ALERT);
        }
        return $valid;
    }

    /**
     * Validate category.
     *
     * @param $category
     * @return bool
     */
    private function validateCategory($category): bool {
        // TODO this shoul be part of validation schema.
        $category = strtolower($category);
        return in_array($category, self::$categories);
    }
}
