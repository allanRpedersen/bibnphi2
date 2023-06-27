<?php

namespace App\Controller;

use App\Repository\BookNoteRepository;
use App\Repository\BookParagraphRepository;
use App\Repository\BookRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

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
        $openBook = null;;

        $hlContents = $session->get('hlContents');
        $stringToSearch = $session->get('hlString');
        $nbFoundStrings = $session->get('nbFoundStrings');
        $nbFoundInBooks = $session->get('nbFoundInBooks');

        $matchingAuthors = [];
        $openBookId = $session->get('openBookId', null);

        foreach($nbFoundInBooks as $key => $val){

            $book = $this->br->findOneById($key);
            $book->setNbFoundStrings($val);
            $matchingBookList[] = $book;

            if ($book->getId() == $openBookId){ $openBook = $book; }

            $matchingAuthors[$book->getAuthor()->getLastName()][] = [
                'id'        => $key,
                'title'     => $book->getTitle(),
                'nbFound'   => $val
            ];
        }
        $scrollTo = null;
        if (!$openBook) {
            $openBook = $matchingBookList[0];
            $openBookId = $openBook->getId();
        }

        $scrollTo = 'occurrence_1/' . $openBook->getNbFoundStrings();
        // $firstAuthor = array_key_first($matchingAuthors);
        // dump($matchingAuthors, $firstAuthor);
        
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
            // 'matchingBookList'	=> $matchingBookList,
            'matchingAuthors'   => $matchingAuthors,
            'nbFoundStrings'	=> $nbFoundStrings,
            'openBook'			=> $openBook,
            'openBookId'        => $openBookId,
            'scrollTo'			=> $scrollTo,
            'hideAbout'         => true,
        ]);
    }
}
