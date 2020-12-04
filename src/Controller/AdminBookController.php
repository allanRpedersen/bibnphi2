<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/admin")
 */

class AdminBookController extends AbstractController
{
    /**
     * @Route("/book", name="admin_book_index")
     */
    public function index(BookRepository $repo): Response
    {
        return $this->render('admin/book/index.html.twig', [
            'books' => $repo->findAll(),
        ]);
    }
}
