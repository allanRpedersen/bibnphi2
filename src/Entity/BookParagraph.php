<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookParagraphRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Traits\TraitContentMgr;
// use ApiPlatform\Metadata\ApiResource;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=BookParagraphRepository::class)
 * @ApiResource()
 */
class BookParagraph
{
    /**
     * 
     */
    use TraitContentMgr;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Book::class, inversedBy="bookParagraphs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $book;

    /**
     * @ORM\OneToMany(targetEntity=BookNote::class, mappedBy="bookParagraph", orphanRemoval=true)
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity=TextAlteration::class, mappedBy="bookParagraph", orphanRemoval=true)
     * 
     * La liste des altérations applicables au contenu du paragraphe
     */
    private $alterations;

    /**
     * @ORM\OneToMany(targetEntity=Illustration::class, mappedBy="bookParagraph", orphanRemoval=true)
     */
    private $illustrations;

     /**
     * @ORM\OneToOne(targetEntity=BookTable::class, mappedBy="anchorParagraph", cascade={"persist", "remove"})
     */ 
    private $bookTable; // si positionné, ce paragraphe sert d'ancre à un tableau


    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->alterations = new ArrayCollection();
        $this->illustrations = new ArrayCollection();
        // $this->content = '';
        $this->bookTable = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }
    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    /**
     * @return Collection|BookNote[]
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }
    public function addNote(BookNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setBookParagraph($this);
        }

        return $this;
    }
    public function removeNote(BookNote $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getBookParagraph() === $this) {
                $note->setBookParagraph(null);
            }
        }

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
            $alteration->setBookParagraph($this);
        }

        return $this;
    }
    public function removeAlteration(TextAlteration $alteration): self
    {
        if ($this->alterations->removeElement($alteration)) {
            // set the owning side to null (unless already changed)
            if ($alteration->getBookParagraph() === $this) {
                $alteration->setBookParagraph(null);
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
            $illustration->setBookParagraph($this);
        }

        return $this;
    }
    public function removeIllustration(Illustration $illustration): self
    {
        if ($this->illustrations->removeElement($illustration)) {
            // set the owning side to null (unless already changed)
            if ($illustration->getBookParagraph() === $this) {
                $illustration->setBookParagraph(null);
            }
        }

        return $this;
    }

    public function getBookTable(): ?BookTable
    {
        return $this->bookTable;
    }
    public function setBookTable(BookTable $bookTable): self
    {
        // set the owning side of the relation if necessary
        if ($bookTable->getAnchorParagraph() !== $this) {
            $bookTable->setAnchorParagraph($this);
        }

        $this->bookTable = $bookTable;

        return $this;
    }

/*********************************************************************
 * 
 *********************************************************************/

}
