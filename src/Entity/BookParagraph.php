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
     * Le contenu du paragraphe 'raw' cad sans aucun attribut de mise en forme ou de citation de note
     * 
     */
    private $content;

    /**
     * Le contenu mise en forme pour l'affichage.
     * C'est le contenu brut dans lequel sont ajoutés des balises de mise en forme.
     * 
     * 1- présence de notes, ajout des citations encadrées par des balises <sup><a href="note_$noteId>..</a></sup>
     * 2- application du surlignage <mark>..</mark>
     * 3- application de styles, <strong>, <em>, .. (à venir)
     * 
     */
    private $formattedContent;

    /**
     * 
     * Dans le cas d'une recherche réussie, le contenu du paragraphe
     * est enrichi par des balises <span class="found-content"></span>
     * qui encadrent les chaînes recherchées.
    */
    private $highlightedContent;
    
    // Tableau/Collection des indices des occurences de la chaine recherchée dans le paragraphe
    private $foundStringIndexes = [];
    private $searchedString = '';
    private $nextOccurence;

    private $noteCitationIndexes = [];

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
        $this->searchedString = $stringToSearch;

        // $excludedZones = [];
        // $excludedZone = [
        //     'begin' => 0,
        //     'end'   => 0,
        // ];


        // // if there are notes in this paragraph, build an exclusion zone mapping the tags <sup>..</sup>
        // if ($this->notes){
        //     $excludedZoneSize = 0;
        //     foreach($this->notes as $key => $note){

        //         $begin = $note->getCitationIndex() + $key * $excludedZoneSize; // index where <sup> has been added + n *
        //         $end = mb_strpos($this->content, '</sup>', $begin ) + 6; // 6 == sizeof('</sup>') !!!! !-/
        //         if (!$excludedZoneSize) $excludedZoneSize = $end - $begin; // always the same size !

        //         $excludedZone = ['begin'=>$begin,'end'=>$end];
        //         $excludedZones[] = $excludedZone;
        //     }
        // }

        // //
        // //
        // while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){
        //     //
        //     //
        //     $excluded = false;
        //     if ($excludedZones){
        //         foreach($excludedZones as $excludedZone){

        //             if($indexFound >= $excludedZone['begin'] && $indexFound < $excludedZone['end']){
        //                 $fromIndex = $excludedZone['end'];
        //                 $excluded = true;
        //                 break;
        //             }
        //         }
        //     }
        //     //
        //     // if the index is not in an excluded area !!
        //     // surrounded by : <sup> .. </sup>
        //     //
        //     if (!$excluded) {
        // }

        while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

            $this->foundStringIndexes[] = $indexFound;
            $fromIndex = $indexFound + $strLength;

        }

        //
        //
        // if ($this->foundStringIndexes){
        
        //     $contentMgr = new ContentMgr();
        //     $beginTag = '<a title="Aller dans l\'ouvrage" href="book/'
        //                 . $this->book->getSlug()
        //                 . '/jumpTo/_'
        //                 . $this->id
        //                 . '"><mark>';
        //     $endTag = '</mark></a>';
    
        //     $this->highlightedContent = $contentMgr
        //                                     ->setOriginalContent($this->content)
        //                                     ->addTags($this->foundStringIndexes, $strLength, $beginTag, $endTag);

        // }

        // $this->searchResult[] = [$stringToSearch, $this->foundStringIndexes];



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

    public function getContent($raw=false): ?string
    {
        if ($raw) return($this->content);
        return($this->getFormattedContent());
        // return($this->highlightedContent?$this->highlightedContent:$this->content);
        // return($this->content);
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
     * construit et retourne le contenu mise en forme pour l'affichage.
     */ 
    public function getFormattedContent(): string
    {
        //
        $htmlToInsert = [];
        $formattedContent = '';

        // du coup on "glane" les mises en forme

        // sous-chaîne(s) à afficher en surbrillance
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
                . '/jumpTo/_'
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

        // ajouts des notes
        if (count($this->notes)){

            foreach($this->notes as $note){

                $str =  '<sup id="citation_'
                        . $note->getCitation()
                        . '"><a class="" href="#note_'
                        . $note->getId()
                        .'">'
                        . $note->getCitation()
                        . '</a></sup>';

                $htmlToInsert[] = ['index' => $note->getCitationIndex(), 'string' => $str];

            }
        }

        // si mises en forme, on les insère ..
        if ($htmlToInsert){

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
        // sinon, on retourne le contenu "cru"
        return $this->content;
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
     * Get the value of foundStringIndexes
     */ 
    public function getFoundStringIndexes()
    {
        return $this->foundStringIndexes;
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
     * Set the value of nextOccurence
     *
     * @return  self
     */ 
    public function setNextOccurence($nextOccurence)
    {
        $this->nextOccurence = $nextOccurence;

        return $this;
    }
}
