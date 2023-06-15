<?php

namespace App\Entity;

use App\Model\TraitContentMgr;
use App\Repository\CellParagraphRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CellParagraphRepository::class)
 */
class CellParagraph
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
     * @ORM\ManyToOne(targetEntity=TableCell::class, inversedBy="cellParagraphs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tableCell;

    /**
     * @ORM\OneToMany(targetEntity=TextAlteration::class, mappedBy="cellParagraph", orphanRemoval=true)
     */
    private $alterations;

    /**
     * @ORM\OneToMany(targetEntity=Illustration::class, mappedBy="cellParagraph", orphanRemoval=true)
     */
    private $illustrations;

    /**
     * @ORM\OneToMany(targetEntity=BookNote::class, mappedBy="cellParagraph", orphanRemoval=true)
     */
    private $notes;

    private $bookTable;

    public function __construct()
    {
        $this->alterations = new ArrayCollection();
        $this->illustrations = new ArrayCollection();
        $this->notes = new ArrayCollection();
        $this->bookTable = null;
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $alteration->setCellParagraph($this);
        }

        return $this;
    }

    public function removeAlteration(TextAlteration $alteration): self
    {
        if ($this->alterations->removeElement($alteration)) {
            // set the owning side to null (unless already changed)
            if ($alteration->getCellParagraph() === $this) {
                $alteration->setCellParagraph(null);
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
            $illustration->setCellParagraph($this);
        }

        return $this;
    }

    public function removeIllustration(Illustration $illustration): self
    {
        if ($this->illustrations->removeElement($illustration)) {
            // set the owning side to null (unless already changed)
            if ($illustration->getCellParagraph() === $this) {
                $illustration->setCellParagraph(null);
            }
        }

        return $this;
    }

    public function getTableCell(): ?TableCell
    {
        return $this->tableCell;
    }

    public function setTableCell(?TableCell $tableCell): self
    {
        $this->tableCell = $tableCell;

        return $this;
    }

    /**
     * @return Collection<int, BookNote>
     */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(BookNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setCellParagraph($this);
        }

        return $this;
    }

    public function removeNote(BookNote $note): self
    {
        if ($this->notes->removeElement($note)) {
            // set the owning side to null (unless already changed)
            if ($note->getCellParagraph() === $this) {
                $note->setCellParagraph(null);
            }
        }

        return $this;
    }

    /**
     * Get the value of bookTable
     */ 
    public function getBookTable()
    {
        return $this->bookTable;
    }

    /**
     * Set the value of bookTable
     *
     * @return  self
     */ 
    public function setBookTable($bookTable)
    {
        $this->bookTable = $bookTable;

        return $this;
    }
}

