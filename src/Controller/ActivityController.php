<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Activity;
use App\Entity\User;

class ActivityController extends AbstractController
{
    #[Route('/activity', name: 'activity')]
    public function index(): Response
    {
        return $this->render('activity/index.html.twig', [
            'controller_name' => 'ActivityController',
        ]);
    }

    #[Route('/activity/{type}', name: 'activityType', methods: ['GET', 'POST'])]
    public function activityType(Request $request, string $type): Response
    {
        return $this->render('activity/chooseCar.html.twig', [
            'license_plates' => $this->getUser()->getLicensePlates(),
            'type' => $type,
        ]);
    }

    #[Route('/activity/choose/{type}/{id}', name: 'chooseLP', methods: ['GET', 'POST'])]
    public function choose(Request $request, string $type, LicensePlates $licensePlates): Response
    {
        $licensePlatesPlain = $licensePlates->getLicensePlate();

        $activities = $this->getDoctrine()->getRepository(Activity::class)->findAll();

        if($type == 'blocker')
        {
            $result = $activities->findByBlocker();

            if($result != null)
            {
                echo 'The activity was already reported. Check you email';
                return $this->redirectToRoute('home');
            }

            return $this->redirectToRoute('reportActivity', [
                'licensePlates' => $licensePlates,
                'type' => $type,
            ]);
        }

        $result = $activities->findByBlockee();

        if($result != null)
        {
            echo 'The activity was already reported. Check you email';
            return $this->redirectToRoute('home');
        }

        return $this->redirectToRoute('reportActivity', [
            'licensePlates' => $licensePlates,
            'type' => $type,
        ]);

    }

    #[Route('/reportActivity', name: 'reportActivity', methods: ['GET', 'POST'])]
    public function new(Request $request, string $type, LicensePlates $licensePlates): Response
    {


        return $this->render('activity/new.html.twig', [
            //'licensePlate' => $licensePlate,
            'type' => $type,
        ]);
    }

}
