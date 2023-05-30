<?php

namespace App\Entity;

use App\Repository\TableCellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TableCellRepository::class)
 */
class TableCell
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=BookTable::class, inversedBy="cells")
     * @ORM\JoinColumn(nullable=false)
     */
    private $bookTable;

    /**
     * @ORM\OneToMany(targetEntity=TableCellParagraph::class, mappedBy="tableCell", orphanRemoval=true)
     */
    private $cellParagraphs;

    public function __construct()
    {
        $this->cellParagraphs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookTable(): ?BookTable
    {
        return $this->bookTable;
    }

    public function setBookTable(?BookTable $bookTable): self
    {
        $this->bookTable = $bookTable;

        return $this;
    }

    /**
     * @return Collection<int, TableCellParagraph>
     */
    public function getCellParagraph(): Collection
    {
        return $this->cellParagraphs;
    }

    public function addCellParagraph(TableCellParagraph $cellParagraph): self
    {
        if (!$this->cellParagraphs->contains($cellParagraph)) {
            $this->cellParagraphs[] = $cellParagraph;
            $cellParagraph->setTableCell($this);
        }

        return $this;
    }

    public function removeCellParagraph(TableCellParagraph $cellParagraph): self
    {
        if ($this->cellParagraphs->removeElement($cellParagraph)) {
            // set the owning side to null (unless already changed)
            if ($cellParagraph->getTableCell() === $this) {
                $cellParagraph->setTableCell(null);
            }
        }

        return $this;
    }
}
