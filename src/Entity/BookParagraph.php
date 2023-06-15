<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookParagraphRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Model\TraitContentMgr;
// use ApiPlatform\Metadata\ApiResource;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass=BookParagraphRepository::class)
 * @ApiResource()
 */
class BookParagraph
{
    /**
     * 
     */
    use TraitContentMgr;

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
     * @ORM\Column(type="text")
     * 
     * Le contenu brut du paragraphe cad sans aucun attribut de mise en forme ou de citation de note
     * 
     */
    // private $content;

    /**
     * @ORM\OneToMany(targetEntity=BookNote::class, mappedBy="bookParagraph", orphanRemoval=true)
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity=TextAlteration::class, mappedBy="bookParagraph", orphanRemoval=true)
     * 
     * La liste des altérations applicables au contenu du paragraphe
     */
    private $alterations;

    /**
     * @ORM\OneToMany(targetEntity=Illustration::class, mappedBy="bookParagraph", orphanRemoval=true)
     */
    private $illustrations;

    /**
     * @ORM\Column(type="string", length=255)
     * 
     * La chaine de caractères qui contient les attributs de mise en forme applicables au paragraphe entier
     * 
     * e.g. "text-align:justify; font-weight: normal; font-style: normal;"
     * 
     * text-align: start || end || center || justify
     * font-weight: normal || bold || light
     * font-style: normal || italic
     * 
     */
    // private $paragraphStyles;

    // // Recherche de chaîne de cararactères dans le paragraphe.
    // //
    // private $foundStringIndexes = [];       // Les indices des occurences de la chaine recherchée dans le paragraphe
    // private $searchedString = '';           // La chaîne recherchée
    // private $nextOccurence;                 // la prochaine occurence dans le livre (paragraphe ou note)
    // private $nbOccurrencesInBook;           //  
    // private $firstOccurrenceInParagraph;    // le numéro dans le livre de la première occurrence trouvée dans le paragraphe

    /**
     * @ORM\OneToOne(targetEntity=BookTable::class, mappedBy="anchorParagraph", cascade={"persist", "remove"})
     */
    private $bookTable; // si positionné, ce paragraphe sert d'ancre à un tableau


    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->alterations = new ArrayCollection();
        $this->illustrations = new ArrayCollection();
        // $this->content = '';
        $this->bookTable = null;
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
            $alteration->setBookParagraph($this);
        }

        return $this;
    }
    public function removeAlteration(TextAlteration $alteration): self
    {
        if ($this->alterations->removeElement($alteration)) {
            // set the owning side to null (unless already changed)
            if ($alteration->getBookParagraph() === $this) {
                $alteration->setBookParagraph(null);
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
            $illustration->setBookParagraph($this);
        }

        return $this;
    }
    public function removeIllustration(Illustration $illustration): self
    {
        if ($this->illustrations->removeElement($illustration)) {
            // set the owning side to null (unless already changed)
            if ($illustration->getBookParagraph() === $this) {
                $illustration->setBookParagraph(null);
            }
        }

        return $this;
    }

    public function getBookTable(): ?BookTable
    {
        return $this->bookTable;
    }
    public function setBookTable(BookTable $bookTable): self
    {
        // set the owning side of the relation if necessary
        if ($bookTable->getAnchorParagraph() !== $this) {
            $bookTable->setAnchorParagraph($this);
        }

        $this->bookTable = $bookTable;

        return $this;
    }

    // /**
    //  * Set the value of nextOccurence
    //  *
    //  * @return  self
    //  */ 
    // public function setNextOccurence($nextOccurence): self
    // {
    //     $this->nextOccurence = $nextOccurence;

    //     return $this;
    // }



/*********************************************************************
 * 
 *********************************************************************/

// public function getContent($raw=false): ?string
// {
//     return($this->content);
// }
// public function setContent(string $content): self
// {
//     $this->content = $content;

//     return $this;
// }

// /**
//  * 
//  */
// public function isContentMatching($stringToSearch) : array
// {
//     $encoding = mb_detect_encoding($this->content);
    
//     $fromIndex = 0;
//     $indexFound = 0;
//     $strLength = mb_strlen($stringToSearch);

//     // $this->highlightedContent = '';
//     $this->foundStringIndexes = [];
//     $this->searchedString = $stringToSearch;

//     //
//     //
//     while (FALSE !== ($indexFound = mb_stripos($this->content, $stringToSearch, $fromIndex, $encoding))){

//         $this->foundStringIndexes[] = $indexFound;
//         $fromIndex = $indexFound + $strLength;

//     }

//     // false if empty !!
//     return ($this->foundStringIndexes);
// }
// /**
//  * Construit et retourne le contenu mise en forme pour l'affichage.
//  *
//  * C'est le contenu brut dans lequel sont ajoutés des balises de mise en forme.
//  * 
//  * 1- présence de notes, ajout des citations encadrées par des balises <sup><a href="note_$noteId>..</a></sup>
//  * 2- application du surlignage <mark>..</mark>
//  * 3- application de styles, <strong>, <em>, ..
//  * 4- insertion des illustrations
//  * 
//  */ 
// public function getFormattedContent(): string
// {
//     //
//     $htmlToInsert = [];
//     $formattedContent = '';

//     // du coup on "glane" les mises en forme ...

//     // mises en forme permanentes ( bold, italic, ..)
//     if (count($this->alterations)){
//         foreach ($this->alterations as $alteration){
//             $i = $alteration->getPosition();
//             $l = $alteration->getLength();

//             $htmlToInsert[] = [ 'index'=>$i, 'string'=>$alteration->getBeginTag() ];
//             if ($l > 0){
//                 // length may be null ..
//                 $htmlToInsert[] = [ 'index'=>$i+$l, 'string'=>$alteration->getEndTag() ];
//             }
//         }
//     }

//     // sous-chaîne(s) à afficher en surbrillance du fait d'une recherche
//     $nbOccurencesInParagraph = count($this->foundStringIndexes);
//     if ($nbOccurencesInParagraph){

//         // use <mark></mark> to highlight an occurrence of a found string

//         $endTag = '</a></mark>';
//         $strLength = mb_strlen($this->searchedString);

//         $currentOccurrenceInBook = $this->firstOccurrenceInParagraph;

//         foreach($this->foundStringIndexes as $foundStringIndex){

//             $nextOccurrenceInBook = ($currentOccurrenceInBook == $this->nbOccurrencesInBook) ? 1 : $currentOccurrenceInBook+1;

//             $beginTag = '<mark id="occurrence_' . $currentOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">'
//                         . '<a title="Occurrence ' .  $currentOccurrenceInBook . ' sur ' . $this->nbOccurrencesInBook . '"'
//                         . ' href="#occurrence_'
//                         . $nextOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">';

//             $htmlToInsert[] = [ 'index'=>$foundStringIndex, 'string'=>$beginTag ];
//             $htmlToInsert[] = [ 'index'=>$foundStringIndex + $strLength, 'string'=>$endTag ];

//             $currentOccurrenceInBook++;
//         }    
//     }

//     // citations des notes et contenu en title
//     if (count($this->notes)){

//         foreach($this->notes as $note){

//             $str =  '<sup id="citation_'
//                     . $note->getCitation()
//                     . '"><a title="'
//                     . $note->getContent()
//                     . '" class="" href="#note_'
//                     . $note->getId()
//                     .'">'
//                     . $note->getCitation()
//                     . '</a></sup>';

//             $htmlToInsert[] = ['index' => $note->getCitationIndex(), 'string' => $str];

//         }
//     }

//     // illustrations
//     if (count($this->illustrations)){

//         foreach($this->illustrations as $illustration){

//             $mimeType = $illustration->getMimeType();
//             if ($mimeType){

//                 $str = '<img src="'
//                         // . 'https://bibnphi2.webcoop.fr'    <<<<<<<<<< big bug.. to be removed !!!!!!!!
//                         . $illustration->getFileName()
//                         . '" alt="'
//                         . $illustration->getName()
//                         . '" title="'
//                         . $illustration->getSvgTitle();

//                 if($mimeType == "image/jpeg"){
//                     $str .= '" width="'
//                             . $illustration->getSvgWidth()
//                             . '" height="'
//                             . $illustration->getSvgHeight()
//                             . '" style="margin:0px 5px;"';
//                 }
//                 // else $mimeType could be "image/svg+xml" , "image/png, image/gif"
//                 else {
//                         $str .= '" style="max-width:100%; margin:Opx 5px;';    
//                 }

//                 $str .= '">';
//                 $htmlToInsert[] = ['index' => $illustration->getIllustrationIndex(), 'string' => $str];
//             }
//             else {
//                 // $mimeType null or "" not set ..
//             }

//         }
//     }

//     // et si a trouvé des mises en forme, on les insère ..
//     if ($htmlToInsert){

//         //
//         //
//         $indexes = array_column($htmlToInsert, 'index');
//         array_multisort($indexes, SORT_ASC, $htmlToInsert);

//         //
//         //
//         $currentIndex = 0;
//         $insertIndex = 0;
//         for($i=0; $i<count($htmlToInsert); $i++){
//             $insertIndex = $htmlToInsert[$i]['index'];
//             $formattedContent .= mb_substr($this->content, $currentIndex, $insertIndex-$currentIndex);
//             $formattedContent .= $htmlToInsert[$i]['string'];
//             $currentIndex = $insertIndex;
//         }
//         $formattedContent .= mb_substr($this->content, $insertIndex);
//         return $formattedContent;
        
//     }
//     // sinon, on retourne le contenu "cru"
//     return $this->content;
// }

// /**
//  * Set the value of foundStringIndexes
//  *
//  * @return  self
//  */ 
// public function setFoundStringIndexes($foundStringIndexes): self
// {
//     $this->foundStringIndexes = $foundStringIndexes;

//     return $this;
// }

// /**
//  * Get the value of foundStringIndexes
//  */ 
// public function getFoundStringIndexes()
// {
//     return $this->foundStringIndexes;
// }

// public function getParagraphStyles(): ?string
// {
//     return $this->paragraphStyles;
// }

// public function setParagraphStyles(string $paragraphStyles): self
// {
//     $this->paragraphStyles = $paragraphStyles;

//     return $this;
// }

// /**
//  * Get the value of nbOccurrencesInBook
//  */ 
// public function getNbOccurrencesInBook()
// {
//     return $this->nbOccurrencesInBook;
// }

// /**
//  * Set the value of nbOccurrencesInBook
//  *
//  * @return  self
//  */ 
// public function setNbOccurrencesInBook($nbOccurrencesInBook): self
// {
//     $this->nbOccurrencesInBook = $nbOccurrencesInBook;

//     return $this;
// }

// /**
//  * Get the value of firstOccurrenceInParagraph
//  */ 
// public function getFirstOccurrenceInParagraph()
// {
//     return $this->firstOccurrenceInParagraph;
// }

// /**
//  * Set the value of firstOccurrenceInParagraph
//  *
//  * @return  self
//  */ 
// public function setFirstOccurrenceInParagraph($firstOccurrenceInParagraph): self
// {
//     $this->firstOccurrenceInParagraph = $firstOccurrenceInParagraph;

//     return $this;
// }

// /**
//  * Set the value of searchedString
//  *
//  * @return  self
//  */ 
// public function setSearchedString($searchedString): self
// {
//     $this->searchedString = $searchedString;

//     return $this;
// }

}
