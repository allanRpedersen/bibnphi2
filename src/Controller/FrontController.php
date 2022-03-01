<?php

namespace App\Controller;

use App\Entity\SentenceSearch;
use App\Form\SentenceSearchType;
use App\Entity\HighlightedContent;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Repository\HighlightedContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FrontController extends AbstractController
{
	private $em;

	private $hlRepo;

	public function __construct(EntityManagerInterface $em, HighlightedContentRepository $hlRepo){

		$this->em = $em;

		$this->hlRepo = $hlRepo;
	}


    /**
     * @Route("/", name="front")
	 * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator, BookRepository $bookRepository, AuthorRepository $authorRepository)
    {
		// init
		//
		$authors = [];
		$bookList = [];
		
		// dd($this->getParameter('kernel.environment'));
		//
		$matchingSentences = [];
		$matchingSentence = [
			'book' => NULL,
			'sentence' => NULL,
			'iNeedle' => 0,
		];


		//
		$this->hlRepo->DeleteAll();
		$this->em->flush();

		//
		// $hlContents = $this->hlRepo->findAll();
		// if (count($hlContents)){
		// 	foreach($hlContents as $hlContent){
		// 		$this->em->remove($hlContent);
		// 	}
		// 	$this->em->flush();
		// }
		
		//
		$nbBooksInLibrary = count($bookRepository->findAll());

		//
		// the search form
		$search = new SentenceSearch();
		$form = $this->createForm(SentenceSearchType::class, $search);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			$stringToSearch = $search->getStringToSearch();

			if ($search->getBooks()->isEmpty()){

				if ($search->getAuthors()->isEmpty()){

					// search in all the library .. huge !-|
					// echo "<script>alert(\"(Vous allez effectuer une recherche sur toute la bibliothèque ??-)\")</script>";
					$bookList = $bookRepository->findAll();

								// ===================================+
								// std execution time is 30 sec.      |
								// set execution time to infinite !!  |
								//                                    |
								ini_set('max_execution_time', '0'); //|
								//                                    |
								// ===================================+

				}
				
				else {

					// search in all the books wrote by the given author list ..
					$authors = $search->getAuthors();
					foreach($authors as $author){
						$books = $bookRepository->findByAuthor($author);
						foreach($books as $book) $bookList[] = $book;
					}
					
				}

			}
			else {
				
				// search through a list of books ..
				$bookList = $search->getBooks();
				
			}
			
			//
			//
			$matchingParagraphs = [];
			$matchingNotes = [];
			$matchingBookList = [];
			$nbFoundStrings = 0;

			foreach($bookList as $book){

				$paragraphs = $book->getBookParagraphs();
				$notes = $book->getBookNotes();

				foreach($paragraphs as $paragraph){

					if ( $paragraph->isContentMatching($stringToSearch)){

						$matchingParagraphs[] = $paragraph;
						$nbFoundStrings += sizeof($paragraph->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$foundContent = new HighlightedContent();

						$foundContent
									->setBookId($book->getId())
									->setContentType('paragraph')
									->setOrigId($paragraph->getId())
									->setHighlightedString($stringToSearch)
									->setMatchingIndexes($paragraph->getFoundStringIndexes());
						
						$this->em->persist($foundContent);
					}

				}

				foreach($notes as $note){

					if ( $note->isContentMatching($stringToSearch)){

						$matchingNotes[] = $note;
						$nbFoundStrings += sizeof($note->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$foundContent = new HighlightedContent();

						$foundContent
									->setBookId($book->getId())
									->setContentType('note')
									->setOrigId($note->getId())
									->setHighlightedString($stringToSearch)
									->setMatchingIndexes($note->getFoundStringIndexes());
						
						$this->em->persist($foundContent);

					}
				}

				if ($nbFoundStrings) $this->em->flush();
			}

			return $this->render('front/search.html.twig', [
				'string'			=> $stringToSearch,
				'bookList'			=> $bookList,
				'matchingBookList'	=> $matchingBookList,
				'paragraphs'		=> $matchingParagraphs,
				'notes'				=> $matchingNotes,
				'nbFoundStrings'	=> $nbFoundStrings

			]);

		}

        return $this->render('front/index.html.twig', [
			'authors' => $authorRepository->findByLastName(),
			// 'authors' => $paginator->paginate(
			// 			$authorRepository->findByLastNameQuery(),
			// 			$request->query->getInt('page', 1),
			// 			3
			// ),
			'nbBooks' => $nbBooksInLibrary,
			'form' => $form->createView(),
        ]);
    }
}
