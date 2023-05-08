<?php

namespace App\Entity;

use App\Repository\BookmarkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BookmarkRepository::class)
 */
class Bookmark
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Book::class, inversedBy="bookmarks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Book;

    /**
     * @ORM\OneToOne(targetEntity=BookParagraph::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $Paragraph;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $Name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->Book;
    }

    public function setBook(?Book $Book): self
    {
        $this->Book = $Book;

        return $this;
    }

    public function getParagraph(): ?BookParagraph
    {
        return $this->Paragraph;
    }

    public function setParagraph(BookParagraph $Paragraph): self
    {
        $this->Paragraph = $Paragraph;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): self
    {
        $this->Name = $Name;

        return $this;
    }
}
