<?php

namespace App\Form;

use App\Entity\MissionVolunteer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('lieu')
            ->add('dateDebut')
            ->add('dateFin')
            ->add('statut')
            ->add('requiredSkills', TextType::class, [
                'required' => false,
                'label' => 'Competences requises (CSV)',
                'attr' => ['placeholder' => 'premiers soins, conduite, enseignement'],
            ])
            ->add('thematicTags', TextType::class, [
                'required' => false,
                'label' => 'Tags thematiques (CSV)',
                'attr' => ['placeholder' => 'medical, social, education'],
            ])
            ->add('criticalPeriods', TextType::class, [
                'required' => false,
                'label' => 'Periodes critiques (CSV)',
                'attr' => ['placeholder' => 'soir, weekend, matin'],
            ])
            ->add('targetAudience', TextType::class, [
                'required' => false,
                'label' => 'Public cible',
            ])
            ->add('difficultyLevel', IntegerType::class, [
                'required' => false,
                'label' => 'Difficulte (1-5)',
                'attr' => ['min' => 1, 'max' => 5],
            ])
            ->add('urgencyLevel', IntegerType::class, [
                'required' => false,
                'label' => 'Urgence (1-5)',
                'attr' => ['min' => 1, 'max' => 5],
            ])
            ->add('latitude', TextType::class, [
                'required' => false,
                'label' => 'Latitude',
            ])
            ->add('longitude', TextType::class, [
                'required' => false,
                'label' => 'Longitude',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Image de la mission (JPG/PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ]),
                ],
                'attr' => [
                    'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MissionVolunteer::class,
        ]);
    }
}
