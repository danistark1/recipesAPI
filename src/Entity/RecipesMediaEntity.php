<?php

namespace App\Entity;

use App\Repository\RecipesMediaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesMediaRepository::class)
 * @ORM\Table(name="recipesMedia")
 */
class RecipesMediaEntity
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
     * @ORM\Column(type="string", length=100)
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @ORM\Column(type="datetime", name="insertDateTime")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="integer", name="imageWidth", nullable=true)
     */
    private $imageWidth;

    /**
     * @ORM\Column(type="integer", name="imageHeight", nullable=true)
     */
    private $imageHeight;

    /**
     * @ORM\Column(type="integer", name="insertUserID", nullable=true)
     */
    private $insertUserID;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private $foreignTable;

    /**
     * @ORM\Column(type="integer")
     */
    private $foreignID;

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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

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

    public function getImageWidth(): ?int
    {
        return $this->imageWidth;
    }

    public function setImageWidth(?int $imageWidth): self
    {
        $this->imageWidth = $imageWidth;

        return $this;
    }

    public function getImageHeight(): ?int
    {
        return $this->imageHeight;
    }

    public function setImageHeight(?int $imageHeight): self
    {
        $this->imageHeight = $imageHeight;

        return $this;
    }

    public function getInsertUserID(): ?int
    {
        return $this->insertUserID;
    }

    public function setInsertUserID(?int $insertUserID): self
    {
        $this->insertUserID = $insertUserID;

        return $this;
    }

    public function getForeignTable(): ?string
    {
        return $this->foreignTable;
    }

    public function setForeignTable(string $foreignTable): self
    {
        $this->foreignTable = $foreignTable;

        return $this;
    }

    public function getForeignID(): ?int
    {
        return $this->foreignID;
    }

    public function setForeignID(int $foreignID): self
    {
        $this->foreignID = $foreignID;

        return $this;
    }
}
