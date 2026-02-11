<?php

namespace App\Form;

use App\Entity\Annonce;
use App\Entity\Donation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeDon', null, [
                'required' => false,
            ])
            ->add('quantite', null, [
                'required' => false,
            ])
            ->add('dateDonation', null, [
                'required' => false,
            ])
            ->add('statut', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'En attente' => 'en attente',
                    'Accepté' => 'accepté',
                    'Refusé' => 'refusé',
                ],
            ])
            ->add('annonce', EntityType::class, [
                'class' => Annonce::class,
                'choice_label' => 'id',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Donation::class,
        ]);
    }
}
