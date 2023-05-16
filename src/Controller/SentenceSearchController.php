<?php

namespace App\Controller;

use App\Repository\BookNoteRepository;
use App\Repository\BookParagraphRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SentenceSearchController extends AbstractController
{
    private $br, $nr, $pr;

    private $hlContent = [
        'bookId'				=> 0,
        'contentType'			=> 'p',
        'contentId'				=> 0,
        'nbOccurrencesInBook'	=> 0,
        'firstOccurrence'		=> 0,
        'needles'				=> [],
        ];



    public function __construct(BookRepository $br, BookNoteRepository $nr, BookParagraphRepository $pr)
    {
        $this->br = $br;
        $this->nr = $nr;
        $this->pr = $pr;
    }


    /**
     * @Route("/sentence/search", name="sentence_search_result")
     */
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $matchingBookList = [];

        $hlContents = $session->get('hlContents');
        $stringToSearch = $session->get('hlString');
        $nbFoundStrings = $session->get('nbFoundStrings');
        $nbFoundInBooks = $session->get('nbFoundInBooks');

        foreach($nbFoundInBooks as $key=>$val){
            $book = $this->br->findOneById($key);
            $book->setNbFoundStrings($val);
            $matchingBookList[] = $book;
        }

        $openBookId = $session->get('openBookId', NULL);
        $openBook = null;
        $scrollTo = null;

        if ($matchingBookList){
            $openBook = $openBookId ? $this->br->findOneById($openBookId) : $matchingBookList[0];
            $scrollTo = 'occurrence_1/' . $openBook->getNbFoundStrings();
        }
        foreach($hlContents as $hlContent){

            if ($openBook->getId() == $hlContent['bookId']){

                switch($hlContent['contentType']){
                    case 'p':
                        $p = $this->pr->findOneById($hlContent['contentId']);
                        $p->setFoundStringIndexes($hlContent['needles'])
                          ->setSearchedString($stringToSearch)
                          ->setFirstOccurrenceInParagraph($hlContent['firstOccurrence'])
                          ->setNbOccurrencesInBook($hlContent['nbOccurrencesInBook'])
                          ;
                    break;

                    case 'n':
                        $n = $this->nr->findOneById($hlContent['contentId']);
                        $n->setFoundStringIndexes($hlContent['needles'])
                          ->setSearchedString($stringToSearch)
                          ->setFirstOccurrenceInNote($hlContent['firstOccurrence'])
                          ->setNbOccurrencesInBook($hlContent['nbOccurrencesInBook'])
                          ;
                    break;
                }
            }
        }
        return $this->render('sentence_search/search_result.html.twig', [
            // 'sentenceSearchForm'=> $sentenceSearchForm->createView(),
            // 'bookSelectForm'	=> $bookSelectForm->createView(),
            'string'			=> $stringToSearch,
            'matchingBookList'	=> $matchingBookList,
            'nbFoundStrings'	=> $nbFoundStrings,
            'openBook'			=> $openBook,
            'scrollTo'			=> $scrollTo,
        ]);
    }
}
