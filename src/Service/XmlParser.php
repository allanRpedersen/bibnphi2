<?php

namespace App\Service;

use Monolog\Logger;
use App\Entity\Book;
use App\Entity\Bookmark;
use App\Entity\BookNote;
use App\Entity\BookTable;
use App\Entity\TableCell;
use App\Entity\Illustration;
use App\Entity\BookParagraph;
use App\Entity\CellParagraph;
use App\Entity\TextAlteration;
use Monolog\Handler\StreamHandler;
use Doctrine\ORM\EntityManagerInterface;

class XmlParser {

	/**
	 * 
	 */
	// const READ_BUFFER_SIZE = 65536; // 64kb ... 32768 32 Kb ??
	private  $READ_BUFFER_SIZE;
	/**
	 * 
	 */
	private Book $book; // the book being parsed
	
    private $nbWords;
    private $nbSentences;
	private $nbParagraphs;
	
	private $xmlfh;   // xml file handler
    private $xmlFileSize;
	private $workingDir;
	
	private $parsingTime;
	private $parsingCompleted;
	
	private $ratio; // $xmlFileSize / READ_BUFFER_SIZE  i.e. total of buffers read
	private $numBuffer;
	// private $percentProgress;
	
	private $timeStart;
	private $noteCollection;

	private $parser;

	private $insideNote,
			$insideAnnotation,
			$insideMasterStyles,
			$insideDrawFrame,
			$isNoteBody,
			$isNoteCitation,
			$noteBody,
			$noteCitation,
			$indexNoteCitation,
			// $indexDrawFrame,
			// $drawFrameWithObject,
			$text;

	private $cell,
			$table,
			$nbRows,
			$nbCells,
			$nbColumns,
			$insideTable,
			$cellParagraph;

	// private $style = [
	// 	'name'			=> '',
	// 	'family'		=> '', // "text"
	// 	'fontStyle'		=> '', // "normal" | "italic" ..
	// 	'fontWeight'	=> '', // "bold" ..
	// 	'text-position'	=> 'normal',
	// ];

	private $styleProperty = [
		'name'			=> '',
		'family'		=> '',
		'text-align'	=> 'justify',
		'font-style'	=> 'normal',
		'font-weight'	=> 'normal',
		'text-position'	=> 'normal',
	];

	/**
	 * Table indexées par le nom d'un style qui spécifie des altérations du texte ou du paragraphe
	 */
	private $abnormalStyles = []; // all the styles with non-default attributes - "nobody's normal" (PSB :-))

	private $currentStyleName = '';

	/**
	 * Une balise <SPAN TEXT:STYLE-NAME> dans le texte du paragraphe
	 */
	private $span = [
		'styleName'		=> '',
		'beginIndex'	=> 0,
		'endIndex'		=> 0,
	];
	/**
	 * Une balise <SPAN TEXT:STYLE-NAME> dans le texte d'une note
	 */
	private $noteSpan = [
		'styleName'		=> '',
		'beginIndex'	=> 0,
		'endIndex'		=> 0,
	];
	/**
	 * Le saut de ligne considéré comme une altération du contenu
	 */
	private $lineBreak = [
		'styleName'		=> '',
		'beginIndex'	=> 0,
		'endIndex'		=> 0,
	];

	/**
	 * Les balises relevées dans le paragraphe courant.
	 */
	private $spans = [];
	private $noteSpans = [];

	/**
	 *	 
	 * 
	 */
	private $firstImage;

	private $isSvgTitle;
	private $svgTitle ='';
	private $illustration = [
		'index'		=> 0,	// index from the beginning of the paragraph
		'name'		=> '',	// "DRAW:NAME"
		'svgWidth'	=> '',	// "SVG:WIDTH"
		'svgHeight'	=> '',	// "SVG:HEIGHT"
		'fileName'	=> '',	// "XLINK:HREF"
		'mimeType'	=> '',	// "DRAW:MIME-TYPE
		'svgTitle'	=> '',
	];
	private $illustrations = [];
	private $noteIllustrations = [];

	// Le texte du signet
	private $bookmarkName = '';

	private $logger;
	private $em;

	private $percentProgressFileName;
	private $modeDev = false;
		
	public function __construct( $book, $workingDir, $projectDir, $bufferSize, EntityManagerInterface $em, $mode='prod' )
	{
		$this->book = $book;
		$this->READ_BUFFER_SIZE = $bufferSize;
		
		$this->workingDir = $workingDir;
		$xmlFileName = $workingDir . '/document.xml';

		$this->em = $em;
		$this->modeDev = ($mode=='dev');
		
		// $this->logger = $logger;
		$this->logger = new Logger('_xmlParser');
		$this->logger->pushHandler( new StreamHandler($projectDir . '/public/bibnphi.log', Logger::DEBUG) );

		$this->percentProgressFileName = $projectDir . '/public/percentProgress.log';
		if (!file_put_contents($this->percentProgressFileName, "3%")) $this->logger->error('>> erreur file_put_contents');

		$this->xmlFileSize = filesize($xmlFileName);
		$this->ratio = ceil($this->xmlFileSize / $bufferSize);
		$this->logger->info('XmlParser::__construct - ' . $xmlFileName . ' - ratio ('. $this->xmlFileSize . '/' . $bufferSize . ') : ' . $this->ratio );

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

		$this->insideTable = false;


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

	// from php.net
	function mapped_implode($glue, $array, $symbol = ' => ') {
		return implode($glue, array_map(
				function($k, $v) use($symbol) {
					return $k . $symbol . $v;
				},
				array_keys($array),
				array_values($array)
				)
			);
	}

	/**
	 * Parse the xml file which describes the document.
	 * Store the paragraphs in $this->book set by __construct()
	 *
	 */
	public function parse()
	{

		//
		if ($this->timeStart == 0){

			// various initialization settings

			$this->noteCollection = [];
			$this->text = '';
			// $this->drawFrameWithObject = false;

			// init parsing time 
			$this->timeStart = microtime(true);

			if ($this->ratio > 3){
				ini_set('max_execution_time', '0'); // no execution time out !
				$this->logger->info('!!!!! PHP(max_execution_time) set to 0');

			}

			$this->parser = xml_parser_create();

			//
			// set up the handlers
			xml_set_element_handler($this->parser, [$this, "start_element_handler"], [$this, "end_element_handler"]);
			xml_set_character_data_handler($this->parser, [$this, "character_data_handler"]);

		}

		//
		if ( $this->xmlfh ){

			while (($buffer = fread($this->xmlfh, $this->READ_BUFFER_SIZE)) != FALSE){

				$this->numBuffer ++;
				$percentProgress = intval($this->numBuffer / $this->ratio *100) . '%';
				
				$am = microtime(true);
				xml_parse($this->parser, $buffer);
				$bufferParsingTime = microtime(true) - $am;

				$this->logger->info('buffer n°' . $this->numBuffer . '/' . $this->ratio . ' - parsing time: ' . $bufferParsingTime );
				
				if (!file_put_contents($this->percentProgressFileName, $percentProgress)) $this->logger->error('>> erreur file_put_contents');
				$this->logger->info('percentProgress : ' . $percentProgress );

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

			case "BIBNPHI":
			case "OFFICE:DOCUMENT-STYLES":
			case "OFFICE:DOCUMENT-CONTENT":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );
				break;
			
			case "OFFICE:BODY":
				if ($this->modeDev){
					// log document styles ??
				}
				break;

			case "DRAW:A":
				break;

			case "DRAW:FRAME":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );

				//
				$this->insideDrawFrame = true;
				$this->firstImage = true;
				
				//
				// des illustrations apparaissent dans les notes de fin de texte ..
				// 
				$this->illustration['index'] = $this->insideNote ? iconv_strlen($this->noteBody): iconv_strlen($this->text);

				if (array_key_exists('DRAW:NAME', $attribs)) $this->illustration['name'] = $attribs['DRAW:NAME'];
				//
				// svg:width="5.009cm" svg:height="3.03cm"
				if(array_key_exists('SVG:WIDTH', $attribs)) $this->illustration['svgWidth'] = $attribs['SVG:WIDTH'];
				if(array_key_exists('SVG:HEIGHT', $attribs)) $this->illustration['svgHeight'] = $attribs['SVG:HEIGHT'];
				
				break;

			case "DRAW:IMAGE":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );

				if ($this->firstImage){
					//
					if(array_key_exists('XLINK:HREF', $attribs)) $this->illustration['fileName'] = $attribs['XLINK:HREF'];

					//
					// "image/jpeg" | "image/svg+xml" | "image/png"
					$m1 = array_key_exists('DRAW:MIME-TYPE', $attribs) ? $attribs['DRAW:MIME-TYPE'] : "";
					$m2 = array_key_exists('LOEXT:MIME-TYPE', $attribs) ? $attribs['LOEXT:MIME-TYPE'] : "";
					$this->illustration['mimeType'] = $m1 ? $m1 : $m2;

					$this->firstImage = false;
				}

				break;
			
			case "DRAW:OBJECT":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );
				break;
					
			case "OFFICE:ANNOTATION":
				$this->insideAnnotation = true;
				$this->logger->info("<$element> " . json_encode($attribs) );
				break;

			case "OFFICE:AUTOMATIC-STYLES":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );
				break;

			case "OFFICE:MASTER-STYLES":
				$this->insideMasterStyles = true;
				break;

			case "STYLE:STYLE":
				$this->styleProperty['name'] = $attribs['STYLE:NAME'];

				// 'text', 'paragraph', 'table', 'table-column', 'table-cell'
				$this->styleProperty['family'] = $attribs['STYLE:FAMILY'];
				break;

			case "STYLE:PARAGRAPH-PROPERTIES":
				// FO:TEXT-ALIGN ('justify', 'center', 'start', 'end')
				$this->styleProperty['text-align'] = (array_key_exists('FO:TEXT-ALIGN', $attribs) ?
															$attribs['FO:TEXT-ALIGN'] :
															'justify');
				break;
				
			case "STYLE:TEXT-PROPERTIES":
				// FO:FONT-STYLE ('normal', 'italic')
				if(array_key_exists('FO:FONT-STYLE', $attribs)){
					$this->styleProperty['font-style'] = $attribs['FO:FONT-STYLE'];
				}
				// FO:FONT-WEIGHT ('normal', 'bold')
				if(array_key_exists('FO:FONT-WEIGHT', $attribs)){
					$this->styleProperty['font-weight'] = $attribs['FO:FONT-WEIGHT'];
				}
				// STYLE:TEXT-POSITION ("super 58%", "0% 100%" )
				if(array_key_exists('STYLE:TEXT-POSITION', $attribs)){
					$this->styleProperty['text-position'] = ( "super 58%" == $attribs['STYLE:TEXT-POSITION'] ) ? 'sup' : 'normal';
				}
				
				// All the other attributes ..
				// $this->style['others'] = $this->mapped_implode(', ', $attribs);
				break;

			case "SVG:TITLE":
			case "SVG:DESC":	// dans platon-menon, 
				$this->isSvgTitle = true;
				$this->svgTitle = '';
				break;
			
			case "TABLE:TABLE": // create table entity
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );

				$this->insideTable = true;
				$this->nbColumns = 0;
				$this->nbRows = 0;

				$this->table = new BookTable();
				$this->table->setNbRows($this->nbRows);
				$this->table->setNbColumns($this->nbColumns);

				$anchor = new BookParagraph();
				$anchor->setBook($this->book)
						->setContent('')
						->setParagraphStyles('')
						->setBookTable($this->table)
						;

				// $this->table->setAnchorParagraph($anchor);

				$this->em->persist($this->table);


				break;

			case "TABLE:TABLE-CELL":
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );
				$nbRowsSpan = (array_key_exists('TABLE:NUMBER-ROWS-SPAN', $attribs) ? $attribs['TABLE:NUMBER-ROWS-SPAN'] : 0);
				$nbColsSpan = (array_key_exists('TABLE:NUMBER-COLUMNS-SPAN', $attribs) ? $attribs['TABLE:NUMBER-COLUMNS-SPAN'] : 0);
						
				$this->cell = new TableCell();
				$this->cell->setBookTable($this->table);
				$this->em->persist($this->cell); // 

				break;

			case "TABLE:COVERED-TABLE-CELL": // fusion de plusieurs cellules sur une rangée ?! cf pascal-pensees
				if ($this->modeDev) $this->logger->info("<$element> " . json_encode($attribs) );
				break;

			
			case "TABLE:TABLE-COLUMN": // count number of columns, thanks to type casting !! rather than intval()...
				$this->nbColumns += array_key_exists('TABLE:NUMBER-COLUMNS-REPEATED', $attribs) ? $attribs['TABLE:NUMBER-COLUMNS-REPEATED'] : "1";
				break;

			case "TABLE:TABLE-ROW":
				$this->nbRows += 1;
				break;

			case "TEXT:BOOKMARK":
				$this->bookmarkName = $attribs['TEXT:NAME'];
				break;

			case "TEXT:H":
				if (!$this->insideNote){
					$this->currentStyleName = array_key_exists('TEXT:STYLE-NAME', $attribs) ? $attribs['TEXT:STYLE-NAME'] : '';

					if (array_key_exists($this->currentStyleName, $this->abnormalStyles)){
							if ($this->abnormalStyles[$this->currentStyleName]['font-style'] != 'italic')
									// set to bold
									$this->abnormalStyles[$this->currentStyleName]['font-weight'] = 'bold';
					}
					else {
						// add a new 
						$styleProperty = [
							'name'			=> $this->currentStyleName,
							'family'		=> 'paragraph',
							'text-align'	=> 'center',
							'font-weight'	=> 'bold',
							'font-style'	=> 'normal',
							'text-position' => 'normal',
						];
						$this->abnormalStyles[$this->currentStyleName] = $styleProperty;
					}

					// reset paragraph style properties and illustrations
					
					$this->styleProperty = [
						'text-align'	=> 'justify',
						'font-weight'	=> 'bold',
						'font-style'	=> 'normal',
						'text-position' => 'normal',
					];

					$this->illustration = [
						'index'		=> 0,	// index from the beginning of the paragraph
						'name'		=> '',	// "DRAW:NAME"
						'svgWidth'	=> '',	// "SVG:WIDTH"
						'svgHeight'	=> '',	// "SVG:HEIGHT"
						'fileName'	=> '',	// "XLINK:HREF"
						'mimeType'	=> '',	// "DRAW:MIME-TYPE
						'svgTitle'	=> '',
					];

				}
				break;
	
			case "TEXT:LINE-BREAK":
				break;

			case "TEXT:NOTE":
				//
				// strlen, number of bytes, but some characters may be multi-bytes ...
				// iconv_strlen, number of characters
				//
				// index from the beginning of the paragraph !!
				$this->indexNoteCitation = iconv_strlen($this->text);
				$this->insideNote = true;
				break;
				
			case "TEXT:NOTE-BODY":
				$this->isNoteBody = true;
				break;
			
			case "TEXT:NOTE-CITATION":
				$this->isNoteCitation = TRUE;
				break;

			case "TEXT:P":
				if (!$this->insideNote){
					$this->currentStyleName = array_key_exists('TEXT:STYLE-NAME', $attribs) ? $attribs['TEXT:STYLE-NAME'] : '';

					// reset paragraph style properties and illustrations
					$this->styleProperty = [
						'text-align'	=> 'justify',
						'font-weight'	=> 'normal',
						'font-style'	=> 'normal',
						'text-position' => 'normal',
					];

					$this->illustration = [
						'index'		=> 0,	// index from the beginning of the text of the paragraph or the body of a note !!!
						'name'		=> '',	// "DRAW:NAME"
						'svgWidth'	=> '',	// "SVG:WIDTH"
						'svgHeight'	=> '',	// "SVG:HEIGHT"
						'fileName'	=> '',	// "XLINK:HREF"
						'mimeType'	=> '',	// "DRAW:MIME-TYPE
						'svgTitle'	=> '',
					];
				}
				break;
				
			case "TEXT:SOFT-PAGE-BREAK":
				break;

			case "TEXT:SPAN":
				//
				// strlen, number of bytes, some characters may be multi-bytes ...
				// iconv_strlen, number of characters
				//
				// index from the beginning of the paragraph or the note content!!

				if ($this->insideNote){
					$this->noteSpan['styleName'] = $attribs['TEXT:STYLE-NAME'];
					$this->noteSpan['beginIndex'] = iconv_strlen($this->noteBody);
				}
				else {
					$this->span['styleName'] = $attribs['TEXT:STYLE-NAME'];
					$this->span['beginIndex'] = iconv_strlen($this->text);
				}
				break;
				
			default ;
				if ($this->modeDev) $this->logger->info("--- BALISE NON GÉRÉE !! <$element> " . json_encode($attribs));
		} 
	}

	private function end_element_handler($parser, $element)
	{
		switch($element){

			case "BIBNPHI":
			case "OFFICE:BODY":
			case "OFFICE:DOCUMENT-STYLES":
			case "OFFICE:DOCUMENT-CONTENT":
				if ($this->modeDev) $this->logger->info("</$element> ");
				break;
				
			case "DRAW:FRAME":
				// handle illustration(s)
				//
				$this->illustration['svgTitle'] = $this->svgTitle ? $this->svgTitle : '';

				if ($this->insideNote)
					$this->noteIllustrations[] = $this->illustration;
				else
					$this->illustrations[] = $this->illustration;

				$this->svgTitle = '';
				$this->insideDrawFrame = false;
				break;

			case "OFFICE:ANNOTATION":
				$this->insideAnnotation = false;
				break;
			
			case "OFFICE:AUTOMATIC-STYLES":
				break;

			case "OFFICE:MASTER-STYLES":
				$this->insideMasterStyles = false;
				break;

			case "STYLE:STYLE":
				if( $this->styleProperty['font-weight']		!= 'normal'		||
					$this->styleProperty['font-style']		!= 'normal'		||
					$this->styleProperty['text-align']		!= 'justify'	||
					$this->styleProperty['text-position']	!= 'normal'		)
				{
					$name = $this->styleProperty['name'];
					$this->abnormalStyles[$name] = $this->styleProperty;
				}
				// reset style
				$this->styleProperty = [
					'name'			=> '',
					'family'		=> '',
					'text-align'	=> 'justify',
					'font-style' 	=> 'normal',
					'font-weight'	=> 'normal',
					'text-position'	=> 'normal',
				];
				break;
			
			case "SVG:TITLE":
			case "SVG:DESC":
				$this->isSvgTitle = false;
				break;

			case "TABLE:TABLE":
				$this->table->setNbColumns($this->nbColumns);
				$this->table->setNbRows($this->nbRows);
				
				$this->em->persist($this->table);
				$this->em->flush();

				$this->insideTable = false;
				if ($this->modeDev) $this->logger->info("</$element>");
				break;

			case "TABLE:TABLE-COLUMN":
			case "TABLE:TABLE-ROW":
				break;

			case "TABLE:TABLE-CELL":
				// if ($this->modeDev) $this->logger->info("</$element>");
				$this->table->addCell($this->cell);
				$this->em->persist($this->cell);
				break;
					
			case "TEXT:BOOKMARK":
				break;
			
			case "TEXT:LINE-BREAK":
				// Cette balise est traitée comme un span, une altération du texte.
				// Un style spécifique est ajouté à la liste des styles pris en compte
				// pour l'ajout de la balise html <BR> dans le contenu du texte.

				if (!array_key_exists($element, $this->abnormalStyles)){
					$this->abnormalStyles[$element] = [
						'name'			=> $element,
					];
				}

				$this->lineBreak['styleName'] = $element;
				
				if ($this->insideNote) {
					$this->lineBreak['beginIndex'] = iconv_strlen($this->noteBody);
					$this->lineBreak['endIndex'] = $this->lineBreak['beginIndex'];
					$this->noteSpans[] = $this->lineBreak;
				}
				else {
					$this->lineBreak['beginIndex'] = iconv_strlen($this->text);
					$this->lineBreak['endIndex'] = $this->lineBreak['beginIndex'];
					$this->spans[] = $this->lineBreak;
				}

				break;
			
			case "TEXT:NOTE":
				//
				$this->noteCollection[] = [ 'index'			=> $this->indexNoteCitation,
											'citation'		=> $this->noteCitation,
											'content'		=> $this->noteBody,
											'alterations'	=> $this->noteSpans,
											'illustrations'	=> $this->noteIllustrations ];
											
				$this->insideNote = false;
				$this->noteBody = '';
				$this->noteSpans = [];
				$this->noteIllustrations =[];
				break;

			case "TEXT:NOTE-BODY":
				$this->isNoteBody = false;
				break;
			
			case "TEXT:NOTE-CITATION":
				$this->isNoteCitation = FALSE;
				break;

			case "TEXT:H":
			case "TEXT:P":
				if (!$this->insideNote){

					// handle paragraph content, notes, alterations, illustrations
					// either BookParagraph or TableCellParagraph
					$this->handleParagraph($this->text, $this->noteCollection);

					// reset
					$this->text = '';
					$this->noteCollection = [];
					$this->spans = [];
					$this->illustrations = [];
					$this->bookmarkName = '';
				}
				break;

			case "TEXT:SPAN":
				//
				// strlen, number of bytes, some characters may be multi-bytes ...
				// iconv_strlen, number of characters
				//
				// index from the beginning of the paragraph or the note content !!

				if ($this->insideNote){
					$this->noteSpan['endIndex'] = iconv_strlen($this->noteBody);
					if ($this->noteSpan['endIndex'] != $this->noteSpan['beginIndex'])
						$this->noteSpans[] = $this->noteSpan;
				}
				else {
					$this->span['endIndex'] = iconv_strlen($this->text);
					if ($this->span['endIndex'] != $this->span['beginIndex'])
						$this->spans[] = $this->span;
				}
				break;

			default:
				break;
			
		}	

	}

	private function character_data_handler($parser, $data)
	{
		//
		// Les données récupérées peuvent être :
		//		- un contenu de paragraphe à ajouter au texte déjà existant		> ajouté à $this->text
		//		- un contenu de note à ajouter au texte de la note en cours		> ajouté à $this->noteBody
		//		- le texte de la citation qui fait référence à la note en cours	> ajouté à $this->noteCitation
		//		- le titre d'une image !!										> ajouté à $this->svgTitle
		//
		//

									// if ($this->isNoteBody) $this->noteBody .= $data;
									// else if (!$this->insideAnnotation){
									// 	if ($this->isNoteCitation) $this->noteCitation = $data; 
									// 	else if ($this->insideDrawFrame){
									// 			$this->svgTitle .= $data;
									// 		} 
									// 		else $this->text .= $data;
									// }

		if ( !$this->insideAnnotation &&
			 !$this->insideMasterStyles )
		{
			//
			//
			if ( $this->isSvgTitle ) $this->svgTitle = $data;
			else
				if ($this->isNoteCitation) $this->noteCitation = $data;
				else
					if ($this->isNoteBody) $this->noteBody .= $data;
					else $this->text .= $data;
			//
			//
		}


	}


	/**
	 * Flush every raw paragraph followed by its notes (each as a paragraph) in db
	 * 
	 * 
	 */
	private function handleParagraph($rawParagraph, $noteCollection)
	{
		$bookParagraph = NULL;
		$illustrations = $this->illustrations;
		$isBookmark = ($this->bookmarkName != '');

		$rawParagraph = ltrim($rawParagraph);

		// remove all non-breaking spaces !! (  "/ regex /u" means unicode support )
		// 
		// $rawParagraph = preg_replace("/[\x{00a0}\s]+/u", " ", $rawParagraph);
		// <<== there is an issue when replacing a two-bytes-coded char by a one-coded one


			// split the paragraph using the punctuation signs [.?!], into sentences
			// with a "negative look-behind feature" to exclude :
			// 			- roman numbers (example CXI.)
			//			- ordered list ( 1. aaa 2. bbb 3. ccc etc)
			//			- S. as St, Saint
			//
			// $sentences = preg_split('/(?<![IVXLCM1234567890S].)(?<=[.?!])\s+/', $rawParagraph, -1, PREG_SPLIT_DELIM_CAPTURE);
			// if ($sentences){
			// 	foreach ($sentences as $sentence ){
			// 	}
			// }


		if ($rawParagraph != '' || $illustrations || $isBookmark)
		{

			if ($this->insideTable)
			{
				$bookParagraph = new CellParagraph();
				$bookParagraph->setTableCell($this->cell);
			}
			else {

				$bookParagraph = new BookParagraph();
				$bookParagraph->setBook($this->book);
	
			} 
			// handle content alteration, text style attributes
			//
			foreach($this->spans as $span){

				if ($alt = $this->isStyleManaged($span['styleName'])){

					$alt->SetLength($span['endIndex'] - $span['beginIndex'])
						->SetPosition($span['beginIndex']);

					if ($this->insideTable){$alt->setCellparagraph($bookParagraph);}
					else {$alt->setBookparagraph($bookParagraph);}
						
					
					$this->em->persist($alt);
					$bookParagraph->addAlteration($alt);

				}

			}
			//
			// handle notes if any for the paragraph
			if (!empty($noteCollection)){
				foreach($noteCollection as $note){
					
					$bookNote = new BookNote();
					
					$bookNote->setBook($this->book)
							->setContent($note['content'])
							->setCitation($note['citation'])
							->setCitationIndex($note['index']);

					if ($this->insideTable){$bookNote->setCellparagraph($bookParagraph);}
					else {$bookNote->setBookparagraph($bookParagraph);}
				

					if ($note['alterations'] ){
						foreach( $note['alterations'] as $alteration){

							if ($alt=$this->isStyleManaged($alteration['styleName'])){

								$alt->SetLength($alteration['endIndex'] - $alteration['beginIndex'])
									->SetPosition($alteration['beginIndex'])
									->setBookNote($bookNote);
							
							$this->em->persist($alt);
							$bookNote->addAlteration($alt);
	
							//
							//
							// printf("note citation: %s, alt.name: %s, pos.:%d, len.:%d \r\n",
							// $note['citation'],
							// $alt->getName(),
							// $alt->getPosition(),
							// $alt->getLength() );

							}
						
						}
					}
				
					if ($note['illustrations']){
						foreach($note['illustrations'] as $illustration){
							$ill = new Illustration();

							//
							// convert width and height from cm to pixels
							// 1 cm is 37.7952755906 px   or   1 cm = 37.79527559055118 px
							$cmWidth = substr($illustration['svgWidth'], 0, -2);
							$pxWidth =  intval($cmWidth * 37.7952755906);
			
							$cmHeight = substr($illustration['svgHeight'], 0, -2);
							$pxHeight =  intval($cmHeight * 37.7952755906);
			
							$ill->setIllustrationIndex($illustration['index'])
								->setName($illustration['name'])
								->setSvgWidth($pxWidth)
								->setSvgHeight($pxHeight)
								->setFileName('/' . $this->workingDir . '/' . $illustration['fileName'])
								->setMimeType(($illustration['mimeType']))
								->setSvgTitle($illustration['svgTitle'])
								->setBookNote($bookNote)
								;
			
							$this->em->persist($ill);
							$bookNote->addIllustration($ill);
			
						}

					}

					$this->em->persist($bookNote);
					$bookParagraph->addNote($bookNote); //
				}
			}
			//
			// handle paragraph illustrations
			foreach ($illustrations as $illustration){
				$ill = new Illustration();

				//
				// convert width and height from cm to pixels
				// 1 cm is 37.7952755906 px   or   1 cm = 37.79527559055118 px
				$cmWidth = substr($illustration['svgWidth'], 0, -2);
				$pxWidth =  intval($cmWidth * 37.7952755906);

				$cmHeight = substr($illustration['svgHeight'], 0, -2);
				$pxHeight =  intval($cmHeight * 37.7952755906);

				$ill->setIllustrationIndex($illustration['index'])
					->setName($illustration['name'])
					->setSvgWidth($pxWidth)
					->setSvgHeight($pxHeight)
					->setFileName('/' . $this->workingDir . '/' . $illustration['fileName'])
					->setMimeType(($illustration['mimeType']))
					->setSvgTitle($illustration['svgTitle'])
					;

				if ($this->insideTable){$ill->setCellparagraph($bookParagraph);}
				else {$ill->setBookparagraph($bookParagraph);}


				$this->em->persist($ill);
				$bookParagraph->addIllustration($ill);
			}
			//
			// handle paragraph style attributes if they're differents from default values
			$styleStr = '';
			if (array_key_exists($this->currentStyleName, $this->abnormalStyles)){
				$style = $this->abnormalStyles[$this->currentStyleName];

				$styleStr = 'style="text-align: ' . $style['text-align'] . ';' .
								'font-style: ' . $style['font-style'] . ';' .
								'font-weight: ' . $style['font-weight'] . ';"';

				// foreach($style as $k => $v){ $styleStr .= $k . ": " . $v . ";"; }
				if ($this->modeDev)	$this->logger->info("style de paragraphes : $this->currentStyleName, $styleStr");

				
			}
			//
			// this paragraph may be a bookmark even if there is no content !!
			if ($isBookmark){
				$bookmark =  new Bookmark();

				// $bookmark->setBook($this->book);
				$bookmark->setParagraph($bookParagraph);
				$bookmark->setName($this->bookmarkName);

				$this->em->persist($bookmark);
				$this->book->addBookmark($bookmark);
				// $this->em->persist($this->book);
			}

			//
			//
			$bookParagraph
						->setParagraphStyles($styleStr)
						->setContent($rawParagraph);
		}

		//
		if ( NULL !== $bookParagraph ){

			$this->nbParagraphs++;				
			$this->em->persist($bookParagraph);

			if ($this->insideTable){
				$this->cell->addCellParagraph($bookParagraph);
			}

			$this->em->flush(); 
		}
				
	}


	/**
	 * Check if the text style name is managed, if true return associated alteration object
	 */
	private function isStyleManaged($styleName): ? TextAlteration {

		$bt = $et = '';

		if (array_key_exists($styleName, $this->abnormalStyles)){

			if ($styleName == "TEXT:LINE-BREAK"){ $bt = "<BR>" ; $et = ""; }
			else {
				if ($this->abnormalStyles[$styleName]['font-style']		== 'italic'){ $bt .= '<EM>'; $et .= '</EM>'; }
				if ($this->abnormalStyles[$styleName]['font-weight']	== 'bold'){ $bt .= '<STRONG>'; $et .= '</STRONG>'; }
				if ($this->abnormalStyles[$styleName]['text-position']	== 'sup' ){ $bt .= '<SUP>'; $et .= '</SUP>'; }
			}

			$alt = new TextAlteration();
			$alt->setName($styleName)
				->setBeginTag($bt)
				->setEndTag($et)
				;
			return $alt;
		}
		return null;
	}

	/**
	 * Get the value of spans
	 */ 
	public function getSpans()
	{
		return $this->spans;
	}

	/**
	 * Get the value of abnormalStyles
	 */ 
	public function getAbnormalStyles()
	{
		return $this->abnormalStyles;
	}

	/**
	 * Get the value of illustrations
	 */ 
	public function getIllustrations()
	{
		return $this->illustrations;
	}
}



