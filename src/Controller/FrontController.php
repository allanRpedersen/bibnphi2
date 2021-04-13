<?php

namespace App\Controller;

use App\Entity\SentenceSearch;
use App\Form\SentenceSearchType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class FrontController extends AbstractController
{

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
		
		//
		$matchingSentences = [];
		$matchingSentence = [
			'book' => NULL,
			'sentence' => NULL,
			'iNeedle' => 0,
		];

		$matchingParagraphs = [];
		

		//
		$nbBooksInLibrary = count($bookRepository->findAll());

		//
		// the search form
		$search = new SentenceSearch();
		$form = $this->createForm(SentenceSearchType::class, $search);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			// spinner ?

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
			
			// watabout a spinner (processing state) ??


			foreach($bookList as $book){

				$paragraphs = $book->getBookParagraphs();

				foreach($paragraphs as $paragraph){
					if ($paragraph->isMatchingParagraph($stringToSearch)){
						$matchingParagraphs[] = $paragraph;
					}
				}
			}



			return $this->render('front/search.html.twig', [
				'string' => $stringToSearch,
				'bookList' => $bookList,
				'paragraphs' => $matchingParagraphs,

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
