<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Project Title',
                'attr' => [
                    'placeholder' => 'Ex: Mobile app development',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Project title is required',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Title must be at least {{ limit }} characters long',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Describe your project in detail...',
                    'rows' => 5,
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Description is required',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Description must be at least {{ limit }} characters long',
                    ]),
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'required' => false,
                'empty_data' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Start date is required',
                    ]),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'End Date',
                'widget' => 'single_text',
                'required' => false,
                'empty_data' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'End date is required',
                    ]),
                    new Assert\GreaterThan([
                        'propertyPath' => 'parent.all[dateDebut].data',
                        'message' => 'End date must be later than start date',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'En attente',
                    'In Progress' => 'En cours',
                    'On Hold' => 'En pause',
                    'Completed' => 'Terminé',
                    'Canceled' => 'Annulé',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Status is required',
                    ]),
                ],
            ])
        ;

        // Ensure dateDebut/dateFin are always populated before mapping.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            $dateDebut = trim((string) ($data['dateDebut'] ?? ''));
            $dateFin = trim((string) ($data['dateFin'] ?? ''));

            if ($dateDebut === '' && $dateFin === '') {
                $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
                $data['dateDebut'] = $today;
                $data['dateFin'] = $today;
            } elseif ($dateDebut === '') {
                $data['dateDebut'] = $dateFin;
            } elseif ($dateFin === '') {
                $data['dateFin'] = $dateDebut;
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
