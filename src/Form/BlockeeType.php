<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\LicensePlates;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityRepository;

use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class BlockeeType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
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
            ->add('blocker')
            ->add('blockee',ChoiceType::class,$formOptions)
            ->add('Report', SubmitType::class)
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
