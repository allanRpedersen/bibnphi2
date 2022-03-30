<?php

namespace App\Controller;

use App\Entity\SentenceSearch;
use App\Form\SentenceSearchType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * 
 */
class FrontController extends AbstractController
{
	private $br, $ar; 

	public function __construct( AuthorRepository $ar, BookRepository $br ){

		$this->br = $br;
		$this->ar = $ar;

	}


    /**
     * @Route("/", name="front")
	 * @return Response
     */
    public function index(Request $request): Response // , PaginatorInterface $paginator)
    {
		// init
		//
		$authors = [];
		$bookList = [];

		$session = $request->getSession();

		$hlContents = [];
		$hlContent = [
			'bookId' => 0,
			'contentType' => 'p',
			'origId'	=> 0,
			'needles'	=> [],
		];


		//
		$nbBooksInLibrary = count($this->br->findAll());

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
					$bookList = $this->br->findAll();

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
						$books = $this->br->findByAuthor($author);
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

			$hlContents = [];

			foreach($bookList as $book){

				$paragraphs = $book->getBookParagraphs();
				$notes = $book->getBookNotes();

				foreach($paragraphs as $paragraph){

					if ( $paragraph->isContentMatching($stringToSearch)){

						$matchingParagraphs[] = $paragraph;
						$nbFoundStrings += sizeof($paragraph->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$hlContent = [
							'bookId' 		=> $book->getId(),
							'contentType'	=> 'p',
							'origId'		=> $paragraph->getId(),
							'needles'		=> $paragraph->getFoundStringIndexes(),
						];
						
						$hlContents[] = $hlContent;

					}

				}

				foreach($notes as $note){

					if ( $note->isContentMatching($stringToSearch)){

						$matchingNotes[] = $note;
						$nbFoundStrings += sizeof($note->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$hlContent = [
							'bookId' 		=> $book->getId(),
							'contentType' 	=> 'n',
							'origId'		=> $note->getId(),
							'needles'		=> $note->getFoundStringIndexes(),
						];
						
						$hlContents[] = $hlContent;

					}
				}

				if ($nbFoundStrings){
					$session->set('hlString', $stringToSearch);
					$session->set('hlContents', $hlContents);
				}
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
			'authors' => $this->ar->findByLastName(),
			// 'authors' => $paginator->paginate(
			// 			$this->ar->findByLastNameQuery(),
			// 			$request->query->getInt('page', 1),
			// 			3
			// ),
			'nbBooks' => $nbBooksInLibrary,
			'form' => $form->createView(),
        ]);
    }
}
