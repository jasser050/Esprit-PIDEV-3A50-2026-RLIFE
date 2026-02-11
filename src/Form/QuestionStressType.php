<?php

namespace App\Form;

use App\Entity\QuestionStress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class QuestionStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionNumber', IntegerType::class, [
                'label' => 'Question Number',
                'attr' => [
                    'class' => 'input input-bordered w-full',
                    'min' => 1,
                ],
            ])
            ->add('questionText', TextareaType::class, [
                'label' => 'Question Text',
                'attr' => [
                    'class' => 'textarea textarea-bordered w-full',
                    'rows' => 3,
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'checkbox checkbox-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionStress::class,
        ]);
    }
}