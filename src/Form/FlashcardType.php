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

class FlashcardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la carte (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Capitales d’Europe'],
            ])
            ->add('question', TextareaType::class, [
                'label' => 'Question / Recto',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Pose ta question ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
            ])
            ->add('reponse', TextareaType::class, [
                'label' => 'Réponse / Verso',
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Écris la réponse complète ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Note (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ajoute un commentaire ou un indice...',
                ],
            ])
            ->add('niveauDifficulte', IntegerType::class, [
                'label' => 'Niveau de difficulté (1 à 5)',
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                    'placeholder' => '1 = très facile, 5 = très difficile',
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État actuel',
                'choices' => [
                    'À apprendre' => 'a_apprendre',
                    'En cours' => 'en_cours',
                    'Maîtrisée' => 'maitrisee',
                ],
                'placeholder' => 'Choisir un état',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnel)',
                'required' => false,
                'mapped' => false, // important pour les fichiers uploadés
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('pdfFile', FileType::class, [
                'label' => 'PDF (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => '.pdf'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flashcard::class,
        ]);
    }
}