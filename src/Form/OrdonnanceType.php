<?php

namespace App\Form;

use App\Entity\Ordonnance;
use App\Entity\Rdv;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
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
            ->add('dateOrdonnance', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date ordonnance'
            ])
            ->add('idU', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'label' => 'Médecin'
            ])
            ->add('idRdv', EntityType::class, [
                'class' => Rdv::class,
                'choice_label' => 'id',
                'label' => 'Rendez-vous'
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
