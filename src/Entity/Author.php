<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Author
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
	private $slug;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     */
    private $birthYear;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     */
    private $deathYear;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $summary;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\OneToMany(targetEntity=Book::class, mappedBy="author", orphanRemoval=true)
     */
    private $books;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $picture;

	//
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
   


    //
    //
    
	/**
	 * Initialisation du slug avant le persist ..
	 * 
	 * @ORM\PrePersist
	 * @ORM\PreUpdate
	 *
	 * @return void
	 */
	public function InitializeSlug()
    {
        // if ( empty($this->slug) ){
                    
            // le slug est systèmatiquement recalculé ..

            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->firstName . '-' . $this->lastName );

            // }
    }

    public function __construct()
    {
        $this->books = new ArrayCollection();

    }



	//
	// Getters / Setters 
	//

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

	public function getSlug(): ?string
                      {
                          return $this->slug;
                      }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }


    public function getBirthYear(): ?string
    {
        return $this->birthYear;
    }

    public function setBirthYear(?string $birthYear): self
    {
        $this->birthYear = $birthYear;

        return $this;
    }

    public function getDeathYear(): ?string
    {
        return $this->deathYear;
    }

    public function setDeathYear(?string $deathYear): self
    {
        $this->deathYear = $deathYear;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }


    /**
     * @return Collection|Book[]
     */
    public function getBooks(): Collection
    {
        $books = $this->books;

        $sortedBooks = new ArrayCollection;
        $titles = [];


        // ascendant sort on title
        foreach ($books as $book){
            $title = $book->getTitle();
            $titles[] = strtr($title, $this->table);
        }

        if (sizeof($titles) > 1){

            asort($titles);

            foreach( $titles as $key => $val){
                $sortedBooks[] = $books[$key];
            }

            return $sortedBooks;

        }
        
        return $books;
    }

    public function addBook(Book $book): self
    {
        if (!$this->books->contains($book)) {
            $this->books[] = $book;
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): self
    {
        if ($this->books->contains($book)) {
            $this->books->removeElement($book);
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }


}
