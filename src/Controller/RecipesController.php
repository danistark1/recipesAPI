<?php

namespace App\Controller;

use App\Entity\RecipesEntity;
use App\Repository\RecipesRepository;
use App\RecipesLogger;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use App\Kernel;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

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
    const VALIDATION_INVALID_SEARCH_QUERY = "Invalid search query provided, query should be search?q=";

    const CATEGORY_dessert = 'dessert';
    const CATEGORY_SALAD = 'salad';
    const CATEGORY_MAIN_DISH = 'main dish';
    const CATEGORY_BREAKFAST = 'breakfast';

    public static $categories = [];

    /** @var RecipesLogger  */
    private $logger;

    /** @var Response  */
    private $response;

    /** @var Request  */
    private $request;

    /** @var RecipesRepository|null  */
    private $recipesRepository;

    /** @var float|string Capture response execution time */
    private $time_start;

    /** @var Serializer  */
    private $serializer;

    /**
     * SensorController constructor.
     *
     * @param RecipesRepository|null $recipesRepository
     * @param RecipesLogger $logger
     * @param ObjectNormalizer $objectNormalizer
     */
    public function __construct(
        RecipesRepository $recipesRepository,
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
        $this->response->headers->set('recipes-api-version', "1.0");
        $this->time_start = microtime(true);
    }

    /**
     * Get all recipes.
     * 
     * @Route("/recipes", name="get_all_recipies")
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
        $results = $this->recipesRepository->findByQuery(['name' => $name]);
        $this->normalize($results);
        $this->validateResponse($results, $name);
        $this->updateResponseHeader();
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
    private function getByIdInternal($id) {
        $results = $this->recipesRepository->findByQuery(['id' => $id]);
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
    public function delete($id) {
        $result = $this->recipesRepository->delete($id);
        if (!$result) {
            $this->response->setStatusCode(self::STATUS_NOT_FOUND);
        } else {
            $this->response->setStatusCode(self::STATUS_OK);
        }
        $this->updateResponseHeader();
        return $this->response;

    }

    /**
     * Update a recipe.
     *
     * @Route("recipes/update/{id}", methods={"PUT"}, name="update_recipe")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function put($id, Request $request): Response {
        // Update Fields.
        //name, prep_time, cooking_time, category, directions, ingredients, favourites, calories, cuisine, url

        $recipe = $this->recipesRepository->findOneBy(['id' => $id]);
        if (!empty($recipe)) {
            $data = json_decode($request->getContent(), true);
            $data = $this->normalizeData($data);
            empty($data['name']) ? true : $recipe->setName($data['name']);
            empty($data['prep_time']) ? true : $recipe->setPrepTime($data['prep_time']);
            empty($data['cooking_time']) ? true : $recipe->setCookingTime($data['cooking_time']);
            empty($data['category']) ? true : $recipe->setCategory($data['category']);
            empty($data['directions']) ? true : $recipe->setDirections($data['directions']);
            empty($data['ingredients']) ? true : $recipe->setIngredients($data['ingredients']);
            empty($data['favourites']) ? true : $recipe->setFavourites($data['favourites']);
            empty($data['calories']) ? true : $recipe->setCalories($data['calories']);
            empty($data['cuisine']) ? true : $recipe->setCookingTime($data['cooking_time']);
            empty($data['url']) ? true : $recipe->setCookingTime($data['url']);
            $validFields = $this->validateRecipeFields($data, 'POST');
            $valid = true;
            if (!empty($data['category'])) {
                $valid = $this->validateCategory($data['category']);
            }
            if ($valid && $validFields) {
                $updatedRecipe = $this->recipesRepository->updateRecipe($recipe);
                if ($updatedRecipe instanceof RecipesEntity) {
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
        return $this->response;
    }

    /**
     * Get recipe with a condition.
     * ex. recipes/where?id=1
     *
     * @param Request $request
     * @return Response
     * @Route("recipes/where", methods={"GET"}, name="get_where")
     */
    public function getWhere(Request $request): Response {
        $params = $request->query->all();
        $params = array_change_key_case ($params, CASE_LOWER );
        $valid = $this->validateRecipeFields($params);
        if ($valid) {
            $results = $this->recipesRepository->findByQuery($params);
            if (!empty($results)) {
                $this->normalize($results);
            }
            $this->validateResponse($results);
            $this->updateResponseHeader();
        } else {
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
        }
        return $this->response;
    }

    /**
     * Right wildcard search for a recipe.
     * Ex. - GET /recipes/api/search?q=pizza
     *     - GET /recipes/api/search?q=main dish
     *
     * @param Request $request
     * @param string $keyword
     * @return Response
     * @Route("recipes/search", methods={"GET"}, name="get_search")
     */
    public function getSearch(Request $request): Response {
        $query = $request->getQueryString();
        dump($query);
        $keyword = explode('=', $query);
        $result = str_replace('%20',' ', $keyword[1]);
        $filter = array_search('filter', $keyword);
        dump($filter);
        dump($keyword);
        if (isset($keyword[1])) {
            $keyword[1]= $result;
            $results = $this->recipesRepository->search($keyword[1]);
            if (!empty($results)) {
                $this->normalize($results);
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
     * @param array $result
     * @param string $recipeIdentifier
     */
    private function validateResponse(array $result, $recipeIdentifier = '') {
        $responseJson = !empty($result) ? $this->serializer->serialize($result, 'json') : [];
        if (empty($responseJson)) {
            $this->response->setStatusCode(self::STATUS_NOT_FOUND);

            $this->logger->log(self::VALIDATION_NO_RECORD, ['id' => $recipeIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($responseJson);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Post a recipe.
     *
     * @Route("recipes",  methods={"POST"}, name="post")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function post(Request $request): Response {
        // turn request data into an array
        $parameters = json_decode($request->getContent(), true);
        $parameters = $this->normalizeData($parameters);
        $validPostFields = $this->validateRecipeFields($parameters, 'POST');
        $valid = false;
        if ($parameters && is_array($parameters) && $validPostFields) {
            $valid = $this->validateRequiredFields($parameters, __CLASS__.__FUNCTION__);
        }
        if ($valid) {
            $recipeID = $this->recipesRepository->save($parameters);
            $this->response->setStatusCode(self::STATUS_OK);
            // Return posted data back.(use get to normalize ingredients array).
            $this->getByIdInternal($recipeID);
            } else {
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            }
        $this->updateResponseHeader();
        return $this->response;
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
    }

    /**
     * Normalize Post data
     *
     * @param array $parameters
     * @return array Normalized Data
     */
    private function normalizeData(array $parameters): array {
        $normalizedData = [];
        foreach($parameters as $param => $value) {
            $normalizedData += [strtolower($param) => $value];
        }
        $normalizedData['favourites'] = $normalizedData['favourites'] ?? 0;
        $normalizedData['added_by'] = $normalizedData['added_by'] ?? '';
        $normalizedData['prep_time'] = $normalizedData['prep_time'] ?? null;
        $normalizedData['cooking_time'] = $normalizedData['cooking_time'] ?? null;
        $normalizedData['calories'] = $normalizedData['calories'] ?? null;
        $normalizedData['cuisine'] = $normalizedData['cuisine'] ?? '';
        $normalizedData['url'] = $normalizedData['url'] ?? '';
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
        $category = strtolower($category);
        if (empty(self::$categories)) {
            self::$categories = [self::CATEGORY_BREAKFAST, self::CATEGORY_dessert, self::CATEGORY_SALAD, self::CATEGORY_MAIN_DISH];
        }
        return in_array($category, self::$categories);
    }
}
