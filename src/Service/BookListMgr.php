<?php
namespace App\Service;

use App\Entity\BookSelect;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;

class BookListMgr
{
    private $bookList = [];
    private $br; // Book Repository
    private $sm; // Sort Manager

    public function __construct(BookRepository $br)
    {
        // $this->bookList = new ArrayCollection;
        $this->br = $br;
        $this->sm = new SortMgr();
    }

    public function SetList(BookSelect $bookSelect){

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

        $this->bookList = $this->sm->sortByAuthor($bookList);

    }

    public function GetList()
    {
        return $this->bookList;
    }
}