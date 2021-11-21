<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookNoteRepository;
use ApiPlatform\Core\Annotation\ApiResource;

/**
 *  @ApiResource()
 *  @ORM\Entity(repositoryClass=BookNoteRepository::class)
 */
class BookNote
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Book::class, inversedBy="bookNotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $book;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $citation;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="integer")
     */
    private $citationIndex;

    /**
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="Notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bookParagraph;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $Book): self
    {
        $this->book = $Book;

        return $this;
    }

    public function getCitation(): ?string
    {
        return $this->citation;
    }

    public function setCitation(string $citation): self
    {
        $this->citation = $citation;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCitationIndex(): ?int
    {
        return $this->citationIndex;
    }

    public function setCitationIndex(int $citationIndex): self
    {
        $this->citationIndex = $citationIndex;

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
}
