<?php

namespace App\Command;

use App\Entity\Book;
use App\Service\XmlParser;
use App\Repository\BookRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class XmlParserCommand extends Command
{
    protected static $defaultName = 'app:xml-parser';
    protected static $defaultDescription = 'Run the xml parser';

    private $parser;
    private $book;

    private $br;
    private $em;
    private $params;

    public function __construct(BookRepository $br, EntityManagerInterface $em, ParameterBagInterface $params) 
    {
        parent::__construct();
        
        $this->br = $br;
        $this->em = $em;
        $this->params = $params;

    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('xmlFileName', InputArgument::REQUIRED, 'The name of the xml file to be parsed')
            ->addArgument('bookId', InputArgument::REQUIRED, 'the id of the book')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $xmlFileName = $input->getArgument('xmlFileName');

        if ($xmlFileName) {
            $io->note(sprintf('le fichier xml: %s', $xmlFileName));
        }

        $bookId = $input->getArgument('bookId');
        if ($bookId) {
            $io->note(sprintf('l\'id du livre: %d', $bookId));

        }

        if ($input->getOption('option1')) {
            // ...
        }


        $book = $this->br->find($bookId);


        $parser = new XmlParser(
                        $book,
                        $xmlFileName,
                        $this->params->get('kernel.project_dir'),
                        $this->params->get('app.parsing_buffer_size_xl'),
                        $this->em,
        );

        $parser->parse();


        if ( $parser->isParsingCompleted() ){

            //
            //
            $book->setParsingTime($parser->getParsingTime())
                ->setNbParagraphs($parser->getNbParagraphs())
                ->setNbSentences($parser->getNbSentences())
                ->setNbWords($parser->getNbWords())
                ;

        
            $this->em->persist($book);
            $this->em->flush();

            // $nbParagraphs = $book->getNbParagraphs();

            // $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

            // $io->success('Parsing is completed !-) ' . $book->getTitle() . ' got ' . $nbParagraphs . ' paragraphs' );
            // $io->note($nbParagraphs);
            // $io->note($book->getTitle());

        }
        else {
            $io->note('Parsing of ' . $xmlFileName . ' is NOT completed !!');
        }

        // foreach($paragraphs as $paragraph)
        // {
        //     $io->text($paragraph->getContent());
        // }
        
        //
        return Command::SUCCESS;
    }
}
