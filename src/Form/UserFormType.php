<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email',null,[
                'label' => 'Email',
                'attr' => [
                    'class' => 'fs-6',
                    'placeholder' => "Enter your email ...",
                    'autocomplete' => 'off',
                    'style' => '
                        width: 250px;
                        height: 40px;
                        color: orange;
                        text-align: center
                    ',
                ],
            ])
            ->add('username',null,[
                'label' => 'Username',
                'attr' => [
                    'class' => 'fs-6',
                    'placeholder' => "Enter your username ...",
                    'autocomplete' => 'off',
                    'style' => '
                        width: 250px;
                        height: 40px;
                        color: orange;
                        text-align: center
                        ',
                ],
            ])
            //->add('password')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
