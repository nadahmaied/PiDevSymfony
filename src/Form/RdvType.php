<?php

namespace App\Form;

use App\Entity\Rdv;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RdvType extends AbstractType
{
    private const MEDECINS = [
        'Dr. Sarah Amrani'      => 'Dr. Sarah Amrani',
        'Dr. Mohamed Kallel'    => 'Dr. Mohamed Kallel',
        'Dr. Karim Ben Youssef' => 'Dr. Karim Ben Youssef',
    ];

    private const MOTIFS = [
        'En ligne' => 'en ligne',
        'Sur site' => 'sur site',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $medecinChoices = self::MEDECINS;
        $motifChoices   = self::MOTIFS;

        if ($options['rdv'] instanceof Rdv) {
            $rdv = $options['rdv'];
            if ($rdv->getMedecin() && !isset($medecinChoices[$rdv->getMedecin()])) {
                $medecinChoices = array_merge([$rdv->getMedecin() => $rdv->getMedecin()], $medecinChoices);
            }
            $motifValues = array_values($motifChoices);
            if ($rdv->getMotif() && !in_array($rdv->getMotif(), $motifValues, true)) {
                $motifChoices = array_merge([$rdv->getMotif() => $rdv->getMotif()], $motifChoices);
            }
        }

        $builder
            ->add('medecin', ChoiceType::class, [
                'choices'     => $medecinChoices,
                'placeholder' => 'Choisir un médecin',
            ])
            ->add('motif', ChoiceType::class, [
                'choices'     => $motifChoices,
                'placeholder' => 'Choisir un motif',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'html5'  => true,
            ])
            ->add('hdebut', TimeType::class, [
                'widget' => 'single_text',
                'html5'  => true,
                'input'  => 'datetime',
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'attr'     => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rdv::class,
            'rdv'        => null,
        ]);
        $resolver->setAllowedTypes('rdv', ['null', Rdv::class]);
    }
}