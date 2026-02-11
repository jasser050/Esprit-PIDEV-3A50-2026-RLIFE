<?php

namespace App\Form;

use App\Entity\Matiere;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
    'constraints' => [
        new Assert\NotBlank([
            'message' => 'The code is required'
        ]),
        new Assert\Length([
            'min' => 2,
            'max' => 10,
            'minMessage' => 'The code must contain at least {{ limit }} characters',
            'maxMessage' => 'The code must not exceed {{ limit }} characters'
        ]),
        new Assert\Regex([
            'pattern' => '/^[A-Z0-9-]+$/i',
            'message' => 'The code may contain only letters, numbers, and hyphens'
        ])
    ]
])

            ->add('nomMatiere', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom de la matière est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'The name must contain at least {{ limit }} characters',
                        'maxMessage' => 'The name must not exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'The description must not exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('coefficientMatiere', NumberType::class, [
                'constraints' => [
                   new Assert\NotBlank(['message' => 'The coefficient is required']),
                    new Assert\Positive(['message' => 'The coefficient must be a positive number'])
                ]
            ])
            ->add('heureMatiere', NumberType::class, [
                'constraints' => [
                   new Assert\NotBlank(['message' => 'The number of hours is required']),
                    new Assert\PositiveOrZero(['message' => 'The number of hours must be positive or zero'])
                ]
            ])
            ->add('sectionMatiere', ChoiceType::class, [
                'choices' => [
                    'Science' => 'Science',
                    'Literature' => 'Literature',
                    'Mathematics' => 'Mathematics',
                    'Computer Science' => 'Computer Science',
                    'Economics' => 'Economics',
                    'Technology' => 'Technology',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The section is required']),
                    new Assert\Choice([
                        'choices' => ['Science','Literature','Mathematics','Computer Science','Economics','Technology'],
                        'message' => 'Section invalide'
                    ])
                ]
            ])
            ->add('typeMatiere', ChoiceType::class, [
                'choices' => [
                    'Cours magistral' => 'Cours magistral',
                    'TD' => 'Travaux dirigés',
                    'TP' => 'Travaux pratiques',
                ],
                'placeholder' => 'Select a type',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'The subject type is required']),
                    new Assert\Choice([
                        'choices' => ['Cours magistral','Travaux dirigés','Travaux pratiques'],
                        'message' => 'Invalid subject type'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Matiere::class,
        ]);
    }
}
