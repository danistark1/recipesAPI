<?php

namespace App\Entity;

use App\Repository\RecipesConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RecipesConfigurationEntityRepository::class)
 * @ORM\Table(name="recipesConfiguration")
 */
class RecipesConfigurationEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $configKey;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $configValue;

    /**
     * @ORM\Column(type="datetime")
     */
    private $insertDateTime;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $configType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfigKey(): ?string
    {
        return $this->configKey;
    }

    public function setConfigKey(string $configKey): self
    {
        $this->configKey = $configKey;

        return $this;
    }

    public function getConfigValue(): ?string
    {
        return $this->configValue;
    }

    public function setConfigValue(?string $configValue): self
    {
        $this->configValue = $configValue;

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

    public function getConfigType(): ?string
    {
        return $this->configType;
    }

    public function setConfigType(string $configType): self
    {
        $this->configType = $configType;

        return $this;
    }
}
