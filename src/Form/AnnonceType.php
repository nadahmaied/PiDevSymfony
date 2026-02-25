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
            ->add('titreAnnonce')
            ->add('description')
            ->add('datePublication')
            ->add('urgence', ChoiceType::class, [
                'choices' => [
                    'Faible' => 'faible',
                    'Moyenne' => 'moyenne',
                    'Élevée' => 'élevée',
                ],
            ])
            ->add('etatAnnonce', ChoiceType::class, [
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
