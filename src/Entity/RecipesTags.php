<?php

namespace App\Entity;

use App\Repository\RecipesTagsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesTagsRepository::class)
 * @ORM\Table(indexes={@ORM\Index(columns={"name"})}, name="recipesTagsEntity")
 */
class RecipesTags
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=RecipesEntity::class, inversedBy="recipesTags")
     */
    private $recipe;

    /**
     * @ORM\Column(type="datetime", name="insertDateTime")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $description;

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

    public function getRecipe(): ?RecipesEntity
    {
        return $this->recipe;
    }

    public function setRecipe(?RecipesEntity $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getInsertDateTime(): ?\DateTimeInterface
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(\DateTimeInterface $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
