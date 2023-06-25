<?php
namespace App\Service;

use Twig\Environment;
use App\Entity\Contact;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ContactNotification
{
    /** 
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $renderer;

    public function __construct(MailerInterface $mailer, Environment $renderer) {
        $this->mailer = $mailer;
        $this->renderer = $renderer;
    }
    
    public function notify(Contact $contact) {

        $email = (new TemplatedEmail())
            ->from($contact->getEmail())
            ->to('contact.bibnphi@webcoop.fr')
            ->subject('formulaire de contact')
            ->htmlTemplate('emails\contact.html.twig')
            ->context([
                'contact'=> $contact
            ]);
            // ->text($contact->getMessage())
            // ->html('<p>Demande de contact de : ' . $contact->getFirstName() . ' ' . $contact->getLastName() . '</p>' .
            //         '<p>Contenu du message : </p>' .
            //         '<p style="font-size:2em;">' . $contact->getMessage() . '</p>')

        $this->mailer->send($email);
    }
}