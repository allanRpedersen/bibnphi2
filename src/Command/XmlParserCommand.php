<?php

namespace App\Command;

use Monolog\Logger;
use App\Entity\Book;
use App\Service\XmlParser;
use Monolog\Handler\StreamHandler;
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

    private $projectDir;
    private $logger;

    private $br;
    private $em;
    private $params;

    public function __construct(BookRepository $br, EntityManagerInterface $em, ParameterBagInterface $params) 
    {
        parent::__construct();
        
        $this->br = $br;
        $this->em = $em;
        $this->params = $params;

        $this->projectDir = $this->params->get('kernel.project_dir');


        // $this->logger = $logger;
		$this->logger = new Logger('_xmlParserCommand');
		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );

    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('workingDir', InputArgument::REQUIRED, 'The working directory where stands the document to be parsed')
            ->addArgument('bookId', InputArgument::REQUIRED, 'the id of the book')
            ->addOption('mode', 'm', InputOption::VALUE_OPTIONAL, 'Running mode', 'prod')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //
        //
        $io = new SymfonyStyle($input, $output);
        $workingDir = $input->getArgument('workingDir');

        if ($workingDir) {
            $io->note(sprintf('le document: %s.odt', $workingDir));
        }

        $bookId = $input->getArgument('bookId');
        if ($bookId) {
            $io->note(sprintf('l\'id du livre: %d', $bookId));

        }

        if ($mode = $input->getOption('mode')) {
            $io->note(sprintf('mode: %s', $mode));
        }

        //
        //
        if ('test+' == $mode) return 0;

        //
        //
        $this->logger->info('~~~~~ Through the XmlParserCommand ~~~~~');

        //
        //
        $book = $this->br->find($bookId);
        $readBufferSize = $this->params->get('app.parsing_buffer_size_xl');

        $parser = new XmlParser(
                        $book,
                        $workingDir,
                        $this->projectDir,
                        $readBufferSize,
                        $this->em,
                        $mode
        );

        $title = $book->getTitle();
        $io->text($title);
        $parser->parse();

        //
        //
        if ( $parser->isParsingCompleted() ){

            //
            //
            $book->setParsingTime($parser->getParsingTime())
                ->setNbParagraphs($parser->getNbParagraphs())
                ->setXmlFileSize($parser->getXmlFileSize())
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
            $io->note('Parsing of ' . $workingDir . '.odt is NOT completed !!');
        }

        // foreach($paragraphs as $paragraph)
        // {
        //     $io->text($paragraph->getContent());
        // }
        
        //
        return Command::SUCCESS;
    }
}
