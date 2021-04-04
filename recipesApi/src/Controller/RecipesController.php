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
    const CATEGORY_MAIN_DISH = 'main_dish';

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
     * @Route("/recipies", name="recipies")
     */
    public function index(): Response {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/RecipiesController.php',
        ]);
    }

    /**
     * Get recipe by name.
     *
     * @param string $name Recipe name
     * @param Request $request
     * @return Response
     * @Route("recipes/api/name/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     */
    public function getByName(string $name): Response {
        $name = strtolower($name);
        $results = $this->recipesRepository->findByQuery(['name' => $name]);
        foreach($results as $key => $value) {
            $parsedIngredients = $this->normalizeIngredients($value->getIngredients());
            $value->setIngredients($parsedIngredients);
        }
        $this->validateResponse($results, $name);
        $this->updateResponseHeader();
        return $this->response;
    }

    /**
     * Get recipe by id internally.
     *
     * @param $id
     */
    private function getByIdInternal($id) {
        $results = $this->recipesRepository->findByQuery(['id' => $id]);
        foreach($results as $key => $value) {
            $parsedIngredients = $this->normalizeIngredients($value->getIngredients());
            $value->setIngredients($parsedIngredients);
        }
        $this->validateResponse($results, $id);
    }

    /**
     * Delete a recipes.
     *
     * @Route("recipes/api/delete/{id}", methods={"DELETE"}, requirements={"id"="\d+"}, name="delete_recipe")
     * @param $id
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete($id) {
        $result = $this->recipesRepository->delete($id);
        if (!$result) {
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
        } else {
            $this->response->setStatusCode(self::STATUS_OK);
        }
        $this->updateResponseHeader();
        return $this->response;

    }

    /**
     * Update a recipe.
     *
     * @Route("recipes/api/update/{id}", methods={"PUT"}, name="update_recipe")
     * @param $id
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function patch($id, Request $request): Response {
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
            $valid = true;
            if (!empty($data['category'])) {
                $valid = $this->validateCategory($data['category']);
            }
            if ($valid) {
                $updatedRecipe = $this->recipesRepository->updateRecipe($recipe);
                if ($updatedRecipe instanceof RecipesEntity) {
                    $this->getByIdInternal($id);
                } else {
                    $this->response->setStatusCode(self::STATUS_NO_CONTENT);
                }
            } else {
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            }

        } else {
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
        }
        return $this->response;
    }

    /**
     * Get recipe with a condition.
     *
     * @param Request $request
     * @return Response
     * @Route("recipes/api/where", methods={"GET"}, name="get_where")
     */
    public function getWhere(Request $request): Response {
        $params = $request->query->all();
        // TODO Validate Sent field
        $params = array_change_key_case ($params, CASE_LOWER );
        //$field = strtolower($field);
        $results = $this->recipesRepository->findByQuery($params);
        if (!empty($results)) {
            foreach($results as $key => $value) {
                $parsedIngredients = $this->normalizeIngredients($value->getIngredients());
                $value->setIngredients($parsedIngredients);
            }
        }
        $this->validateResponse($results);
        $this->updateResponseHeader();
        return $this->response;
    }

    /**
     * Right wildcard search for a recipe.
     *
     * @param Request $request
     * @param string $keyword
     * @return Response
     * @Route("recipes/api/search", methods={"GET"}, requirements={"name"="\w+"}, name="get_search")
     */
    public function getSearch(Request $request): Response {
        $query = $request->getQueryString();
        $keyword = explode('=', $query);

        if (isset($keyword[1])) {
            // TODO Validate Sent field
            //$keyword = strtolower($keyword);
            $results = $this->recipesRepository->search($keyword[1]);
            if (!empty($results)) {
                foreach($results as $key => $value) {
                    $parsedIngredients = $this->normalizeIngredients($value->getIngredients());
                    $value->setIngredients($parsedIngredients);
                }
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
     * Parse ingredients into a usable array.
     *
     * @param $ingredients
     * @return array
     */
    private function normalizeIngredients(string $ingredients): array {
        // should be received as "ingredients": "100 g tomatoes,200 tps salt,500 g cheese",
        $ingredients = explode(',', $ingredients);
        $parsedArray = [];
        foreach($ingredients as $ingredient) {
            $parsed = explode(' ', $ingredient);
            $parsedArray[] = [
                'quantity' => $parsed[0],
                'unit' => $parsed[1],
                'ingredient' => $parsed[2]
            ];
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
        $responseJson = $this->serializer->serialize($result, 'json');
        if (empty($responseJson)) {
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
            $this->logger->log(self::VALIDATION_NO_RECORD, ['id' => $recipeIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($responseJson);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Post a recipe.
     *
     * @Route("recipes/api",  methods={"POST"}, name="post")
     * @param Request $request
     * @return Response
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function post(Request $request): Response {
        // turn request data into an array
        $parameters = json_decode($request->getContent(), true);
        $parameters = $this->normalizeData($parameters);

        $valid = false;
        if ($parameters && is_array($parameters)) {
            $valid = $this->validatePost($parameters, __CLASS__.__FUNCTION__);
        }
        if ($valid) {
            $recipeID = $this->recipesRepository->save($parameters);
            $this->response->setStatusCode(self::STATUS_OK);
            // Return posted data back.(use get to normalize ingredients array).
            $this->getByIdInternal($recipeID);
            } else {
                $this->response->setStatusCode(self::STATUS_EXCEPTION);
            }
        $this->updateResponseHeader();
        return $this->response;
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
    private function validatePost(array $parameters, $sender): bool {
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
        if (empty(self::$categories)) {
            self::$categories = [self::CATEGORY_dessert, self::CATEGORY_SALAD, self::CATEGORY_MAIN_DISH];
        }
        $valid = in_array($category, self::$categories);
        return $valid;
    }
}
