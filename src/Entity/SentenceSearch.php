<?php

namespace App\Entity;

use App\Entity\Book;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

class SentenceSearch
{
	/**
     * @Assert\NotBlank
     * @Assert\Length(
     *      min = 3,
     *      max = 150,
     *      minMessage = "au minimum {{ limit }} caractères",
     *      maxMessage = "au plus {{ limit }} caractères" )
     *
     * 
	 * La chaîne à rechercher, forcément non nulle
	 * 
	 * 		un mot
	 * 		une chaine de mots sans ponctuation ( ET )( séparateur caractère espace )
     * 
     *      vvvv ???
	 * 		une chaine de mots avec ponctuation [.!?] ( OU )( le premier caractère non alphabétique sert de séparateur )
	 *
	 * @var string
	 */
	private $stringToSearch;

	/**
	 * Les oeuvres dans lesquelles rechercher
	 *
	 * @var Collection | null
	 */
	private $books;

	/**
	 * Les auteurs chez qui rechercher
	 *
	 * @var Collection | null
	 */
	private $authors;
	
	public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->authors = new ArrayCollection();
    }


	public function getStringToSearch(): ?string
	{
		return $this->stringToSearch;
	}

	public function setStringToSearch( $stringToSearch ): self
	{
		$this->stringToSearch = $stringToSearch;
		
		return $this;
	}


    /**
     * @return Collection|Book[]
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
            // $book->setBook($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->contains($book)) {
            $this->books->removeElement($book);
            // set the owning side to null (unless already changed)
            // if ($book->getBook() === $this) {
            //     $book->setBook(null);
            // }
        }

        return $this;
    }


    /**
     * @return Collection|author[]
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(author $author): self
    {
        if (!$this->authors->contains($author)) {
            $this->authors[] = $author;
            // $author->setAuthor($this);
        }

        return $this;
    }

    public function removeAuthor(author $author): self
    {
        if ($this->authors->contains($author)) {
            $this->authors->removeElement($author);
            // set the owning side to null (unless already changed)
            // if ($author->getAuthor() === $this) {
            //     $author->setAuthor(null);
            // }
        }

        return $this;
    }


}
