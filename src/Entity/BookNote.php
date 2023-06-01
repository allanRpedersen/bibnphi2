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
    private $nbOccurrencesInBook = 0;
    
    /**
     * le numéro dans le livre de la première occurrence trouvée dans le paragraphe
     */
    private $firstOccurrenceInNote;

    /**
     * @ORM\ManyToOne(targetEntity=CellParagraph::class, inversedBy="notes")
     */
    private $cellParagraph;


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
        $nbOccurencesInNote = count($this->foundStringIndexes);
        if ($nbOccurencesInNote){

            // use <mark></mark> to highlight an occurrence of a found string

            $endTag = '</a></mark>';
            $strLength = mb_strlen($this->searchedString);

            $currentOccurrenceInBook = $this->firstOccurrenceInNote;

            foreach($this->foundStringIndexes as $foundStringIndex){

                $nextOccurrenceInBook = $currentOccurrenceInBook == $this->nbOccurrencesInBook ? 1 : $currentOccurrenceInBook+1;

                $beginTag = '<mark id="occurrence_' . $currentOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">'
                            . '<a title="aller à la prochaine occurrence"'
                            . ' href="#occurrence_'
                            . $nextOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">';

                $htmlToInsert[] = [ 'index'=>$foundStringIndex, 'string'=>$beginTag ];
                $htmlToInsert[] = [ 'index'=>$foundStringIndex + $strLength, 'string'=>$endTag ];

                $currentOccurrenceInBook++;
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
                                . '" style="margin:Opx 5px;';
                    }
                    // else $mimeType could be "image/svg+xml" , "image/png", "image/gif"
                    else {
                        $str .= '" style="max-width:100%; margin:Opx 5px;';
                    }

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
        $this->foundStringIndexes = [];
        $this->searchedString = $stringToSearch;
        $strLength = mb_strlen($this->searchedString);

        //
        //
        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

            $this->foundStringIndexes[] = $indexFound;
            $fromIndex = $indexFound + $strLength;

        }

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
    public function setFoundStringIndexes($foundStringIndexes): self
    {
        $this->foundStringIndexes = $foundStringIndexes;

        return $this;
    }

    /**
     * Set the value of nextOccurence
     *
     * @return  self
     */ 
    public function setNextOccurence($nextOccurence): self
    {
        $this->nextOccurence = $nextOccurence;

        return $this;
    }

    /**
     * Set the value of searchedString
     *
     * @return  self
     */ 
    public function setSearchedString($searchedString): self
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

    /**
     * Get the value of nbOccurrencesInBook
     */ 
    public function getNbOccurrencesInBook()
    {
        return $this->nbOccurrencesInBook;
    }

    /**
     * Set the value of nbOccurrencesInBook
     *
     * @return  self
     */ 
    public function setNbOccurrencesInBook($nbOccurrencesInBook)
    {
        $this->nbOccurrencesInBook = $nbOccurrencesInBook;

        return $this;
    }

    /**
     * Get the value of firstOccurrenceInNote
     */ 
    public function getFirstOccurrenceInNote()
    {
        return $this->firstOccurrenceInNote;
    }

    /**
     * Set the value of firstOccurrenceInNote
     *
     * @return  self
     */ 
    public function setFirstOccurrenceInNote($firstOccurrenceInNote)
    {
        $this->firstOccurrenceInNote = $firstOccurrenceInNote;

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
