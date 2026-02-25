<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'First name',
                'attr' => [
                    'placeholder' => 'John',
                    'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                    'autocomplete' => 'given-name'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'First name is required'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'First name must be at least {{ limit }} characters',
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s\-\']+$/',
                        'message' => 'First name can only contain letters, spaces, hyphens and apostrophes'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Last name',
                'attr' => [
                    'placeholder' => 'Doe',
                    'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                    'autocomplete' => 'family-name'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Last name is required'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Last name must be at least {{ limit }} characters',
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z\s\-\']+$/',
                        'message' => 'Last name can only contain letters, spaces, hyphens and apostrophes'
                    ])
                ]
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'johndoe',
                    'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                    'autocomplete' => 'username'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Username is required'
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Username must be at least {{ limit }} characters',
                        'maxMessage' => 'Username cannot be longer than {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9_]+$/',
                        'message' => 'Username can only contain letters, numbers and underscores'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'placeholder' => 'you@university.edu',
                    'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                    'autocomplete' => 'email'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Email is required'
                    ]),
                    new Assert\Email([
                        'message' => 'Please enter a valid email address'
                    ]),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'Email cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => 'Create a strong password',
                        'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirm password',
                    'attr' => [
                        'placeholder' => 'Confirm your password',
                        'class' => 'block w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'invalid_message' => 'Password fields must match',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Password is required'
                    ]),
                    new Assert\Length([
                        'min' => 8,
                        'max' => 4096,
                        'minMessage' => 'Password must be at least {{ limit }} characters'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number'
                    ])
                ]
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Male' => 'male',
                    'Female' => 'female',
                ],
                'expanded' => true,
                'data' => 'male',
                'attr' => [
                    'class' => 'gender-radio'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Gender is required'
                    ]),
                    new Assert\Choice([
                        'choices' => ['male', 'female'],
                        'message' => 'Please select a valid gender'
                    ])
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'I agree to the Terms of Service and Privacy Policy',
                'mapped' => false,
                'attr' => [
                    'class' => 'mt-1 w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500 bg-slate-50 dark:bg-slate-800'
                ],
                'constraints' => [
                    new Assert\IsTrue([
                        'message' => 'You must agree to the terms and conditions'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
