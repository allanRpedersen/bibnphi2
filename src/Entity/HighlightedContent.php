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
     * @ORM\Column(type="integer")
     */
    private $bookId;

    /**
     * @ORM\Column(type="string", length=64)
     * 
     * can be either 'paragraph' or 'note' ..
     */
    private $contentType;
    
    /**
     * @ORM\Column(type="integer")
     */

    private $origId;

    /**
     * @ORM\Column(type="string")
     */
    private $highlightedString;

    /**
     * @ORM\Column(type="array")
     */
    private $matchingIndexes = [];


    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * Get the value of contentType
     */ 
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set the value of contentType
     *
     * @return  self
     */ 
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get the value of origId
     */ 
    public function getOrigId()
    {
        return $this->origId;
    }

    /**
     * Set the value of origId
     *
     * @return  self
     */ 
    public function setOrigId($origId)
    {
        $this->origId = $origId;

        return $this;
    }

    /**
     * Get the value of highlightedString
     */ 
    public function getHighlightedString()
    {
        return $this->highlightedString;
    }

    /**
     * Set the value of highlightedString
     *
     * @return  self
     */ 
    public function setHighlightedString($highlightedString)
    {
        $this->highlightedString = $highlightedString;

        return $this;
    }

    /**
     * Get the value of bookId
     */ 
    public function getBookId()
    {
        return $this->bookId;
    }

    /**
     * Set the value of bookId
     *
     * @return  self
     */ 
    public function setBookId($bookId)
    {
        $this->bookId = $bookId;

        return $this;
    }
}
