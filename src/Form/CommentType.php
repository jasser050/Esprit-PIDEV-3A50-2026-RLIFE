<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Commentaire',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Ajouter un commentaire...',
                    'class' => 'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le commentaire ne peut pas être vide'
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Le commentaire doit contenir au moins {{ limit }} caractères',
                        'max' => 2000,
                        'maxMessage' => 'Le commentaire ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
