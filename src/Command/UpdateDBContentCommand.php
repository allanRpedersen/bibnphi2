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
        $count = count($notes);

        $processAll = false;
        

        // foreach($notes as $note){
        //     $io->text('[_' . $note->getBookParagraph()->getId() . ']');
        //     $io->text($note->getBookParagraph()->getContent());
        // }

        // return Command::SUCCESS;


        //
        // Correction / Ajout des notes dans l'objet BookParagraph concerné

        // Suppression du formatage relatif aux notes inscrits "en dur" dans les paragraphes
        //
        foreach($notes as $key => $note){


            // $io->text($note->getContent());
            $io->text('=====' . $note->getId() . '======');

            //
            //

            $paragraph = $this->pr->findOneById($note->getBookParagraph()->getId());
            //$paragraph->addNote($note);

            $content = $paragraph->getContent(true); // raw .. without formatting
            
            $citation = $note->getCitation();

            $strToReach = '<sup id="citation_'
                        . $citation
                        . '"><a class="" href="#note_'
                        ;

            $strToRemove = $strToReach
                        . $note->getId()
                        . '">'
                        . $citation
                        . '</a></sup>'
                        ;
 
            $lengthToRemove = mb_strlen($strToRemove);


            // if (!$processAll){
            //     switch($io->choice('validate this content ? ',['yes','no','all'],'yes')){
            //         case 'all' :
            //             $processAll = true;
            //             $io->text('all');

            //         case 'yes' :
            //             // $paragraph->setContent($newContent);
            //             // $this->em->persist($paragraph);
            //             // $this->em->flush();
            //             $io->text('yes');
            //             break;

            //         case 'no' :
            //             $io->text('no');
            //             break;

            //     }
            // }

            
            if ($indexFound = mb_stripos($content, $strToReach)){
                $newContent = mb_substr( $content, 0, $indexFound );
                $newContent .= mb_substr( $content, $indexFound + $lengthToRemove );


                $io->text('<<< ' . $paragraph->getBook()->getTitle() . ' >>> ' . $paragraph->getId() );
                $io->text($content);
                $io->text('<< replaced by >>');
                $io->text($newContent);

                if (!$processAll){
                    switch($io->choice('validate this content ? ',['yes','no','all'],'yes')){
                        case 'all' :
                            $processAll = true;
                            $io->text('all');

                        case 'yes' :
                            $paragraph->setContent($newContent);
                            $this->em->persist($paragraph);
                            $this->em->flush();
                            $io->text('yes');
                            break;

                        case 'no' :
                            $io->text('no');
                            break;

                    }
                }
                else {
                    $paragraph->setContent($newContent);
                    $this->em->persist($paragraph);
                    $this->em->flush();
                }

  
                // if ($io->confirm("validate new content",false))
                // {
                //     $paragraph->setContent($newContent);
                //     $this->em->persist($paragraph);
                //     $this->em->flush();
                // }
            };


            

            // if ( !($key%20) ){
            //     // every 20 paragraphs
            //     $this->em->flush();
            // }

        }
    
    $this->em->flush(); // before leaving ..

    $io->text('===========');
    $io->text('nb de notes ' . $count);


    //
    return Command::SUCCESS;

    }

}
