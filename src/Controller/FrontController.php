<?php

namespace App\Controller;

use App\Service\SortMgr;
use App\Entity\BookSelect;
use App\Form\BookSelectType;
use App\Entity\SentenceSearch;
use App\Form\SentenceSearchType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Repository\BookNoteRepository;
use App\Repository\BookParagraphRepository;
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
	private $ar, $br, $nr, $pr;

	private $authors;
	private $books;
	private $nbAuthors, $nbBooks;

	public function __construct( AuthorRepository $ar, BookRepository $br, BookParagraphRepository $pr, BookNoteRepository $nr )
	{

		$this->ar = $ar;
		$this->authors = $this->ar->findByLastName();
		$this->nbAuthors = count($this->authors);
		
		$this->br = $br;
		$this->books = $this->br->findAll();
		$this->nbBooks = count($this->books);

		$this->pr = $pr;
		$this->nr = $nr;

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
		$authorSelected = false;

		$hlContent = [
			'bookId' => 0,
			'contentType' => 'p',
			'origId'	=> 0,
			'needles'	=> [],
		];

		$session = $request->getSession();
		$hlContents = $session->get('hlContents', []);
		$stringToSearch = $session->get('hlString', '');


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
				// dd($bookList);
			}

			$bookList = $sm->sortByAuthor($bookList);

			$bookSelectionIds = [];
			foreach( $bookList as $book ){
				$bookSelectionIds[] = $book->getId();
			}

			$session->set('currentBookSelectionIds', $bookSelectionIds);
			$session->set('openBookId', NULL);
		}
		
		//
		//
		$currentBookSelectionIds = $session->get('currentBookSelectionIds');
		if ($currentBookSelectionIds){

			$bookList = [];
			foreach($currentBookSelectionIds as $id){
				//
				// 
				if ($book = $this->br->findOneById($id)) $bookList[] = $book;
			}
			$bookList = $sm->sortByAuthor($bookList);

		}
		else $bookList = $sm->sortByAuthor($this->books);

		
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

				$nbFoundStringsOrig = $nbFoundStrings;

				$paragraphs = $book->getBookParagraphs();
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

				$notes = $book->getBookNotes();
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

				$book->setNbFoundStrings($nbFoundStrings - $nbFoundStringsOrig);
			}

			if ($nbFoundStrings){
				$session->set('hlString', $stringToSearch);
				$session->set('hlContents', $hlContents);
			}

			$scrollTo = null;
			$openBook = $matchingBookList ? $matchingBookList[0] : null ;

			if ($hlContents){

				$openBookHlContents = [];
				foreach( $hlContents as $hlContent )
					{ if($hlContent['bookId'] == $openBook->getId()) $openBookHlContents[] = $hlContent; }

				$navLinks = [];
				$s = sizeof($openBookHlContents);
				for($i=0; $i < $s; $i++){
					if($i < $s-1){
						$navLinks[] = ( 'p' == $openBookHlContents[$i+1]['contentType'] ? '_' : 'note_' ) . $openBookHlContents[$i+1]['origId'];
					}
					else {
						$navLinks[] = ( 'p' == $openBookHlContents[0]['contentType'] ? '_' : 'note_' ) . $openBookHlContents[0]['origId'];
					}
				}
				if ($navLinks) $scrollTo = $navLinks[sizeof($navLinks)-1];
			
				foreach( $openBookHlContents as $key => $hlContent ){

					switch ($hlContent['contentType']){
				
						case 'p' : // paragraph
							$paragraph = $this->pr->findOneById($hlContent['origId']);
							$paragraph
								->setFoundStringIndexes($hlContent['needles'])
								->setSearchedString($stringToSearch)
								->setNextOccurence($navLinks[$key]);
								;
						break;
		
						case 'n' : // note
							$note = $this->nr->findOneById($hlContent['origId']);
							$note
								->setFoundStringIndexes($hlContent['needles'])
								->setSearchedString($stringToSearch)
								->setNextOccurence($navLinks[$key])
								;
						break;
		
						default :
							//error
					}
				}
			}


			return $this->render('front/search_result.html.twig', [
				'form'				=> $sentenceSearchForm->createView(),
				'string'			=> $stringToSearch,
				// 'bookList'			=> $bookList,
				'matchingBookList'	=> $matchingBookList,
				// 'paragraphs'		=> $matchingParagraphs,
				// 'notes'				=> $matchingNotes,
				'nbFoundStrings'	=> $nbFoundStrings,
				'openBook'			=> $openBook,
				'scrollTo'			=> $scrollTo,
			]);
		}

		if (( $currentBookSelectionIds ) && $bookList){

			$scrollTo = null;
			$openBookId = $session->get('openBookId');
			$openBook = $openBookId ? $this->br->findOneById($openBookId) : $bookList[0]; //

			return $this->render('front/selected_index.html.twig', [
				'books'					=> $bookList,
				'openBook'				=> $openBook,
				'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
				'bookSelectForm'		=> $bookSelectForm->createView(),
				'isSelectedList'		=> $currentBookSelectionIds,
				'scrollTo'				=> $scrollTo,
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
		$session->set('openBookId', NULL);

		return $this->redirectToRoute('front');
	}

	/**
	 * @Route("/showSelected/{id}", name="show_selected")
	 */
	public function showSelected(Request $request, $id): Response
	{
		$request->getSession()->set('openBookId', $id);
		return $this->redirectToRoute('front');
	}

}
