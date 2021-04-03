<?php

namespace App\Entity;

use App\Repository\RecipesLoggerEntityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesLoggerEntityRepository::class)
 * @ORM\Table(name="recipesLogger")
 */
class RecipesLoggerEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $context = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $level;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $levelName;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $extra = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $insert_date_time;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevelName(): ?string
    {
        return $this->levelName;
    }

    public function setLevelName(?string $levelName): self
    {
        $this->levelName = $levelName;

        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function getInsertDateTime(): ?\DateTimeInterface
    {
        return $this->insert_date_time;
    }

    public function setInsertDateTime(\DateTimeInterface $insert_date_time): self
    {
        $this->insert_date_time = $insert_date_time;

        return $this;
    }
}
