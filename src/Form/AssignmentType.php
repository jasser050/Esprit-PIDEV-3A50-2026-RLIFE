<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\ProjectShare;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Task Title',
                'attr' => [
                    'placeholder' => 'Ex: Write the final report',
                    'class' => 'input',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Task title is required',
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
                    'rows' => 4,
                    'class' => 'textarea',
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
                    'class' => 'input',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Start date is required',
                    ]),
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Due Date',
                'widget' => 'single_text',
                'required' => false,
                'empty_data' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
                'attr' => [
                    'class' => 'input',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Due date is required',
                    ]),
                    new Assert\GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[dateDebut].data',
                        'message' => 'Due date must be later than or equal to start date',
                    ]),
                ],
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'High' => 'Haute',
                    'Medium' => 'Moyenne',
                    'Low' => 'Basse',
                ],
                'attr' => [
                    'class' => 'select',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Priority is required',
                    ]),
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'To Do' => 'Ã€ faire',
                    'In Progress' => 'En cours',
                    'Completed' => 'TerminÃ©',
                    'Canceled' => 'AnnulÃ©',
                ],
                'attr' => [
                    'class' => 'select',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Status is required',
                    ]),
                ],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'titre',
                'label' => 'Associated Project',
                'placeholder' => 'Select a project',
                'attr' => [
                    'class' => 'select',
                ],
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'You must select a project',
                    ]),
                ],
                'query_builder' => function ($projectRepository) use ($options) {
                    return $projectRepository->createQueryBuilder('p')
                        ->select('DISTINCT p')
                        ->leftJoin(
                            ProjectShare::class,
                            'ps',
                            'WITH',
                            'ps.project = p AND ps.sharedWithUser = :user AND ps.role = :editorRole'
                        )
                        ->andWhere('p.user = :user OR ps.id IS NOT NULL')
                        ->setParameter('user', $options['user'])
                        ->setParameter('editorRole', 'editor')
                        ->orderBy('p.titre', 'ASC');
                },
            ]);

        // Ensure dates are always present before mapping.
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
            'data_class' => Assignment::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', User::class]);
    }
}
