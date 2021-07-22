<?php

namespace App\Controller;

use App\Entity\LicensePlates;
use App\Form\BlockeeType;
use App\Form\BlockerType;
use App\Repository\ActivityRepository;
use App\Form\ActivityType;
use Doctrine\DBAL\Types\TextType;
use http\Message;
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
        $user =$this->getUser();

        if($user == null)
            return $this->redirectToRoute('app_login');

        $licensePlates = $user->getLicensePlates();

        $activities = array();

        $plainLicencePlates = array();

        foreach($licensePlates as $licensePlate)
        {
            $planeLP = $licensePlate->getLicensePlate();

            array_push($plainLicencePlates,$planeLP);

            $result = $activityRepository->findByBlocker($planeLP);

            if($result)
            {
                foreach ($result as $res)
                    array_push($activities,$res);
            }

            $result = $activityRepository->findByBlockee($planeLP);

            if($result)
            {
                foreach ($result as $res)
                    array_push($activities,$res);
            }
        }

        return $this->render('activity/index.html.twig', [
            'controller_name' => 'ActivityController',
            'ownerLPs' => $plainLicencePlates,
            'activeActivities' => $activities,
        ]);
    }

    #[Route('/reportActivity/{type}', name: 'reportActivity', methods: ['GET', 'POST'])]
    public function reportActivity(Request $request, MailerInterface $mailer , string $type): Response
    {
        $activity = new Activity();

        if($type == 'blocker')
            $form = $this->createForm(BlockerType::class,$activity);

        else
            $form = $this->createForm(BlockeeType::class,$activity);

        $form->handleRequest($request);
        //dd($form);
        if($form->isSubmitted() && $form->isValid()) {
            //dd($form->get('Message')->getData());
            $otherUserEmail = $form->get('email')->getData();

            //dd($otherUserEmail);
            //var_dump($activity);die;


            if($type == 'blocker')
                $plainLicensePlate = $activity->getBlockee();
            else
                $plainLicensePlate = $activity->getBlocker();

            $this->addFlash('notice','The Activity was reported!');

            $activity->setBlocker(LicensePlatesController::formatLP($form->get('blocker')->getData()));
            $activity->setBlockee(LicensePlatesController::formatLP($form->get('blockee')->getData()));

            $entityManager = $this->getDoctrine()->getManager();

            $activity->setStatus(1);

            $entityManager->persist($activity);
            $entityManager->flush();

            if($otherUserEmail) {
                $message = $form->get('Message')->getData();

                $this->addFlash('notice', 'The message was sent');
                MailerController::sendMessage($mailer,$this->getUser()->getEmail(),$otherUserEmail,$message);
            }
            else
            {

                    $licensePlate = new LicensePlates();
                    $licensePlate->setLicensePlate(LicensePlatesController::formatLP($plainLicensePlate));

                    $this->addFlash('notice', 'The car reported has no registered owner');

                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($licensePlate);
                    $entityManager->flush();

            }
            return $this->redirectToRoute('activity');

        }

        //echo $licensePlates->getLicensePlate();
        return $this->render('activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }
    #[Route('/deleteActivity/{blocker}/{blockee}', name: 'deleteActivity', methods: ['GET', 'POST'])]
    public function delete(Request $request, Activity $activity): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($activity);
        $entityManager->flush();

        return $this->redirectToRoute('activity');
    }

    #[Route('/contact/{blocker}/{blockee}', name: 'contact', methods: ['GET', 'POST'])]
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

        if($isBlocker == true)
            $user = $this->getDoctrine()
                ->getRepository(LicensePlates::class)
                ->findByLicensePlate($blockeeLP)
                ->getUserId()
            ;
        else
            $user = $this->getDoctrine()
                ->getRepository(LicensePlates::class)
                ->findByLicensePlate($blockerLP)
                ->getUserId()
            ;

        if($user != null)
        {return $this->redirectToRoute('messageForm', [
                'from' => $this->getUser()->getID(),
                'to' => $user->getID(),
            ]);
        }
        $this->addFlash('notice', "There is still no user, who claims the car.");
        return $this->redirectToRoute('activity');
    }

}
