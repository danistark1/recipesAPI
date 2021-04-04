<?php

namespace App\Entity;

use App\Repository\RecipesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesRepository::class)
 * @ORM\Table(name="recipesEntity")
 */
class RecipesEntity {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $prep_time;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $cooking_time;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $servings;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $category;

    /**
     * @ORM\Column(type="text")
     */
    private $directions;

    /**
     * @ORM\Column(type="string")
     */
    private $insert_date_time;

    /**
     * @ORM\Column(type="integer")
     */
    private $favourites;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $added_by;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $calories;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cuisine;

    /**
     * @ORM\Column(type="text")
     */
    private $ingredients;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $url;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrepTime(): ?\DateTimeInterface
    {
        return $this->prep_time;
    }

    public function setPrepTime(?\DateTimeInterface $prep_time): self
    {
        $this->prep_time = $prep_time;

        return $this;
    }

    public function getCookingTime(): ?\DateTimeInterface
    {
        return $this->cooking_time;
    }

    public function setCookingTime(?\DateTimeInterface $cooking_time): self
    {
        $this->cooking_time = $cooking_time;

        return $this;
    }

    public function getServings(): ?int
    {
        return $this->servings;
    }

    public function setServings(?int $servings): self
    {
        $this->servings = $servings;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDirections(): ?string
    {
        return $this->directions;
    }

    public function setDirections(string $directions): self
    {
        $this->directions = $directions;

        return $this;
    }

    public function getInsertDateTime(): ?string
    {
        return $this->insert_date_time;
    }

    public function setInsertDateTime(string $insert_date_time): self
    {
        $this->insert_date_time = $insert_date_time;

        return $this;
    }

    public function getFavourites(): ?int
    {
        return $this->favourites;
    }

    public function setFavourites(int $favourites): self
    {
        $this->favourites = $favourites;

        return $this;
    }

    public function getAddedBy(): ?string
    {
        return $this->added_by;
    }

    public function setAddedBy(?string $added_by): self
    {
        $this->added_by = $added_by;

        return $this;
    }

    public function getCalories(): ?string
    {
        return $this->calories;
    }

    public function setCalories(?string $calories): self
    {
        $this->calories = $calories;

        return $this;
    }

    public function getCuisine(): ?string
    {
        return $this->cuisine;
    }

    public function setCuisine(?string $cuisine): self
    {
        $this->cuisine = $cuisine;

        return $this;
    }

    public function getIngredients()
    {
        return $this->ingredients;
    }

    public function setIngredients($ingredients): self
    {
        $this->ingredients = $ingredients;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }


}
