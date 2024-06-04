<?php 
namespace App\Service;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;

class MailerService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendNotificationEmail(string $to, string $subject, string $message, string $nom)
    {
        $email = (new Email())
            ->from(new Address('no-reply@demomailtrap.com', $nom))
            ->to($to)
            ->subject($subject)
            ->html($message);

        $this->mailer->send($email);
        return new Response('Email sent successfully');
    }


    public function sendPasswordEmail(string $to, string $message)
    {
        $subject = 'Your Password Recovery';
        $email = (new Email())
        ->from(new Address('no-reply@demomailtrap.com','Edu School'))
        ->to($to)
            ->subject($subject)
            ->html($message);

        $this->mailer->send($email);
    }
}
