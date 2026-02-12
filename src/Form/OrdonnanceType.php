<?php

namespace App\Form;

use App\Entity\Fiche;
use App\Entity\Medicament;
use App\Entity\Ordonnance;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrdonnanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('posologie', TextType::class, [
                'label' => 'Posologie'
            ])
            ->add('frequence', TextType::class, [
                'label' => 'Fréquence'
            ])
            ->add('dureeTraitement', IntegerType::class, [
                'label' => 'Durée du traitement (jours)'
            ])
            ->add('idU', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Médecin'
            ])
            ->add('id_fiche', EntityType::class, [
                'class' => Fiche::class,
                'choice_label' => 'id',
                'label' => 'Fiche Médicale'
            ])
            ->add('medicaments', EntityType::class, [
                'class' => Medicament::class,
                'choice_label' => 'nomMedicament',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Médicaments'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ordonnance::class,
        ]);
    }
}
