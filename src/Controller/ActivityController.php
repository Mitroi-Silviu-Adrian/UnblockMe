<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Repository\ActivityRepository;
use App\Form\ActivityType;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Entity\Activity;
use App\Entity\User;

class ActivityController extends AbstractController
{
    #[Route('/activity', name: 'activity')]
    public function index(ActivityRepository $activityRepository): Response
    {
        $licensePlates = $this->getUser()->getLicensePlates();

        $activities = array();

        foreach($licensePlates as $licensePlate)
        {
            $planeLP = $licensePlate->getLicensePlate();

            $result = $activityRepository->findByBlocker($planeLP);

            if($result)
                array_push($activities,$result);

            $result = $activityRepository->findByBlockee($planeLP);

            if($result)
                array_push($activities,$result);

        }

        return $this->render('activity/index.html.twig', [
            'controller_name' => 'ActivityController',
            'activityRepository' => $activityRepository->findAll(),
            'activeActivities' => $activities,
        ]);
    }

    #[Route('/activity/{type}', name: 'activityType', methods: ['GET', 'POST'])]
    public function activityType(Request $request,
                                 string $type,
                                 ActivityRepository $activityRepository): Response
    {
        return $this->render('activity/chooseCar.html.twig', [
            'license_plates' => $this->getUser()->getLicensePlates(),
            'type' => $type,
            'activityRepository' => $activityRepository,
        ]);
    }

    #[Route('/activity/choose/{type}/{id}', name: 'chooseLP', methods: ['GET', 'POST'])]
    public function choose(Request $request,
                           string $type,
                           LicensePlates $licensePlates,
                           ActivityRepository $activityRepository): Response
    {
        $licensePlatesPlain = $licensePlates->getLicensePlate();

        //$activities = $this->getDoctrine()->getRepository(Activity::class)->findAll();

        if($type == 'blocker')
        {
            $result = $activityRepository->findByBlocker($licensePlatesPlain);

            if($result != null)
            {
                echo 'The activity was already reported. Check you email';
                return $this->redirectToRoute('home');
            }

            return $this->redirectToRoute('reportActivity', [
                'id' => $licensePlates->getId(),
                'type' => $type,
            ]);
        }

        $result = $activityRepository->findByBlockee($licensePlatesPlain);

        if($result != null)
        {
            echo 'The activity was already reported. Check you email';
            return $this->redirectToRoute('home');
        }

        return $this->redirectToRoute('reportActivity', [
            'id' => $licensePlates->getId(),
            'type' => $type,
        ]);

    }

    #[Route('/reportActivity/{type}/{id}', name: 'reportActivity', methods: ['GET', 'POST'])]
    public function new(Request $request, string $type, LicensePlates $licensePlates): Response
    {

        $activity = new Activity();

        if($type == 'blocker')
            $activity->setBlocker($licensePlates->getLicensePlate());
        else
            $activity->setBlockee($licensePlates->getLicensePlate());

        $form = $this->createForm(ActivityType::class,$activity);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activity = $form->getData();

            //echo $activity->getBlocker();
            //echo $activity->getBlockee();

            $entityManager = $this->getDoctrine()->getManager();

            $activity->setStatus(1);

            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToRoute('activity');
        }

        //echo $licensePlates->getLicensePlate();
        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/deleteActivity/{status}', name: 'deleteActivity', methods: ['GET', 'POST'])]
    public function delete(Request $request, Activity $activity): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($activity);
        $entityManager->flush();

        return $this->redirectToRoute('activity');
    }
}
