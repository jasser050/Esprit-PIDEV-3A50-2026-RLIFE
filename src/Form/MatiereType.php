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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\DateTime;

use Symfony\Component\Validator\Constraints\choice;
use Symfony\Component\Validator\Constraints\length; // pour score pouvant être 0

class MatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code du cours',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: MATH101',
                    'maxlength' => 10,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code du cours est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 10,
                        'minMessage' => 'Le code doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le code ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z0-9-]+$/i',
                        'message' => 'Le code ne peut contenir que des lettres, chiffres et tirets',
                    ]),
                ],
            ])
            ->add('nomMatiere', TextType::class, [
                'label' => 'Nom du cours',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Mathématiques',
                    'maxlength' => 255,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du cours est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Description du cours...',
                    'rows' => 4,
                    'maxlength' => 500,
                    'class' => 'textarea textarea-bordered w-full',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('coefficientMatiere', NumberType::class, [
                'label' => 'Coefficient',
                'required' => true,
                'attr' => [
                    'min' => 0.1,
                    'max' => 20,
                    'step' => 0.5,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'the coefficient is required']),
                    new Assert\Positive(['message' => 'Le coefficient doit être positif']),
                    new Assert\Range([
                        'min' => 0.1,
                        'max' => 20,
                        'notInRangeMessage' => 'Le coefficient doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ])
            ->add('heureMatiere', NumberType::class, [
                'label' => 'Heures par semaine',
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => 40,
                    'step' => 0.5,
                    'class' => 'input input-bordered w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre d\'heures est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'Le nombre d\'heures doit être zéro ou positif']),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 40,
                        'notInRangeMessage' => 'Le nombre d\'heures doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ])
            ->add('sectionMatiere', ChoiceType::class, [
                'label' => 'Section',
                'placeholder' => 'select a section',
                'attr' => [
                    'class' => 'form-control',
                ],
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
                        'message' => 'La section est obligatoire',
                    ]),
                    new Assert\Choice([
                        'choices' => ['Science', 'Literature', 'Mathematics', 'Computer Science', 'Economics', 'Technology'],
                        'message' => 'Section invalide. Veuillez sélectionner une option valide.',
                    ]),
                ],
            ])
            ->add('typeMatiere', ChoiceType::class, [
                'label' => 'Type',
                'placeholder' => 'Sélectionnez un type',
                'attr' => [
                    'class' => 'form-control',
                ],
                'choices' => [
                    'Cours magistral' => 'Cours magistral',
                    'TD' => 'Travaux dirigés',
                    'TD' => 'Travaux pratiques',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le type de cours est obligatoire',
                    ]),
                    new Assert\Choice([
                        'choices' => ['Cours magistral', 'Travaux dirigés', 'Travaux pratiques'],
                        'message' => 'Type de cours invalide. Veuillez sélectionner une option valide.',
                    ]),
                ],
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
