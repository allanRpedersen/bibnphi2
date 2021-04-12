<?php

namespace App\Controller\API;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api/book")
 * 
 * 
 * 
 * 
 */
class BookController extends AbstractController {


    /**
     * @Route("/getParsingProgress", name="api_book_getparsingprogress", methods="GET")
     * 
     */
    public function getParsingProgress(Request $request): JsonResponse
    {

        // $percentProgress = file_get_contents('percentProgress');
        // $response=['parsingProgress' => $percentProgress];
        // return new JsonResponse($response);

        $parsingProgress = file_exists('percentProgress') ? file_get_contents('percentProgress') : "0%";
        
        return new JsonResponse([
            'parsingProgress' => $parsingProgress,
        ]);
    }
    
}