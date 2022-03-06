<?php

namespace App\Controller;

use Monolog\Logger;
use App\Entity\Book;
use App\Entity\Author;
use App\Form\BookType;
use App\Service\XmlParser;
use App\Service\ContentMgr;
use App\Entity\BookSentence;
use App\Entity\BookParagraph;
use App\Repository\BookNoteRepository;
use App\Repository\BookParagraphRepository;
use App\Repository\BookRepository;
use Monolog\Handler\StreamHandler;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichFileType;
use App\Repository\HighlightedContentRepository;

use Symfony\Component\Console\Output\NullOutput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

//
// $bool=pcntl_async_signals(true);
//

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{

	public function __construct(KernelInterface $kernel, EntityManagerInterface $em, UploaderHelper $uploaderHelper)
	{
		$this->projectDir = $kernel->getProjectDir();

		$this->em = $em;

		$this->uploaderHelper = $uploaderHelper;

		$this->logger = new Logger('bibnphi');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );


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
		passthru('mkdir -v ' . $dirName . ' > /dev/null 2>&1', $errCode );
		if ($errCode){
			$this->logger->debug('Erreur de création du répertoire : ' . $dirName . ', errCode : ' . $errCode );
			return null;
		}
		//
		//
		passthru('unzip '. $odtFilePath . ' -d ' . $dirName . ' > /dev/null 2>&1', $errCode);
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
	public function bookProcessing(Request $request, Book $book, KernelInterface $kernel)
	{


		// check if odt file is well-founded and get xml file name from it
		$xmlFileName = $this->isXmlFileValid($book);

		if ($xmlFileName){


			// $application = new Application($kernel);

			// $application->setAutoExit(false);

			// $input = new ArrayInput([
			// 	'command' => 'app.:xml-parser',
			// 	// (optional) define the value of command arguments
			// 	'xmlFileName' => $xmlFileName,
			// 	'bookId' => $book->getId(),
			// 	// (optional) pass options to the command
			// 	// '--message-limit' => $messages,
			// ]);

			// // You can use NullOutput() if you don't need the output
			// $output = new BufferedOutput();

			// $output = new NullOutput();
			// $application->run($input, $output);

			// // return the output, don't use if you used NullOutput()
			// // $content = $output->fetch();

			// // return new Response(""), if you used NullOutput()
			// // return new Response($content);
			// return new Response("");
 
			$xmlFileSize = filesize($xmlFileName);

			// for the big xml files, use an external command (  NOT TESTED ) =======
			if ( $xmlFileSize > $this->getParameter('app.xmlfile_size_external_process')){
				//
				$cmd = $this->getParameter('kernel.project_dir')
						. '/bin/console app:xml-parser --env=prod --quiet '
						. $xmlFileName . ' '
						. $book->getId() . ' > /dev/null 2>&1';
				
				passthru( $cmd );  /////////////////
			}
			else {

				//
				// is that relevant ???
				$fileBufferSize = $this->getParameter('app.parsing_buffer_size_xl');
				if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_l');
				if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_m');
				if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_s');
				if ( $xmlFileSize < $fileBufferSize ) $fileBufferSize = $this->getParameter('app.parsing_buffer_size_xs');

				$xmlParser = new XmlParser(
											$book, 
											$xmlFileName, 
											$this->getParameter('kernel.project_dir'), 
											$fileBufferSize, 
											$this->em,
										);

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

				//  $container
				// ContainerInterface $container->get('krlove.service')->call('app.service.parse', 'parse');

				// could be a long time process ..
				$xmlParser->parse(); // can be very, very long for some books !-/ get a command for it !!!!!
				
				if ($xmlParser->isParsingCompleted()){
					//
					//
					$book->setParsingTime($xmlParser->getParsingTime())
						->setNbParagraphs($xmlParser->getNbParagraphs())
						->setNbSentences($xmlParser->getNbSentences())
						->setNbWords($xmlParser->getNbWords())
					;

					$this->em->persist($book);
					$this->em->flush();

					$this->addFlash(
						'info',
						'L\'analyse du document s\'est terminée avec succès ! ( ' . $xmlParser->getNbParagraphs() . ' paragraphes en '. round($xmlParser->getParsingTime(), 2) . ' secondes)');

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
			}

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
		$this->logger->info('>>> Entrée BookController->new()' . microtime(true));
		$this->logger->info('>>> $request->getMethod() : ' . $request->getMethod() );

		//
        $book = new Book();
		$form = $this->createForm(BookType::class, $book);

		// if ($request->getMethod()!='GET'){

		// 	// $data1 = $request->getContent();
		// 	// dd($request->getMethod(), $data1, $request, $book);
			
		// 	// $data2 = $this->get('serializer')->deserialize($data1, 'App\Entity\Book', 'json');
		// 	// dd($data2);
		// }

		//
		if (!file_put_contents('percentProgress.log', '0%')) $this->logger->error('>>> on file_put_contents');

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
	 * @Route("/{slug}/jumpTo/{whereToJump}", name="book_show_with_jump", methods={"GET"})
	 */
	public function showAndJump(Book $book, $whereToJump, HighlightedContentRepository $hlRepo, BookParagraphRepository $pRepo, BookNoteRepository $nRepo)
	{
		// This route is reached after a string research in the library
		$highlightedContents = $hlRepo->findByBookId($book->getId());

		$circumArray = [];
		$targetArray = [];

		foreach($highlightedContents as $highlightedContent){
			$circumArray[] = [$highlightedContent->getContentType(), $highlightedContent->getOrigId()];
		}

		for ($i=0;$i<sizeof($circumArray)-1;$i++){

			switch ($circumArray[$i+1][0]){
				case 'paragraph' :
					$targetArray[$i] = '_' . $circumArray[$i+1][1];
					break;

				case 'note' :
					$targetArray[$i] = 'note_' . $circumArray[$i+1][1];
					break;

			}
		}
		
		switch ($circumArray[0][0]){
			case 'paragraph' :
				$targetArray[$i]='_' . $circumArray[0][1];
				break;

			case 'note' :
				$targetArray[$i] = 'note_' . $circumArray[0][1];
				break;

		}
		

		$endTag = '</mark></a>' ;
		
		$contentMgr = new ContentMgr();
		$hlParagraphs = [];
		$hlNotes = [];
		
		foreach($highlightedContents as $key => $highlightedContent){
			
			$indexArray = $highlightedContent->getMatchingIndexes();
			$lengthToSurround = mb_strlen($highlightedContent->getHighlightedString());

			$beginTag = '<a title="Aller à la prochaine occurrence" href="#'
					. $targetArray[$key]
					. '"><mark>';
			
			switch ($highlightedContent->getContentType()){
				
				case 'paragraph' :
					
					$paragraph = $pRepo->findOneById($highlightedContent->getOrigId());
					
					$paragraph->setHighlightedContent(
						$contentMgr->setOriginalContent($paragraph->getContent())
									->addTags($indexArray, $lengthToSurround, $beginTag, $endTag )
						);
					
					$hlParagraphs[] = $paragraph;
				break;

				case 'note' :

					$note = $nRepo->findOneById($highlightedContent->getOrigId());

					$note->setHighlightedContent(
						$contentMgr->setOriginalContent($note->getContent())
									->addTags($indexArray, $lengthToSurround, $beginTag, $endTag )
						);
					
					$hlNotes[] = $note;

				break;

				default :
					//error
				

			}


		}

        return $this->render('book/show.html.twig', [
            'book'			=> $book,
			'jump2'			=> $whereToJump,
			'hlParagraphs'	=> $hlParagraphs,
			'hlNotes'		=> $hlNotes,
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
						if (!file_put_contents('percentProgress.log', '0%')) $this->logger->error('>>> on file_put_contents');
		
						//
						// xml parsing !!
						$this->book = $book;
						$book->setNbParagraphs(0)
							->setNbSentences(0)
							->setNbWords(0)
							->setParsingTime(0)
							;
		
						$entityManager->persist($book);
						$entityManager->flush();
						
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
			passthru('rm -v books/'. $dirName . ' > /dev/null 2>&1', $errCode );

			$this->logger->info('Remove odt file : books/' . $dirName . ' (with title : ' . $book->getTitle() . ')' );

			// remove .whatever to get directory name << buggy !-(
			$dirName = substr($dirName, 0, strpos($dirName, '.'));
			// then delete associated directory recursive
			passthru('rm -v -r books/' . $dirName . ' > /dev/null 2>&1', $errCode );

			//
			//
            $entityManager->remove($book);
            $entityManager->flush();
        }

        // return $this->redirectToRoute('book_index');
        return $this->redirectToRoute('front');
	}

}
