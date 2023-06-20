<?php
namespace App\Service;

use App\Entity\Contact;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ContactNotification
{
    private $mailer;

    public function __construct(MailerInterface $mailer) {
        $this->mailer = $mailer;
    }
    
    public function notify(Contact $contact) {

        $email = (new Email())
            ->from($contact->getEmail())
            ->to('contact.bibnphi@webcoop.fr')
            ->subject('formulaire de contact')
            // ->text($contact->getMessage())
            ->html('<p>Demande de contact de : ' . $contact->getFirstName() . ' ' . $contact->getLastName() . '</p><br>' .
                    '<p style="font-size:2em;">Contenu du message : ' . $contact->getMessage() . '</p>')
            ;

        $this->mailer->send($email);
    }
}