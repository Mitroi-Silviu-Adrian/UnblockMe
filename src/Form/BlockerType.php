<?php

namespace App\Form;

use App\Controller\LicensePlatesController;
use App\Entity\Activity;
use App\Entity\LicensePlates;
use App\Repository\LicensePlatesRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;


class BlockerType extends AbstractType
{
    private Security $security;
    private $licensePlatesRepository;
    public function __construct(Security $security, LicensePlatesRepository $licensePlatesRepository)
    {
        $this->security = $security;
        $this->licensePlatesRepository = $licensePlatesRepository;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->security->getUser();

        $licensePlates= $user->getLicensePlates();

        $plainLPs =  array();

        foreach ($licensePlates as $licensePlate)
        {
            $key = $licensePlate->getLicensePlate();
            array_push($plainLPs, $key);
            //echo $licensePlate->getLicensePlate();
            //echo '<br>';
        }

        //echo $plainLPs[0];

        $formOptions = [

            'choices' => $plainLPs,
            'choice_label' => function($choice,$key,$value){
                return $value;
            },
            'mapped' => false,

        ];
        $noLPs = count($plainLPs);
        $submitOptions = array();
        if($noLPs > 1)
            $formOptions += ['placeholder' => 'Choose a car'];
        else
        {
            $formOptions+=['disabled' => true,'data' => current($plainLPs)];
            if($noLPs == 0)
            {
                $submitOptions += ['disabled' => true];
                $formOptions+=['help' => 'Add first new cars'];
            }
        }

        $builder
            ->add('blocker',ChoiceType::class,$formOptions)
            ->add('blockee')
            ->add('Report', SubmitType::class,$submitOptions)
        ;


        $builder->get('blockee')->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($user){
                $event->getForm()->getParent()
                    ->add('Message', HiddenType::class,[
                        'mapped' => false,
                        'data' => null,
                        'disabled'=> true,
                    ])
                    ->add('email', HiddenType::class,[
                        'data' => null,
                        'mapped' => false,
                        'disabled'=> true,
                    ]);
            }
        );

        $builder->get('blockee')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($user)
            {
                $form = $event->getForm();


                $blockeeLP = $form->getData();

                $licensePlate = $this->licensePlatesRepository->findByLicensePlate(LicensePlatesController::formatLP($blockeeLP));

                if($licensePlate) {

                    $otherUser = $licensePlate->getUserId();
                    if ($otherUser != null) {
                        //$blockerLP = $form->getParent()->get('blocker')->getData();

                        $email =$otherUser->getEmail();

                        $form->getParent()
                            ->add('Message', TextareaType::class,[
                                'mapped' => false,
                                'attr' => [
                                    'placeholder' => 'Enter your message',
                                ]])

                            ->add('email', HiddenType::class,[
                                'empty_data' => $email,
                                'mapped' => false,
                            ])
                            ->remove('Report')
                            ->add('Send',SubmitType::class)
                        ;

                    }
                }
            });


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
