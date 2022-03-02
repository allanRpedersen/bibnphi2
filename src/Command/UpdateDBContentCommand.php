<?php
namespace App\Command;

use App\Entity\Book;
use App\Service\XmlParser;
use App\Repository\BookRepository;

use App\Repository\BookNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BookParagraphRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateDBContentCommand extends Command
{
    protected static $defaultName = 'app:db-update';
    protected static $defaultDescription = 'Update Database';

    private $parser;
    private $book;

    private $br, $pr, $nr;

    private $em;
    private $params;

    public function __construct(
                            BookRepository $br,
                            BookParagraphRepository $pr,
                            BookNoteRepository $nr,
                            EntityManagerInterface $em,
                            ParameterBagInterface $params) 
    {
        parent::__construct();
        
        $this->br = $br;
        $this->pr = $pr;
        $this->nr = $nr;
        $this->em = $em;
        $this->params = $params;

    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            // ->addArgument('xmlFileName', InputArgument::REQUIRED, 'The name of the xml file to be parsed')
            // ->addArgument('bookId', InputArgument::REQUIRED, 'the id of the book')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        //$xmlFileName = $input->getArgument('xmlFileName');

        $io->note('Updating is running ... ');

        $notes = $this->nr->findAll();
        $count = 0;

        // foreach($notes as $note){
        //     $io->text('[_' . $note->getBookParagraph()->getId() . ']');
        //     $io->text($note->getBookParagraph()->getContent());
        // }

        // return Command::SUCCESS;


        foreach($notes as $key => $note){

            //
            //

            $paragraph = $this->pr->find($note->getBookParagraph());

            $content = $paragraph->getContent();
            $content = ltrim($content, "\n\t");


            $citation = $note->getCitation();
            $citationIndex = $note->getCitationIndex();
            
            $strToPut = '<sup id="citation_'
            . $citation
            . '"><a class="" href="#note_'
            . $note->getId()
            . '">'
            . $citation
            . '</a></sup>';
            

            $strToReach = '<sup id="citation_'
                        . $citation;

            $strToRemove = $strToReach
                        . '"><a class="" href="#note_'
                        . $note->getCitation()
                        . '">'
                        . $citation
                        . '</a></sup>'
                        ;
            
            $lengthToRemove = mb_strlen($strToRemove);

            $indexFound = mb_stripos($content, $strToReach);

            $newContent = mb_substr( $content, 0, $indexFound );
            $newContent .= $strToPut;
            $newContent .= mb_substr( $content, $indexFound + $lengthToRemove + 11 ); // <<<<< magic 11 !-)

            $io->text('<<< ' . $paragraph->getBook()->getTitle() . ' >>> ' . $paragraph->getId() );
            // $io->text($content);
            // $io->text($newContent);


            $paragraph->setContent($newContent);
            $this->em->persist($paragraph);

            if ( !($key%20) ){
                // every 20 paragraphs
                $this->em->flush();
            }
            // 

            // dd($content, $strToReach, $strToRemove, $newContent);



            // $indexesFound = [];
            // //
            // //
            // while (FALSE !== ($indexFound = mb_stripos($content, $strToRemove, $fromIndex))){

            //     $indexesFound[] = $indexFound;
            //     $fromIndex = $indexFound + $lengthToRemove;

            // }

            // $nbFound = count($indexesFound);

            // dd($content, $strToRemove, $indexesFound);

            //
        }

    // foreach($paragraphs as $paragraph)
    // {
    //     $io->text($paragraph->getContent());
    // }
    
    $this->em->flush(); // before leaving ..
    //
    return Command::SUCCESS;

    }

}
