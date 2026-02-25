<?php

namespace App\Form;

use App\Entity\RecommendationStress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class RecommendationStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'placeholder' => 'Recommendation title',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => [
                    'class' => 'textarea textarea-bordered w-full',
                    'rows' => 4,
                    'placeholder' => 'Detailed recommendation content...',
                ],
            ])
            ->add('level', ChoiceType::class, [
                'label' => 'Stress Level',
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ],
                'attr' => [
                    'class' => 'select select-bordered w-full',
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'checkbox checkbox-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RecommendationStress::class,
        ]);
    }
}
