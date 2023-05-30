<?php

namespace App\Entity;

use App\Repository\BookTableRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BookTableRepository::class)
 */
class BookTable
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
    private $nbColumns;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbRows;

    /**
     * @ORM\OneToMany(targetEntity=TableCell::class, mappedBy="bookTable", orphanRemoval=true)
     */
    private $cells;

    /**
     * @ORM\OneToOne(targetEntity=BookParagraph::class, inversedBy="bookTable", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $anchorParagraph; // un paragraphe vide auquel est raccroché le tableau

    public function __construct()
    {
        $this->cells = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNbColumns(): ?int
    {
        return $this->nbColumns;
    }

    public function setNbColumns(int $nbColumns): self
    {
        $this->nbColumns = $nbColumns;

        return $this;
    }

    public function getNbRows(): ?int
    {
        return $this->nbRows;
    }

    public function setNbRows(int $nbRows): self
    {
        $this->nbRows = $nbRows;

        return $this;
    }

    /**
     * @return Collection<int, TableCell>
     */
    public function getCells(): Collection
    {
        return $this->cells;
    }

    public function addCell(TableCell $cell): self
    {
        if (!$this->cells->contains($cell)) {
            $this->cells[] = $cell;
            $cell->setBookTable($this);
        }

        return $this;
    }

    public function removeCell(TableCell $cell): self
    {
        if ($this->cells->removeElement($cell)) {
            // set the owning side to null (unless already changed)
            if ($cell->getBookTable() === $this) {
                $cell->setBookTable(null);
            }
        }

        return $this;
    }

    public function getAnchorParagraph(): ?BookParagraph
    {
        return $this->anchorParagraph;
    }

    public function setAnchorParagraph(BookParagraph $anchorParagraph): self
    {
        $this->anchorParagraph = $anchorParagraph;

        return $this;
    }


}
