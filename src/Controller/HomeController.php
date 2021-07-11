<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use ContainerABtM8bi\getLicensePlatesControllerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



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


}
