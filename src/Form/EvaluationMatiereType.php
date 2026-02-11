<?php

namespace App\Form;

use App\Entity\EvaluationMatiere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EvaluationMatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scoreEval', NumberType::class, [
                'label' => 'Score obtained',
                'attr' => [
                    'placeholder' => 'e.g. 15',
                    'min' => 0,
                    'step' => 0.01,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The obtained score is required']),
                    new Assert\PositiveOrZero(['message' => 'The score must be positive or zero']),
                ],
            ])
            ->add('noteMaximaleEval', NumberType::class, [
                'label' => 'Maximum score',
                'attr' => [
                    'placeholder' => 'e.g. 20',
                    'min' => 1,
                    'max' => 1000,
                    'step' => 0.01,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The maximum score is required']),
                    new Assert\Positive(['message' => 'The maximum score must be strictly positive']),
                ],
            ])
            ->add('dateEvaluation', DateTimeType::class, [
                'label' => 'Evaluation date',
                'widget' => 'single_text',
                'attr' => ['class' => 'input input-bordered w-full'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The evaluation date is required']),
                ],
            ])
            ->add('dureeEvaluation', IntegerType::class, [
                'label' => 'Duration (in minutes)',
                'attr' => [
                    'placeholder' => 'e.g. 60',
                    'min' => 1,
                    'max' => 600,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The duration is required']),
                    new Assert\Positive(['message' => 'The duration must be positive']),
                ],
            ])
            ->add('prioriteE', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Urgent' => 'urgent',
                ],
                'placeholder' => 'Select a priority',
                'required' => false,
                'attr' => ['class' => 'select select-bordered w-full'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvaluationMatiere::class,
        ]);
    }
}
