<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorType;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/author")
 */
class AuthorController extends AbstractController
{

    private $em; // Entity manager


    /**
     * 
     */
    public function __construct( EntityManagerInterface $em ){

        $this->em = $em;

		// $this->authors = $this->ar->findByLastName();
		// $this->nbAuthors = count($this->authors);
    }	

    /**
     * @Route("/", name="author_index", methods={"GET"})
     */
    public function index(AuthorRepository $authorRepository): Response
    {
        return $this->render('author/index.html.twig', [
            'authors' => $authorRepository->findByLastName(),
        ]);
    }

    /**
     * @Route("/new", name="author_new", methods={"GET","POST"})
	 * @IsGranted("ROLE_USER")
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
	 * @IsGranted("ROLE_USER")
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
	 * @IsGranted("ROLE_USER")
     */
    public function delete(Request $request, Author $author): Response
    {
        if ($this->isCsrfTokenValid('delete'.$author->getId(), $request->request->get('_token'))) {

            $this->em->remove($author);
            $this->em->flush();
        }

        return $this->redirectToRoute('author_index');
    }
}
