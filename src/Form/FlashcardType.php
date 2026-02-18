<?php

namespace App\Form;

use App\Entity\Flashcard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints as Assert;

class FlashcardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Card Title',
                'required' => true,
                'attr' => [
                    'placeholder' => 'e.g., European Capitals, Pythagorean Theorem...',
                    'class' => 'w-full p-3 border rounded-lg',
                    'minlength' => 3,
                    'maxlength' => 255,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Title is required'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Title must be at least {{ limit }} characters',
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[^0-9]/',
                        'message' => 'Title cannot start with a number'
                    ])
                ]
            ])
            ->add('question', TextareaType::class, [
                'label' => 'Question / Front',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Write your question here...',
                    'class' => 'w-full p-3 border rounded-lg',
                    'minlength' => 5,
                    'maxlength' => 2000,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Question is required'
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 2000,
                        'minMessage' => 'Question must be at least {{ limit }} characters',
                        'maxMessage' => 'Question cannot exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('reponse', TextareaType::class, [
                'label' => 'Answer / Back',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Write the complete answer here...',
                    'class' => 'w-full p-3 border rounded-lg',
                    'minlength' => 1,
                    'maxlength' => 2000,
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Answer is required'
                    ]),
                    new Assert\Length([
                        'min' => 1,
                        'max' => 2000,
                        'minMessage' => 'Answer must be at least {{ limit }} character',
                        'maxMessage' => 'Answer cannot exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Note (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Add a comment or hint...',
                    'class' => 'w-full p-3 border rounded-lg',
                    'maxlength' => 2000,
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 2000,
                        'maxMessage' => 'Description cannot exceed {{ limit }} characters'
                    ])
                ]
            ])
            ->add('niveauDifficulte', ChoiceType::class, [
                'label' => 'Difficulty Level',
                'required' => true,
                'choices' => [
                    'Very Easy (1)' => 1,
                    'Easy (2)' => 2,
                    'Medium (3)' => 3,
                    'Hard (4)' => 4,
                    'Very Hard (5)' => 5,
                ],
                'placeholder' => '-- Select a level --',
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Difficulty level is required'
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'Difficulty level must be between {{ min }} and {{ max }}'
                    ])
                ]
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'Current Status',
                'required' => true,
                'choices' => [
                    'Active' => 'actif',
                    'Draft' => 'brouillon',
                    'Archived' => 'archive',
                    'Inactive' => 'inactif',
                ],
                'data' => 'actif', // Default value
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Status is required'
                    ]),
                    new Assert\Choice([
                        'choices' => ['actif', 'brouillon', 'archive', 'inactif'],
                        'message' => 'Status must be: active, draft, archived or inactive'
                    ])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optional)',
                'required' => false,
                'mapped' => false, // Important: this field is not mapped directly to the entity
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'maxSizeMessage' => 'Image cannot exceed {{ limit }} {{ suffix }}',
                        'mimeTypesMessage' => 'Invalid image format. Accepted formats: JPG, PNG, WEBP, GIF'
                    ])
                ]
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'PDF (optional)',
                'required' => false,
                'mapped' => false, // Important: this field is not mapped directly to the entity
                'attr' => [
                    'accept' => 'application/pdf,.pdf',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'maxSizeMessage' => 'PDF cannot exceed {{ limit }} {{ suffix }}',
                        'mimeTypesMessage' => 'Only PDF files are accepted'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flashcard::class,
        ]);
    }
}