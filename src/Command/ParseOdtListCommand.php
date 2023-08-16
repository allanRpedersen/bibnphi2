<?php
namespace App\Command;

use Monolog\Logger;
use App\Entity\Book;
use App\Service\XmlParser;
use App\Traits\TraitFileMgr;
use App\Repository\BookRepository;
use Monolog\Handler\StreamHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParseOdtListCommand extends Command
{
    protected static $defaultName           = 'app:parse-odt-list';
    protected static $defaultDescription    = 'Parse a list of odt files';
    private $params;


    private $em; // the Entity manager
    private $br; // the Book repository

    private $projectDir;
    private $logger;

    use TraitFileMgr;

    public function __construct(
                            EntityManagerInterface $em,
                            ParameterBagInterface $params,
                            BookRepository $br
                            )
    {
        parent::__construct();

        $this->em = $em;
        $this->params = $params;
        $this->br = $br;

        $this->logger = new Logger('admin_bibnphi');


    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            // ->addArgument('xmlFileName', InputArgument::REQUIRED, 'The name of the xml file to be parsed')
            // ->addArgument('bookId', InputArgument::REQUIRED, 'the id of the book')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Option description', 'prod')
            ->addArgument('fileList', InputArgument::IS_ARRAY, 'Liste de fichiers' )
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        //$xmlFileName = $input->getArgument('xmlFileName');


        $io->note(self::$defaultName . ' / ' . self::$defaultDescription);        
        $io->note('Processing with mode : ' . $input->getOption('mode') . ' ... ');
        $startingTime = \microtime(true);
        
        $this->projectDir = $this->params->get('kernel.project_dir');
        $io->note($this->projectDir);

		$this->logger->pushHandler( new StreamHandler($this->projectDir . '/public/bibnphi.log', Logger::DEBUG) );

        $fileList = $input->getArgument('fileList');

        $count = count($fileList);
        $io->note( $count . ' fichiers à analyser ');

        for($i=0; $i<$count; $i++){
            $io->text( $i+1 . '- ' . $fileList[$i]);

            //
            $DIRNAME    = \pathinfo($fileList[$i], PATHINFO_DIRNAME);
            $BASENAME   = \pathinfo($fileList[$i], PATHINFO_BASENAME);
            $EXTENSION  = \pathinfo($fileList[$i], PATHINFO_EXTENSION);
            $FILENAME   = \pathinfo($fileList[$i], PATHINFO_FILENAME);

            $io->text( '[' . $DIRNAME . '][' . $BASENAME. '][' . $EXTENSION. '][' . $FILENAME . ']' );

            if ($book = $this->br->findOneByOdtBookName($BASENAME)){

                $readBufferSize = $this->params->get('app.parsing_buffer_size_xl');
                $workingDir = $this->projectDir . '/' . $DIRNAME . '/' . $FILENAME;

                $io->text($book->getId());
                $io->text($book->getTitle());
                $io->text($book->getAuthor()->getLastName());
                $io->text($book->getSlug());
                $io->text($book->getPublishedYear());
                $io->text($book->getOdtBookName());

                $previousId             = $book->getId();
                $previousTitle          = $book->getTitle();
                $previousAuthor         = $book->getAuthor();
                $previousSlug           = $book->getSlug();
                $previousPublishedYear  = $book->getPublishedYear();
                $previousOdtBookName    = $book->getOdtBookName();
                $previousOdtBookSize    = $book->getOdtBookSize();

                

                $io->text(' - rangé dans ' . $workingDir);

                // suppression de l'ouvrage dans la base (et du fichier odt $BASENAME ??-x )
                passthru('cp -fv ' . $fileList[$i] . ' ' . $DIRNAME . '/_' . $BASENAME );

                $this->em->remove($book);
                $this->em->flush();
                $io->text('Suppression de l\'ouvrage : ' . $book->getTitle() . ' écrit par ' . $book->getAuthor()->getLastName() . ' de la base.');

                // suppression répertoire existant
                passthru('rm -fr ' . $workingDir . ' > /dev/null 2>&1', $errCode );
                $io->text('Suppression du répertoire : ' . $workingDir );

                passthru('mv -fv ' . $DIRNAME . '/_' . $BASENAME . ' ' . $fileList[$i] );

                // $io->text($book->getId());
                // $io->text($book->getTitle());
                // $io->text($book->getAuthor()->getLastName());
                // $io->text($book->getSlug());
                // $io->text($book->getPublishedYear());
                // $io->text($book->getOdtBookName());

                // nouvelle analyse du doc odt
                $io->text('Un nouveau parsing du document : ' . $DIRNAME . '/' . $BASENAME . ' est envisageable !!');

                // $workingDir = $this->bookctl->isOdtDocValid($book);
                $workingDir = $this->isOdtDocValid($DIRNAME . '/' . $BASENAME);
                $io->text('working directory for the parser : ' . $workingDir);

                if($workingDir){

                    $book = new Book();

                    $book->setAuthor($previousAuthor)
                        ->setNbParagraphs(0)
                        ->setOdtBookName($BASENAME)
                        ->setOdtBookSize($previousOdtBookSize)
                        ->setParsingTime(0)
                        ->setPublishedYear($previousPublishedYear)
                        ->setTitle($previousTitle)
                        ->setUpdatedAt(new \DateTimeImmutable())
                        ->setXmlFileSize(0);
    
                    $this->em->persist($book);

                    $parser = new XmlParser(
                                    $book,
                                    $workingDir,
                                    $this->projectDir,
                                    $readBufferSize,
                                    $this->em,
                                    'prod'
                    );

                    $parser->parse();

                    if($parser->isParsingCompleted()){
                        $book->setParsingTime($parser->getParsingTime())
                            ->setNbParagraphs($parser->getNbParagraphs());

                        $this->em->persist($book);
                        $this->em->flush();

                        $io->text($book->getId());


                        $io->note( 'L\'analyse du document s\'est terminée avec succès ! ( ' .
                                    $parser->getNbParagraphs() .
                                    ' paragraphes en ' .
                                    round($parser->getParsingTime(), 2) .
                                    ' secondes)');
                    }
                }
            }
        }

        // $text = '';
        // if (count($fileList) > 0) {
        //     $text .= ' ' . implode(', ', $fileList);
        //     $io->text($text);
        // }
        //

        $endingTime = \microtime(true);

        $io->note('... completed in : ' . ($endingTime - $startingTime) . ' seconds !-)');
        return Command::SUCCESS;
    }


}