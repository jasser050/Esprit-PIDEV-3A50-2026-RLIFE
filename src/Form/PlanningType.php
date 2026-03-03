<?php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'titre',
                'label' => 'Session',
                'placeholder' => 'Choose a session',
            ])

            // Un seul champ "date", obligatoire
            ->add('date', DateType::class, [
                'label' => 'Date',
                'mapped' => false,
                'widget' => 'single_text',
                
            ])

            ->add('start_time', TimeType::class, [
                'label' => 'Start Time',
                'mapped' => false,
                'widget' => 'single_text',
                
            ])

            ->add('end_time', TimeType::class, [
                'label' => 'End Time',
                'mapped' => false,
                'widget' => 'single_text',
                
            ])

            // Couleurs
            ->add('color', ChoiceType::class, [
                'label' => 'Color',
                'placeholder' => 'Choose a color',
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
                'required' => true,
        'attr' => ['id' => 'color_event']
            ]);

    //        ->add('allDay', CheckboxType::class, [
    //     'label' => 'Toute la journée',
    //     'required' => false,
    // ]);

        // Option: on garantit le mapping dateDebut/dateFin à partir des 3 champs si besoin
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Planning $data */
            $data = $event->getData();
            $form = $event->getForm();
            $date = $form->get('date')->getData();
            $start = $form->get('start_time')->getData();
            $end = $form->get('end_time')->getData();

            if ($date && $start && $end) {
                $dateDebut = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $start->format('H:i:s'));
                $dateFin   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $end->format('H:i:s'));
                $data->setDateDebut($dateDebut);
                $data->setDateFin($dateFin);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
        ]);
    }
}