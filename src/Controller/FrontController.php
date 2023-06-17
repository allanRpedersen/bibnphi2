<?php

namespace App\Controller;

use App\Service\SortMgr;
use App\Entity\BookSelect;
use App\Form\BookSelectType;
use App\Entity\SentenceSearch;
use App\Service\SelectAndSearch;
use App\Form\SentenceSearchType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
// use App\Repository\BookNoteRepository;
// use App\Repository\BookParagraphRepository;
// use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * 
 */
class FrontController extends AbstractController
{
	private $ar, $br; // , $nr, $pr;

	private $authors, $books;
	private $nbAuthors, $nbBooks;

	public function __construct( AuthorRepository $ar, BookRepository $br )
	{
		$this->ar = $ar;
		$this->authors = $this->ar->findByLastName();
		$this->nbAuthors = count($this->authors);
		
		$this->br = $br;
		$this->books = $this->br->findAll();
		$this->nbBooks = count($this->books);

		// $this->pr = $pr;
		// $this->nr = $nr;

	}

    /**
     * @Route("/", name="front")
	 * @return Response
     */
    public function index(Request $request, SelectAndSearch $sas): Response // , PaginatorInterface $paginator)
    {
		// init
		//
		$sm = new SortMgr();
		$session = $request->getSession();
		// $bookList =[];
		// $authors = [];
		// $authorSelected = false;

		$hlContent = [
			'bookId'				=> 0,
			'contentType'			=> 'p',
			'contentId'				=> 0,
			'nbOccurrencesInBook'	=> 0,
			'firstOccurrence'		=> 0,
			'needles'				=> [],
		];

		//

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
			// set currentBookSelectionIds in the session
			$sas->SelectBooks($bookSelect);
		}
		//
		$currentBookSelectionIds = $session->get('currentBookSelectionIds', []);
		if ($currentBookSelectionIds){
			foreach($currentBookSelectionIds as $id){
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
		//
		if ($sentenceSearchForm->isSubmitted() && $sentenceSearchForm->isValid())
		{
			// If the string to search is found ..
			// then set nbFoundStrings, nbFoundInBooks, hlString, hlContents in the session
			$sas->SearchString($sentenceSearch, $bookList);
		}

		$hlContents = $session->get('hlContents', []);
		if ($hlContents) return $this->redirectToRoute('sentence_search_result');

		if (( $currentBookSelectionIds ) && $bookList){

			$openBookId = $session->get('openBookId');
			$openBook = $openBookId ? $this->br->findOneById($openBookId) : $bookList[0];

			return $this->render('front/selected_index.html.twig', [
				'books'					=> $bookList,
				'openBook'				=> $openBook,
				'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
				'bookSelectForm'		=> $bookSelectForm->createView(),
				'showCancelSelection'	=> true
			]);
		}

		//
		// $bookList = $sm->sortByAuthor($this->br->findByDate());
		$bookList = $this->br->findByDate();
        return $this->render('front/index.html.twig', [
			// 'authors' => $this->authors,
			// 'authors' => $paginator->paginate(
			// 			$this->ar->findByLastNameQuery(),
			// 			$request->query->getInt('page', 1),
			// 			3
			// ),
			'books'		=> $bookList,
			// 'books'		=> $this->br->findByDate(),
			'nbAuthors'	=> $this->nbAuthors,
			'nbBooks'	=> $this->nbBooks,
			'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
			'bookSelectForm'		=> $bookSelectForm->createView(),
			'isSelectedList'		=> $currentBookSelectionIds,
			// 'hideContact'			=> true,
        ]);
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
     * @Route("/about", name="about")
	 * @return Response
     */
	
	public function about(Request $request): Response
	{
		return $this->render('front/about.html.twig',[
			'hideAbout' => true,
		]);
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
