<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin")
 */

class AdminBookController extends AbstractController
{
    /**
     * @Route("/book/{sortBy}", name="admin_book_index")
     */
    public function index($sortBy = 'Title', AuthorRepository $authorRepo, BookRepository $bookRepo): Response
    {
        switch($sortBy){
            case 'Id':
                $books = $bookRepo->findAll();
                break;

            case 'Title':
                $books = $bookRepo->findByTitle();
                break;
                
            case 'NbParagraphs':
                $books = $bookRepo->findByNbParagraphs();
                break;
                
            case 'ParsingTime':
                $books = $bookRepo->findByParsingTime();
                break;
                
            case 'Author':
                $authors = $authorRepo->findAll();
                $books = [];
                foreach( $authors as $author){
                    $books[] = $author->getBooks();
                }

                break;
                
            // default:
            //     $books = $repo->findByTitle();
            //     break;
    
        }

        return $this->render('admin/book/index.html.twig', [
            'books' => $books,
        ]);
    }


    /**
     * @Route("/{slug}", name="admin_book_delete", methods={"DELETE"})
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

			// $this->logger->info('Remove odt file : books/' . $dirName . ' (with title : ' . $book->getTitle() . ')' );

			// remove .whatever to get directory name << buggy !-(
			$dirName = substr($dirName, 0, strpos($dirName, '.'));
			// then delete associated directory recursive
			passthru('rm -v -r books/' . $dirName . ' >>books/sorties_console 2>&1', $errCode );

			//
			//
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_book_index');
	}


    /**
     * @Route("/erase-log", name="admin_erase_log")
     */
    public function eraseLog(Request $request): Response
    {

			//
			// unix cmd
			// remove log file bibnphi.log
			passthru('rm -v bibnphi.log >>books/sorties_console 2>&1', $errCode );
            passthru('touch bibnphi.log');

        return $this->redirectToRoute('admin_book_index');
	}


}
