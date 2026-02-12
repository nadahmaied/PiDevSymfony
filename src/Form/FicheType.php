<?php

namespace App\Form;

use App\Entity\Fiche;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FicheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poids', NumberType::class, [
                'label' => 'Poids (kg)'
            ])
            ->add('taille', NumberType::class, [
                'label' => 'Taille (cm)'
            ])
            ->add('grpSanguin', TextType::class, [
                'label' => 'Groupe Sanguin',
                'help' => 'Ex format: A+, O-, AB+'
            ])
            ->add('allergie', TextType::class, [
                'label' => 'Allergies',
                'required' => false
            ])
            ->add('maladieChronique', TextType::class, [
                'label' => 'Maladie Chronique',
                'required' => false
            ])
            ->add('tension', TextType::class, [
                'label' => 'Tension',
                'help' => 'Format: SYS/DIA (ex: 120/80)'
            ])
            ->add('glycemie', NumberType::class, [
                'label' => 'Glycémie'
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date'
            ])
            ->add('libelleMaladie', TextType::class, [
                'label' => 'Libellé de la maladie'
            ])
            ->add('gravite', ChoiceType::class, [
                'choices' => [
                    'Faible' => 'Faible',
                    'Moyenne' => 'Moyenne',
                    'Élevée' => 'Élevée',
                ],
                'label' => 'Gravité'
            ])
            ->add('recommandation', TextareaType::class, [
                'label' => 'Recommandations',
                'required' => false
            ])
            ->add('idU', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Patient'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Fiche::class,
        ]);
    }
}
