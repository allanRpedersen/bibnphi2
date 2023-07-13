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

        foreach ($originalList as $book) $titles[] = strtr($book->getTitle(), $this->table);

        $direction == "ASC" ? asort($titles) : arsort($titles);
        
        foreach( $titles as $key => $val) $sortedBooks[] = $originalList[$key];
        
        return $sortedBooks;
    }

    /**
     * 
     * @param Collection|Book[] $originalList
     * @return Collection|Book[]
     */
    public function sortByAuthor($originalList, $direction="ASC"): Collection
    {
        $sortedByAuthor = new ArrayCollection();
        $books = [];
        foreach( $originalList as $book ){
            $books[]= [
                "author"    => strtr($book->getAuthor()->getLastName(), $this->table),
                "title"     => strtr($book->getTitle(), $this->table),
            ];
        }

        $direction == "ASC" ? asort($books) : arsort($books);

        foreach( $books as $key => $val){
            if (!$sortedByAuthor->contains($originalList[$key])) $sortedByAuthor[] = $originalList[$key];
        }

        return $sortedByAuthor;
    }

        /**
     * 
     * @param Collection|Book[] $originalList
     * @return Collection|Book[]
     */
    public function sortByPublicationDate($originalList, $direction="ASC"): Collection
    {
        $sortedBooks = new ArrayCollection();
        $books = [];

        $years = []; $titles = []; $clefs = [];

        foreach( $originalList as $key => $book ){
            $year = $book->getPublishedYear();


            // Le signe '-' marque une date av jc, on inverse le tri
            if (substr($year, 0, 1) == '-'){

                $direction = "DESC";

                if (preg_match('/[\d]+/', $year, $matchingArray)) $year = $matchingArray[0];
                // dump($matchingArray);

                $years[] = $year;
                $titles[] = $book->getTitle();
                $clefs[] = $key;

            }
            else
                $books[] = [
                    "year"  => $year,
                    "title" => $book->getTitle(),
                    "clef"  => $key,
                ];
        }

        if ($years && $titles && $clefs ){

            array_multisort( $years, SORT_DESC, $titles, SORT_ASC, $clefs );

            $books = [];
            foreach( $years as $key => $value){
                $books[] = [
                    "year"  => $years[$key],
                    "title" => $titles[$key],
                    "clef"  => $clefs[$key],
                ];
            }
        }
        else{
            $direction == "ASC" ? asort($books) : arsort($books);
        }

        foreach( $books as $book) $sortedBooks[] = $originalList[$book['clef']];
        return $sortedBooks;
    }
}