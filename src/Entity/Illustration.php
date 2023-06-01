<?php

namespace App\Entity;

use App\Repository\IllustrationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=IllustrationRepository::class)
 */
class Illustration
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
    private $illustrationIndex;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fileName;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $mimeType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $svgTitle;

    /**
     * @ORM\Column(type="integer")
     */
    private $svgWidth;

    /**
     * @ORM\Column(type="integer")
     */
    private $svgHeight;

    /**
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="illustrations")
     */
    private $bookParagraph;

    /**
     * @ORM\ManyToOne(targetEntity=BookNote::class, inversedBy="illustrations")
     */
    private $bookNote;

    /**
     * @ORM\ManyToOne(targetEntity=CellParagraph::class, inversedBy="illustrations")
     */
    private $cellParagraph;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getillustrationIndex(): ?int
    {
        return $this->illustrationIndex;
    }

    public function setillustrationIndex(int $illustrationIndex): self
    {
        $this->illustrationIndex = $illustrationIndex;

        return $this;
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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSvgTitle(): ?string
    {
        return $this->svgTitle;
    }

    public function setSvgTitle(?string $svgTitle): self
    {
        $this->svgTitle = $svgTitle;

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

    public function getSvgWidth(): ?int
    {
        return $this->svgWidth;
    }

    public function setSvgWidth(int $svgWidth): self
    {
        $this->svgWidth = $svgWidth;

        return $this;
    }

    public function getSvgHeight(): ?int
    {
        return $this->svgHeight;
    }

    public function setSvgHeight(int $svgHeight): self
    {
        $this->svgHeight = $svgHeight;

        return $this;
    }

    /**
     * Get the value of bookNote
     */ 
    public function getBookNote()
    {
        return $this->bookNote;
    }

    /**
     * Set the value of bookNote
     *
     * @return  self
     */ 
    public function setBookNote($bookNote)
    {
        $this->bookNote = $bookNote;

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
}
