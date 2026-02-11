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
                'label' => 'Course Code',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: MATH101',
                    'maxlength' => 10
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The course code is required'
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
                'label' => 'Course Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Mathematics',
                    'maxlength' => 255
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The course name is required'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'The name must contain at least {{ limit }} characters',
                        'maxMessage' => 'The name must not exceed {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\'-]+$/u',
                        'message' => 'The name contains invalid characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Course description...',
                    'rows' => 4,
                    'maxlength' => 500
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'The description must not exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('coefficientMatiere', NumberType::class, [
                'label' => 'Coefficient',
                'required' => true,
                'attr' => [
                    'step' => '0.5',
                    'min' => '0.1',
                    'max' => '20'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The coefficient is required'
                    ]),
                    new Assert\Positive([
                        'message' => 'The coefficient must be a positive number'
                    ]),
                    new Assert\Range([
                        'min' => 0.1,
                        'max' => 20,
                        'notInRangeMessage' => 'The coefficient must be between {{ min }} and {{ max }}'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1})?$/',
                        'message' => 'The coefficient must have at most one decimal place'
                    ])
                ]
            ])
            ->add('heureMatiere', NumberType::class, [
                'label' => 'Hours per Week',
                'required' => true,
                'attr' => [
                    'step' => '0.5',
                    'min' => '0',
                    'max' => '40'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The number of hours is required'
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'The number of hours must be positive or zero'
                    ]),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 40,
                        'notInRangeMessage' => 'The number of hours must be between {{ min }} and {{ max }}'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1})?$/',
                        'message' => 'The hours must have at most one decimal place'
                    ])
                ]
            ])
            ->add('sectionMatiere', ChoiceType::class, [
                'label' => 'Section',
                'required' => true,
                'placeholder' => 'Select a section',
                'choices' => [
                    'Science' => 'Science',
                    'Literature' => 'Literature',
                    'Mathematics' => 'Mathematics',
                    'Computer Science' => 'Computer Science',
                    'Economics' => 'Economics',
                    'Technology' => 'Technology',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The section is required'
                    ]),
                    new Assert\Choice([
                        'choices' => ['Science', 'Literature', 'Mathematics', 'Computer Science', 'Economics', 'Technology'],
                        'message' => 'Invalid section. Please select a valid option.'
                    ])
                ]
            ])
            ->add('typeMatiere', ChoiceType::class, [
                'label' => 'Type',
                'required' => true,
                'placeholder' => 'Select a type',
                'choices' => [
                    'Lecture' => 'Cours magistral',
                    'Tutorial' => 'Travaux dirigés',
                    'Practical Work' => 'Travaux pratiques',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The course type is required'
                    ]),
                    new Assert\Choice([
                        'choices' => ['Cours magistral', 'Travaux dirigés', 'Travaux pratiques'],
                        'message' => 'Invalid course type. Please select a valid option.'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Matiere::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'matiere_item',
        ]);
    }
}