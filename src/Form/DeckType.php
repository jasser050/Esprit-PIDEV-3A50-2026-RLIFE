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
        $isEdit = $options['data'] && $options['data']->getId() !== null;

        $builder
            ->add('titre', TextType::class, [
                'label'    => 'Titre du Deck *',
                'required' => false, // On laisse les contraintes de l'entité faire le travail
                'attr'     => [
                    'placeholder' => 'Exemple : Biologie - Système nerveux, Vocabulaire Espagnol A2...',
                ],
            ])
            ->add('matiere', TextType::class, [
                'label'    => 'Matière *',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Exemple : Biologie, Anglais, Mathématiques...',
                ],
            ])
            ->add('niveau', TextType::class, [
                'label'    => 'Niveau *',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Exemple : Lycée, Licence, B1, Terminale...',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description *',
                'required' => false,
                'attr'     => [
                    'rows'        => 4,
                    'placeholder' => 'Décrivez le contenu, les objectifs, les chapitres ou compétences abordées...',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'     => $isEdit ? 'Image de couverture' : 'Image de couverture *',
                'mapped'    => false,
                'required'  => !$isEdit,
                'constraints' => $isEdit ? [
                    new Assert\File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG, WEBP, GIF seulement)',
                        'maxSizeMessage'   => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ] : [
                    new Assert\NotNull(['message' => 'L\'image est obligatoire']),
                    new Assert\File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Format invalide (JPG, PNG, WEBP, GIF seulement)',
                        'maxSizeMessage'   => 'L\'image ne doit pas dépasser 5 Mo',
                    ]),
                ],
            ])
            ->add('pdfFile', FileType::class, [
                'label'     => $isEdit ? 'Fichier PDF' : 'Fichier PDF *',
                'mapped'    => false,
                'required'  => !$isEdit,
                'constraints' => $isEdit ? [
                    new Assert\File([
                        'maxSize'          => '10M',
                        'mimeTypes'        => ['application/pdf'],
                        'mimeTypesMessage' => 'Format invalide (PDF seulement)',
                        'maxSizeMessage'   => 'Le PDF ne doit pas dépasser 10 Mo',
                    ]),
                ] : [
                    new Assert\NotNull(['message' => 'Le PDF est obligatoire']),
                    new Assert\File([
                        'maxSize'          => '10M',
                        'mimeTypes'        => ['application/pdf'],
                        'mimeTypesMessage' => 'Format invalide (PDF seulement)',
                        'maxSizeMessage'   => 'Le PDF ne doit pas dépasser 10 Mo',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Deck::class,
        ]);
    }
}
