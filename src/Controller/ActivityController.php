<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Repository\ActivityRepository;
use App\Form\ActivityType;
use Doctrine\DBAL\Types\TextType;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
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

    #[Route('/reportActivity/{type}', name: 'reportActivity', methods: ['GET', 'POST'])]
    public function new(Request $request, string $type): Response
    {
        $activity = new Activity();

        if($type == 'blocker')
            $form = $this->createForm(BlockerType::class,$activity);

        else
            $form = $this->createForm(BlockeeType::class,$activity);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $activity->setBlockee($form->get('blockee')->getData());
            $activity->setBlocker($form->get('blocker')->getData());

            //var_dump($activity);die;

            $entityManager = $this->getDoctrine()->getManager();

            $activity->setStatus(1);

            $entityManager->persist($activity);
            $entityManager->flush();

            //$this->addFlash('notice','The Activity was reported!');

            $currentUserEmail= $this->getUser()->getEmail();

            if($type == 'blocker')
                $plainLicensePlate = $activity->getBlockee();
            else
                $plainLicensePlate = $activity->getBlocker();

            $licensePlate = $this->getDoctrine()
                ->getRepository(LicensePlates::class)
                ->findByLicensePlate($plainLicensePlate);

            if($licensePlate) {
                $otherUserEmail = $licensePlate->getUserId()->getEmail();
                return $this->redirectToRoute('messageForm', [
                    'from' => $currentUserEmail,
                    'to' => $otherUserEmail,
                ]);
            }
            $this->addFlash('error','The owner of the car you reported, is not registered.');
            $this->addFlash('error','We will let you know, if the user registers meanwhile');

            $licensePlate = new LicensePlates();
            $licensePlate->setLicensePlate($plainLicensePlate);

            $entityManager->persist($licensePlate);
            $entityManager->flush();
        }

        //echo $licensePlates->getLicensePlate();
        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
            'type' => $type,
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

    #[Route('/contact/{status}', name: 'contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, Activity $activity): Response
    {
        $blockerLP = $activity->getBlocker();
        $blockeeLP = $activity->getBlockee();

        $currentUserLPs = $this->getUser()->getLicensePlates();

        $isBlocker = true;
        foreach ($currentUserLPs as $lp)
        {
            if($lp->getLicensePlate() == $blockeeLP)
            {
                $isBlocker = false;
                break;
            }
            if($lp == $blockerLP)
                break;
        }

        $from = $this->getUser()->getEmail();

        if($isBlocker == true)
            $to = $this->getDoctrine()
                ->getRepository(LicensePlates::class)
                ->findByLicensePlate($blockeeLP)
                ->getUserId()
                ->getEmail()
            ;
        else
            $to = $this->getDoctrine()
                ->getRepository(LicensePlates::class)
                ->findByLicensePlate($blockerLP)
                ->getUserId()
                ->getEmail()
            ;
        //var_dump($to,$from, $isBlocker); die;
        return $this->redirectToRoute('messageForm', [
            'from' => $from,
            'to' => $to,
        ]);
    }

}
