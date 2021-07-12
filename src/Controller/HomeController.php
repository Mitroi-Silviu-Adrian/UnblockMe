<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Entity\User;
use ContainerABtM8bi\getLicensePlatesControllerService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use function Sodium\add;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function index(): Response
    {
        $user = $this->getUser();
        //$this->addFlash('notice', "Welcome back!");
        return $this->render('home/index.html.twig', [
            'current_user' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/myCars', name: 'showMyCars', methods: ['GET'])]
    public function showMyCars(): Response
    {
        return $this->redirectToRoute('license_plates_index');
    }

    #[Route('/myProfile', name: 'myProfile', methods: ['GET'])]
    public function showMyProfile(): Response
    {
        return $this->render('User/index.html.twig',[
            'email' => $this->getUser()->getEmail(),
        ]);
    }

    #[Route('/changePassword', name: 'changePassword',  methods: ['GET', 'POST'])]
    public function changePassword(Request $request,
                                   UserPasswordHasherInterface $passwordHasher,
                                    MailerController $mail,
                                    MailerInterface $mailer): Response
    {
        $Password = null;

        $passwordForm = $this->createFormBuilder($Password)
            ->add('OldPassword', PasswordType::class,[
                'invalid_message' => 'Wrong Password',
            ])
            ->add('NewPassword',PasswordType::class,[
                'invalid_message' => 'The password doesn\'t respect the constrains',
                'help' => 'The password must have at least 8 characters in length',
            ])
            ->add('RepeatPassword',PasswordType::class)
            ->add('Save',SubmitType::class)
            ->getForm();


        $passwordForm->handleRequest($request);

        if($passwordForm->isSubmitted() && $passwordForm->isValid())
        {
            $oldPassword = $passwordForm->get('OldPassword')->getData();
            $newPassword = $passwordForm->get('NewPassword')->getData();
            $newPasswordAgain = $passwordForm->get('RepeatPassword')->getData();

            $user = $this->getUser();

            if($passwordHasher->isPasswordValid($user,$oldPassword) == true &&
                count_chars($newPassword)>=8 &&
                count_chars($newPasswordAgain)>=8 &&
                $newPassword != $oldPassword &&
                $newPassword == $newPasswordAgain
            )
            {
                $user->setPassword($passwordHasher->hashPassword($user,$newPassword));
                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('notice','Password changed!');

                return $mail->sendEmail($mailer,$user->getEmail(),$newPassword);
            }

            return $this->render('user/newPassword.html.twig',[
                'form'=>$passwordForm->createView(),
                'password' =>$Password,
            ]);
        }

        return $this->render('user/newPassword.html.twig',[
            'form'=>$passwordForm->createView(),
            'password' =>$Password,
        ]);
    }

}
