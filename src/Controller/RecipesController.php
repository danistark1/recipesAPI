<?php

namespace App\Controller;

use App\CategorySchema;
use App\Entity\CategoriesEntity;
use App\Entity\RecipesEntity;
use App\Entity\RecipesMediaEntity;
use App\Kernel;
use App\RecipesCacheHandler;
use App\RecipesPatchSchema;
use App\RecipesPostSchema;
use App\RecipesUpdateSchema;
use App\Repository\RecipesMediaRepository;
use App\Repository\RecipesRepository;
use App\RecipesLogger;
use App\Repository\RecipesSelectorRepository;
use App\Repository\RecipesTagsRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
    const VALIDATION_INVALID_SEARCH_FILTER = "Invalid search filter provided.";
    const VALIDATION_INVALID_FILTER = "Invalid filter provided.";

    const CATEGORY_DESSERT = 'Dessert';
    const CATEGORY_SALAD = 'Salad';
    const CATEGORY_APPETIZER = 'Appetizer';
    const CATEGORY_MAIN_DISH = 'Main Dish';
    const CATEGORY_HOLIDAYS = 'Holidays';
    const CATEGORY_BREAKFAST = 'Breakfast';
    const CATEGORY_SIDE_DISH = 'Side Dish';
    const CATEGORY_BEVERAGE = 'Beverage';
    const CATEGORY_BREAD = 'Bread';
    const CATEGORY_SOUP = 'Soup';

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

    /** @var RecipesSelectorRepository|null  */
    private $recipesSelectorRepository;

    /** @var RecipesTagsRepository  */
    private $recipesTagsRepository;

    /** @var float|string Capture response execution time */
    private $time_start;

    /** @var Serializer  */
    private $serializer;

    /** @var Kernel */
    private $kernel;

    /** @var RecipesCacheHandler $config */
    private $config;

    /** @var RecipesMediaRepository */
    private $recipesMediaRepository;

    /** @var Environment  */
    public $templating;

    /** @var MailerInterface  */
    public $mailer;


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
        ObjectNormalizer $objectNormalizer,
        RecipesMediaRepository $recipesMediaRepository,
        Kernel $kernel,
        RecipesCacheHandler $config,
        RecipesSelectorRepository $recipesSelectorRepository,
        Environment $templating,
        MailerInterface $mailer) {
        $this->response  = new Response();

        $encoders = [new JsonEncoder()];
        $normalizers = [$objectNormalizer];
        $this->kernel =  $kernel;
        $this->recipesMediaRepository = $recipesMediaRepository;
        $this->recipesSelectorRepository = $recipesSelectorRepository;
        $this->config = $config;
        $this->templating = $templating;
        $this->mailer = $mailer;

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
     * Get categories dropdown options
     *
     * @Route("/recipes/categories-dropdown", methods={"GET", "OPTIONS"}, name="get_categories_dropdown")
     */
    public function getCategoryDropDown(): Response
    {
        $content = $this->serializer->serialize(self::$categories, 'json');
        $this->response->setContent($content);
        return $this->response;
    }

    /**
     * Random recipe Selector
     * Randomly select from available recipes of type "Main Dish"
     * If a recipe has already been selected (recipeSelectorEntity table), it does not get selected again, unless all
     * other recipes have been selected. Once all recipes are selected, the table gets erased, and we start all over.
     *
     * @Route("recipes/selector", methods={"GET"}, name="get_random_recipes")
     */
    public function randomRecipeSelector(Request $request): Response
    {
        // FE doesn't need to send and email for randomly selected recipes, just display recipes in a label.
        $feRequest  = $request->get('fe-selector') ?? false;

        // this is just for FE to display the randomly selected recipes
        // TODO Parse # out of directions && ingredients before sending to twig
        $recipes = $this->recipesRepository->findByQuery(['category' => self::CATEGORY_MAIN_DISH]);
        $totalRecipesCounter = count($recipes);
        $totalRecipesSelectorCounter = 0;
        $recipesFoundCounter = 0;
        $recipesSelectedName = [];
        $counter = 0;
        $shouldSendRecipeSelectorEmail = $this->config->getConfigKey('send-recipe-selector-email');
        // # of recipes to randomly select.
        $numRecipesSend = $this->config->getConfigKey('send-recipe-selector-counter');
        // To check if a randomly selected recipes do not have the same sub-categories
        $recipesSelectedSubCategory = [];
        $recipeSelectorNames = [];

        while($counter <= $totalRecipesCounter && $recipesFoundCounter < $numRecipesSend) {
            // Selected recipe has already been saved in recipesselectorentity OR all recipes have already been selected.
            $allRecipesSelected = $totalRecipesSelectorCounter === $totalRecipesCounter;
            // All recipes have already been selected, reset table...
            if ($allRecipesSelected) {
                $counter = 0;
                $recipesFoundCounter = 0;
                $this->recipesSelectorRepository->delete();
            }
            // TODO: Do not select a recipe if it has already been selected at least 2 weeks ago.

            if (empty($recipes)) {
                $this->response->setContent("No recipes available!");
                return $this->response;
            }

            $allRecipesIds = [];
            // Get an array of all available recipes previously selected.
            /** @var RecipesEntity $recipe */
            foreach($recipes as $recipe) {
                $allRecipesIds[] = $recipe->getId();
            }
            // TODO # of selected recipes should be a config.
            $randId = array_rand($allRecipesIds,1);
            $selectedRandId = $allRecipesIds[$randId];
            $selectedRecipe = $this->recipesRepository->findOneBy(['id' => $selectedRandId]);

            $selectedName = $selectedRecipe->getName();

            // Random recipe selected.
            if ($selectedRecipe !== '' && $selectedRecipe !== null) {
                $totalRecipesSelectorCounter++;
                // has this name been selected before?
                // -- if selected before, find another recipe
                $previouslySelectedRecipe = $this->recipesSelectorRepository->findByQuery(['recipeId' => $selectedRecipe->getId()]);
                if ($previouslySelectedRecipe) {
                   var_dump("here");
                    $selectedName = '';
                    $counter++;
                    continue;
                }

                // Store randomly selected recipe in recipeselector
                $recipeSelectorData = [
                    'name' => $selectedName,
                    'recipeId' => $selectedRecipe->getId()
                ];

                // If sub-category already selected, skip
                // We don't want to select more than 1 dish with the sub-category (pasta & pasta should be skipped)
//                chicken
//                pasta
//                meat
//                grain
//                dough
//                vegetables
//                rice
//                fish

                if ($recipesSelectedSubCategory && in_array($selectedRecipe->getSubCategory(), $recipesSelectedSubCategory)) {
                    continue;
                }
                $recipesSelectedName[] = $selectedRecipe->getName();
                // Get recipe sub-category (ex. category Main Dish, subcategory = pasta)
                $recipesSelectedSubCategory[] = $selectedRecipe->getSubCategory();
                $recipeSelectorNames[] = $selectedName;
                $this->recipesSelectorRepository->save($recipeSelectorData);
                $recipesFoundCounter++;
            }
            $counter++;
        }
        // Send Email, if config 'send-recipe-selector-email' is set to 1
        // && this is not an FE request to randomly select a recipe.
        if ($shouldSendRecipeSelectorEmail && !$feRequest) {
            $this->sendRecipeSelectorEmail($recipesSelectedName);
        }
        $this->response->setContent(empty($selectedName) ?  'No recipes found, table could be empty.' : implode("," , $recipeSelectorNames));
        return $this->response;
    }

    /**
     * Send selected recipe email.
     *
     * @param array  $recipesSelectedName
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendRecipeSelectorEmail($recipesSelectedName) {
        $recipeData = [
            'recipe1Name'        => $recipesSelectedName[0],
            'recipe2Name'        => $recipesSelectedName[1]
        ];
        $message = (new Email())
            ->subject('Your weekly Selected Recipes are here!')
            ->from($this->config->getConfigKey('email-from'))
            ->to($this->config->getConfigKey('email-to'))
            ->html(
                $this->templating->render(
                    'email/recipe_selector.html.twig',
                    $recipeData
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }

    /**
     * Parse recipe data.
     *
     * searches for # and returns parsed data.
     *
     * @param $directions
     */
    private function parseRecipeData($directions) {
        return explode('#', $directions);
    }
    /**
     * Get recipe by name.
     *
     * @param string $name Recipe name
     * @param Request $request
     * @return Response
     * @Route("recipes/name/{name}", methods={"GET"}, name="get_by_name")
     */
    public function getByName(
        Request $request,
        string $name,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config): Response {

        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        $name = strtolower($name);
        $valid = !empty($name);
        if ($valid) {
            $results = $this->recipesRepository->findByQuery(['name' => $name]);
            if (!empty($results)) {
                foreach($results as $result) {
                    $this->getFileInternal($result);
                }
            }
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
    public function postCategory(
        Request $request,
        ValidatorInterface $validator,
        CategorySchema $categorySchema,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }


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
    private function normalize(array &$results, $parsed = true) {
        $parsed = true;
        foreach($results as $result) {
            $parsedIngredients = $this->parseArray($result->getIngredients());
            $parsedDirections = $this->parseArray($result->getDirections());
            $result->setIngredients($parsedIngredients);
            $result->setDirections($parsedDirections);
//            if (!$parsed) {
//                $result->setIngredients($result->getIngredients());
//                $result->setDirections($result->getDirections());
//            } else {
//                $parsedIngredients = $this->parseArray($result->getIngredients());
//                $parsedDirections = $this->parseArray($result->getDirections());
//
//                $result->setIngredients($parsedIngredients);
//                $result->setDirections($parsedDirections);
//            }
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
    public function delete(
        Request $request,
        $id,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }

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
    public function patchFavourites(
        $id,
        Request $request,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
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
    public function patchFeatured(
        $id,
        Request $request,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
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
     * Get recipe with a condition.
     * ex. recipes/where?id=1
     *
     * @param Request $request
     * @return Response
     * @Route("recipes/where", methods={"GET", "OPTIONS", "HEAD"}, name="get_where")
     */
    public function getWhere(
        Request $request,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {

        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        // TODO Validate request.
        $page = $request->get('page');
        $parsed = $request->get('parsed');
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
                foreach($results as $result) {
                    $this->getFileInternal($result);
                }
                $this->normalize($results, $parsed);
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
     * Get all recipes filtered by category
     *
     * @param Request $request
     * @Route("recipes/all", methods={"GET", "OPTIONS", "HEAD"}, name="get_all")
     */
    public function getAllRecipes(
        Request $request,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        $page = $request->query->get('page') ?? 1;
        //TODO Sanitize query remove special chars.
        $parsed = $request->get('parsed');
        $field = $request->get('filter');
        $value = null;
        if ($field) {
            $validFields = RecipesRepository::VALID_FIELDS;
            if (!in_array($field, $validFields)) {
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                $this->response->setContent(self::VALIDATION_INVALID_SEARCH_FILTER);
                return $this->response;
            }
            $value = $request->query->get('value');
        }

        $filter = [];
        if (!empty($field) && !empty($value)) {
            $filter =  ['field' => $field, 'value' => $value];
        }
        $resultsAll = $this->recipesRepository->getAllByPage($filter, $page) ?? [];
        $results = $resultsAll['results'] ?? [];
        $pagesCount = $resultsAll['pagesCount'] ?? 0;
        $totalItems = $resultsAll['totalItems'] ?? 0;
        if (!empty($results)) {
            foreach($results as $result) {
                $this->getFileInternal($result);
            }
            $this->normalize($results, $parsed);
            $this->response->headers->set('recipes-totalItems', $totalItems);
            $this->response->headers->set('recipes-pagesCount', $pagesCount);
        }
        $this->validateResponse($results);
        $this->updateResponseHeader();
    return $this->response;
    }

    /**
     * Left/Right wildcard search a recipe. Returns a paginated search result.
     * Ex. - GET /recipes/search?q=pizza&page=1&filter={field}&{field}=Main Dish
     *
     *
     * @param Request $request
     * @return Response
     * @Route("recipes/search", methods={"GET", "OPTIONS", "HEAD"}, name="get_search")
     */
    public function getSearchPager(
        Request $request,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        // TODO Validate request.
        $query = $request->query->get('q');
        $page = $request->query->get('page') ?? 1;
        //TODO Sanitize query remove special chars.
        $field = $request->query->get('filter');
        $value = null;
        if ($field) {
            $validFields = RecipesRepository::VALID_FIELDS;
            if (!in_array($field, $validFields)) {
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                $this->response->setContent(self::VALIDATION_INVALID_SEARCH_FILTER);
                return $this->response;
            }
            $value = $request->query->get('value');
        }
        $query = str_replace('%20',' ', $query);
        if (!empty(trim($query))) {
            // We should be able to use the search without any filter, set to empty array by defautl.
            // getSearchByPage will ignore empty filters.
            $filter = [];
            if (!empty($field) && !empty($value)) {
                $filter =  ['field' => $field, 'value' => $value];
            }
            $resultsAll = $this->recipesRepository->getSearchByPage($query, $filter, $page) ?? [];
            $results = $resultsAll['results'];
            $pagesCount = $resultsAll['pagesCount'] ?? 0;
            $totalItems = $resultsAll['totalItems'] ?? 0;
            if (!empty($results)) {
                $this->normalize($results);
                // Get recipes images. Adds imageUrl to returned array if an image exists.
                foreach($results as $result) {
                    $this->getFileInternal($result);
                }
                $this->response->headers->set('recipes-totalItems', $totalItems);
                $this->response->headers->set('recipes-pagesCount', $pagesCount);
            }
            $this->validateResponse($results);
            $this->updateResponseHeader();
        } else {
            $response = new JsonResponse([], self::STATUS_NOT_FOUND);
            $this->response = $response;
        }
        return $this->response;
    }

    /**
     * Get recipe attached file internally.
     *
     * @param $id
     */
    public function getFileInternal(&$result) {
        /** @var  RecipesEntity $result */
        $results = $this->recipesMediaRepository->findByQuery(['foreignID' => $result->getID()]);
        if (!empty($results)) {
            $results = $results[0];
            $name = $results->getName();
            $url = $this->config->getConfigKey('image-url') ?? "http://192.168.4.10/recipesAPI/public/";
            $imageUrl = $url.$name;
            $result->setImageUrl($imageUrl);
        } else {
            $result->setImageUrl('');
        }
    }

    /**
     * Get a file using the API.
     *
     * @param $id
     * @param Request $request
     * @Route("recipes/file/{id}",  methods={"GET", "OPTIONS"}, name="get_recipes_image")
     */
    public function getFile(
        $id,
        Request $request,
        Kernel $kernel,
        RecipesMediaRepository $recipesMediaRepository,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {

        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        $webPath = $kernel->getProjectDir() . '/public/';
        // TODO Validate Id exists.
        $record = $this->getByIdInternal($id, true);
        if (!empty($record)) {
            $results = $recipesMediaRepository->findByQuery(['foreignID' => $id])[0];
            $name = $results->getName();
            $imageUrl = "http://192.168.4.10/recipesAPI/public/$name";
            $this->response->setContent($imageUrl);
        } else {
            $this->response->setContent("Record with id $id does not exist.");
            $this->response->setStatusCode(self::STATUS_NOT_FOUND);
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
    private function validateResponse($result, string $recipeIdentifier = '') {
        $response = $this->serializer->serialize($result, 'json');
        if (empty($response)) {
            $this->response->setStatusCode(404);
            $this->response->headers->set('recipes-totalItems', 0);
            $this->response->headers->set('recipes-pagesCount', 0);
            $this->response->setContent($response);
            $this->logger->log(self::VALIDATION_NO_RECORD, ['id' => $recipeIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($response);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Prepare patch data object.
     *
     * @param array $patchData
     * @return RecipesEntity|null
     */
    private function preparePatchData(array $patchData) {
        $recipe = $this->recipesRepository->findOneBy(['id' => $patchData['id']]);
        if (!empty($recipe)) {
            // TODO Refactor this nonsense.

            empty($patchData['name']) ? true : $recipe->setName($patchData['name']);
            empty($patchData['prepTime']) ? true : $recipe->setPrepTime($patchData['prepTime']);
            empty($patchData['cookingTime']) ? true : $recipe->setCookingTime($patchData['cookingTime']);
            empty($patchData['category']) ? true : $recipe->setCategory($patchData['category']);
            empty($patchData['directions']) ? true : $recipe->setDirections($patchData['directions']);
            empty($patchData['ingredients']) ? true : $recipe->setIngredients($patchData['ingredients']);
            empty($patchData['favourites']) ? true : $recipe->setFavourites($patchData['favourites']);
            empty($patchData['calories']) ? true : $recipe->setCalories($patchData['calories']);
            empty($patchData['cuisine']) ? true : $recipe->setCuisine($patchData['cuisine']);
            empty($patchData['addedBy']) ? true : $recipe->setAddedBy($patchData['addedBy']);
            empty($patchData['url']) ? true : $recipe->setUrl($patchData['url']);
            $updatedRecipe = $this->recipesRepository->updateRecipe($recipe);
        }

        if (empty($updatedRecipe) || !$recipe) {
            $this->response->setStatusCode('Recipe update failed.');
            $this->logger->log(self::VALIDATION_FAILED, ['recipeObject' => $recipe, 'returnedData' => $updatedRecipe], Logger::ALERT);

        } else {
            $this->getByIdInternal($updatedRecipe->getId());
        }
        return $recipe;
    }

    /**
     * Check if a request should be rate limited.
     * This depends on a recipesConfiguration value of "rate-limit-requests", (check RateLimitFactory::create())
     * If the config "rate-limit-requests" is not set, rate limit defaults to what is set in rate_limiter.yaml file.
     *
     * @param Request $request API Request.
     * @param RateLimiterFactory $anonymousApiLimiter Symfony's rate limiting factory.
     * @param RecipesCacheHandler $config Configuration class.
     * @return bool If a request should be rate limited.
     */
    private function isRateLimited(Request $request, RateLimiterFactory $anonymousApiLimiter, RecipesCacheHandler $config): bool {
        $isRateLimited = false;
        $shouldRateLimit = $config->getConfigKey('should-rate-limit') ?? false;
        if (!$shouldRateLimit) {
            return $isRateLimited;
        }
        // create a limiter based on a unique identifier of the client
        // (e.g. the client's IP address, a username/email, an API key, etc.)
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        // the argument of consume() is the number of tokens to consume
        // and returns an object of type Limit
        if (false === $limiter->consume(1)->isAccepted()) {
            $this->response->setStatusCode(429);
            $this->response->setContent("You've hit the rate limit, slow down!.");
            $isRateLimited = true;
        }

        return $isRateLimited;
    }

    /**
     * Post a recipe.
     *
     * @Route("recipes",  methods={"POST", "OPTIONS"}, name="post_recipes")
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function post(
        Request $request,
        ValidatorInterface $validator,
        RecipesPostSchema $recipesPostSchema,
        RecipesPatchSchema $recipesPatchSchema,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config): Response {
        $pascalEm = (array)json_decode($request->getContent(), true);
        $insert = !isset($pascalEm['id']);
        $recipe = false;

        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }

        if (!$insert) {
            $pascalEm['id'] = (int)$pascalEm['id'];
            // if this is an update, get the recipe and update the directions field if it wasn't sent.
            // sine this field is normalized to empty string when its not set and the schema requires a min of 3 chars.
            $recipe = $this->recipesRepository->findOneBy(['id' => $pascalEm['id']]);
        }
        $schema = !$insert ? $recipesPatchSchema::$schema : $recipesPostSchema::$schema;
        $pascalEm = $this->normalizeRecipeData($pascalEm, $recipe, $insert);
        $violations = $validator->validate($pascalEm, $schema);
        $valid = $this->validateRequest($violations);
        if ($valid) {
            if (!$insert) {
               $this->preparePatchData($pascalEm);
            } else {
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
            }
            $this->updateResponseHeader();
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
    public function patch(
        $id,
        Request $request,
        ValidatorInterface $validator,
        RecipesUpdateSchema $recipesUpdateSchema,
        RateLimiterFactory $anonymousApiLimiter,
        RecipesCacheHandler $config
    ): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
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
                empty($data['favourites']) ?  true: $recipe->setFavourites($data['favourites']);
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
     * Convert size.
     *
     * @param $bytes
     * @param string $conversion
     * @return mixed|string
     */
    public function byteConversion($bytes, string $conversion = 'MB') {
        switch ($conversion) {
            case 'MB':
                $bytes = number_format($bytes / 1048576, 2);
                break;
            case 'KB':
                $bytes = number_format($bytes / 1024, 2);
                break;
            case 'GB':
                $bytes = number_format($bytes / 1073741824, 2);
        }
            return $bytes;
        }

    /**
     * Post a file.
     *
     *  POST: recipes/upload/{id} where $id is a recipe's primary key
     *  Access on the server :http://.../recipesAPI/public/{imageName}
     *
     * $id The ID of the foreign record you are attaching media to.
     *
     * @Route("recipes/upload/{id}",  methods={"POST", "OPTIONS"}, name="upload_recipes_image")
     * @return Response
     * @throws InvalidArgumentException
     */
    public function postFile(
        $id,
        Request $request,
        RecipesMediaRepository $mediaRepository,
        RecipesCacheHandler $config,
        RateLimiterFactory $anonymousApiLimiter): Response {
        $isRateLimited = $this->isRateLimited($request, $anonymousApiLimiter, $config);
        if ($isRateLimited) {
            return $this->response;
        }
        $valid = $this->validateFileRequest($request, $config, $id);
        $dbFileExists = false;
        /** @var RecipesMediaEntity $dbFile */
        $dbFile = $this->recipesMediaRepository->findByQuery(['foreignID' => $id]);
        if (!empty($dbFile[0])) {
            $dbFileExists = true;
        }
        if ($valid) {
            /** @var FileBag $requestFile */
            $requestFile = $request->files;
            $file = $requestFile->get('file');
            $mimeType = $file->getClientMimeType();
            $fileName = rand(1, 1000000000).'recipeIMG';
            $filePath =  "public/{$fileName}";
            $fileSize = $file->getSize();
            $fileSize = $this->byteConversion($fileSize);
            $fileContent = $file->getContent();
            $result = file_put_contents($fileName, $fileContent);

            //TODO Validate {$id} is for an actual recipe to prevent orphaned records.

            if (is_int($result)) {
                if ($dbFileExists) {
                    // Update
                    $fileData = $dbFile;
                    empty($fileName) ? true : $dbFile[0]->setName($fileName);
                    empty($filePath) ? true : $dbFile[0]->setPath($filePath);
                    empty($mimeType) ? true : $dbFile[0]->setType($mimeType);
                } else {
                    // insert
                    $fileData = [
                        'name' => $fileName,
                        'path' => $filePath,
                        'type' => $mimeType,
                        'size' => $fileSize,
                        'foreignID' => $id,
                        'foreignTable' => 'recipesEntity'
                    ];
                }

                $result = $mediaRepository->save($fileData);
                if (!$result) {
                    $this->logger->log('Failed inserting image data into recipesMedia.', ['result' => $result], Logger::CRITICAL);
                    $this->response->setContent('Failed inserting image data into recipesMedia.');
                    $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                }
            }
            else {
                $this->logger->log('Failed writing image to disk.', ['result' => $result], Logger::CRITICAL);
                $this->response->setContent('Failed writing image to disk.');
                $this->response->setStatusCode(self::VALIDATION_FAILED);
            }
        }
        return $this->response;
    }

    /**
     * Validate fileRequest.
     *
     * @param Request $request
     * @param RecipesCacheHandler $config
     * @return bool
     * @throws InvalidArgumentException
     */
    private function validateFileRequest(Request $request, RecipesCacheHandler $config, $id): bool {
        $valid = true;
        $dbFileExists = true;
        $dbFile = $this->recipesMediaRepository->findByQuery(['foreignID' => $id]);
        if (!empty($dbFile)) {
            $dbFileExists = true;
        }
        $allowedExtensions = $config->getConfigKey('allowed-extensions') ?? 'gif,jpg,jpeg';
        $allowedExtensions = explode(',', $allowedExtensions);
        // Type FileBag
        /** @var FileBag $requestFile */
        $requestFile = $request->files;
        // Type uploaded file
        /** @var UploadedFile $file */
        $file = $requestFile->get('file');
        if (!($requestFile instanceof FileBag) || !($file instanceof UploadedFile) || !$file) {
            $this->logger->log('Invalid image.', [
                'file' => $file,
                'requestFile' => $requestFile,
                'request' => $request], Logger::CRITICAL);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->response->setContent('Invalid image.');
            $valid = false;
        } else {
            $mimeType = $file->getClientMimeType();
            $pos = strpos($mimeType, '/');
            $fileType = substr($mimeType, $pos + 1);
            if (!in_array($fileType, $allowedExtensions)) {
                $this->logger->log('File type not allowed.', ['file' => $file], Logger::CRITICAL);
                $this->response->setContent('File type not allowed');
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                $valid = false;
            }
            $maxFileSize = $config->getConfigKey('max-file-size');
            $fileSize = $file->getSize();
            $fileSize = $fileSize/1000000;
            if ($fileSize >= $maxFileSize) {
                $valid = false;
                $this->logger->log("File size is greater than the defined max file size of $maxFileSize.", ['file' => $file], Logger::CRITICAL);
                $this->response->setContent("File size is greater than the defined max file size of $maxFileSize.");
                $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            }

            if ($dbFileExists) {
                $this->response->setStatusCode(self::STATUS_OK);
                $this->response->setContent('File updated.');
            }
        }
        return $valid;
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
    private function normalizeRecipeData(array $parameters, $recipeObject = false , $insert = false): array {
        // TODO this shouldn't be needed with Schema validation.
        $normalizedData = [];
        foreach($parameters as $param => $value) {
            if ($param === 'name' || $param === 'addedBy') {
                $value =  ucwords($value);
            }
            if ($param === 'name') {
                // prevent names with spaces #recipesAPI/issues/33
                $value = trim($value);
            }
            $normalizedData += [$param => $value];
        }
        /** RecipeEntity $recipeObject */
        // This needs to be set when updating, otherwise it gets normalized to an empty string, and schema validation
        // fails.
        if ($recipeObject && !$insert) {
            if (empty($normalizedData['directions'])) {
                $normalizedData['directions'] = $recipeObject->getDirections();
            }
            if (empty($normalizedData['ingredients'])) {
                $normalizedData['ingredients'] = $recipeObject->getIngredients();
            }
        }
        if (!empty($normalizedData)) {
            $normalizedData['directions'] = empty($normalizedData['directions']) ? '' : ucfirst($normalizedData['directions']);
            $normalizedData['ingredients'] = empty($normalizedData['ingredients']) ? '' : ucfirst($normalizedData['ingredients']);
            $normalizedData['favourites'] = $normalizedData['favourites'] ?? 0;
            $normalizedData['addedBy'] = empty($normalizedData['addedBy']) ? 'NA' : $normalizedData['addedBy'];
            $normalizedData['prepTime'] = empty($normalizedData['prepTime']) ? 'NA' : $normalizedData['prepTime'];
            $normalizedData['cookingTime'] = empty($normalizedData['cookingTime']) ?  'NA' : $normalizedData['cookingTime'];
            $normalizedData['calories'] = $normalizedData['calories'] ?? 'NA';
            $normalizedData['cuisine'] = empty($normalizedData['cuisine']) ?  'NA' : $normalizedData['cuisine'];
            $normalizedData['url'] = $normalizedData['url'] ?? '';
            $normalizedData['servings'] = empty($normalizedData['servings']) ?  'NA' : $normalizedData['servings'];
            $normalizedData['featured'] = $normalizedData['featured'] ?? false;
        }
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
        return in_array($category, self::$categories);
    }
}
