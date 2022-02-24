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
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="Notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bookParagraph;

    /**
     * @ORM\Column(type="integer")
     */ 
    private $citationIndex;

    /**
     * @ORM\Column(type="string", length=255)
     */  
    private $citation;

    /**
     * @ORM\Column(type="text")
     */  
    private $content;

    /**
     * Content modified if the note matches a searched string 
     * 
     */
    private $highlightedContent;
    private $foundStringIndexes = [];

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



    /**
     * Get the content modified if the note matches a searched string
     */ 
    public function getHighlightedContent()
    {
        return $this->highlightedContent;
    }

    /**
     * Set the content modified if the note matches a searched string
     *
     * @return  self
     */ 
    public function setHighlightedContent($highlightedContent)
    {
        $this->highlightedContent = $highlightedContent;

        return $this;
    }

    /**
     * isContentMatching
     */
    public function isContentMatching($stringToSearch) : array
    {
        $encoding = mb_detect_encoding($this->content);
        
        $fromIndex = 0;
        $indexFound = 0;
        $length = mb_strlen($stringToSearch);
        $this->highlightedContent = '';
        $this->foundStringIndexes = [];

        //
        //
        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

            $this->foundStringIndexes[] = $indexFound;

            if (count($this->foundStringIndexes) > 1)
                $this->highlightedContent .= mb_substr($this->content, $fromIndex, $indexFound - $fromIndex, $encoding);
            else
                $this->highlightedContent = mb_substr($this->content, $fromIndex, $indexFound, $encoding);

            $this->highlightedContent .= '<a href="book/' . $this->book->getSlug() . '/matchingNote/' . $this->citation . '">';
            $this->highlightedContent .= '<span class="found-content">';
            $this->highlightedContent .= mb_substr($this->content, $indexFound, $length, $encoding);
            $this->highlightedContent .= '</span></a>';

            $fromIndex = $indexFound + $length;
            
        }
        if ($this->foundStringIndexes){

            $this->highlightedContent .= mb_substr($this->content, $fromIndex, NULL, $encoding);

        }

        // false if empty !! ?-/
        return ($this->foundStringIndexes);

    }



    /**
     * Get the value of foundStringIndexes
     */ 
    public function getFoundStringIndexes()
    {
        return $this->foundStringIndexes;
    }

    /**
     * Set the value of foundStringIndexes
     *
     * @return  self
     */ 
    public function setFoundStringIndexes($foundStringIndexes)
    {
        $this->foundStringIndexes = $foundStringIndexes;

        return $this;
    }
}
