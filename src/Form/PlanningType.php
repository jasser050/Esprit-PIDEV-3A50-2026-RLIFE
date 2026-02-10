<?php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // seance_id
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'titre', // nécessite getTitre() dans Seance
                'placeholder' => 'Choisir une séance',
                'label' => 'Séance',
            ])

            // On prend date + heure séparées pour être compatible avec ton ancienne UI
            // (on reconstruit dateDebut/dateFin dans le controller)
            ->add('date', DateType::class, [
                'label' => 'Date',
                'mapped' => false,
                'widget' => 'single_text',
            ])
            ->add('start_time', TimeType::class, [
                'label' => 'Heure début',
                'mapped' => false,
                'widget' => 'single_text',
            ])
            ->add('end_time', TimeType::class, [
                'label' => 'Heure fin',
                'mapped' => false,
                'widget' => 'single_text',
            ])

            // color (tailwind keys chez toi: indigo, teal...) => on garde en Choice
            ->add('color', ChoiceType::class, [
                'label' => 'Couleur',
                'required' => false,
                'choices' => [
                    'Indigo' => 'indigo',
                    'Teal' => 'teal',
                    'Amber' => 'amber',
                    'Blue' => 'blue',
                    'Green' => 'green',
                    'Red' => 'red',
                    'Purple' => 'purple',
                    'Pink' => 'pink',
                ],
            ])

            // feedback emojis (1..5)
            ->add('feedback', ChoiceType::class, [
                'label' => 'Feedback',
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    '😡' => 1,
                    '😕' => 2,
                    '😐' => 3,
                    '🙂' => 4,
                    '🤩' => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}