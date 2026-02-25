<?php

namespace App\Form;

use App\Entity\EvaluationMatiere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;
class EvaluationMatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('scoreEval', IntegerType::class, [
                'label' => 'Score obtained',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'The obtained score is required']),
                    new PositiveOrZero(['message' => 'The score must be zero or positive']),
                ],
                'attr' => [
                    'min' => 0,
                ],
            ])

            ->add('noteMaximaleEval', IntegerType::class, [
                'label' => 'Maximum score',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'The maximum score is required']),
                    new Positive(['message' => 'The maximum score must be positive']),
                ],
                'attr' => [
                    'min' => 1,
                    'max' => 100,
                ],
            ])

            ->add('dateEvaluation', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a date']),
                ],
            ])

            ->add('dureeEvaluation', IntegerType::class, [
                'label' => 'Duration (in minutes)',
                'required' => true,
                'attr' => [
                    'placeholder' => 'e.g. 60',
                    'min' => 1,
                    'max' => 600,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'The duration is required']),
new Range([                          // ← remplace Positive
            'min'               => 1,
            'max'               => 600,
            'notInRangeMessage' => 'Duration must be between {{ min }} and {{ max }} minutes.',
        ]),                ],
            ])

            // 🔥 Priority — validation ajoutée
            ->add('prioriteE', ChoiceType::class, [
                'label'       => 'Priority',
                'choices'     => [
                    'Low'    => 'low',
                    'Medium' => 'medium',
                    'High'   => 'high',
                    'Urgent' => 'urgent',
                ],
                'placeholder' => 'Select a priority',
                'required'    => true,   // <-- était false, maintenant true
                'constraints' => [
                    new NotBlank(['message' => 'Please select a priority level.']),
                    new Assert\NotNull(['message' => 'Priority cannot be null.']),
                    new Assert\Choice([
                        'choices' => ['low', 'medium', 'high', 'urgent'],
                        'message' => 'Invalid priority value "{{ value }}". Choose: low, medium, high or urgent.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvaluationMatiere::class,
        ]);
    }
}
