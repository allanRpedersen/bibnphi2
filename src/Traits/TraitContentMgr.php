<?php
namespace App\Traits;

/**
 * TraitContentMgr defines the following methods :
 * 
 *  - isContentMatching()
 *  - getContent()
 *  - getFormattedContent()
 *  - getFirstOccurrenceInParagraph()
 *  - getFoundStringIndexes()
 *  - getNbOccurrencesInBook()
 *  - getParagraphStyles()
 *  - getSearchedString()
 *  - setContent()
 *  - setFirstOccurrenceInParagraph()
 *  - setFoundStringIndexes()
 *  - setNbOccurrencesInBook()
 *  - setParagraphStyles()
 *  - setSearchedString()
 * 
 */
trait TraitContentMgr
{
    /**
     * @ORM\Column(type="text")
     * 
     * Le contenu brut du paragraphe cad sans aucun attribut de mise en forme ou de citation de note
     * 
     */
    private $content;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * 
     * La chaine de caractères qui contient les attributs de mise en forme applicables au paragraphe entier
     * 
     * e.g. "text-align:justify; font-weight: normal; font-style: normal; font-size: 1em;"
     * 
     * text-align: start || end || center || justify
     * font-weight: normal || bold || light
     * font-style: normal || italic
     * font-size: 1em
     * 
     */
    private $paragraphStyles;

    /**
     * 
     */
    
    /**
     *  Recherche dans le contenu
     */
    private $firstOccurrenceInParagraph;  // le numéro dans le livre de la première occurrence trouvée dans le paragraphe
    private $foundStringIndexes = [];     // La table des indices des occurrences de la chaîne de caractères trouvée dans le contenu
    private $nbOccurrencesInBook;         // le nombre d'occurrences trouvé dans le livre
    private $nextOccurence;               // la prochaine occurence dans le livre (paragraphe ou note)
    private $searchedString;              // la chaîne recherchée


    /**
     *   
     * initialise et remplit le tableau :  $this->foundStringIndexes
     * 
     */
    public function isContentMatching($stringToSearch) : array
    {
        $encoding = mb_detect_encoding($this->content);
        
        $fromIndex = 0;
        $indexFound = 0;
        $strLength = mb_strlen($stringToSearch);

        // $this->highlightedContent = '';
        $this->foundStringIndexes = [];
        $this->searchedString = $stringToSearch;

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
     * Construit et retourne le contenu mise en forme pour l'affichage.
     *
     * C'est le contenu brut dans lequel sont ajoutés des balises de mise en forme.
     * 
     * 1- présence de notes, ajout des citations encadrées par des balises <sup><a href="note_$noteId>..</a></sup>
     * 2- application du surlignage <mark>..</mark>
     * 3- application de styles, <strong>, <em>, ..
     * 4- insertion des illustrations
     * 
     * les propriétés suivantes sont définies par les classes qui utilisent ce Trait
     * 
     *      $this->illustrations
     *      $this->alterations
     *      $this->notes
     * 
     * 
     */ 
    public function getFormattedContent(): string
    {
        //
        $htmlToInsert = [];
        $formattedContent = '';

        // du coup on "glane" les mises en forme ...

        // mises en forme permanentes ( bold, italic, ..)
        if (count($this->alterations)){
            foreach ($this->alterations as $alteration){
                $i = $alteration->getPosition();
                $l = $alteration->getLength();

                $htmlToInsert[] = [ 'index'=>$i, 'string'=>$alteration->getBeginTag() ];
                if ($l > 0){
                    // length may be null ..
                    $htmlToInsert[] = [ 'index'=>$i+$l, 'string'=>$alteration->getEndTag() ];
                }
            }
        }

        // sous-chaîne(s) à afficher en surbrillance du fait d'une recherche
        $nbOccurencesInParagraph = count($this->foundStringIndexes);
        if ($nbOccurencesInParagraph){

            // use <mark></mark> to highlight an occurrence of a found string

            $endTag = '</a></mark>';
            $strLength = mb_strlen($this->searchedString);

            $currentOccurrenceInBook = $this->firstOccurrenceInParagraph;

            foreach($this->foundStringIndexes as $foundStringIndex){

                $nextOccurrenceInBook = ($currentOccurrenceInBook == $this->nbOccurrencesInBook) ? 1 : $currentOccurrenceInBook+1;

                $beginTag = '<mark id="occurrence_' . $currentOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">'
                            . '<a title="Occurrence ' . $currentOccurrenceInBook . ' sur ' . $this->nbOccurrencesInBook . '"'
                            . ' href="#occurrence_'
                            . $nextOccurrenceInBook . '/' . $this->nbOccurrencesInBook . '">';

                $htmlToInsert[] = [ 'index'=>$foundStringIndex, 'string'=>$beginTag ];
                $htmlToInsert[] = [ 'index'=>$foundStringIndex + $strLength, 'string'=>$endTag ];

                $currentOccurrenceInBook++;
            }    
        }

        // citations des notes et contenu en title
        if (count($this->notes)){

            foreach($this->notes as $note){

                $str =  '<sup id="citation_'
                        . $note->getCitation()
                        . '"><a title="'
                        . $note->getContent()
                        . '" class="" href="#note_'
                        . $note->getId()
                        .'">'
                        . $note->getCitation()
                        . '</a></sup>';

                $htmlToInsert[] = ['index' => $note->getCitationIndex(), 'string' => $str];

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

                    switch ($mimeType) {
                        case "image/jpeg":
                        case "image/png":
                            $str .= '" width="'
                                . $illustration->getSvgWidth()
                                . '" height="'
                                . $illustration->getSvgHeight()
                                . '" style="margin:0px 5px;"';
                            break;
                        
                        default:// else $mimeType could be "image/svg+xml" , image/gif, image/"
                            $str .= '" style="max-width:100%; margin:Opx 5px;';    
                            break;
                    }
                                                        // if($mimeType == "image/jpeg"){
                                                        //     $str .= '" width="'
                                                        //             . $illustration->getSvgWidth()
                                                        //             . '" height="'
                                                        //             . $illustration->getSvgHeight()
                                                        //             . '" style="margin:0px 5px;"';
                                                        // }     
                                                        // else {
                                                        //         $str .= '" style="max-width:100%; margin:Opx 5px;';    
                                                        // }

                    $str .= '">';
                    $htmlToInsert[] = ['index' => $illustration->getIllustrationIndex(), 'string' => $str];
                }
                else {
                    // $mimeType null or "" not set ..
                }

            }
        }

        // et si a trouvé des mises en forme, on les insère ..
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
     * Get the value of content
     */ 
    public function getContent(): ?string
    {
        return $this->content;
    }
    /**
     * Get the value of firstOccurrenceInParagraph
     */ 
    public function getFirstOccurrenceInParagraph()
    {
        return $this->firstOccurrenceInParagraph;
    }
    /**
     * Get la table des indices des chaînes de caractères trouvées dans le contenu
     */ 
    public function getFoundStringIndexes()
    {
        return $this->foundStringIndexes;
    }
    /**
     * Get the value of nbOccurrencesInBook
     */ 
    public function getNbOccurrencesInBook()
    {
        return $this->nbOccurrencesInBook;
    }
    /**
     * Get the value of paragraphStyles
     */ 
    public function getParagraphStyles(): ?string
    {
        return $this->paragraphStyles;
    }
    /**
     * Get the value of searchedString
     */ 
    public function getSearchedString()
    {
        return $this->searchedString;
    }


    /**
     * Set the value of content
     *
     * @return  self
     */ 
    public function setContent($content): self
    {
        $this->content = $content;

        return $this;
    }
    /**
     * Set the value of firstOccurrenceInParagraph
     *
     * @return  self
     */ 
    public function setFirstOccurrenceInParagraph($firstOccurrenceInParagraph): self
    {
        $this->firstOccurrenceInParagraph = $firstOccurrenceInParagraph;

        return $this;
    }
    /**
     * Set la table des indices des chaînes de caractères trouvées dans le contenu
     *
     * @return  self
     */ 
    public function setFoundStringIndexes($foundStringIndexes): self
    {
        $this->foundStringIndexes = $foundStringIndexes;

        return $this;
    }
    /**
     * Set the value of nbOccurrencesInBook
     *
     * @return  self
     */ 
    public function setNbOccurrencesInBook($nbOccurrencesInBook): self
    {
        $this->nbOccurrencesInBook = $nbOccurrencesInBook;

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
     * Set the value of paragraphStyles
     *
     * @return  self
     */ 
    public function setParagraphStyles($paragraphStyles): self
    {
        $this->paragraphStyles = $paragraphStyles;

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

}
