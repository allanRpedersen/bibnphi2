<?php

namespace App\Entity;

// use App\Service\ContentMgr;
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
     * 
     */    
    private $foundStringIndexes = [];
    private $searchedString = '';
    private $nextOccurence;

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
     * 
     */
    public function getFormattedContent(): string
    {
        $htmlToInsert = [];
        $formattedContent = '';

        // les index des sous-chaîne(s) à afficher en surbrillance
        if (count($this->foundStringIndexes)){

            // $beginTag = '<mark>';
            // $endTag = '</mark>';

            if ($this->nextOccurence){

                $beginTag = '<a title="Aller à la prochaine occurrence" href="#'
                . $this->nextOccurence
                . '"><mark>';
            }
            else
            {   
                $beginTag = '<a title="Aller dans l\'ouvrage" href="book/'
                . $this->book->getSlug()
                . '/jumpTo/note_'
                . $this->id
                . '"><mark>';
            }
	    	
            $endTag = '</mark></a>';

            $strLength = mb_strlen($this->searchedString);

            foreach($this->foundStringIndexes as $foundStringIndex){

                    $htmlToInsert[] = [ 'index'=>$foundStringIndex, 'string'=>$beginTag ];
                    $htmlToInsert[] = [ 'index'=>$foundStringIndex + $strLength, 'string'=>$endTag ];

            }
                //
            //
            $indexes = array_column($htmlToInsert, 'index');
            array_multisort($indexes, SORT_ASC, $htmlToInsert);

            //
            //
            $currentIndex = 0;
            $insertIndex = 0;
            for($i=0; $i<count($htmlToInsert); $i++){

                $insertIndex = $htmlToInsert[$i]['index'];
                $formattedContent .= mb_substr($this->content, $currentIndex, $insertIndex-$currentIndex);
                $formattedContent .= $htmlToInsert[$i]['string'];
                $currentIndex = $insertIndex;
            }
            $formattedContent .= mb_substr($this->content, $insertIndex);

            return $formattedContent;

        }

        return $this->content;

    }

    // /**
    //  * Get the content modified if the note matches a searched string
    //  */ 
    // public function getHighlightedContent()
    // {
    //     return $this->highlightedContent;
    // }

    // /**
    //  * Set the content modified if the note matches a searched string
    //  *
    //  * @return  self
    //  */ 
    // public function setHighlightedContent($highlightedContent)
    // {
    //     $this->highlightedContent = $highlightedContent;

    //     return $this;
    // }

    /**
     * isContentMatching
     */
    public function isContentMatching($stringToSearch) : array
    {
        $encoding = mb_detect_encoding($this->content);
        
        $fromIndex = 0;
        $indexFound = 0;
        $this->highlightedContent = '';
        $this->foundStringIndexes = [];
        $this->searchedString = $stringToSearch;
        $strLength = mb_strlen($this->searchedString);

        //
        //
        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

            $this->foundStringIndexes[] = $indexFound;
            $fromIndex = $indexFound + $strLength;

        }

        // if ($this->foundStringIndexes){
        
        //     $contentMgr = new ContentMgr();
        //     $beginTag = '<a title="Aller dans l\'ouvrage" href="book/'
        //                 . $this->book->getSlug()
        //                 . '/jumpTo/note_'
        //                 . $this->id
        //                 . '"><mark>';
        //     $endTag = '</mark></a>';
    
        //     $this->highlightedContent = $contentMgr
        //                                     ->setOriginalContent($this->content)
        //                                     ->addTags($this->foundStringIndexes, $strLength, $beginTag, $endTag);
    
        // }

        // false if empty !!
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

    /**
     * Set the value of nextOccurence
     *
     * @return  self
     */ 
    public function setNextOccurence($nextOccurence)
    {
        $this->nextOccurence = $nextOccurence;

        return $this;
    }

    /**
     * Set the value of searchedString
     *
     * @return  self
     */ 
    public function setSearchedString($searchedString)
    {
        $this->searchedString = $searchedString;

        return $this;
    }
}
