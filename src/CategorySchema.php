<?php

namespace App;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CategorySchema
 *
 * @package App
 */
class CategorySchema {
    public static $schema = [];

    public function __construct() {
        self::setSchema();
    }

    public static function setSchema() {
        if (empty(self::$schema)) {
           self::$schema = new Assert\Collection([
               'name' => [new Assert\Length(['min' => 3]), new Assert\NotBlank],
               'type' => [new Assert\Length(['min' => 3]), new Assert\NotBlank],
           ]);
        }

    }
}
