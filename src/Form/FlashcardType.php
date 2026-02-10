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
                'label' => 'Titre de la carte *',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Capitales d\'Europe, Théorème de Pythagore...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[^0-9]/',
                        'message' => 'Le titre ne doit pas commencer par un chiffre',
                    ]),
                ],
            ])

            ->add('question', TextareaType::class, [
                'label' => 'Question / Recto *',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Pose ta question ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La question est obligatoire']),
                    new Assert\Length([
                        'min' => 5,
                        'max' => 2000,
                        'minMessage' => 'La question doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La question ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('reponse', TextareaType::class, [
                'label' => 'Réponse / Verso *',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Écris la réponse complète ici...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La réponse est obligatoire']),
                    new Assert\Length([
                        'min' => 1,
                        'max' => 2000,
                        'minMessage' => 'La réponse doit contenir au moins {{ limit }} caractère',
                        'maxMessage' => 'La réponse ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description / Note (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ajoute un commentaire ou un indice...',
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 2000,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])

            ->add('niveauDifficulte', ChoiceType::class, [
                'label' => 'Niveau de difficulté *',
                'required' => false,
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
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le niveau de difficulté est obligatoire']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'Le niveau de difficulté doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
            ])

            ->add('etat', ChoiceType::class, [
                'label' => 'État actuel *',
                'required' => false,
                'choices' => [
                    'Actif' => 'actif',
                    'Brouillon' => 'brouillon',
                    'Archivé' => 'archive',
                    'Inactif' => 'inactif',
                ],
                'data' => 'actif', // Valeur par défaut
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'état est obligatoire']),
                    new Assert\Choice([
                        'choices' => ['actif', 'brouillon', 'archive', 'inactif'],
                        'message' => 'L\'état doit être : actif, brouillon, archive ou inactif',
                    ]),
                ],
            ])

            ->add('imageFile', FileType::class, [
                'label' => 'Image (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                    // Pas d'accept ici → on laisse le navigateur proposer ce qu'il veut, la validation est en PHP
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
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser {{ limit }} {{ suffix }}',
                        'mimeTypesMessage' => 'Format d\'image invalide. Formats acceptés : JPG, PNG, WEBP, GIF',
                    ]),
                ],
            ])

            ->add('pdfFile', FileType::class, [
                'label' => 'PDF (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'w-full p-3 border rounded-lg',
                    // Pas d'accept ici non plus
                ],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'maxSizeMessage' => 'Le PDF ne doit pas dépasser {{ limit }} {{ suffix }}',
                        'mimeTypesMessage' => 'Seuls les fichiers PDF sont acceptés',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Flashcard::class,
            'attr' => ['novalidate' => 'novalidate'], // Bloque toute validation HTML5 côté navigateur
        ]);
    }
}