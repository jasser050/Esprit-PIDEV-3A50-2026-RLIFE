<?php

namespace App\Form;

use App\Entity\Seance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class)
            ->add('typeSeance', ChoiceType::class, [
                'choices' => [
                    'Cours' => 'COURS',
                    'Révision' => 'REVISION',
                    'Examen' => 'EXAMEN',
                    'Pause' => 'PAUSE'
                ]
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text', // Important pour ton template HTML5
                'label' => false
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => false
            ])
            ->add('priorite', IntegerType::class)
            ->add('description', TextareaType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Seance::class]);
    }
}