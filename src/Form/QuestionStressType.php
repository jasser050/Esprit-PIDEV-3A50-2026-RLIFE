<?php

namespace App\Form;

use App\Entity\QuestionStress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionStressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('questionNumber_ques')
            ->add('questionText_ques')
            ->add('isActive_ques')
            ->add('createdAt_ques')
            ->add('updatedAt_ques')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionStress::class,
        ]);
    }
}
