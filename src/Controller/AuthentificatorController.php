<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Entity\User;
use App\Form\LicensePlatesType;
use App\Form\UserFormType as UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthentificatorController extends AbstractController
{
    #[Route('/loginIndex', name: 'loginIndex')]
    public function index(): Response
    {
        return $this->redirectToRoute("app_login");
    }
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {

             $this->addFlash('notice', "Welcome back!");
             return $this->render('home/index.html.twig', [
                 'current_user' => $this->getUser()->getUserIdentifier(),
             ]);
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/register', name: 'register', methods: ['GET', 'POST'])]
    public function register(Request $request,
                             UserPasswordHasherInterface $passwordHasher,
                             MailerController $mail,
                             MailerInterface $mailer): Response
    {
        $user = new User();
        $password = substr(sha1(time()),0,rand(8,12));

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword($passwordHasher->hashPassword($user,$password));
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $mail->sendEmail($mailer,$user->getEmail(),$password);
        }

        return $this->render('user/new.html.twig', [
            'license_plate' => $user,
            'form' => $form->createView(),
        ]);
    }


}
