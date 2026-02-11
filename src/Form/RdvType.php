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
                    'Dr. Sarah Amrani' => 'Dr. Sarah Amrani',        // ✅ Changé de 1 à string
                    'Dr. Mohamed Kallel' => 'Dr. Mohamed Kallel',    // ✅ Changé de 2 à string
                    'Dr. Karim Ben Youssef' => 'Dr. Karim Ben Youssef', // ✅ Changé de 3 à string
                ],
                'placeholder' => 'Choisir un médecin',
            ])
            ->add('motif', ChoiceType::class, [
                'choices' => [
                    'En ligne' => 'en ligne',      // ✅ Changé à minuscules
                    'Sur site' => 'sur site',      // ✅ Changé à minuscules
                ],
                'placeholder' => 'Choisir un motif',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,               // ✅ Ajouté pour HTML5
            ])
            ->add('hdebut', TimeType::class, [
                'widget' => 'single_text',
                'html5' => true,               // ✅ Ajouté pour HTML5
                'input' => 'datetime',         // ✅ Changé de 'string' à 'datetime'
            ])
            
            
            ->add('message', TextareaType::class, [
                'required' => false,           // ✅ Ajouté required false
                'attr' => [
                    'rows' => 4,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rdv::class,
        ]);
    }
}