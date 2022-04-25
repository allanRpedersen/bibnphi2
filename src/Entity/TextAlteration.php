<?php

namespace App\Entity;

use App\Entity\BookParagraph;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TextAlterationRepository;

/**
 * @ORM\Entity(repositoryClass=TextAlterationRepository::class)
 */
class TextAlteration
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
    private $name;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $beginTag;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $endTag;

    /**
     * @ORM\Column(type="integer")
     */
    private $length;

    /**
     * @ORM\Column(type="integer")
     */
    private $position; // I mean index in the string .. but it's a reserved word !!-/

    /**
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="alterations")
     */
    private $bookParagraph;

    /**
     * @ORM\ManyToOne(targetEntity=BookNote::class, inversedBy="alterations")
     */
    private $bookNote;


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

    public function getBeginTag(): ?string
    {
        return $this->beginTag;
    }

    public function setBeginTag(string $beginTag): self
    {
        $this->beginTag = $beginTag;

        return $this;
    }

    public function getEndTag(): ?string
    {
        return $this->endTag;
    }

    public function setEndTag(string $endTag): self
    {
        $this->endTag = $endTag;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getBookParagraph(): ?BookParagraph
    {
        return $this->bookParagraph;
    }

    public function setBookParagraph(?BookParagraph $bookParagraph): self
    {
        $this->bookParagraph = $bookParagraph;

        return $this;
    }

    public function getBookNote(): ?BookNote
    {
        return $this->bookNote;
    }

    public function setBookNote(?BookNote $bookNote): self
    {
        $this->bookNote = $bookNote;

        return $this;
    }

}
