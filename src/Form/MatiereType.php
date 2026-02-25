<?php

namespace App\Form;

use App\Entity\Matiere;
use App\Entity\User;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatiereType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class)
            ->add('nomMatiere', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('coefficientMatiere', NumberType::class)
            ->add('heureMatiere', NumberType::class)
            ->add('sectionMatiere', ChoiceType::class, [
                'choices' => [
                    'choices' => [
                        'Science'          => 'Science',
                        'Literature'       => 'Literature',
                        'Mathematics'      => 'Mathematics',
                        'Computer Science' => 'Computer Science',
                        'Economics'        => 'Economics',
                        'Technology'       => 'Technology',
],




                ],
            ])
            ->add('typeMatiere', ChoiceType::class, [
                'choices' => [
                    'Cours magistral' => 'Cours magistral',
                    'TD' => 'Travaux dirigés',
                    'TP' => 'Travaux pratiques',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Matiere::class,
        ]);
    }
}
