<?php

namespace App\Form;

use App\Entity\MissionVolunteer;
use App\Entity\Sponsor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType; // Import Important
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File; // Import Important

class SponsorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomSociete', TextType::class, [
                'label' => 'Nom de la Société'
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact'
            ])
            // Champ Fichier pour le Logo
            ->add('logo', FileType::class, [
                'label' => 'Logo de l\'entreprise (Image)',
                'mapped' => false, // Important : pas lié directement à la base
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG)',
                    ])
                ],
            ])
            ->add('missions', EntityType::class, [
                'class' => MissionVolunteer::class,
                'choice_label' => 'titre', // On affiche le titre de la mission
                'multiple' => true,
                'expanded' => true, // mettez 'false' pour une liste déroulante, 'true' pour des cases à cocher
                'label' => 'Missions sponsorisées'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sponsor::class,
        ]);
    }
}