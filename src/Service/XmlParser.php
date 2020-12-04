<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\BookSentence;
use App\Entity\BookParagraph;


class XmlParser {

	/**
	 * 
	 */
	const READ_BUFFER_SIZE = 65536; // 64kb

	/**
	 * 
	 */
	private $book; // the book being parsed
	
    private $nbWords;
    private $nbSentences;
	private $nbParagraphs;
	
	private $xmlfh;   // xml file handler
    private $xmlFileSize;
	// private $xmlFileName;
	
	private $parsingTime;
	private $parsingCompleted;
	
	private $ratio; // $xmlFileSize / READ_BUFFER_SIZE
	private $numBuffer;
	
	private $timeStart;
	private $noteCollection;

	private $parser;

	private $insideNote,
			$insideAnnotation,
			$isNoteBody,
			$isNoteCitation,
			$noteBody,
			$noteCitation,
			// $paragraphCounter,
			$text;

	private $logger;
	private $em;
		
	public function __construct( Book $book, $xmlFileName, $em, $logger = NULL )
	{
		$this->book = $book;
		
		// $this->xmlFileName = $xmlFileName;
		$this->em = $em;
		$this->logger = $logger;

		$this->xmlFileSize = filesize($xmlFileName);
		$this->ratio = ceil($this->xmlFileSize / self::READ_BUFFER_SIZE);

		// ??
		// $fh = @fopen() 
		// ( @ symbol supresses any php driven error message !? )
		//
		$this->xmlfh = fopen($xmlFileName, 'rb');
		$this->timeStart = 0;
		$this->parsingCompleted = FALSE;
		$this->parsingTime = -1;

	}

    /**
     * Get the value of nbWords
     */ 
    public function getNbWords()
    {
        return $this->nbWords;
    }

    /**
     * Get the value of nbSentences
     */ 
    public function getNbSentences()
    {
        return $this->nbSentences;
    }

    /**
     * Get the value of nbParagraphs
     */ 
    public function getNbParagraphs()
    {
        return $this->nbParagraphs;
    }

    /**
     * Get the value of xmlFileSize
     */ 
    public function getXmlFileSize()
    {
        return $this->xmlFileSize;
    }

    /**
     * Get the value of parsingTime
     */ 
    public function getParsingTime()
    {
        return $this->parsingTime;
    }

	public function isParsingCompleted() : bool
	{
		return $this->parsingCompleted;
	}

	public function getRatio()
	{
		return $this->ratio;
	}

	/**
	 * Parse the xml file which contains the odt document.
	 * Store the sentences in $this->book set by __construct()
	 *
	 */
	public function parse()
	{
		//
		if ($this->timeStart == 0){

			// various initialization settings
			$this->noteCollection = [];
			$this->text = '';
			$this->nbWords = 0;
			$this->nbSentences = 0;
			$this->nbParagraphs = 0;
			$this->numBuffer= 0;

			// init
			$this->timeStart = microtime(true);
			if ($this->ratio > 3) ini_set('max_execution_time', '0'); // no execution time out !

			$this->parser = xml_parser_create();

			//
			// set up the handlers
			xml_set_element_handler($this->parser, [$this, "start_element_handler"], [$this, "end_element_handler"]);
			xml_set_character_data_handler($this->parser, [$this, "character_data_handler"]);

		}

		//
		if ( $this->xmlfh ){

			// if( ($buffer = fread($this->xmlfh, self::READ_BUFFER_SIZE)) != FALSE ){
			// 	$this->numBuffer ++;
	
			// 	xml_parse($this->parser, $buffer);
			// 	$this->logger->info('n° read buffer : ' . $this->numBuffer );

			// }
			// else {
			// 	xml_parse($this->parser, '', true); // to finalize parsing
			// 	xml_parser_free($this->parser);
			// 	unset($this->parser);
	
			// 	if (feof($this->xmlfh)){
			// 		$this->parsingCompleted = true;
			// 		$this->parsingTime = \microtime(true) - $this->timeStart;
			// 		$this->logger->info("ParsingCompleted : " . $this->parsingTime);
			// 	}
			// 	else {
			// 		$this->parsingTime = -1;
			// 		$this->logger->info("ERREUR: feof(xmlFile) retourne FALSE !! ???");
			// 	}
			// 	fclose($this->xmlfh);
			// }


			while (($buffer = fread($this->xmlfh, self::READ_BUFFER_SIZE)) != FALSE){

				$this->numBuffer ++;
	
				xml_parse($this->parser, $buffer);
				$this->logger->info('n° read buffer : ' . $this->numBuffer . ' / ' . $this->ratio );

			}

			xml_parse($this->parser, '', true); // to finalize parsing
			xml_parser_free($this->parser);
			unset($this->parser);

			if (feof($this->xmlfh)) {
				$this->parsingCompleted = true;
				$this->parsingTime = \microtime(true) - $this->timeStart;
				$this->logger->info("ParsingCompleted : " . $this->parsingTime);
			}
			else {
				$this->logger->info("ERREUR: feof(xmlFile) retourne FALSE !! ???");
			}
			fclose($this->xmlfh);

		}

	}


	/**
	 * 
	 *
	 * 
	 * 
	 * 
	 */



	/**
	 *      O D T   X M L   p a r s i n g
	 */
	private function start_element_handler($parser, $element, $attribs)
	{

		switch($element){

			case "TEXT:P" ;
			case "TEXT:H" ;
				// $this->paragraphCounter++;
				// dump([$element, $attribs]);
				break;
			
			case "TEXT:SPAN":
			case "DRAW:FRAME" ;
			case "DRAW:IMAGE" ;
			// dump([$element, $attribs]);
				break;
			
			case "OFFICE:ANNOTATION" ;
				$this->insideAnnotation = true;
				break;

			case "TEXT:NOTE" ;
				$this->text .= '(#';
				$this->insideNote = true;
				break;
				
			case "TEXT:NOTE-CITATION" ;
				$this->isNoteCitation = TRUE;
				// dump([$element, $attribs]);
				break;
				
			case "TEXT:NOTE-BODY" ;
				$this->isNoteBody = true;
				// dump([$element, $attribs]);
				break;
			
		} 
	}

	private function end_element_handler($parser, $element)
	{
		switch($element){
			case "TEXT:P" ;
			case "TEXT:H" ;
				if (!$this->insideNote){

					$this->handleBookParagraph($this->text, $this->noteCollection);
					$this->text = '';
					$this->noteCollection = [];

				}
				break;

			case "OFFICE:ANNOTATION" ;
				$this->insideAnnotation = false;
				break;
			
			case "TEXT:NOTE" ;
				//
				$this->noteCollection[] = '[note#' . $this->noteCitation . ') ' . $this->noteBody . '#]';
				//
				$this->text .= ')'; // to end the note citation in the text
				$this->insideNote = false;
				$this->noteBody = '';
				break;

			case "TEXT:NOTE-CITATION" ;
				$this->isNoteCitation = FALSE;
				break;

			case "TEXT:NOTE-BODY" ;
				// 
				$this->isNoteBody = false;
				break;
			
			case "TEXT:LINE-BREAK" ;
				//
				$this->text .= ' ';
				break;
			
			case "TEXT:SPAN" ;
				break;

		}	

	}

	private function character_data_handler($parser, $data)
	{
		if ($this->isNoteBody) $this->noteBody .= $data;
		else if (!$this->insideAnnotation){
			$this->text .= $data;
			if ($this->isNoteCitation) $this->noteCitation = $data; 
		}
	}

	/**
	 * Flush every raw paragraph followed by its notes (each as a paragraph) in db
	 * 
	 * 
	 */
	private function handleBookParagraph($rawParagraph, $noteCollection)
	{
		if ($rawParagraph != ''){

			// $entityManager = $this->getDoctrine()->getManager(); <<<<<<<< !!!!!

			$bookParagraph = NULL;
			
			// split the paragraph using the punctuation signs [.?!]
			// with a negative look-behind feature to exclude :
			// 			- roman numbers (example CXI.)
			//			- ordered list ( 1. aaa 2. bbb 3. ccc etc)
			//			- S. as St, Saint
			//
			$sentences = preg_split('/(?<![IVXLCM1234567890S].)(?<=[.?!])\s+/', $rawParagraph, -1, PREG_SPLIT_DELIM_CAPTURE);
			if ($sentences){
				foreach ($sentences as $sentence ){
					
					// remove all non-breaking space !!
					// regex / /u << unicode support
					$sentence = preg_replace("/[\x{00a0}\s]+/u", " ", $sentence);
					$sentence = ltrim($sentence);
					
					if ($sentence != ''){
						
						if ( NULL === $bookParagraph ){
							$bookParagraph = new BookParagraph();
							$bookParagraph->setBook($this->book);
						}

						$bookSentence = new BookSentence();
						$bookSentence->setBookParagraph($bookParagraph);
						$bookSentence->setContent($sentence);

						$this->nbSentences++;
						$this->em->persist($bookSentence);
					}

				}
			}

			//
			if ( NULL !== $bookParagraph ){

				$this->nbParagraphs++;				
				$this->em->persist($bookParagraph);

				//
				// then get notes if any for the paragraph
				if (!empty($noteCollection)){
					foreach($this->noteCollection as $note){
						$pNote = new BookParagraph();
						$pNote->SetBook($this->book);
						$this->em->persist($pNote);

						$sNote = new BookSentence();
						$sNote->setBookParagraph($pNote);
						$sNote->setContent($note);
						$this->em->persist($sNote);

					}
				}

				$this->em->flush();
			}
			
		}
	}

}



