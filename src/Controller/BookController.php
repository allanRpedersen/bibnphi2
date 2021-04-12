<?php

namespace App\Controller;

use Monolog\Logger;
use App\Entity\Book;
use App\Entity\Author;
use App\Form\BookType;
use App\Service\XmlParser;
use App\Entity\BookSentence;
use App\Entity\BookParagraph;
use App\Repository\BookRepository;
use Monolog\Handler\StreamHandler;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;

//
// $bool=pcntl_async_signals(true);
//

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{

	/**
	 * XML parser
	 *
	 */
	private $parser;

	private $insideNote,
			$insideAnnotation,
			$counter,
			$text,
			$isNoteBody,
			$isNoteCitation,
			$noteBody,
			$noteCitation,
			$noteCollection;

	private $nbBookWords,
			$nbBookSentences,
			$nbBookParagraphs,
			$xmlFileSize,
			$iCurrentBuffer;

	const READ_BUFFER_SIZE = 65536; // 64kb
	private $book;


	private $uploaderHelper;
	private $logger;
	private $projectDir;

	private $em;


	public function __construct(KernelInterface $kernel, EntityManagerInterface $em, UploaderHelper $uploaderHelper)
	{
		$this->projectDir = $kernel->getProjectDir();

		$this->em = $em;

		$this->uploaderHelper = $uploaderHelper;

		$this->logger = new Logger('bibnphi');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/bibnphi.log', Logger::DEBUG) );
	}

    /**
     * @Route("/", name="book_index", methods={"GET"})
     */
    public function index(Request $request, PaginatorInterface $paginator, BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
			'books' => $paginator->paginate(
				$bookRepository->findByTitleQuery(),
				$request->query->getInt('page', 1),
				6
			),
		]);
	}
	

	/**
	 * 
	 */
	// public function renderProgressBar($prorata=0){

	// 	echo "<script>";
	// 	echo "document.getElementById('progress-value').innerHTML=’".$prorata."%’;";
	// 	echo "document.getElementById('progress-bar').style.width=’".$prorata."%’;";
	// 	echo "</script>";
		
	// 	ob_flush();
	// 	flush();
		
	// 	ob_flush();
	// 	flush();
		
	// 	return $this->render('partials/_progress_bar.html.twig', [
	// 		'prorata' => $prorata,
	// 	]);

	// }

	/**
	 * 
	 */
	public function isXmlFileValid(Book $book): ?string
	{
		//
		// get the xml file out of odt file

		// uploaded odt file, $odtFilePath, is set once the entity has been persisted ..
		$odtFilePath = $this->uploaderHelper->asset($book, 'odtBookFile');

		// to rip the leading slash ..
		$odtFilePath = substr($odtFilePath, 1);
		$this->logger->debug('$odtFilePath : ' . $odtFilePath );

		$dirName = \pathinfo($odtFilePath, PATHINFO_DIRNAME) . '/' . \pathinfo($odtFilePath, PATHINFO_FILENAME);
		$xmlFileName = $dirName . '/content.xml';

		$this->logger->info( '$dirName : ' . $dirName);
		$this->logger->info( '$xmlFileName : ' . $xmlFileName);

		if (!file_exists($odtFilePath)){
			$this->logger->info( '$odtFilePath : ' . $odtFilePath . ' does not exist !!!');
			// internal error !!
			return null;
		}
		//
		// unix cmd
		passthru('mkdir -v ' . $dirName . ' >>books/sorties_console 2>&1', $errCode );
		if ($errCode){
			$this->logger->debug('Erreur de création du répertoire : ' . $dirName . ', errCode : ' . $errCode );
			return null;
		}
		//
		//
		passthru('unzip '. $odtFilePath . ' -d ' . $dirName . ' >>books/sorties_console 2>&1', $errCode);
		if ($errCode){
			$this->logger->debug('Erreur de décompression : ' . $odtFilePath . ', errCode : ' . $errCode );
			return null;
		}
		//
		//
		if (!file_exists($xmlFileName)){
			$this->logger->info( '$xmlFileName : ' . $xmlFileName . ' does not exist !!!');
			// internal error !!
			return null;
		}

		// success ..
		return $xmlFileName;
	}

	/**
	 * @Route("/{slug}/processing", name="book_processing")
	 */
	public function bookProcessing(Request $request, Book $book, ContainerInterface $container)
	{
		// check if odt file is well-founded and get xml file name from it
		$xmlFileName = $this->isXmlFileValid($book);

		if ($xmlFileName){

			$xmlParser = new XmlParser($book, $xmlFileName, $this->em, $this->logger);
			$this->xmlParser = $xmlParser;

			// setting no execution time out .. bbrrrr !! 
			// if ($xmlParser->getRatio() > 1) ini_set('max_execution_time', '0');

			//
			// xml parsing !
			// while( !$xmlParser->isParsingCompleted() ){
			// 		$xmlParser->parse();
			// }

			// try async ..
			// $this->get('krlove.async.factory')
			// 		->call('app.service.parse', 'parse');

			
			// $container->get('krlove.service')->call('app.service.parse', 'parse');

			// could be a long time process ..
			$xmlParser->parse(); // can be very, very long for some books !-/ 
			
			if ($xmlParser->isParsingCompleted()){
				//
				//
			}

			$book->setParsingTime($xmlParser->getParsingTime())
				->setNbParagraphs($xmlParser->getNbParagraphs())
				->setNbSentences($xmlParser->getNbSentences())
				->setNbWords($xmlParser->getNbWords())
			;
			
			$this->em->persist($book);
			$this->em->flush();

			$this->addFlash(
				'info',
				'L\'analyse du document s\'est terminée avec succès !');

			return $this->redirectToRoute('book_show', [
				'slug' => $book->getSlug()
				]);
		}
		else {
			// flash message
			$this->addFlash(
				'warning',
				'Le fichier xml : ' . $xmlFileName . ' est invalide ou absent (cf bibnphi.log) !-\\'
			);
		}
		
		//
		//
        return $this->redirectToRoute('book_index');

	}

    /**
     * @Route("/new", name="book_new", methods={"GET","POST"})
	 * @IsGranted("ROLE_USER")
     */
	public function new( Request $request, 
						 EntityManagerInterface $entityManager ): Response
    {

		//
		$this->logger->info('>>> Entrée fonction BookController->new()' . microtime(true));
		$this->logger->info('>>> $request->getMethod() : ' . $request->getMethod() );

		//
        $book = new Book();
		$form = $this->createForm(BookType::class, $book);

		if ($request->getMethod()!='GET'){

			// $data1 = $request->getContent();
			// dd($request->getMethod(), $data1, $request, $book);
			
			// $data2 = $this->get('serializer')->deserialize($data1, 'App\Entity\Book', 'json');
			// dd($data2);
		}



		$form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

			$book->setNbParagraphs(0)
				->setNbSentences(0)
				->setNbWords(0)
				->setParsingTime(0)
				;

            $entityManager->persist($book);
			$entityManager->flush();

			$this->logger->info('>>> $book->getOdtOriginalName() : ' . $book->getOdtOriginalName() );

			//
			//
			return $this->redirectToRoute('book_processing',[
				'slug' => $book->getSlug(),
			]);
			//
			//

        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", name="book_show", methods={"GET"})
     */
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="book_edit", methods={"GET","POST"})
	 * @IsGranted("ROLE_USER")
     */
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, UploaderHelper $uploaderHelper): Response
    {
	
		// $odtBookSize = $book->getOdtBookSize(); // set if exists <-- never used !-))

		// dump($book);

		$localPath = $uploaderHelper->asset($book, 'odtBookFile');
		$fileName = \pathinfo($localPath, PATHINFO_FILENAME);
		$fileExt = \pathinfo($localPath, PATHINFO_EXTENSION);

		$dirName = 'books/' . $fileName; // to rip leading slash !?
		$fileName = $dirName . '.' . $fileExt;

		$form = $this->createFormBuilder($book)
					->add('title')
					->add('summary')
					->add('publishedYear')
					->add('author', EntityType::class, [
						'class' => Author::class,
						'choice_label' => 'lastName'
					])
					->add('odtBookFile', VichFileType::class, [
						'label' => 'Document au format odt',
						'required' => false,
						'allow_delete' => false,
						'download_label' => new PropertyPath('odtBookName')
					])
					->getForm();

		
		// $form = $this->createForm(BookType::class, $book);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
            // $entityManager = $this->getDoctrine()->getManager();
			$entityManager->flush();
			
			if (null !== $book->getOdtBookFile()){

				// a new book file has been loaded ..
				// need to remove previous document directory
				
				// unix cmd
				// delete previous directory recursive
				passthru('rm -v -r ' . $dirName . ' >books/sorties_console 2>&1', $errCode );
				// and odt file
				passthru('rm -v '. $dirName . '.odt >>books/sorties_console 2>&1', $errCode );

				// then create new document directory
				$localPath = $uploaderHelper->asset($book, 'odtBookFile');
				$fileName = \pathinfo($localPath, PATHINFO_FILENAME);
				$fileExt = \pathinfo($localPath, PATHINFO_EXTENSION);
		
				$dirName = 'books/' . $fileName; // to rip leading slash !?
				$fileName = $dirName . '.' . $fileExt;
		
				// unix cmd
				// create new directory
				passthru('mkdir -v ' . $dirName . ' >>books/sorties_console 2>&1', $errCode );
				
				// and unzip in it !
				passthru('unzip -q ' . $fileName . ' -d ' . $dirName . ' >>books/sorties_console 2>&1', $errCode);

				// if (!$errCode){}

				//
				// xml parsing !!
				$this->book = $book;
				$book->setParsingTime($this->parseXmlContent($dirName . '/content.xml'))
					->setNbParagraphs($this->nbBookParagraphs)
					->setNbSentences($this->nbBookSentences)
					->setNbWords($this->nbBookWords)
					;
				
				$entityManager->persist($book);
				$entityManager->flush();
				
			}
						
            return $this->redirectToRoute('book_show', [
				'slug' => $book->getSlug()
			]);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", name="book_delete", methods={"DELETE"})
	 * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request, Book $book): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
			$entityManager = $this->getDoctrine()->getManager();
			
			foreach( $book->getBookParagraphs() as $paragraph ){
				$book->removeBookParagraph($paragraph);
			}

			//
			// unix cmd
			// remove odt file
			$dirName = $book->getOdtBookName();
			passthru('rm -v books/'. $dirName . ' >>books/sorties_console 2>&1', $errCode );

			$this->logger->info('Remove odt file : books/' . $dirName . ' (with title : ' . $book->getTitle() . ')' );

			// remove .whatever to get directory name << buggy !-(
			$dirName = substr($dirName, 0, strpos($dirName, '.'));
			// then delete associated directory recursive
			passthru('rm -v -r books/' . $dirName . ' >>books/sorties_console 2>&1', $errCode );

			//
			//
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('book_index');
	}


	/**
	 *      O D T   X M L   p a r s i n g
	 */
	private function start_element_handler($parser, $element, $attribs)
	{

		switch($element){

			case "TEXT:P" ;
			case "TEXT:H" ;
				$this->counter++;
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
	 * Parse the xml file which contains the odt document.
	 *
	 * @param string $fileName
	 * @return void
	 */
	private function parseXmlContent( string $fileName ) : ?float
	{
		//
		$timeStart = microtime(true);

		// various initialization settings
		$this->noteCollection = [];
		$this->text = '';
		$this->nbBookWords = 0;
		$this->nbBookSentences = 0;
		$this->nbBookParagraphs = 0;

		// get file size
		$this->xmlFileSize = filesize($fileName);
		// nb of file buffers to be read
		$ratio = ceil($this->xmlFileSize / self::READ_BUFFER_SIZE);

		// unix cmd
		// 
		passthru('echo \'$fileName:' . $fileName . ' ~ $fileSize:' . $this->xmlFileSize . '\' >>books/sorties_console 2>&1', $errCode );
		$this->logger->info('$fileName : ' . $fileName . ' ~ $fileSize:' . $this->xmlFileSize);
		passthru('echo \'ratio:' . $ratio . '\' >>books/sorties_console 2>&1', $errCode );
		$this->logger->info('$ratio : ' . $ratio );


		// setting no execution time out .. bbrrrr !! 
		if ($ratio > 1) ini_set('max_execution_time', '0');

		//
		// $fh = @fopen() 
		// ( @ symbol supresses any php driven error message !? )
		//
		$fh = fopen($fileName, 'rb');
		if ( $fh ){

			$nbBuffer = 0;

			$this->parser = xml_parser_create();
			$this->counter = 0; // nb de paragraphes !!?

			//
			// set up the handlers
			xml_set_element_handler($this->parser, [$this, "start_element_handler"], [$this, "end_element_handler"]);
			xml_set_character_data_handler($this->parser, [$this, "character_data_handler"]);

			// fread vs fgets !! ??
			while (($buffer = fread($fh, self::READ_BUFFER_SIZE)) != false){
				//
				// 
				$nbBuffer++;
				xml_parse($this->parser, $buffer);

				sleep(1); // ?? cf err 503 !-(
				$this->logger->info('n° read buffer : ' . $nbBuffer );
			}

			xml_parse($this->parser, '', true); // finalize parsing
			xml_parser_free($this->parser);
			unset($this->parser);


			if (!feof($fh)) {
				passthru('echo "Erreur: fread() a échoué ..." >>books/sorties_console 2>&1', $errCode);
				return 0;
			}

			fclose($fh);

		}
		else {
			passthru('echo "Erreur: fopen a retourné FALSE !!" >>books/sorties_console 2>&1', $errCode);
			return 0 ; // no parsing !!
		}

		// stop timer !
		//$timeEnd = \microtime(true);

		$duration = \microtime(true) - $timeStart;
		$this->logger->info('Parsing duration : ' . $duration );
		// passthru('echo \'Parsing duration:' . $duration . '\' >>books/sorties_console 2>&1', $errCode );

		// dd($timeStart, $timeEnd, $timeEnd - $timeStart);
		return($duration);
	}

	private function handleBookParagraph($rawParagraph, $noteCollection)
	{
		if ($rawParagraph != ''){

			$entityManager = $this->getDoctrine()->getManager();
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

						$this->nbBookSentences++;
						$entityManager->persist($bookSentence);
					}

				}
			}

			//
			if ( NULL !== $bookParagraph ){

				$this->nbBookParagraphs++;				
				$entityManager->persist($bookParagraph);

				//
				// then get notes if any for the paragraph
				if (!empty($noteCollection)){
					foreach($this->noteCollection as $note){
						$pNote = new BookParagraph();
						$pNote->SetBook($this->book);
						$entityManager->persist($pNote);

						$sNote = new BookSentence();
						$sNote->setBookParagraph($pNote);
						$sNote->setContent($note);
						$entityManager->persist($sNote);

					}
				}

				$entityManager->flush();
			}
			
		}
	}

}
