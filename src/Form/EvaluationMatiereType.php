<?php

namespace App\Form;

use App\Entity\EvaluationMatiere;
use App\Entity\Matiere;
use App\Repository\MatiereRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class EvaluationMatiereType extends AbstractType
{
    private MatiereRepository $matiereRepository;

    // Injection du repository
    public function __construct(MatiereRepository $matiereRepository)
    {
        $this->matiereRepository = $matiereRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'] ?? null;

         $sectionsRaw = $this->matiereRepository->createQueryBuilder('m')
            ->select('DISTINCT m.sectionMatiere')
            ->orderBy('m.sectionMatiere', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $sections = array_map(fn($s) => $s['sectionMatiere'], $sectionsRaw);

        

        // =====================
        // MATIÈRES : filtrées selon la section
        // =====================
        $builder->add('matieres', EntityType::class, [
            'class' => Matiere::class,
            'choice_label' => 'nomMatiere',
            'multiple' => true,
            'expanded' => true,
            'mapped' => false,
            'choices' => [], // rempli dynamiquement via PRE_SUBMIT
        ]);
        $builder
    ->add('matieres', EntityType::class, [
        'class' => Matiere::class,
        'choice_label' => 'nomMatiere',
        'multiple' => true,
        'mapped' => false,
    ]);


        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($user) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!isset($data['section'])) {
                return;
            }

            $section = $data['section'];

            $form->add('matieres', EntityType::class, [
                'class' => Matiere::class,
                'choice_label' => 'nomMatiere',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'query_builder' => function (MatiereRepository $repo) use ($section, $user) {
                    $qb = $repo->createQueryBuilder('m')
                        ->where('m.sectionMatiere = :section')
                        ->setParameter('section', $section)
                        ->orderBy('m.nomMatiere', 'ASC');

                    if ($user) {
                        $qb->andWhere('m.user = :user')
                           ->setParameter('user', $user);
                    }

                    return $qb;
                },
                'constraints' => [
                    new Assert\Count([
                        'min' => 1,
                        'minMessage' => 'Please select at least one subject'
                    ])
                ]
            ]);
        });

        // =====================
        // AUTRES CHAMPS
        // =====================
        $builder
            ->add('scoreEval', NumberType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\PositiveOrZero()]
            ])
            ->add('noteMaximaleEval', NumberType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Positive()]
            ])
            ->add('dateEvaluation', DateTimeType::class, [
                'widget' => 'single_text'
            ])
            ->add('dureeEvaluation', IntegerType::class, [
                'constraints' => [new Assert\Positive()]
            ])
            ->add('prioriteE', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                    'Urgent' => 'urgent'
                ],
                'required' => false
            
            ]);
    }

  public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => EvaluationMatiere::class,
        'user' => null,        // option facultative
        'sections' => [],      // <-- définir ici pour qu'elle soit reconnue
    ]);

    // Si tu veux rendre user et sections explicitement définissables
    $resolver->setDefined(['user', 'sections']);
}


}
