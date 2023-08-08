<?php

namespace App\Service;

use App\Entity\Book;
use App\Service\SortMgr;
use App\Entity\BookSelect;
use App\Form\BookSelectType;
use App\Entity\SentenceSearch;

use App\Form\SentenceSearchType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use App\Repository\BookNoteRepository;

use App\Repository\BookParagraphRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Handle BookSelect form and SearchString form
 */
class SelectAndSearch extends AbstractController
{
    private $ar, $br, $nr, $pr;

	private $authors;
	private $books;
	private $nbAuthors, $nbBooks;

    private $session;

    private $hlContent = [
                    'bookId'				=> 0,
                    'contentType'			=> 'p',
                    'contentId'				=> 0,
                    'nbOccurrencesInBook'	=> 0,
                    'firstOccurrence'		=> 0,
                    'needles'				=> [],
                    ];



	public function __construct(    AuthorRepository $ar,
                                    BookRepository $br,
                                    BookParagraphRepository $pr,
                                    BookNoteRepository $nr,
                                    RequestStack $requestStack )
	{

		$this->ar = $ar;
		$this->authors = $this->ar->findByLastName();
		$this->nbAuthors = count($this->authors);
		
		$this->br = $br;
		$this->books = $this->br->findAll();
		$this->nbBooks = count($this->books);

		$this->pr = $pr;
		$this->nr = $nr;

        $this->session = $requestStack->getCurrentRequest()->getSession();

	}

    /**
     * Set currentBookSelectionIds in the session
     */
    public function SelectBooks(BookSelect $bookSelect)
    {
        $bookList = [];
        // $sm = new SortMgr();

        if (!$bookSelect->getAuthors()->isEmpty()){
            $authors = $bookSelect->getAuthors();
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

        $bookSelectionIds = [];
        foreach( $bookList as $book ){
            $bookSelectionIds[] = $book->getId();
        }

        $this->session->set('currentBookSelectionIds', $bookSelectionIds);
        $this->session->set('openBookId', NULL);

    }

    /**
     * 
     */
    public function SearchString(SentenceSearch $sentenceSearch, $bookList)
    {
        $stringToSearch = $sentenceSearch->getStringToSearch();
        $this->session->set('hlString', $stringToSearch);

        $matchingBookList = [];
        $hlContents = [];
        $nbFoundInBooks = [];
        $nbFoundStrings = 0;

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
                $nbFoundInBooks[$book->getId()] = $nbOccurrencesInBook;
            }


        }

        // 

        $this->session->set('nbFoundStrings', $nbFoundStrings);
        $this->session->set('nbFoundInBooks', $nbFoundInBooks);

        if ($nbFoundStrings){
            $this->session->set('hlContents', $hlContents);
        }
        else {
            $this->session->set('hlContents', []);
        }

    }

}
