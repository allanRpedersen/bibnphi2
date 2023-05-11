<?php

namespace App\Entity;

use App\Entity\Author;
use App\Entity\Book;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraints as Assert;

class BookSelect
{

	/**
	 * @var Collection | null
	 */
	private $authors;
    private $books;



	public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->books = new ArrayCollection();
    }

    /**
     * @return Collection|Author[]
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): self
    {
        if (!$this->authors->contains($author)) {
            $this->authors[] = $author;
        }
        return $this;
    }

    public function removeAuthor(Author $author): self
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

    /**
     * @return Collection|Books[]
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
            // $book->setBooks($this);
        }

        return $this;
    }

    public function removeBooks(Book $book): self
    {
        if ($this->books->contains($book)) {
            $this->books->removeElement($book);
            // set the owning side to null (unless already changed)
            // if ($book->getBooks() === $this) {
            //     $book->setBooks(null);
            // }
        }

        return $this;
    }
}