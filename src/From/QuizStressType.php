<?php

namespace App\Form;

use App\Entity\QuizStress;
use App\Entity\QuestionStress;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Quiz Title'
            ])
            ->add('questions', EntityType::class, [
                'class' => QuestionStress::class,
                'choice_label' => 'questionText',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Select Questions',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuizStress::class,
        ]);
    }
}
