<?php

namespace App\Form;

use App\Entity\Deck;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DeckType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Détermine si on est en mode édition ou création
        $isEdit = $options['data'] && $options['data']->getIdDeck() !== null;

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre *',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Exemple : Biologie - Système nerveux...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 255]),
                    new Assert\Regex([
                        'pattern' => '/^[^\d].*$/',
                        'message' => 'Le titre ne doit pas commencer par un chiffre',
                    ]),
                ],
            ])

            ->add('matiere', TextType::class, [
                'label' => 'Matière *',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Mathématiques',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La matière est obligatoire']),
                    new Assert\Length(['min' => 3, 'max' => 100]),
                ],
            ])

            ->add('niveau', TextType::class, [
                'label' => 'Niveau *',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : 3ème, Bac, Terminale...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le niveau est obligatoire']),
                    new Assert\Length(['min' => 1, 'max' => 50]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le contenu...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire']),
                    new Assert\Length(['max' => 2000]),
                ],
            ])

            ->add('imageFile', FileType::class, [
                'label' => $isEdit ? 'Image de couverture' : 'Image de couverture *',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => $isEdit ? [
                    // En mode EDIT : image optionnelle (seulement si fournie)
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG, WEBP, GIF seulement)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ] : [
                    // En mode CREATE : image obligatoire
                    new Assert\NotNull(['message' => 'L\'image est obligatoire']),
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG, WEBP, GIF seulement)',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ],
            ])

            ->add('pdfFile', FileType::class, [
                'label' => $isEdit ? 'Fichier PDF' : 'Fichier PDF *',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => $isEdit ? [
                    // En mode EDIT : PDF optionnel (seulement si fourni)
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Format invalide (PDF seulement)',
                        'maxSizeMessage' => 'Le PDF ne doit pas dépasser 10 Mo',
                    ]),
                ] : [
                    // En mode CREATE : PDF obligatoire
                    new Assert\NotNull(['message' => 'Le PDF est obligatoire']),
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Format invalide (PDF seulement)',
                        'maxSizeMessage' => 'Le PDF ne doit pas dépasser 10 Mo',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Deck::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
