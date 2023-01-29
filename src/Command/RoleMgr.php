<?php
namespace App\Command;

use App\Entity\Role;

use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class RoleMgr extends Command
{
    protected static $defaultName = 'app:role-mgr';
    protected static $defaultDescription = 'Role manager';

    private $role;

    private $rr;
    private $ur;

    private $em;
    private $params;

    public function __construct(RoleRepository $rr, UserRepository $ur, EntityManagerInterface $em, ParameterBagInterface $params) 
    {
        parent::__construct();
        
        $this->rr = $rr;
        $this->ur = $ur;
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

        $io->note('Processing ... ');

        $newRole = new Role();
        $newRole->setTitle('ROLE_LIBRARIAN');
        $this->em->persist($newRole);
        $this->em->flush();


        $roles = $this->rr->findAll();

        foreach($roles as $role){

            $io->text($role->getTitle());

        }
        // foreach($notes as $note){
        //     $io->text('[_' . $note->getBookParagraph()->getId() . ']');
        //     $io->text($note->getBookParagraph()->getContent());
        // }

        // return Command::SUCCESS;

        $role = new Role();
        $role->setTitle('ROLE_ADMIN');
        $this->em->persist($role);

        $adminMail = "elisee.reclus@webcoop.fr";
        $adminUser = $this->ur->findOneByEmail($adminMail);


        $adminUser->addUserRole($role);
        $this->em->persist($adminUser);

        //
        // Correction / Ajout des notes dans l'objet BookParagraph concerné

        // Suppression du formatage relatif aux notes inscrits "en dur" dans les paragraphes
        //
        // foreach($notes as $key => $note){


        //     // $io->text($note->getContent());
        //     $io->text('=====' . $note->getId() . '======');

        //     //
        //     //

        //     $paragraph = $this->pr->findOneById($note->getBookParagraph()->getId());
        //     //$paragraph->addNote($note);

        //     $content = $paragraph->getContent(true); // raw .. without formatting
            
        //     $citation = $note->getCitation();

        //     $strToReach = '<sup id="citation_'
        //                 . $citation
        //                 . '"><a class="" href="#note_'
        //                 ;

        //     $strToRemove = $strToReach
        //                 . $note->getId()
        //                 . '">'
        //                 . $citation
        //                 . '</a></sup>'
        //                 ;
 
        //     $lengthToRemove = mb_strlen($strToRemove);


        //     // if (!$processAll){
        //     //     switch($io->choice('validate this content ? ',['yes','no','all'],'yes')){
        //     //         case 'all' :
        //     //             $processAll = true;
        //     //             $io->text('all');

        //     //         case 'yes' :
        //     //             // $paragraph->setContent($newContent);
        //     //             // $this->em->persist($paragraph);
        //     //             // $this->em->flush();
        //     //             $io->text('yes');
        //     //             break;

        //     //         case 'no' :
        //     //             $io->text('no');
        //     //             break;

        //     //     }
        //     // }

            
        //     if ($indexFound = mb_stripos($content, $strToReach)){
        //         $newContent = mb_substr( $content, 0, $indexFound );
        //         $newContent .= mb_substr( $content, $indexFound + $lengthToRemove );


        //         $io->text('<<< ' . $paragraph->getBook()->getTitle() . ' >>> ' . $paragraph->getId() );
        //         $io->text($content);
        //         $io->text('<< replaced by >>');
        //         $io->text($newContent);

        //         if (!$processAll){
        //             switch($io->choice('validate this content ? ',['yes','no','all'],'yes')){
        //                 case 'all' :
        //                     $processAll = true;
        //                     $io->text('all');

        //                 case 'yes' :
        //                     $paragraph->setContent($newContent);
        //                     $this->em->persist($paragraph);
        //                     $this->em->flush();
        //                     $io->text('yes');
        //                     break;

        //                 case 'no' :
        //                     $io->text('no');
        //                     break;

        //             }
        //         }
        //         else {
        //             $paragraph->setContent($newContent);
        //             $this->em->persist($paragraph);
        //             $this->em->flush();
        //         }

  
        //         // if ($io->confirm("validate new content",false))
        //         // {
        //         //     $paragraph->setContent($newContent);
        //         //     $this->em->persist($paragraph);
        //         //     $this->em->flush();
        //         // }
        //     };


            

        //     // if ( !($key%20) ){
        //     //     // every 20 paragraphs
        //     //     $this->em->flush();
        //     // }

        // }
    
        $this->em->flush(); // before leaving ..

        $io->text('');
        $io->note('===D O N E===');
        // $io->text('nb de notes ' . $count);


        //
        return Command::SUCCESS;

    }

}