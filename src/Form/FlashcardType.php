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

class FlashcardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la carte',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex : Capitales d\'Europe, Théorème de Pythagore...',
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('question', TextareaType::class, [
                'label' => 'Question / Recto',
                'required' => true,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Pose ta question ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('reponse', TextareaType::class, [
                'label' => 'Réponse / Verso',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Écris la réponse complète ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Note (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ajoute un commentaire ou un indice...',
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('niveauDifficulte', ChoiceType::class, [
                'label' => 'Niveau de difficulté',
                'required' => true,
                'choices' => [
                    'Très facile (1)' => 1,
                    'Facile (2)' => 2,
                    'Moyen (3)' => 3,
                    'Difficile (4)' => 4,
                    'Très difficile (5)' => 5,
                ],
                'placeholder' => '-- Sélectionnez un niveau --',
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État actuel',
                'required' => true,
                'choices' => [
                    'Actif' => 'actif',
                    'Brouillon' => 'brouillon',
                    'Archivé' => 'archive',
                    'Inactif' => 'inactif',
                ],
                'data' => 'actif', // Valeur par défaut
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    'class' => 'w-full p-3 border rounded-lg',
                ]
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'PDF (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'accept' => 'application/pdf,.pdf',
                    'class' => 'w-full p-3 border rounded-lg',
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