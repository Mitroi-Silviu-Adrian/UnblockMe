<?php

namespace App\Form;

use App\Controller\LicensePlatesController;
use App\Entity\Activity;
use App\Entity\LicensePlates;
use App\Repository\LicensePlatesRepository;
use Doctrine\ORM\EntityRepository;

use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use function PHPUnit\Framework\any;
use function Sodium\add;

class BlockeeType extends AbstractType
{
    private $security;
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
            ->add('blockee',ChoiceType::class,$formOptions)
            ->add('blocker')
            ->add('Report', SubmitType::class,$submitOptions)
        ;

        $builder->get('blocker')->addEventListener(
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

        $builder->get('blocker')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($user)
        {
            $form = $event->getForm();

            //dd($form);
            $blockerLP = $form->getData();
            //dd($blockerLP);
            $licensePlate = $this->licensePlatesRepository->findByLicensePlate(LicensePlatesController::formatLP($blockerLP));
            //dd($licensePlate);
            if($licensePlate) {

                $otherUser = $licensePlate->getUserId();
                if ($otherUser != null) {
                    $blockeeLP = $form->getParent()->get('blockee')->getData();
                    //dd($blockeeLP);
                    $email =$otherUser->getEmail();
                    //dd($email);
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

                    //dd($form->getParent());
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
