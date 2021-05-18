<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\BookNote;
use App\Entity\BookSentence;
use App\Entity\BookParagraph;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;


class XmlParser {

	/**
	 * 
	 */
	// const READ_BUFFER_SIZE = 65536; // 64kb ... 32768 32 Kb ??
	const READ_BUFFER_SIZE = 32768; // 32 Kb

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
	// private $percentProgress;
	
	private $timeStart;
	private $noteCollection;

	private $parser;

	private $insideNote,
			$insideAnnotation,
			$isNoteBody,
			$isNoteCitation,
			$noteBody,
			$indexNoteCitation,
			$noteCitation,
			// $paragraphCounter,
			$text;

	private $logger;
	private $em;
		
	public function __construct( Book $book, $xmlFileName, EntityManagerInterface $em, $logger = NULL )
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

		$this->nbWords = 0;
		$this->nbSentences = 0;
		$this->nbParagraphs = 0;
		$this->numBuffer = 0;

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
	 * Parse the xml file which describes the document.
	 * Store the sentences in $this->book set by __construct()
	 *
	 */
	public function parse()
	{
		//
		if ($this->timeStart == 0){

			// various initialization settings
			if (!file_put_contents('percentProgress', '0%')) dd('BOH'); // <<<<<<<<< :-/

			$this->noteCollection = [];
			$this->text = '';

			// init parsing time 
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

			while (($buffer = fread($this->xmlfh, self::READ_BUFFER_SIZE)) != FALSE){

				$this->numBuffer ++;
				$percentProgress = intval($this->numBuffer / $this->ratio *100) . '%';
				
				file_put_contents('percentProgress', $percentProgress);
				$this->logger->info('percentProgress : ' . $percentProgress );
				
				xml_parse($this->parser, $buffer);

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

	public function parse_async()
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

			// init parsing time 
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
				$percentProgress = intval($this->numBuffer / $this->ratio *100) . '%';
	
				xml_parse($this->parser, $buffer);

				$this->logger->info('n° read buffer : ' . $this->numBuffer . ' / ' . $this->ratio );
				$this->logger->info('percentProgress : ' . $percentProgress );

				file_put_contents('percentProgress', $percentProgress);

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
	 *      O D T   X M L   p a r s i n g
	 */
	private function start_element_handler($parser, $element, $attribs)
	{

		switch($element){

			case "TEXT:P" ;
			case "TEXT:H" ;
				// $this->paragraphCounter++;
				break;
				
			case "TEXT:SPAN":
				break;
				
			case "DRAW:FRAME" ;
				$this->logger->info("BaliseXML <$element> " . json_encode($attribs) );
				break;
				// dump([$element, $attribs]);
					
			case "DRAW:IMAGE" ;
				$this->logger->info("BaliseXML <$element> " . json_encode($attribs) );
				// $this->logger->info("BaliseXML : $element avec les attributs > " . serialize($attribs) );
				// $this->logger->info("BaliseXML : $element avec les attributs > " . implode('#', $attribs) );
				
				// store Illustration
				$this->logger->info($attribs['XLINK:HREF']);
				break;
			
			case "OFFICE:ANNOTATION" ;
				$this->insideAnnotation = true;
				break;

			case "TEXT:NOTE" ;
				//
				// strlen, number of bytes, some characters may be multi-bytes ...
				// iconv_strlen, number of characters
				//
				// index from the beginning of the paragraph !!
				$this->indexNoteCitation = iconv_strlen($this->text);

				// $this->text .= '(#';
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
			
			default ;
				// $this->logger->info("élément XML $element non géré"); // <<<<<<<<<<<<<<<<<<<<
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
				$this->noteCollection[] = ['index' => $this->indexNoteCitation,
											'citation' => $this->noteCitation,
											'content' => $this->noteBody];
											
				// $this->noteCollection[] = '[note#' . $this->noteCitation . ') ' . $this->noteBody . '#]';
				//
				// $this->text .= ')'; // to end the note citation in the text
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
			if ($this->isNoteCitation) $this->noteCitation = $data; 
			else $this->text .= $data;
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
			// $sentences = preg_split('/(?<![IVXLCM1234567890S].)(?<=[.?!])\s+/', $rawParagraph, -1, PREG_SPLIT_DELIM_CAPTURE);
			// if ($sentences){
			// 	foreach ($sentences as $sentence ){
			// 	}
			// }


			// remove all non-breaking space !! ( regex / /u means unicode support )
			// 
			$rawParagraph = preg_replace("/[\x{00a0}\s]+/u", " ", $rawParagraph);
			$rawParagraph = ltrim($rawParagraph);

			if ($rawParagraph != ''){
				$bookParagraph = new BookParagraph();
				$bookParagraph->setBook($this->book);

				//
				// handle notes if any for the paragraph
				if (!empty($noteCollection)){

					foreach($this->noteCollection as $key => $note){

						$bookNote = new BookNote();
						$bookNote->setBook($this->book);
						$bookNote->setBookParagraph($bookParagraph);

						$bookNote->setContent($note['content']);
						$bookNote->setCitation($note['citation']);
						$bookNote->setCitationIndex($note['index']);

						$this->em->persist($bookNote);

						$citation = $note['citation'];
						$index = $note['index'];

						$htmlToAdd = '<sup id="citation_' . $citation . '">
										<a class="" href="#note_' . $citation .'">'	. $citation .
										'</a></sup>';

						$indexShift = strlen($htmlToAdd);
						$index = $index + ($indexShift * $key); // in case of several notes in the paragraph


						// inject html to set superscript note tags
						$rawParagraph = mb_substr($rawParagraph, 0, $index)
										. $htmlToAdd
										. mb_substr($rawParagraph, $index);

					}
				}

				$bookParagraph->setContent($rawParagraph);
			}



			//
			if ( NULL !== $bookParagraph ){

				$this->nbParagraphs++;				
				$this->em->persist($bookParagraph);

				$this->em->flush();
			}
			
		}
	}

}



