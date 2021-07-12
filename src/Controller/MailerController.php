<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\MessageType;
use Doctrine\DBAL\Types\StringType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Message;
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
            ->html("<p>Thank you for registration! Your password is $password</p>");

        $mailer->send($email);

        return $this->redirectToRoute('app_login');
    }

    #[Route('/messageForm{from}/{to}', name: 'messageForm')]
    public function messageForm(Request $request,
                                MailerInterface $mailer,
                                string $from,
                                string $to): Response
    {
        $message = null;

        $form = $this->createForm(MessageType::class,$message);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $message = current($form->getData());

            //echo $this->message;

            if($to != "null") {
                $email = (new TemplatedEmail())
                    ->from($from)
                    ->to($to)
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('UnBlockMe Notification')
                    ->htmlTemplate('mailer/notification.html.twig')
                    ->context([
                        'text' => $message,
                        'from' => $from,
                    ]);

                $mailer->send($email);
            }

            //$this->addFlash($to,$message);

            return $this->redirectToRoute('activity');
        }
        return $this->render('mailer/new.html.twig',[
            'form' => $form->createView(),
            'from' => $from,
            'to' => $to,
        ]);
    }

}
