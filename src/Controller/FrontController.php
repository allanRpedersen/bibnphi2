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
			'bookId'				=> 0,
			'contentType'			=> 'p',
			'contentId'				=> 0,
			'nbOccurrencesInBook'	=> 0,
			'firstOccurrence'		=> 0,
			'needles'				=> [],
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
		// the Book selection form
		$bookSelect = new BookSelect();
		$bookSelectForm = $this->createForm(BookSelectType::class, $bookSelect);
		$bookSelectForm->handleRequest($request);
		//
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

			if (!$bookList && !$authorSelected){ $bookList = $this->books; }
			$bookList = $sm->sortByAuthor($bookList);

			$bookSelectionIds = [];
			foreach( $bookList as $book ){
				$bookSelectionIds[] = $book->getId();
			}

			$session->set('currentBookSelectionIds', $bookSelectionIds);
			$session->set('openBookId', NULL);
		}
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

			$matchingBookList = [];
			$hlContents = [];

			$nbFoundInBooks = [];

			$nbFoundStrings = 0;

			// Word/Sentence search process
			//
			$bookList = $sm->sortByAuthor($bookList);
			foreach($bookList as $book){

				$matchingParagraphs = [];
				$matchingNotes = [];
				$nbFoundStringsOrig = $nbFoundStrings;

				$paragraphs = $book->getBookParagraphs();
				foreach($paragraphs as $paragraph){

					if ( $paragraph->isContentMatching($stringToSearch)){

						$matchingParagraphs[] = $paragraph;
						$nbFoundStrings += count($paragraph->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$hlContent = [
							'bookId' 		=> $book->getId(),
							'contentType'	=> 'p',
							'contentId'		=> $paragraph->getId(),
							'needles'		=> $paragraph->getFoundStringIndexes(),
						];
						
						$hlContents[] = $hlContent;
					}
				}

				$notes = $book->getBookNotes();
				foreach($notes as $note){

					if ( $note->isContentMatching($stringToSearch)){

						$matchingNotes[] = $note;
						$nbFoundStrings += count($note->getFoundStringIndexes());

						if ( !in_array( $book, $matchingBookList ) ) $matchingBookList[] = $book;

						$hlContent = [
							'bookId' 		=> $book->getId(),
							'contentType' 	=> 'n',
							'contentId'		=> $note->getId(),
							'needles'		=> $note->getFoundStringIndexes(),
						];
						
						$hlContents[] = $hlContent;
					}
				}

				$nbOccurrencesInBook = $nbFoundStrings - $nbFoundStringsOrig;
				$currentOccurrenceInBook = 1;

				if ($nbOccurrencesInBook){
					for ($i=0; $i<count($hlContents); $i++){
						if($book->getId() == $hlContents[$i]['bookId']){
							$hlContents[$i]['nbOccurrencesInBook'] = $nbOccurrencesInBook;
							$hlContents[$i]['firstOccurrence'] = $currentOccurrenceInBook;
							$currentOccurrenceInBook += count($hlContents[$i]['needles']);
						}
					}
				}
				$nbFoundInBooks[$book->getId()] = $nbOccurrencesInBook;
			}

			$session->set('nbFoundStrings', $nbFoundStrings);
			$session->set('nbFoundInBooks', $nbFoundInBooks);

			if ($nbFoundStrings){
				$session->set('hlString', $stringToSearch);
				$session->set('hlContents', $hlContents);
			}
		}
			
		if ($hlContents || $stringToSearch != ''){

			$matchingBookIds = [];
			$matchingBookList = [];
			$nbFoundStrings = $session->get('nbFoundStrings');
			$nbFoundInBooks = $session->get('nbFoundInBooks');

			dump($hlContents);
			foreach($hlContents as $hlContent) $matchingBookIds[] = $hlContent['bookId'];

			$matchingBookIds = array_values(array_unique($matchingBookIds));
			$n = count($matchingBookIds);
			for ($i=0; $i<$n;$i++){
				$book = $this->br->findOneById($matchingBookIds[$i]);
				$book->setNbFoundStrings($nbFoundInBooks[$book->getId()]);
				$matchingBookList[] = $book;
			}

			$openBookId = $session->get('openBookId');
			$openBook = null;
			$scrollTo = null;

			if ($matchingBookList){
				$openBook = $openBookId ? $this->br->findOneById($openBookId) : $matchingBookList[0];
				$scrollTo = 'occurrence_1/' . $openBook->getNbFoundStrings();
			}
			foreach($hlContents as $hlContent){

				if ($openBook->getId() == $hlContent['bookId']){

					switch($hlContent['contentType']){
						case 'p':
							$p = $this->pr->findOneById($hlContent['contentId']);
							$p->setFoundStringIndexes($hlContent['needles'])
							  ->setSearchedString($stringToSearch)
							  ->setFirstOccurrenceInParagraph($hlContent['firstOccurrence'])
							  ->setNbOccurrencesInBook($hlContent['nbOccurrencesInBook'])
							  ;
						break;

						case 'n':
							$n = $this->nr->findOneById($hlContent['contentId']);
							$n->setFoundStringIndexes($hlContent['needles'])
							  ->setSearchedString($stringToSearch)
							  ->setFirstOccurrenceInNote($hlContent['firstOccurrence'])
							  ->setNbOccurrencesInBook($hlContent['nbOccurrencesInBook'])
							  ;
						break;
					}
				}
			}

			return $this->render('front/search_result.html.twig', [
				'sentenceSearchForm'=> $sentenceSearchForm->createView(),
				'string'			=> $stringToSearch,
				'matchingBookList'	=> $matchingBookList,
				'nbFoundStrings'	=> $nbFoundStrings,
				'openBook'			=> $openBook,
				'scrollTo'			=> $scrollTo,
			]);

			
		}

		if (( $currentBookSelectionIds ) && $bookList){

			$openBookId = $session->get('openBookId');
			$openBook = $openBookId ? $this->br->findOneById($openBookId) : $bookList[0];

			return $this->render('front/selected_index.html.twig', [
				'books'					=> $bookList,
				'openBook'				=> $openBook,
				'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
				'bookSelectForm'		=> $bookSelectForm->createView(),
				// 'isSelectedList'		=> $currentBookSelectionIds,
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
		$session->set('hlContents', []);
		$session->set('hlString', '');

		$session->set('nbFoundStrings', 0);
		$session->set('nbFoundInBooks', 0);


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

	/**
	 * @Route("/resetSearch", name="reset_search")
	 */
	public function resetSearch(Request $request): Response
	{
		$session = $request->getSession();

		$session->set('hlContents', []);
		$session->set('hlString', '');
		$session->set('nbFoundStrings', 0);
		$session->set('nbFoundInBooks', 0);

		return $this->redirectToRoute('front');
	}

}
