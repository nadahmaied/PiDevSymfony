<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prenom',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Telephone',
                'required' => false,
            ])
            ->add('skillsProfile', TextType::class, [
                'label' => 'Competences (CSV)',
                'required' => false,
                'attr' => ['placeholder' => 'premiers soins, conduite, enseignement'],
            ])
            ->add('interestsProfile', TextType::class, [
                'label' => 'Centres d interet (CSV)',
                'required' => false,
                'attr' => ['placeholder' => 'enfants, seniors, environnement'],
            ])
            ->add('availabilityProfile', TextType::class, [
                'label' => 'Disponibilites (CSV)',
                'required' => false,
                'attr' => ['placeholder' => 'soir, weekend, matin'],
            ])
            ->add('preferredCity', TextType::class, [
                'label' => 'Ville preferee',
                'required' => false,
            ])
            ->add('actionRadiusKm', IntegerType::class, [
                'label' => 'Rayon d action (km)',
                'required' => false,
                'attr' => ['min' => 1, 'max' => 300],
            ])
            ->add('latitude', TextType::class, [
                'label' => 'Latitude',
                'required' => false,
            ])
            ->add('longitude', TextType::class, [
                'label' => 'Longitude',
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Patient' => 'ROLE_PATIENT',
                    'Médecin' => 'ROLE_MEDECIN',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
            ])
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
