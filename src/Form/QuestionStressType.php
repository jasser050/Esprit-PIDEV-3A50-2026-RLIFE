<?php

namespace App\Form;

use App\Entity\QuestionStress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class QuestionStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionText', TextareaType::class, [
                'label' => 'Texte de la question',
                'attr' => [
                    'class' => 'textarea textarea-bordered w-full',
                    'rows' => 4,
                    'placeholder' => 'Saisissez votre question...',
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'empty_data' => '0',
                'attr' => ['class' => 'checkbox checkbox-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionStress::class,
        ]);
    }
}
