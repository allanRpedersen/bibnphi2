<?php

namespace App\Controller;

use Monolog\Logger;
use App\Entity\Author;
use App\Form\AuthorType;
use App\Entity\BookSelect;
use App\Form\BookSelectType;
use App\Service\SelectAndSearch;
use Monolog\Handler\StreamHandler;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/author")
 */
class AuthorController extends AbstractController
{

    private $em; // Entity manager
    private $ar; // Author repository

    private $logger;
    private $projectDir;

    /**
     * 
     */
    public function __construct(KernelInterface $kernel, AuthorRepository $ar, EntityManagerInterface $em ){

        $this->em = $em;
        $this->ar = $ar;

        $this->projectDir = $kernel->getProjectDir();
		$this->logger = new Logger('bibnphi');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );
    }	

    /**
     * @Route("/", name="author_index", methods={"GET"})
     */
    public function index(Request $request, SelectAndSearch $sas): Response
    {
		// the Book selection form
		$bookSelect = new BookSelect();
		$bookSelectForm = $this->createForm(BookSelectType::class, $bookSelect);
		$bookSelectForm->handleRequest($request);
		//
		if ($bookSelectForm->isSubmitted() && $bookSelectForm->isValid())
		{
			// set currentBookSelectionIds in the session
			$sas->SelectBooks($bookSelect);
			return $this->redirectToRoute('front');
		}



        return $this->render('author/index.html.twig', [
            'authors' => $this->ar->findByLastName(),
            // 'sentenceSearchForm'	=> $sentenceSearchForm->createView(),
            'bookSelectForm'		=> $bookSelectForm->createView(),
            // 'hideContact'           => true,


        ]);
    }

    /**
     * @Route("/new", name="author_new", methods={"GET","POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
	 */
    public function new(Request $request): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->em->persist($author);
            $this->em->flush();

            return $this->redirectToRoute('author_show', ['slug' => $author->getSlug()]);
        }

        return $this->render('author/new.html.twig', [
            'author' => $author,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", name="author_show", methods={"GET"})
     */
    public function show(Author $author): Response
    {
        return $this->render('author/show.html.twig', [
            'author' => $author,
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="author_edit", methods={"GET","POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
     */
    public function edit(Request $request, Author $author): Response
    {
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('author_show', ['slug' => $author->getSlug()]);
        }

        return $this->render('author/edit.html.twig', [
            'author' => $author,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{slug}", name="author_delete", methods={"DELETE", "POST"})
	 * @IsGranted("ROLE_LIBRARIAN")
     */
    public function delete(Request $request, Author $author): Response
    {
        if ($this->isCsrfTokenValid('delete'.$author->getId(), $request->request->get('_token'))) {

            // remove books

            $this->logger->info("Suppression de l'auteur : " . $author->getLastName() );

            $this->em->remove($author);
            $this->em->flush();


        }

        return $this->redirectToRoute('author_index');
    }
}
