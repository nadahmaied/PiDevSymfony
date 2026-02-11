<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\Donation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AnnonceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titreAnnonce', null, [
                'required' => false,
            ])
            ->add('description', null, [
                'required' => false,
            ])
            ->add('datePublication', null, [
                'required' => false,
            ])
            ->add('urgence', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Faible' => 'faible',
                    'Moyenne' => 'moyenne',
                    'Élevée' => 'élevée',
                ],
            ])
            ->add('etatAnnonce', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Active' => 'active',
                    'Clôturée' => 'clôturée',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Annonce::class,
        ]);
    }
}
