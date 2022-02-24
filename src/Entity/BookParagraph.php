<?php

namespace App\Entity;

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

    // private $matchingSentences;

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
    
    // Collection des indices d'occurence de la chaine recherchée dans le paragraphe
    private $foundStringsIndexes;

    // private $matchingSentence = [
    //     'book'=> $this->book,    
    //     'sentence' => "",
    //     'iNeedle' => NULL,
    // ];

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->foundStringsIndexes = new ArrayCollection();
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

	
	public function getMatchingSentences($stringToSearch): ?Collection
    {
        $this->matchingSentences = new ArrayCollection();

        // foreach($this->sentences as $sentence){

        //        //
        //        $content = $sentence->getContent();

        //        $encoding = mb_detect_encoding($content);
        //        $length = mb_strlen($stringToSearch);

        // 	$iNeedle = mb_stripos($content, $stringToSearch, 0, $encoding);

        // 	if(FALSE !== $iNeedle){

        //            $tmp = mb_substr($content, 0, $iNeedle, $encoding);
        //            $tmp .= '<strong>';
        //            $tmp .= mb_substr($content, $iNeedle, $length, $encoding);
        //            $tmp .= '</strong>';
        //            $tmp .= mb_substr($content, $iNeedle + $length, NULL, $encoding);

        //            $sentence->setContent($tmp);
                
        // 		$this->matchingSentences->add([$iNeedle, $sentence]);

        // 	}

        // }
        
        return $this->matchingSentences;
    }

    public function isContentMatching($stringToSearch) : array
    {
        $encoding = mb_detect_encoding($this->content);
        
        $fromIndex = 0;
        $indexFound = 0;
        $length = mb_strlen($stringToSearch);
        $this->highlightedContent = '';
        $this->foundStringsIndexes = [];

        //
        //
        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

            $this->foundStringsIndexes[] = $indexFound;

            if (count($this->foundStringsIndexes) > 1)
                $this->highlightedContent .= mb_substr($this->content, $fromIndex, $indexFound - $fromIndex, $encoding);
            else
                $this->highlightedContent = mb_substr($this->content, $fromIndex, $indexFound, $encoding);

            $this->highlightedContent .= '<a href="book/' . $this->book->getSlug() . '/matchingParagraph/' . $this->id . '">';
            $this->highlightedContent .= '<span class="found-content">';
            $this->highlightedContent .= mb_substr($this->content, $indexFound, $length, $encoding);
            $this->highlightedContent .= '</span></a>';

            $fromIndex = $indexFound + $length;
            
        }
        if ($this->foundStringsIndexes){

            $this->highlightedContent .= mb_substr($this->content, $fromIndex, NULL, $encoding);

        }

        // false if empty !! ?-/
        return ($this->foundStringsIndexes);

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
        return $this->content;
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
     * Get the value of foundStringsIndexes
     */ 
    public function getFoundStringsIndexes()
    {
        return $this->foundStringsIndexes;
    }
}
