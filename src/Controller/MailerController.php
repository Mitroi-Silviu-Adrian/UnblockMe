<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;

class MailerController extends AbstractController
{
    #[Route('/mailer', name: 'mailer')]
    public function index(): Response
    {
        return $this->render('mailer/index.html.twig', [
            'controller_name' => 'MailerController',
        ]);
    }

    #[Route('/sendEmail', name: 'sendEmail')]
    public function sendEmail(MailerInterface $mailer, string $userMail, string $password): Response
    {

        $email = (new Email())
            ->from('UnBlockMe@app.com')
            ->to($userMail)
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Welcome to UnBlockMe')
            ->text($password)
            ->html('<p>Thank you for registration!</p>');

        $mailer->send($email);

        return $this->redirectToRoute('app_login');
    }
}
