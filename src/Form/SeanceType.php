<?php

namespace App\Form;

use App\Entity\Seance;
use App\Entity\TypeSeance;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class SeanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('titre')
            ->add('typeSeance', EntityType::class, [
                'class' => TypeSeance::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a type',
                'query_builder' => function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('t')->orderBy('t.name', 'ASC');
                    if ($user instanceof User) {
                        $qb->andWhere('t.user = :user OR t.user IS NULL')->setParameter('user', $user);
                    } else {
                        $qb->andWhere('1 = 0');
                    }
                    return $qb;
                },
            ])
            ->add('description')
            // ->add('save', SubmitType::class, ['label' => 'Save'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Seance::class,
            'user' => null,
        ]);
    }
}
