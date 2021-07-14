<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\LicensePlates;
use App\Form\LicensePlatesType;
use App\Repository\LicensePlatesRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/license/plates')]
class LicensePlatesController extends AbstractController
{
    #[Route('/', name: 'license_plates_index', methods: ['GET'])]
    public function index(): Response
    {
        $user =$this->getUser();

        if($user==null)
            return $this->redirectToRoute("app_login");

        $licensePlatesRepository = $user->getLicensePlates();

        return $this->render('license_plates/index.html.twig', [
            'license_plates' => $licensePlatesRepository,
        ]);
    }

    static function formatLP(string $plainLP): string
    {
        $plainLP = strtoupper($plainLP);
        $plainLP = strtr($plainLP,' ','');

        $ignoreChars = array(' ','*','-','_','*');
        foreach ($ignoreChars as $char) {
            $pos = strpos($plainLP, $char);
            while ($pos) {
                $plainLP = substr($plainLP, 0, $pos) . substr($plainLP, $pos + 1);
                $pos = strpos($plainLP, $char);
            }
        }
        return $plainLP;
    }

    #[Route('/new', name: 'license_plates_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $licensePlate = new LicensePlates();
        $form = $this->createForm(LicensePlatesType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            $plainLP = $licensePlate->getLicensePlate();
            $plainLP = $this->formatLP($plainLP);

            $auxLP =$this->getDoctrine()
                    ->getRepository(LicensePlates::class)
                    ->findByLicensePlate($plainLP);

            if($auxLP)
            {
                $auxLP->setUserId($this->getUser());
                $this->addFlash('notice','Your licence plate is involved in an activity!');
                $this->addFlash('notice','Please check activity section!');
                $entityManager->flush();
            }
            else
            {
                $licensePlate->setUserId($this->getUser());
                $licensePlate->setLicensePlate($plainLP);

                $entityManager->persist($licensePlate);
                $entityManager->flush();

                $this->addFlash('notice',"Licence Plate $plainLP registered!");

                //return $this->redirectToRoute('license_plates_index');
            }

        }

        return $this->render('license_plates/new.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'license_plates_show', methods: ['GET'])]
    public function show(LicensePlates $licensePlate): Response
    {

        return $this->render('license_plates/show.html.twig', [
            'license_plate' => $licensePlate,
        ]);
    }

    private function actingCars(string $plainLP, ObjectManager $objectManager): bool
    {
        $activities = $objectManager->getRepository(Activity::class)->findByBlocker($plainLP);
        if(count($activities) > 0)
            return true;


        $activities = $objectManager->getRepository(Activity::class)->findByBlockee($plainLP);
        if(count($activities) > 0)
            return true;

        return false;
    }

    #[Route('/edit/{id}', name: 'license_plates_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, LicensePlates $licensePlate): Response
    {
        if($this->actingCars($licensePlate->getLicensePlate(),$this->getDoctrine()->getManager()) == true)
        {
            $this->addFlash('notice','The car cannot be edited as is involved in an activity.');
            return $this->redirectToRoute('license_plates_index');
        }
        $form = $this->createForm(LicensePlatesType::class, $licensePlate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('license_plates_index');
        }

        return $this->render('license_plates/edit.html.twig', [
            'license_plate' => $licensePlate,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/deleteCar/{id}', name: 'license_plates_delete', methods: ['GET','POST'])]
    public function delete(Request $request, LicensePlates $licensePlate): Response
    {
        $objectManager = $this->getDoctrine()->getManager();

        if($this->actingCars($licensePlate->getLicensePlate(), $objectManager) == true)
        {
            $this->addFlash( 'notice','The car cannot be deleted because is involved in a activity');
            return $this->redirectToRoute('license_plates_index');
        }

        $objectManager->remove($licensePlate);
        $objectManager->flush();

        return $this->redirectToRoute('license_plates_index');
    }
}
