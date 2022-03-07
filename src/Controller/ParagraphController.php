<?php

namespace App\Controller;

use App\Entity\BookParagraph;
use App\Form\BookParagraphType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookParagraphRepository;
use App\Service\ContentMgr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/paragraph")
 * @IsGranted("ROLE_ADMIN")
 * 
 * 
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


        $form = $this->createForm(BookParagraphType::class, $paragraph);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $entityManager = $this->getDoctrine()->getManager();

            // remove all <p></p> surrounding
            // before persist()
            if ($newContent = mb_eregi_replace('<P>', '', $paragraph->getContent()))
            
                if ($newContent = mb_eregi_replace('</P>', '', $newContent))
                
                    $paragraph->setContent($newContent);



            //
            $this->em->persist($paragraph);
            $this->em->flush();

            return $this->redirectToRoute('book_show_with_jump',[
                'slug'        => $paragraph->getBook()->getSlug(),
                'whereToJump' => '_' . $paragraph->getId(),
            ]);
        }

        return $this->render('paragraph/edit.html.twig', [
            'paragraph' => $paragraph,
            'form' => $form->createView(),
        ]);



        
        // $notes = $paragraph->getNotes();

        // foreach ($notes as $note){
        //     dump($note);
        // }
        // dd($paragraph);

        
    }

    /**
     * @Route("/{id}", name="paragraph_delete", methods={"DELETE"})
     */
    public function delete(Request $request, $id): Response
    {
        $paragraph = $this->pRepo->findOneById($id);
        
        if ($this->isCsrfTokenValid('delete'.$paragraph->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($paragraph);
            $entityManager->flush();
        }

        return $this->redirectToRoute('paragraph_index');
    }

}
