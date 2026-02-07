<?php

namespace App\Form;

use App\Entity\Flashcard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class FlashcardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Card Title (optional)',
                'required' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white',
                    'placeholder' => 'e.g., European Capitals'
                ],
            ])
            ->add('question', TextareaType::class, [
                'label' => 'Question / Front',
                'attr' => [
                    'rows' => 4,
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white resize-y',
                    'placeholder' => 'Enter your question here...',
                ],
            ])
            ->add('reponse', TextareaType::class, [
                'label' => 'Answer / Back',
                'attr' => [
                    'rows' => 6,
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white resize-y',
                    'placeholder' => 'Write the complete answer here...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Note (optional)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white resize-y',
                    'placeholder' => 'Add a comment or hint...',
                ],
            ])
            ->add('niveauDifficulte', IntegerType::class, [
                'label' => 'Difficulty Level (1 to 5)',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white',
                    'placeholder' => '1 = very easy, 5 = very difficult',
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'Current Status',
                'choices' => [
                    'To Learn' => 'a_apprendre',
                    'In Progress' => 'en_cours',
                    'Mastered' => 'maitrisee',
                ],
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white',
                ],
                'placeholder' => 'Choose a status',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optional)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'],
                    ])
                ],
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'PDF (optional)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'w-full px-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white',
                    'accept' => '.pdf'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flashcard::class,
        ]);
    }
}
