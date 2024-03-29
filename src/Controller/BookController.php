<?php

namespace App\Controller;

use Monolog\Logger;
use App\Entity\Book;
use App\Entity\Author;
use App\Form\BookType;
use App\Service\SortMgr;
use App\Entity\BookSelect;
use App\Service\XmlParser;
use App\Form\BookSelectType;
use App\Entity\SentenceSearch;
use App\Form\SentenceSearchType;
use App\Service\SelectAndSearch;
use App\Repository\BookRepository;
// use Knp\Component\Pager\PaginatorInterface;
use Monolog\Handler\StreamHandler;
use App\Repository\BookNoteRepository;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookParagraphRepository;
use App\Traits\TraitFileMgr;
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


/**
 * @Route("/book")
 */
class BookController extends AbstractController
{
	//
	//
	private $xmlParser;
	private $book;
	private $br;
	
	private $uploaderHelper;
	private $logger;
	private $projectDir;
	private $em;

	//
	// 
	private $fDev; 

	//
	//
	use TraitFileMgr;

	public function __construct(KernelInterface $kernel,
								EntityManagerInterface $em,
								UploaderHelper $uploaderHelper,
								BookRepository $br)
	{
		$this->projectDir = $kernel->getProjectDir();

		$this->em = $em;
		$this->br = $br;

		$this->uploaderHelper = $uploaderHelper;

		$this->logger = new Logger('bibnphi');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );

	}

    /**
     * @Route("/", name="book_index", methods={"GET"})
     */
    public function index(Request $request, SelectAndSearch $sas): Response
    // public function index(Request $request, PaginatorInterface $paginator, BookRepository $bookRepository): Response
    {
        // return $this->render('book/index.html.twig', [
		// 	'books' => $paginator->paginate(
		// 		$this->br->findByTitleQuery(),
		// 		$request->query->getInt('page', 1),
		// 		6
		// 	),
		// ]);

		$sm = new SortMgr();
		//
		// the Book selection form
		$bookSelect = new BookSelect();
		$bookSelectForm = $this->createForm(BookSelectType::class, $bookSelect);
		$bookSelectForm->handleRequest($request);
		//
		if ($bookSelectForm->isSubmitted() && $bookSelectForm->isValid())
		{
			// set currentBookSelectionIds in the session
			$sas->SelectBooks($bookSelect);
			return $this->redirectToRoute('front');
		}

		//
		$bookList = $sm->SortByAuthor($this->br->findAll());

		//
		// the Sentence search form
		$sentenceSearch = new SentenceSearch();
		$sentenceSearchForm = $this->createForm(SentenceSearchType::class, $sentenceSearch);
		$sentenceSearchForm->handleRequest($request);
		//
		if ($sentenceSearchForm->isSubmitted() && $sentenceSearchForm->isValid())
		{
			// If the string to search is found ..
			// then set nbFoundStrings, nbFoundInBooks, hlString, hlContents in the session
			$sas->SearchString($sentenceSearch, $bookList);
			return $this->redirectToRoute('front');
		}

		return $this->render('book/index.html.twig', [
			'books'		=> $bookList,
			'bodyId'	=> 'liste-titres',
			'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
			'bookSelectForm'		=> $bookSelectForm->createView(),
			// 'hideContact'			=> true,
			]);
	}
	
	/**
	 * @Route("/{slug}/processing", name="book_processing")
	 */
	public function bookProcessing(Request $request, Book $book)
	{
		//
		// uploaded odt file, $odtFilePath, is set once the entity has been persisted ..
		$odtFilePath = $this->uploaderHelper->asset($book, 'odtBookFile');
		// to rip the leading slash 
		// as \Vich\UploaderBundle\Templating\Helper\UploaderHelper::asset returns web site public path whhich begins with '/' ...
		$odtFilePath = substr($odtFilePath, 1);
		// file system current path is already website public path

		// check if odt file is well-founded and get the dir name of the xml file document.xml
		$workingDir = $this->isOdtDocValid($odtFilePath);

		if ($workingDir){
 
			$xmlFileSize = filesize($workingDir . '/document.xml');
			$book->setXmlFileSize($xmlFileSize);

			// for the big xml files, use an external command =======
			if ( $xmlFileSize > $this->getParameter('app.xmlfile_size_external_process')){
				//
				//
				$cmd = $this->getParameter('kernel.project_dir')
						. '/bin/console app:xml-parser --quiet --mode=' . $_ENV['APP_ENV']. ' '
						. $workingDir . ' '
						. $book->getId() . ' > /dev/null 2>&1';
				
							////////
				passthru( $cmd );///
							////////

				$this->addFlash(
					'success',
					'L\'analyse du document : ' . $book->getTitle() . ' s\'est terminée avec succès par la commande extérieure !');

				return $this->redirectToRoute('book_show', [
					'slug' => $book->getSlug()
					]);
			}
			else {

				//
				// is that relevant ???
				// $fileBufferSize = $this->getParameter('app.parsing_buffer_size_xl');
				// if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_l');
				// if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_m');
				// if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_s');
				// if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_xs');
				$fileBufferSize = $this->getParameter('app.parsing_buffer_size_l');

				$xmlParser = new XmlParser(
									$book, 
									$workingDir, 
									$this->getParameter('kernel.project_dir'), 
									$fileBufferSize, 
									$this->em,
									$_ENV['APP_ENV'], // 'dev' or 'prod'
									);

				$this->xmlParser = $xmlParser;
				
				// issue with platon-gorgias if not set !! 
				// ini_set('max_execution_time', '0');

				// setting no execution time out .. bbrrrr !! 
				// if ($xmlParser->getRatio() > 1) ini_set('max_execution_time', '0');

				// could be a long time process ..
				$xmlParser->parse();
				
				if ($xmlParser->isParsingCompleted()){
					//
					//
					$book->setParsingTime($xmlParser->getParsingTime())
						->setNbParagraphs($xmlParser->getNbParagraphs())
					;

					$this->em->persist($book);
					$this->em->flush();

					$this->addFlash(
						'success',
						'L\'analyse du document s\'est terminée avec succès ! ( ' . $xmlParser->getNbParagraphs() . ' paragraphes en '. round($xmlParser->getParsingTime(), 2) . ' secondes)');

					return $this->redirectToRoute('book_show', [
						'slug' => $book->getSlug()
						]);
					
				}
				else {
					$this->addFlash(
						'warning',
						'Echec de l\'analyse du document : ' . $book->getTitle());

					return $this->redirectToRoute('front');

				}
			}

		}
		else {
			// flash message
			$this->addFlash(
				'error',
				'Le document odt : ' . $book->getOdtBookName() . ' est invalide ou absent (cf bibnphi.log) !-\\'
			);
		}
		//
		//
        return $this->redirectToRoute('front');

	}

    /**
     * @Route("/new", name="book_new", methods={"GET","POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
     */
	public function new( Request $request ): Response
    {
		//
        $book = new Book();
		$form = $this->createForm(BookType::class, $book);

		//
		if (!file_put_contents($this->projectDir.'/public/percentProgress.log', '0%'))
			$this->logger->error('>>> on file_put_contents');

		$form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

			$book->setNbParagraphs(0)
				->setParsingTime(0)
				->setXmlFileSize(0)
				;

            $this->em->persist($book); // to set the slug

			$slug = $book->getSlug();
			if (!$this->br->findOneBySlug($slug)){
				$this->em->flush();

				$this->logger->info('>>> $book->getOdtOriginalName() : ' . $book->getOdtOriginalName() );
				//
				//
				return
					$this->redirectToRoute('book_processing',[
						'slug' => $book->getSlug(),
					]);
			}
			else {
				$this->addFlash(
					'danger',
					'Ajout impossible !! Un ouvrage du même nom existe déjà dans la bibliothèque.');
			}



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
	 * @Route("/{slug}/jumpTo/{whereToJump}", name="book_show_with_jump", methods={"GET"})
	 */
	public function __no_more_used__showAndJump( Request $request,
								 Book $book, 
								 $whereToJump, 
								 BookParagraphRepository $pRepo, 
								 BookNoteRepository $nRepo)
	{
		$session = $request->getSession();

		// after a search, the matching strings are parameters of the session
		// $hlString: searched string, $allHlContents: array of needles index which may referenced several books
		$allHlContents = $session->get('hlContents', []);

		$hlString = $session->get('hlString');
		$hlContents = [];
		
		//
		// Extraire les éléments hlContent associés au livre courant ..
		foreach($allHlContents as $hlContent){
			if($hlContent['bookId']==$book->getId()) $hlContents[] = $hlContent;
		}

		$navLinks = [];

		$hlParagraphs = [];
		$hlNotes = [];

		$s = sizeof($hlContents);
		for($i=0; $i < $s; $i++){

			if($i < $s-1){
				$navLinks[] = ( 'p' == $hlContents[$i+1]['contentType'] ? '_' : 'note_' ) . $hlContents[$i+1]['origId'];
			}
			else {
				$navLinks[] = ( 'p' == $hlContents[0]['contentType'] ? '_' : 'note_' ) . $hlContents[0]['origId'];
			}
		}


		foreach($hlContents as $key => $hlContent){
						
			switch ($hlContent['contentType']){
				
				case 'p' : // paragraph
					
					$paragraph = $pRepo->findOneById($hlContent['origId']);
					
					$paragraph
						->setFoundStringIndexes($hlContent['needles'])
						->setSearchedString($hlString)
						->setNextOccurence($navLinks[$key]);

					$hlParagraphs[] = $paragraph;
				break;

				case 'n' : // note

					$note = $nRepo->findOneById($hlContent['origId']);

					$note->setFoundStringIndexes($hlContent['needles']);
					$note->setSearchedString($hlString);
					$note->setNextOccurence($navLinks[$key]);
					
					$hlNotes[] = $note;
				break;

				default :
					//error
				

			}

		}

        return $this->render('book/show.html.twig', [
            'book'			=> $book,
			'jump2'			=> $whereToJump,
			// 'hlParagraphs'	=> $hlParagraphs,
			// 'hlNotes'		=> $hlNotes,
        ]);


	}

    /**
     * @Route("/{slug}/edit", name="book_edit", methods={"GET","POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
     */
    public function edit(Request $request, Book $book, UploaderHelper $uploaderHelper): Response
    {
	
		$localPath = $uploaderHelper->asset($book, 'odtBookFile');
		$fileName = \pathinfo($localPath, PATHINFO_FILENAME);
		$fileExt = \pathinfo($localPath, PATHINFO_EXTENSION);

		$dirName = 'books/' . $fileName; // to rip leading slash !?
		$fileName = $dirName . '.' . $fileExt;

		$form = $this->createFormBuilder($book)
					->add('title')
					// ->add('summary')
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
					// ->add('fpImageFile', VichFileType::class, [
					// 	'label' => 'Image de couverture',
					// 	'required' => false,
					// 	'allow_delete' => false,
					// 	'download_label' => static function (Book $book) {
					// 		return $book->getFpImageFileName();
					// 	},
					// ])
		
					->getForm();

		
		// $form = $this->createForm(BookType::class, $book);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			$this->em->flush();
			
			if (null !== $book->getOdtBookFile()){

				// a new book file has been loaded ..
				// need to remove previous document directory
				
				// unix cmd
				// delete previous directory recursive
				passthru('rm -v -r ' . $dirName . ' > /dev/null 2>&1', $errCode );
				// and odt file
				passthru('rm -v '. $dirName . '.odt > /dev/null 2>&1', $errCode );

				// then create new document directory
				$localPath = $uploaderHelper->asset($book, 'odtBookFile');
				$fileName = \pathinfo($localPath, PATHINFO_FILENAME);
				$fileExt = \pathinfo($localPath, PATHINFO_EXTENSION);
		
				$dirName = 'books/' . $fileName; // to rip leading slash !?
				$fileName = $dirName . '.' . $fileExt;
		
				// unix cmd
				// create new directory
				passthru('mkdir -v ' . $dirName . ' > /dev/null 2>&1', $errCode );
				if ($errCode){
					$this->logger->debug('ERREUR de création du répertoire : ' . $dirName . ', errCode : ' . $errCode );
					// flash message !!
				}
				else{
					// then unzip in it !
					passthru('unzip -q ' . $fileName . ' -d ' . $dirName . ' > /dev/null 2>&1', $errCode);
					if ($errCode){
						$this->logger->debug('ERREUR de décompression : ' . $fileName . ', errCode : ' . $errCode );
						// flash message !!
					}
					else{

						//
						// if (!file_put_contents('percentProgress.log', '0%')) $this->logger->error('>>> on file_put_contents');
		
						//
						// xml parsing !!
						$this->book = $book;
						$book->setNbParagraphs(0)
							->setParsingTime(0)
							->setXmlFileSize(0)
							;
		
						$this->em->persist($book);
						$this->em->flush();
						
						return $this->redirectToRoute('book_processing', [
							'slug' => $book->getSlug()
						]);
					}
					
				}
			
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
     * @Route("/{slug}", name="book_delete", methods={"DELETE", "POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
     */
    public function delete(Request $request, Book $book): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
			
			//
			//
			$booksInfo = [];
			$booksInfo[] = [
				'id'			=> $book->getId(),
				'title'			=> $book->getTitle(),
				'filename'		=> $book->getOdtBookName(),
				'parsingtime'	=> $book->getParsingTime()
			];

			foreach( $book->getBookmarks() as $bookmark ){
				$book->removeBookmark($bookmark);
				$this->em->remove($bookmark);
			}

			foreach( $book->getBookParagraphs() as $paragraph ){
				$book->removeBookParagraph($paragraph);
				$this->em->remove($paragraph);
			}

			$this->em->remove($book);
			$this->em->flush();
	
			$this->RemoveOdtAndDirectory($booksInfo, $request->getSession());
        }

        // return $this->redirectToRoute('book_index');
        return $this->redirectToRoute('front'); /// ???
	}
}
