<?php

namespace App\Form;

use App\Entity\Rdv;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
class RdvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('medecin', ChoiceType::class, [
        'choices' => [
            'Dr. Sarah Amrani' => 1,
            'Dr. Mohamed Kallel' => 2,
            'Dr. Karim Ben Youssef' => 3,
        ],
        'placeholder' => 'Choisir un médecin',
    ])
    ->add('motif', ChoiceType::class, [
        'choices' => [
            'En ligne' => 'EN_LIGNE',
            'Sur site' => 'SUR_SITE',
        ],
        'placeholder' => 'Choisir un motif',
    ])
    ->add('date', DateType::class, [
        'widget' => 'single_text', // 👈 CALENDRIER
    ])
    ->add('hdebut', TimeType::class, [
        'widget' => 'single_text', // 👈 input time
        'input' => 'string',
    ])
    ->add('message', TextareaType::class);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rdv::class,
        ]);
    }
}
