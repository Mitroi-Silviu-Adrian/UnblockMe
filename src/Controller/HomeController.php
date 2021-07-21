<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Entity\User;
use ContainerABtM8bi\getLicensePlatesControllerService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

        if($user) {
            //$this->addFlash('notice', "Welcome back!");
            return $this->render('home/index.html.twig', [
                'current_user' => $user->getUsername(),
            ]);
        }

        return $this->redirectToRoute('app_login');
    }

    #[Route('/myCars', name: 'showMyCars', methods: ['GET'])]
    public function showMyCars(): Response
    {
        if($this->getUser())
            return $this->redirectToRoute('license_plates_index');

        return $this->redirectToRoute("app_login");
    }

    #[Route('/myProfile', name: 'myProfile', methods: ['GET'])]
    public function showMyProfile(): Response
    {

        if($this->getUser())
            return $this->render('User/index.html.twig',[
                'email' => $this->getUser()->getEmail(),
                'username' => $this->getUser()->getUsername(),
            ]);

        return $this->redirectToRoute('app_login');
    }
    #[Route('/editUsername', name: 'editUsername', methods: ['GET', 'POST'])]
    public function editUsername(Request $request): Response
    {
        $user = $this->getUser();
        if($user) {
            $username = $this->getUser()->getUsername();
            $usernameForm = $this->createFormBuilder(null)
                ->add('Username',TextType::class,[
                    'empty_data' => $username,
                    'data' => $username,
                ])
                ->add('Save',SubmitType::class,[
                    'attr' => [
                        'class' => 'btn btn-warning btn-blk',
                    ]
                ])
                ->getForm();

            $usernameForm->handleRequest($request);

            if($usernameForm->isSubmitted() && $usernameForm->isValid())
            {
                $user->setUsername($usernameForm->get('Username')->getData());

                $this->getDoctrine()->getManager()->flush();

                $this->addFlash('notice', 'Username changed');
                return $this->redirectToRoute('myProfile');
            }

            return $this->render('User/changeUsername.html.twig', [
                'form' => $usernameForm->createView(),
            ]);
        }
        return $this->redirectToRoute('app_login');
    }
}
