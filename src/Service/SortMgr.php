<?php
namespace App\Service;

use App\Entity\Book;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * 
 */
class SortMgr
{
	//
	//
	private $table = array(
        // 'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 
        // 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 
        'Ç'=>'C', 
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 
        // 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        // 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 
        // 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 
        'ç'=>'c', 
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 
        // 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
        // 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 
        'ù'=>'u', 'ú'=>'u', 'û'=>'u', 
        // 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
        // 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
    );

    /**
     * 
     * @param Collection|Book[] $originalList
     * @return Collection|Book[]
     */
    public function sortByTitle($originalList, $direction="ASC"): ArrayCollection
    {

        $sortedBooks = new ArrayCollection;
        $titles = [];

        // ascendant sort on title
        foreach ($originalList as $book){
            $title = $book->getTitle();
            $titles[] = strtr($title, $this->table);
        }

        asort($titles);

        foreach( $titles as $key => $val){
            $book = $originalList[$key];
            if (!$sortedBooks->contains($book)) $sortedBooks[] = $book;
        }

        return $sortedBooks;

    }

    /**
     * 
     * @param Collection|Book[] $originalList
     * @return Collection|Book[]
     */
    public function sortByAuthor($originalList, $direction="ASC"): Collection
    {
        $sortedByAuthor = New ArrayCollection;
        $sortedByTitle = $this->sortByTitle($originalList);
    
        $authors = [];
        $authorsNames = [];

        foreach( $sortedByTitle as $book ){

            $author = $book->getAuthor();
            $authors[] = $author;
            $authorsNames[] = strtr($author->getLastName(), $this->table);

        }

        asort($authorsNames);

        foreach( $authorsNames as $key => $val){
            $sortedByAuthor[] = $sortedByTitle[$key];
        }

        return $sortedByAuthor;
    }
}