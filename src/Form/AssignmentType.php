<?php

namespace App\Form;

use App\Entity\Assignment;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Task Title',
                'attr' => ['placeholder' => 'Ex: Write the final report', 'class' => 'input'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4, 'class' => 'textarea'],
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Start Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'input'],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Due Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'input'],
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'High' => 'Haute',
                    'Medium' => 'Moyenne',
                    'Low' => 'Basse',
                ],
                'attr' => ['class' => 'select'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'To Do' => 'À faire',
                    'In Progress' => 'En cours',
                    'Completed' => 'Terminé',
                    'Canceled' => 'Annulé',
                ],
                'attr' => ['class' => 'select'],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'titre',
                'label' => 'Associated Project',
                'placeholder' => 'Select a project',
                'attr' => ['class' => 'select'],
                'query_builder' => function ($projectRepository) use ($options) {
                    return $projectRepository->createQueryBuilder('p')
                        ->andWhere('p.user = :user')
                        ->setParameter('user', $options['user'])
                        ->orderBy('p.titre', 'ASC');
                },
            ])
        ;
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
