<?php

namespace App\Form;

use App\Entity\Volunteer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VolunteerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disponibilites', ChoiceType::class, [
                'choices'  => [
                    'Semaine (Matin)' => 'Semaine (Matin)',
                    'Semaine (Après-midi)' => 'Semaine (Après-midi)',
                    'Semaine (Soir)' => 'Semaine (Soir)',
                    'Week-end (Matin)' => 'Week-end (Matin)',
                    'Week-end (Après-midi)' => 'Week-end (Après-midi)',
                ],
                'multiple' => true, // Permet de cocher plusieurs cases
                'expanded' => true, // Affiche des cases à cocher (checkboxes) au lieu d'une liste déroulante
                'label' => 'Vos disponibilités pour cette mission',
                'attr' => ['class' => 'grid grid-cols-1 md:grid-cols-2 gap-4'] // Pour le style
            ])
            ->add('motivation', TextareaType::class, [
                'label' => 'Pourquoi cette mission ? (Motivation)',
                'attr' => [
                    'placeholder' => 'Bonjour, je souhaite participer car...',
                    'rows' => 4,
                    'class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500'
                ]
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Confirmer vos coordonnées',
                'attr' => [
                    'placeholder' => '+216 20 123 456',
                    'class' => 'w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pl-10'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Volunteer::class,
        ]);
    }
}