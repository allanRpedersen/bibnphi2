<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookNoteRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Traits\TraitContentMgr;

// use ApiPlatform\Metadata\ApiResource;


/**
 *  @ORM\Entity(repositoryClass=BookNoteRepository::class)
 *  @ApiResource()
 */
class BookNote
{
    /**
     * 
     */
    use TraitContentMgr;

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
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="notes")
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
     * @ORM\OneToMany(targetEntity=TextAlteration::class, mappedBy="bookNote", orphanRemoval=true)
     */
    private $alterations;

    /**
     * @ORM\OneToMany(targetEntity=Illustration::class, mappedBy="bookNote", orphanRemoval=true)
     */
    private $illustrations;

    /**
     * @ORM\ManyToOne(targetEntity=CellParagraph::class, inversedBy="notes")
     */
    private $cellParagraph;

    /**
     * la propriété $notes n'est ici que pour satisfaire 
     * TraitContentMgr::getFormattedContent() !!!
     * 
     * no notes in notes !!!
     */
    private $notes = [];


    public function __construct()
    {
        $this->alterations = new ArrayCollection();
        $this->illustrations = new ArrayCollection();
    }

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

    public function getCellParagraph(): ?CellParagraph
    {
        return $this->cellParagraph;
    }
    public function setCellParagraph(?CellParagraph $cellParagraph): self
    {
        $this->cellParagraph = $cellParagraph;

        return $this;
    }

    /**
     * @return Collection<int, TextAlteration>
     */
    public function getAlterations(): Collection
    {
        return $this->alterations;
    }
    public function addAlteration(TextAlteration $alteration): self
    {
        if (!$this->alterations->contains($alteration)) {
            $this->alterations[] = $alteration;
            $alteration->setBookNote($this);
        }

        return $this;
    }
    public function removeAlteration(TextAlteration $alteration): self
    {
        if ($this->alterations->removeElement($alteration)) {
            // set the owning side to null (unless already changed)
            if ($alteration->getBookNote() === $this) {
                $alteration->setBookNote(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Illustration>
     */
    public function getIllustrations(): Collection
    {
        return $this->illustrations;
    }
    public function addIllustration(Illustration $illustration): self
    {
        if (!$this->illustrations->contains($illustration)) {
            $this->illustrations[] = $illustration;
            $illustration->setBookNote($this);
        }

        return $this;
    }
    public function removeIllustration(Illustration $illustration): self
    {
        if ($this->illustrations->removeElement($illustration)) {
            // set the owning side to null (unless already changed)
            if ($illustration->getBookNote() === $this) {
                $illustration->setBookNote(null);
            }
        }

        return $this;
    }

}
