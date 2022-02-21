<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\HighlightedContentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=HighlightedContentRepository::class)
 */
class HighlightedContent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $linkTo;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $matchingIndexes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getLinkTo(): ?string
    {
        return $this->linkTo;
    }

    public function setLinkTo(?string $linkTo): self
    {
        $this->linkTo = $linkTo;

        return $this;
    }

    public function getMatchingIndexes(): ?array
    {
        return $this->matchingIndexes;
    }

    public function setMatchingIndexes(?array $matchingIndexes): self
    {
        $this->matchingIndexes = $matchingIndexes;

        return $this;
    }
}
