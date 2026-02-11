<?php

namespace App\Form;

use App\Entity\QuestionStress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextareaType::class, [
                'label' => 'Question Text (English)',
                'attr' => ['rows' => 3, 'placeholder' => 'Example: On a scale of 1 to 5, how stressed do you feel today?'],
            ])
            ->add('orderIndex', IntegerType::class, [
                'label' => 'Order Index',
                'attr' => ['min' => 1, 'placeholder' => '1 (first question)'],
            ])
            ->add('minValue', IntegerType::class, [
                'label' => 'Minimum Value',
                'attr' => ['min' => 1, 'placeholder' => '1 (default)'],
            ])
            ->add('maxValue', IntegerType::class, [
                'label' => 'Maximum Value',
                'attr' => ['max' => 5, 'placeholder' => '5 (default)'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionStress::class,
        ]);
    }
}