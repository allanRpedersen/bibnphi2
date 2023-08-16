<?php

namespace App\Entity;

use App\Entity\Author;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
// use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;


/**
 * @ORM\Entity(repositoryClass=BookRepository::class)
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 * @ApiResource()
 */
class Book
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
	private $title;
	
	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
	private $slug;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $summary;

    /**
     * @ORM\ManyToOne(targetEntity=Author::class, inversedBy="books")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\Column(type="string", length=17, nullable=true)
     */
    private $publishedYear;

	    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
	 * @Assert\File(
     *     mimeTypes = {"application/vnd.oasis.opendocument.text"},
     *     mimeTypesMessage = "Veuillez indiquer un document au format ODT !"
     * )
     * @Vich\UploadableField(mapping="books",
	 * 						fileNameProperty="odtBookName",
	 * 						size="odtBookSize",
	 * 						mimeType="bookMimeType",
     *                      originalName="odtOriginalName")
     * 
     * @var File|null
     */
    private $odtBookFile;

    /**
     * @ORM\Column(type="string")
     *
     * @var string|null
     */
    private $odtBookName;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $odtBookSize;

	/**
	 * Undocumented variable
	 *
	 */
	private $bookMimeType;

	/**
	 * Undocumented variable
	 *
	 */
	private $odtOriginalName;

    /**
     * @ORM\OneToMany(targetEntity=BookParagraph::class, mappedBy="book", orphanRemoval=true)
     */
    private $bookParagraphs;

    /**
     * @ORM\Column(type="integer")
     */
    private $nbParagraphs;

    /**
     * @ORM\Column(type="integer")
     */
    private $xmlFileSize;

    /**
     * @ORM\Column(type="float")
     */
    private $parsingTime;

    /**
     * @ORM\OneToMany(targetEntity=BookNote::class, mappedBy="book", orphanRemoval=true)
     */
    private $bookNotes;

    /**
     * F R O N T P A G E aka fp
     */

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[ORM\Column(type: 'string', length: '255', nullable: true)]
    private ?string $fpImageFileName = null;

    private $fpImageFileSize;
    private $fpMimeType;
    private $fpOriginalName;
    private $fpImageDimensions;

    
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
     * @Vich\UploadableField(mapping="book_fp",
	 * 						fileNameProperty="fpImageFileName",
	 * 						size="fpImageFileSize",
	 * 						mimeType="fpMimeType",
     *                      originalName="fpOriginalName",
     *                      dimensions="fpImageDimensions")
     * 
     * @var File|null
     */
    private ?File $fpImageFile = null;

    /**
     * Nombre d'occurrences dans le livre
     * 
     * dans le cas de la recherche d'une chaîne de caractères
     */
    private int $nbFoundStrings = 0;

    /**
     * @ORM\OneToMany(targetEntity=Bookmark::class, mappedBy="book", orphanRemoval=true)
     */
    private $bookmarks;

    public function __construct()
    {
        $this->bookParagraphs = new ArrayCollection();
        $this->bookNotes = new ArrayCollection();
        $this->bookmarks = new ArrayCollection();
    }
	//
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
        // if ( empty($this->slug) ){}
    
        // le slug est systèmatiquement recalculé ..
        $slugify = new Slugify();
        $this->slug = $slugify->slugify($this->author->getlastName() . '-' . $this->title);
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

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


    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getPublishedYear(): ?string
    {
        return $this->publishedYear;
    }

    public function setPublishedYear(?string $publishedYear): self
    {
        $this->publishedYear = $publishedYear;

        return $this;
    }

	/**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile | File | null $odtBookFile
     */
    public function setOdtBookFile(?File $odtBookFile = null): self
    {
        $this->odtBookFile = $odtBookFile;

        if (null !== $odtBookFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    
    public function getOdtBookFile(): ?File
    {
        return $this->odtBookFile;
    }

    public function setOdtBookName(?string $odtBookName): self
    {
        $this->odtBookName = $odtBookName;
        return $this;
    }

    public function getOdtBookName(): ?string
    {
        return $this->odtBookName;
    }
    
    public function setOdtBookSize(?int $odtBookSize): self
    {
        $this->odtBookSize = $odtBookSize;
        return $this;
    }

    public function getOdtBookSize(): ?int
    {
        return $this->odtBookSize;
    }

	public function setBookMimeType(?string $bookMimeType): self
    {
        $this->bookMimeType = $bookMimeType;
        return $this;
    }

    public function getBookMimeType(): ?string
    {
        return $this->bookMimeType;
    }

	public function setOdtOriginalName(?string $odtOriginalName): self
    {
        $this->odtOriginalName = $odtOriginalName;
        return $this;
    }

    public function getOdtOriginalName(): ?string
    {
        return $this->odtOriginalName;
    }

    /**
     * @return Collection|BookParagraph[]
     */
    public function getBookParagraphs(): Collection
    {
        return $this->bookParagraphs;
    }

    public function addBookParagraph(BookParagraph $bookParagraph): self
    {
        if (!$this->bookParagraphs->contains($bookParagraph)) {
            $this->bookParagraphs[] = $bookParagraph;
            $bookParagraph->setBook($this);
        }

        return $this;
    }

    public function removeBookParagraph(BookParagraph $bookParagraph): self
    {

        if ($this->bookParagraphs->contains($bookParagraph)) {
            $this->bookParagraphs->removeElement($bookParagraph);
            // set the owning side to null (unless already changed)
            if ($bookParagraph->getBook() === $this) {
                $bookParagraph->setBook(null);
            }
        }

        return $this;
    }

    public function getNbParagraphs(): ?int
    {
        return $this->nbParagraphs;
    }

    public function setNbParagraphs(int $nbParagraphs): self
    {
        $this->nbParagraphs = $nbParagraphs;

        return $this;
    }

    public function getParsingTime(): ?float
    {
        return $this->parsingTime;
    }

    public function setParsingTime(float $parsingTime): self
    {
        $this->parsingTime = $parsingTime;

        return $this;
    }

    /**
     * @return Collection|BookNote[]
     */
    public function getBookNotes(): Collection
    {
        return $this->bookNotes;
    }

    public function addBookNote(BookNote $bookNote): self
    {
        if (!$this->bookNotes->contains($bookNote)) {
            $this->bookNotes[] = $bookNote;
            $bookNote->setBook($this);
        }

        return $this;
    }

    public function removeBookNote(BookNote $bookNote): self
    {
        if ($this->bookNotes->removeElement($bookNote)) {
            // set the owning side to null (unless already changed)
            if ($bookNote->getBook() === $this) {
                $bookNote->setBook(null);
            }
        }

        return $this;
    }


    /**
     * Get the value of xmlFileSize
     */ 
    public function getXmlFileSize(): int
    {
        return $this->xmlFileSize;
    }

    /**
     * Set the value of xmlFileSize
     *
     * @return  self
     */ 
    public function setXmlFileSize($xmlFileSize): self
    {
        $this->xmlFileSize = $xmlFileSize;

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
    public function setFpImageFile(?File $fpImageFile = null): void
    {
        $this->fpImageFile = $fpImageFile;

        if (null !== $fpImageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFpImageFile(): ?File
    {
        return $this->fpImageFile;
    }

    public function setFpImageFileName(?string $fpImageFileName): void
    {
        $this->fpImageFileName = $fpImageFileName;
    }

    public function getFpImageFileName(): ?string
    {
        return $this->fpImageFileName;
    }

    public function setFpImageFileSize(?int $fpImageFileSize): void
    {
        $this->fpImageFileSize = $fpImageFileSize;
    }

    public function getFpImageFileSize(): ?int
    {
        return $this->fpImageFileSize;
    }



    /**
     * Get the value of fpMimeType
     */ 
    public function getFpMimeType(): ?string
    {
        return $this->fpMimeType;
    }

    /**
     * Set the value of fpMimeType
     *
     * @return  self
     */ 
    public function setFpMimeType(?string $fpMimeType)
    {
        $this->fpMimeType = $fpMimeType;

        return $this;
    }

    /**
     * Get the value of fpOriginalName
     */ 
    public function getFpOriginalName(): ?string
    {
        return $this->fpOriginalName;
    }

    /**
     * Set the value of fpOriginalName
     *
     * @return  self
     */ 
    public function setFpOriginalName($fpOriginalName)
    {
        $this->fpOriginalName = $fpOriginalName;

        return $this;
    }

    /**
     * Get the value of fpImageDimensions
     */ 
    public function getFpImageDimensions()
    {
        return $this->fpImageDimensions;
    }

    /**
     * Set the value of fpImageDimensions
     *
     * @return  self
     */ 
    public function setFpImageDimensions($fpImageDimensions)
    {
        $this->fpImageDimensions = $fpImageDimensions;

        return $this;
    }



    /**
     * Get nombre d'occurrences dans le livre
     */ 
    public function getNbFoundStrings(): ?int
    {
        return $this->nbFoundStrings;
    }

    /**
     * Set nombre d'occurrences dans le livre
     *
     * @return  self
     */ 
    public function setNbFoundStrings($nbFoundStrings): self
    {
        $this->nbFoundStrings = $nbFoundStrings;

        return $this;
    }

    /**
     * @return Collection<int, Bookmark>
     */
    public function getBookmarks(): Collection
    {
        return $this->bookmarks;
    }

    public function addBookmark(Bookmark $bookmark): self
    {
        if (!$this->bookmarks->contains($bookmark)) {
            $this->bookmarks[] = $bookmark;
            $bookmark->setBook($this);
        }

        return $this;
    }

    public function removeBookmark(Bookmark $bookmark): self
    {
        if ($this->bookmarks->removeElement($bookmark)) {
            // set the owning side to null (unless already changed)
            if ($bookmark->getBook() === $this) {
                $bookmark->setBook(null);
            }
        }

        return $this;
    }


    /**
     * Get the value of updatedAt
     *
     * @return  \DateTimeInterface|null
     */ 
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the value of updatedAt
     *
     * @param  \DateTimeInterface|null  $updatedAt
     *
     * @return  self
     */ 
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
