<?php

namespace App\Entity;

use App\Repository\RecipesSelectorRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesSelectorRepository::class)
 * @ORM\Table(name="recipesSelectorEntity")
 */
class RecipesSelectorEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="integer")
     */
    private $recipeId;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default" : 1})
     */
    private $isActive;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $attributes = [];

    /**
     * @ORM\Column(type="integer",nullable=true, options={"default" : 1})
     */
    private $totalSelectionCounter;

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

    public function getInsertDateTime(): ?\DateTimeInterface
    {
        return $this->insertDateTime;
    }

    public function setInsertDateTime(string $insertDateTime): self
    {
        $this->insertDateTime = $insertDateTime;

        return $this;
    }

    public function getRecipeId(): ?int
    {
        return $this->recipeId;
    }

    public function setRecipeId(int $recipeId): self
    {
        $this->recipeId = $recipeId;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getAttributes(): ?array
    {
        return $this->attributes;
    }

    public function setAttributes(?array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getTotalSelectionCounter(): ?int
    {
        return $this->totalSelectionCounter;
    }

    public function setTotalSelectionCounter(int $totalSelectionCounter): self
    {
        $this->totalSelectionCounter = $totalSelectionCounter;

        return $this;
    }
}
