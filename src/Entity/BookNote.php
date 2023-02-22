<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
// use App\Service\ContentMgr;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookNoteRepository;
use ApiPlatform\Core\Annotation\ApiResource;
// use ApiPlatform\Metadata\ApiResource;


/**
 *  @ORM\Entity(repositoryClass=BookNoteRepository::class)
 *  @ApiResource()
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
     * @ORM\ManyToOne(targetEntity=BookParagraph::class, inversedBy="notes")
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
     * @ORM\OneToMany(targetEntity=TextAlteration::class, mappedBy="bookNote", orphanRemoval=true)
     */
    private $alterations;

    /**
     * @ORM\OneToMany(targetEntity=Illustration::class, mappedBy="bookNote", orphanRemoval=true)
     */
    private $illustrations;


    /**
     * 
     */    
    private $foundStringIndexes = [];
    private $searchedString = '';
    private $nextOccurence;
    private $highlightedContent;

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

        // ajout des mises en forme permanentes ( bold, italic, ..)
        if (count($this->alterations)){
            foreach ($this->alterations as $alteration){
                $i = $alteration->getPosition();
                $l = $alteration->getLength();
                $htmlToInsert[] = [ 'index'=>$i, 'string'=>$alteration->getBeginTag() ];
                if ($l > 0 ) $htmlToInsert[] = [ 'index'=>$i+$l, 'string'=>$alteration->getEndTag() ];
            }
        }

        // les index des sous-chaîne(s) à afficher en surbrillance
        if (count($this->foundStringIndexes)){

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

        }
        
        // illustrations
        if (count($this->illustrations)){

            foreach($this->illustrations as $illustration){

                $mimeType = $illustration->getMimeType();
                if ($mimeType){

                    $str = '<img src="'
                            . $illustration->getFileName()
                            . '" alt="'
                            . $illustration->getName()
                            . '" title="'
                            . $illustration->getSvgTitle();

                    if($mimeType == "image/jpeg"){
                        $str .= '" width="'
                                . $illustration->getSvgWidth()
                                . '" height="'
                                . $illustration->getSvgHeight()
                                . '" style="'
                                . "margin:0px 5px";
                    }
                    // else $mimeType could be "image/svg+xml" , "image/png"
                    //

                    $str .= '">';
                    $htmlToInsert[] = ['index' => $illustration->getIllustrationIndex(), 'string' => $str];
                }
                else {
                    // $mimeType null or "" not set ..


                    // $str = '<img src="'
                    //         . "/default-image.jpg"
                    //         . '" alt="'
                    //         . "image par défaut"
                    //         . '" width="'
                    //         . "20"
                    //         . '" height="'
                    //         . "20"
                    //         . '" title="'
                    //         . "format d'image non supporté"
                    //         . '" style="'
                    //         . "margin:0px 5px"
                    //         . '">';
                }

            }
        }

        //
        if ($count=count($htmlToInsert)){

            //
            //
            $indexes = array_column($htmlToInsert, 'index');
            array_multisort($indexes, SORT_ASC, $htmlToInsert);

            //
            //
            $currentIndex = 0;
            $insertIndex = 0;
            for($i=0; $i<$count; $i++){

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
