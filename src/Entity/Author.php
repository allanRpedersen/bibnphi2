<?php

namespace App\Entity;

use App\Service\SortMgr;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthorRepository;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=AuthorRepository::class)
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
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
    #[ORM\Column(type: 'string', length: '255', nullable: true)]
    private ?string $pictureFileName = null;

    private $pictureFileSize;
    private $mimeType;
    private $originalName;
    private $pictureDimensions;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTimeInterface|null
     */
    private $updatedAt;


    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
	 * @Assert\Image(mimeTypes = {"image/jpeg", "image/gif", "image/png"},
     *               mimeTypesMessage = "Format d'image invalide (jpeg, gif, png)")
     * 
     * @Vich\UploadableField(mapping="author_images",
	 * 						fileNameProperty="pictureFileName",
	 * 						size="pictureFileSize",
	 * 						mimeType="mimeType",
     *                      originalName="originalName",
     *                      dimensions="pictureDimensions")
     * 
     * @var File|null
     */
    private ?File $pictureFile = null;


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
        $sm = new SortMgr;

        return $sm->sortByTitle($books);

        // $sortedBooks = new ArrayCollection;
        // $titles = [];


        // // ascendant sort on title
        // foreach ($books as $book){
        //     $title = $book->getTitle();
        //     $titles[] = strtr($title, $this->table);
        // }

        // if (sizeof($titles) > 1){

        //     asort($titles);

        //     foreach( $titles as $key => $val){
        //         $sortedBooks[] = $books[$key];
        //     }

        //     return $sortedBooks;

        // }
        
        // return $books;
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

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setPictureFile(?File $pictureFile = null): void
    {
        $this->pictureFile = $pictureFile;

        if (null !== $pictureFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPictureFile(): ?File
    {
        return $this->pictureFile;
    }

    public function setPictureFileName(?string $pictureFileName): void
    {
        $this->pictureFileName = $pictureFileName;
    }

    public function getPictureFileName(): ?string
    {
        return $this->pictureFileName;
    }

    public function setPictureFileSize(?int $pictureFileSize): void
    {
        $this->pictureFileSize = $pictureFileSize;
    }

    public function getPictureFileSize(): ?int
    {
        return $this->pictureFileSize;
    }



    /**
     * Get the value of mimeType
     */ 
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * Set the value of mimeType
     *
     * @return  self
     */ 
    public function setMimeType(?string $mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get the value of originalName
     */ 
    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    /**
     * Set the value of originalName
     *
     * @return  self
     */ 
    public function setOriginalName($originalName)
    {
        $this->originalName = $originalName;

        return $this;
    }

    /**
     * Get the value of pictureDimensions
     */ 
    public function getPictureDimensions()
    {
        return $this->pictureDimensions;
    }

    /**
     * Set the value of pictureDimensions
     *
     * @return  self
     */ 
    public function setPictureDimensions($pictureDimensions)
    {
        $this->pictureDimensions = $pictureDimensions;

        return $this;
    }
}
