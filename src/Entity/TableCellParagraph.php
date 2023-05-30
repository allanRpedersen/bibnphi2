<?php

namespace App\Entity;

use App\Repository\TableCellParagraphRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TableCellParagraphRepository::class)
 */
class TableCellParagraph extends BookParagraph
{
    public function __construct()
    {
        BookParagraph::__construct();
    }


    /**
     * @ORM\ManyToOne(targetEntity=TableCell::class, inversedBy="cellParagraphs", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $tableCell;

    // public function getId(): ?int
    // {
    //     return $this->id;
    // }

    public function getTableCell(): ?TableCell
    {
        return $this->tableCell;
    }

    public function setTableCell(?TableCell $tableCell): self
    {
        $this->tableCell = $tableCell;

        return $this;
    }
}
