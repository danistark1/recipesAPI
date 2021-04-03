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
     * Get recipe.
     *
     * @param string $name Recipe name
     * @param Request $request
     * @return Response
     * @Route("recipes/api/name", methods={"GET"}, requirements={"name"="\w+"}, name="get_where")
     */
    public function getWhere(Request $request): Response {
//        $name = strtolower($name);
//        $results = $this->recipesRepository->findByQuery(['name' => $name]);
//        foreach($results as $key => $value) {
//            $parsedIngredients = $this->parseIngredients($value->getIngredients());
//            $value->setIngredients($parsedIngredients);
//        }
//        $this->validateResponse($results, $name);
//        $this->updateResponseHeader();
//        return $this->response;
    }

    /**
     * Parse ingredients into a usable array.
     *
     * @param $ingredients
     */
    private function normalizeIngredients(string $ingredients) {
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
     * @param string $recipeIdentifier
     */
    private function validateResponse($result, $recipeIdentifier = '') {
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
     * Post weatherData.
     *
     * @Route("recipes/api",  methods={"POST"}, name="post")
     * @param Request $request
     * @return Response
     * @throws Exception
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
            $result = $this->recipesRepository->save($parameters);
                $this->response->setStatusCode(self::STATUS_OK);
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
     */
    private function validateCategory($category): bool {
        if (empty(self::$categories)) {
            self::$categories = [self::CATEGORY_dessert, self::CATEGORY_SALAD, self::CATEGORY_MAIN_DISH];
        }
        $valid = in_array($category, self::$categories);
        return $valid;
    }
}
