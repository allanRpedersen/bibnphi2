<?php

namespace App\Controller;

use App\Entity\BookParagraph;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookParagraphRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/paragraph")
 */
class ParagraphController extends AbstractController
{
    public function __construct(EntityManagerInterface $em, BookParagraphRepository $pRepo){
        
        $this->em = $em;
        $this->pRepo = $pRepo;
    }

    /**
     * @Route("/", name="paragraph")
     */
    public function index()
    {
        return $this->render('paragraph/index.html.twig', [
            'controller_name' => 'ParagraphController',
        ]);
	}
	
    /**
     * @Route("/{id}", name="paragraph_edit")
     */
	public function paragraph_edit(Request $request, BookParagraph $paragraph){


        $notes = $paragraph->getNotes();


        dd($paragraph, $notes);

        
    }
}
