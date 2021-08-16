<?php


namespace App;

use Cassandra\Tinyint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RecipesPatchSchema
 *
 * @package App
 */
class RecipesPatchSchema {

    public static $schema = [];

    public function __construct() {
        self::setSchema();
    }

    /**
     * Recipe Schema.
     */
    public static function setSchema() {

        if (empty(self::$schema)) {
            self::$schema = new Assert\Collection([
                'id' => new Assert\Optional(new Assert\Type('integer')),
                'name' => new Assert\Optional(new Assert\Length(['min' => 2])),
                'prepTime' =>  new Assert\Optional([new Assert\Length(['min' => 3])]),
                'cookingTime' => new Assert\Optional([new Assert\Length(['min' => 3])]),
                'servings' => new Assert\Optional(),
                'category' => new Assert\Optional(new Assert\Length(['min' => 3])),
                'directions' => new Assert\Optional(new Assert\Length(['min' => 3])),
                'favourites' => [new Assert\Optional([new Assert\PositiveOrZero()])],
                'addedBy' => new Assert\Optional([new Assert\Length(['min' => 3])]),
                'calories' => new Assert\Optional(),
                'cuisine' =>  new Assert\Optional(),
                'ingredients' => new Assert\Optional(new Assert\Length(['min' => 3])),
                'url' => new Assert\Optional([new Assert\Url()]),
                'featured' => new Assert\Optional(new Assert\Type('bool')),
                'tags' => new Assert\Optional(new Assert\Type('array')),

            ]);
        }
    }
}
