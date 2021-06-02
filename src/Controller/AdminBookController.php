<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
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
    public function index($sortBy = 'Title', BookRepository $repo): Response
    {
        switch($sortBy){
            case 'Id':
                $books = $repo->findAll();
                break;

            case 'Title':
                $books = $repo->findByTitle();
                break;
                
            case 'NbParagraphs':
                $books = $repo->findByNbParagraphs();
                break;
                
            case 'ParsingTime':
                $books = $repo->findByParsingTime();
                break;
                
            case 'Author':
                $books = $repo->findByAuthor();
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


}
