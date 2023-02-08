<?php

namespace App\Controller;

use App\Service\SortMgr;
use App\Entity\BookSelect;
use App\Form\BookSelectType;
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

	private $authors;
	private $books;
	private $nbAuthors, $nbBooks;

	public function __construct( AuthorRepository $ar, BookRepository $br ){

		$this->ar = $ar;
		$this->authors = $this->ar->findByLastName();
		$this->nbAuthors = count($this->authors);
		
		$this->br = $br;
		$this->books = $this->br->findAll();
		$this->nbBooks = count($this->books);

	}


    /**
     * @Route("/", name="front")
	 * @return Response
     */
    public function index(Request $request): Response // , PaginatorInterface $paginator)
    {
		// init
		//
		$sm = new SortMgr();

		$authors = [];
		$bookList = $sm->sortByAuthor($this->books);
		$authorSelected = false;

		$session = $request->getSession();

		$hlContents = [];
		$hlContent = [
			'bookId' => 0,
			'contentType' => 'p',
			'origId'	=> 0,
			'needles'	=> [],
		];

		/** any user authenticated ?
		 ** 
		 ** @var \App\Entity\User $user
		**/
		$user = $this->getUser();
		
		//
		// the Book search form
		$bookSelect = new BookSelect();
		$bookSelectForm = $this->createForm(BookSelectType::class, $bookSelect);
		$bookSelectForm->handleRequest($request);
		
		if ($bookSelectForm->isSubmitted() && $bookSelectForm->isValid())
		{
			$bookList = [];

			if (!$bookSelect->getAuthors()->isEmpty()){
				// search in all the books wrote by the given author list ..
				$authors = $bookSelect->getAuthors();
				$authorSelected = true;
				foreach($authors as $author){
					$books = $this->br->findByAuthor($author);
					foreach($books as $book) $bookList[] = $book;
				}
				
			}
			if (!$bookSelect->getBooks()->isEmpty()){
				$books = $bookSelect->getBooks();
				foreach($books as $book){
					$bookList[] = $book;
				}
			}

			if (!$bookList && !$authorSelected){
				
				// ??????????????
				$bookList = $this->books;
				dd($bookList);
			}

			$bookList = $sm->sortByAuthor($bookList);

			$bookSelectionIds = [];
			foreach( $bookList as $book ){
				$bookSelectionIds[] = $book->getId();
			}

			$session->set('currentBookSelectionIds', $bookSelectionIds);
		}
		
		//
		//
		$currentBookSelectionIds = $session->get('currentBookSelectionIds');
		if ($currentBookSelectionIds){
			$bookList = [];
			foreach($currentBookSelectionIds as $id){
				$bookList[] = $this->br->findOneById($id);
			}
			$bookList = $sm->sortByAuthor($bookList);
		}
		
		//
		// the Sentence search form
		$sentenceSearch = new SentenceSearch();
		$sentenceSearchForm = $this->createForm(SentenceSearchType::class, $sentenceSearch);
		$sentenceSearchForm->handleRequest($request);

		if ($sentenceSearchForm->isSubmitted() && $sentenceSearchForm->isValid())
		{
			$stringToSearch = $sentenceSearch->getStringToSearch();

			// Get the book list, begin words search process..
			//

			$matchingParagraphs = [];
			$matchingBookList = [];
			$matchingNotes = [];
			$nbFoundStrings = 0;
			$hlContents = [];

			// Word/Sentence search process
			//
			$bookList = $sm->sortByTitle($bookList);
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

					// $matchingBookList = $sm->sortByTitle($matchingBookList);
				}
			}

			return $this->render('front/search.html.twig', [
				'form'				=> $sentenceSearchForm->createView(),
				'string'			=> $stringToSearch,
				'bookList'			=> $bookList,
				'matchingBookList'	=> $matchingBookList,
				'paragraphs'		=> $matchingParagraphs,
				'notes'				=> $matchingNotes,
				'nbFoundStrings'	=> $nbFoundStrings

			]);

		}

		if ($currentBookSelectionIds){
			return $this->render('front/selected_index.html.twig', [
				//'authors' => $this->authors,
				// 'authors' => $paginator->paginate(
				// 			$this->ar->findByLastNameQuery(),
				// 			$request->query->getInt('page', 1),
				// 			3
				// ),
				'books'		=> $bookList,
				'openBook'	=> $bookList[0],
				// 'nbAuthors'	=> $this->nbAuthors,
				// 'nbBooks'	=> $this->nbBooks,
				'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
				'bookSelectForm'		=> $bookSelectForm->createView(),
				'isSelectedList'		=> $currentBookSelectionIds,
			]);
	
		}

        return $this->render('front/index.html.twig', [
			'authors' => $this->authors,
			// 'authors' => $paginator->paginate(
			// 			$this->ar->findByLastNameQuery(),
			// 			$request->query->getInt('page', 1),
			// 			3
			// ),
			'books'		=> $bookList,
			'nbAuthors'	=> $this->nbAuthors,
			'nbBooks'	=> $this->nbBooks,
			'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
			'bookSelectForm'		=> $bookSelectForm->createView(),
			'isSelectedList'		=> $currentBookSelectionIds,
        ]);
    }

    /**
     * @Route("/about", name="about")
	 * @return Response
     */
	
	public function about(Request $request): Response
	{
		return $this->render('front/about.html.twig');
	}

	/**
	 * @Route("/resetSelection", name="reset_selection")
	 */
	public function resetBookList(Request $request): Response
	{
		$session = $request->getSession();
		$session->set('currentBookSelectionIds', NULL);

		return $this->redirectToRoute('front');
	}
}
