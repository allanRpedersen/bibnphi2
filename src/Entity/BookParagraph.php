<?php

namespace App\Entity;

use App\Service\ContentMgr;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookParagraphRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=BookParagraphRepository::class)
 */
class BookParagraph
{
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
     * @ORM\Column(type="text")
     * 
     * Le contenu du paragraphe
     * 
     */
    private $content;

    /**
     * 
     * Dans le cas d'une recherche réussie, le contenu du paragraphe
     * est enrichi par des balises <span class="found-content"></span>
     * qui encadrent les chaînes recherchées.
    */
    private $highlightedContent;
    
    // Tableau/Collection des indices des occurences de la chaine recherchée dans le paragraphe
    private $foundStringIndexes = [];

    private $searchResult = [
        'needle' => '',
        'indexes' => [],
    ];

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        // $this->foundStringIndexes = new ArrayCollection();

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

    public function isContentMatching($stringToSearch) : array
    {
        $encoding = mb_detect_encoding($this->content);
        
        $fromIndex = 0;
        $indexFound = 0;
        $strLength = mb_strlen($stringToSearch);
        $this->highlightedContent = '';
        $this->foundStringIndexes = [];

        //
        //
        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){
            
            $this->foundStringIndexes[] = $indexFound;
            $fromIndex = $indexFound + $strLength;
        }    

        if ($this->foundStringIndexes){
        
            $contentMgr = new ContentMgr();
            $beginTag = '<a title="Aller dans l\'ouvrage" href="book/'
                        . $this->book->getSlug()
                        . '/jumpTo/_'
                        . $this->id
                        . '"><mark>';
            $endTag = '</mark></a>';
    
            $this->highlightedContent = $contentMgr
                                            ->setOriginalContent($this->content)
                                            ->addTags($this->foundStringIndexes, $strLength, $beginTag, $endTag);

        }

        $this->searchResult[] = [$stringToSearch, $this->foundStringIndexes];

        // false if empty !!
        return ($this->foundStringIndexes);

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

    public function getContent(): ?string
    {
        return($this->highlightedContent?$this->highlightedContent:$this->content);
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getHighlightedContent(): ?string
    {
        return $this->highlightedContent;
    }

    public function setHighlightedContent(string $highlightedContent): self
    {
        $this->highlightedContent = $highlightedContent;

        return $this;
    }

    /**
     * Get the value of foundStringIndexes
     */ 
    public function getFoundStringIndexes()
    {
        return $this->foundStringIndexes;
    }
}
