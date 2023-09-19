<?php

namespace App\Controller;

use Monolog\Logger;
use App\Entity\Book;
use App\Repository\BookRepository;
use Monolog\Handler\StreamHandler;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin")
 */

class AdminBookController extends AbstractController
{
    private $em;
    private $projectDir;
    private $uploaderHelper;
    private $logger;
    
    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, UploaderHelper $uploaderHelper)
	{
		$this->projectDir = $kernel->getProjectDir();

		$this->em = $em;

		$this->uploaderHelper = $uploaderHelper;

		$this->logger = new Logger('bibnphi');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );


	}

    /**
     * @Route("/book/{sortBy}", name="admin_book_index")
     */
    public function index(AuthorRepository $authorRepo, BookRepository $bookRepo, $sortBy = 'Title'): Response
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
                
            case 'XmlFileSize':
                $books = $bookRepo->findByXmlFileSize();
                break;
                
            case 'Author':
                $authors = $authorRepo->findbyLastName();

                $books = [];
                foreach( $authors as $author){
                    foreach( $author->getBooks() as $book ) $books[] = $book;
                }

                break;
                
        }

        return $this->render('admin/book/index.html.twig', [
            'books' => $books,
        ]);
    }


    /**
     * @Route("/{slug}", name="admin_book_delete", methods={"DELETE", "POST"})
     */
    public function delete(Request $request, Book $book): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
			
			foreach( $book->getBookParagraphs() as $paragraph ){
				$book->removeBookParagraph($paragraph);
			}

			//
			// unix cmd
			// remove odt file
			$dirName = $book->getOdtBookName();
			passthru('rm -v books/'. $dirName . ' > /dev/null 2>&1', $errCode );

			// $this->logger->info('Remove odt file : books/' . $dirName . ' (with title : ' . $book->getTitle() . ')' );
			$this->logger->info('Remove odt file : books/' . $dirName . ' (with title : ' . $book->getTitle() . ')' );
			$this->logger->info('Was parsed in : ' . $book->getParsingTime() . 'sec.');


			// remove .whatever to get directory name << buggy !-(
			$dirName = substr($dirName, 0, strpos($dirName, '.'));
			// then delete associated directory recursive
			passthru('rm -v -r books/' . $dirName . ' > /dev/null 2>&1', $errCode );

			//
			//
            $this->em->remove($book);
            $this->em->flush();
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
