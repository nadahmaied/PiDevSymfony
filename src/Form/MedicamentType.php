<?php

namespace App\Form;

use App\Entity\Medicament;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomMedicament', TextType::class, [
                'label' => 'Nom du médicament'
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie'
            ])
            ->add('dosage', TextType::class, [
                'label' => 'Dosage'
            ])
            ->add('forme', TextType::class, [
                'label' => 'Forme'
            ])
            ->add('dateExpiration', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'expiration'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medicament::class,
        ]);
    }
}
