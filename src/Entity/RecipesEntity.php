<?php

namespace App\Entity;

use App\Repository\RecipesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=RecipesRepository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"name"})}, name="recipesEntity")
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
     * @ORM\Column(type="string", name="prepTime", length=100, nullable=true)
     */
    private $prepTime;

    /**
     * @ORM\Column(type="string", name="cookingTime", length=100, nullable=true)
     */
    private $cookingTime;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $servings;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $category;

    private $imageUrl;

    /**
     * @ORM\Column(type="text")
     */
    private $directions;

    /**
     * @ORM\Column(type="string",  name="insertDateTime")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $favourites;

    /**
     * @ORM\Column(type="string", name="addedBy", length=100, nullable=true)

     */
    private $addedBy;

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

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $featured;

    /**
     * @ORM\OneToMany(targetEntity=RecipesTags::class, mappedBy="recipe")
     */
    private $recipesTags;

    /**
     * @ORM\Column(type="string", length=100, name="subCategory", nullable=true)
     */
    private $subCategory;

    public function __construct()
    {
        $this->recipesTags = new ArrayCollection();
    }

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

    public function getPrepTime(): ?string
    {
        return $this->prepTime;
    }

    public function setPrepTime(?string $prepTime): self
    {
        $this->prepTime = $prepTime;

        return $this;
    }

    public function getCookingTime(): ?string
    {
        return $this->cookingTime;
    }

    public function setCookingTime(?string $cookingTime): self
    {
        $this->cookingTime = $cookingTime;

        return $this;
    }

    public function getServings(): ?string
    {
        return $this->servings;
    }

    public function setServings(?string $servings): self
    {
        $this->servings = $servings;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getDirections()
    {
        return $this->directions;
    }

    public function setDirections($directions): self
    {
        $this->directions = $directions;

        return $this;
    }

    public function getInsertDateTime(): ?string
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(string $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getFavourites(): ?bool
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
        return $this->addedBy;
    }

    public function setAddedBy(?string $addedBy): self
    {
        $this->addedBy = $addedBy;

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

    public function getFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(?bool $featured): self
    {
        $this->featured = $featured;

        return $this;
    }

    /**
     * @return Collection|RecipesTags[]
     */
    public function getRecipesTags(): Collection
    {
        return $this->recipesTags;
    }

    public function addRecipesTag(RecipesTags $recipesTag): self
    {
        if (!$this->recipesTags->contains($recipesTag)) {
            $this->recipesTags[] = $recipesTag;
            $recipesTag->setRecipe($this);
        }

        return $this;
    }

    public function removeRecipesTag(RecipesTags $recipesTag): self
    {
        if ($this->recipesTags->removeElement($recipesTag)) {
            // set the owning side to null (unless already changed)
            if ($recipesTag->getRecipe() === $this) {
                $recipesTag->setRecipe(null);
            }
        }

        return $this;
    }

    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    public function setSubCategory(?string $subCategory): self
    {
        $this->subCategory = $subCategory;

        return $this;
    }
}
