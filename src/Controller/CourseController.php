<?php

namespace App\Controller;

use App\Entity\EvaluationMatiere;
use App\Entity\Matiere;
use App\Form\EvaluationMatiereType;  
use App\Repository\EvaluationMatiereRepository;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/courses')]
class CourseController extends AbstractController
{
    #[Route('', name: 'app_courses')]
    public function index(EvaluationMatiereRepository $courseRepository ,     Request $request): Response
    {

   
    $searchMatiere = $request->query->get('matiere');

    // 🔃 Tri
    $order = $request->query->get('order', 'ASC'); // ASC | DESC

    // 🔨 QueryBuilder
    $qb = $courseRepository->createQueryBuilder('c')
        ->leftJoin('c.evalMats', 'em')
        ->leftJoin('em.matiere', 'm')
        ->where('c.user = :user')
        ->setParameter('user', $this->getUser());

    // 🔍 Appliquer la recherche
    if ($searchMatiere) {
        $qb->andWhere('m.nomMatiere LIKE :matiere')
           ->setParameter('matiere', '%' . $searchMatiere . '%');
    }

    // 🔃 Appliquer le tri par nom de matière
    $qb->orderBy('m.nomMatiere', $order);

    

    $courses = $qb->getQuery()->getResult();

// ---------------------- AJOUTER ICI ----------------------
$successCount = 0;   // notes >= 60%
$failCount = 0;      // notes < 60%
$uniqueMatieres = [];

$totalProgress = 0;

foreach ($courses as $course) {
    // Calcul du pourcentage pour succès/échec
    if ($course->getNoteMaximaleEval() > 0) {
        $percentage = ($course->getScoreEval() / $course->getNoteMaximaleEval()) * 100;
        if ($percentage >= 60) {
            $successCount++;
        } else {
            $failCount++;
        }
    }

    // Matières uniques
    foreach ($course->getEvalMats() as $evalMat) {
        if ($evalMat->getMatiere()) {
            $uniqueMatieres[$evalMat->getMatiere()->getNomMatiere()] = true;
        }
    }

    // Calcul progress pour la moyenne
    if ($course->getNoteMaximaleEval() > 0) {
        $course->progress = round(($course->getScoreEval() / $course->getNoteMaximaleEval()) * 100);
    } else {
        $course->progress = 0;
    }
    $totalProgress += $course->progress;
}

$avgProgress = count($courses) > 0 ? round($totalProgress / count($courses)) : 0;
$totalMatieres = count($uniqueMatieres);
// ---------------------- FIN AJOUT ----------------------


        // ça renvoie des EvaluationMatiere
        $courses = $courseRepository->findBy(
            ['user' => $this->getUser()],
            ['dateEvaluation' => 'DESC']
        );

       $totalNotes = 0;
    $totalProgress = 0;

    foreach ($courses as $course) {
    $matieresNames = [];

foreach ($course->getEvalMats() as $evalMat) {
    if ($evalMat->getMatiere()) {
        $matieresNames[] = $evalMat->getMatiere()->getNomMatiere();
    }
}

$course->nomMatiere = $matieresNames
    ? implode(', ', array_unique($matieresNames))
    : 'Unnamed Course';


    
    
        
        $course->instructor = 'Unknown';
        $course->notes_count = 0;
        $course->assignments_count = 0;
        
        // Calculer le progrès
        if ($course->getNoteMaximaleEval() && $course->getNoteMaximaleEval() > 0) {
            $course->progress = round(($course->getScoreEval() / $course->getNoteMaximaleEval()) * 100);
        } else {
            $course->progress = 0;
        }
        
        // Date d'évaluation
        if ($course->getDateEvaluation()) {
            $course->schedule = $course->getDateEvaluation()->format('d/m/Y à H:i');
        } else {
            $course->schedule = 'No schedule';
        }
        
        $course->color = 'indigo';
        $totalProgress += $course->progress;
    }

    $avgProgress = count($courses) > 0 ? round($totalProgress / count($courses)) : 0;

    return $this->render('pages/courses/index.html.twig', [
        'courses' => $courses,
        'total_notes' => $totalNotes,
        'total_resources' => 0,
        'avg_progress' => $avgProgress,
        'avg_progress' => $avgProgress,
        'search_matiere' => $searchMatiere,
        'order' => $order,
        
    'stat_success' => $successCount,
    'stat_fail' => $failCount,
    'total_matieres' => $totalMatieres,
    
    


    ]);
    }





    
    #[Route('/get-matieres-by-section', name: 'app_courses_get_matieres_by_section', methods: ['GET'])]
    public function getMatieresBySection(
        Request $request,
        MatiereRepository $matiereRepository
    ): Response {
        $section = $request->query->get('section');
        
        if (!$section) {
            return $this->json([]);
        }
        
        // Récupérer les matières de cette section
        $matieres = $matiereRepository->createQueryBuilder('m')
            ->where('m.sectionMatiere = :section')
            ->setParameter('section', $section)
            ->orderBy('m.nomMatiere', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Formater les données pour JSON
        $data = [];
        foreach ($matieres as $matiere) {
            $data[] = [
                'id' => $matiere->getId(),
                'nom' => $matiere->getNomMatiere(),
                'code' => $matiere->getCode(),
            ];
        }
        
        return $this->json($data);
    }

#[Route('/new', name: 'app_courses_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    MatiereRepository $matiereRepository
): Response {
    $course = new EvaluationMatiere();

    // 1️⃣ Récupérer toutes les sections
    $sectionsRaw = $matiereRepository->createQueryBuilder('m')
        ->select('DISTINCT m.sectionMatiere')
        ->orderBy('m.sectionMatiere', 'ASC')
        ->getQuery()
        ->getScalarResult();

    $sections = array_map(fn($s) => $s['sectionMatiere'], $sectionsRaw);

    // 2️⃣ Récupérer toutes les matières et les grouper par section
    $allMatieres = $matiereRepository->findAll();
    $matieresBySection = [];

    foreach ($allMatieres as $matiere) {
        $matieresBySection[$matiere->getSectionMatiere()][] = $matiere;
    }
    

    // 3️⃣ Créer le formulaire
    $form = $this->createForm(EvaluationMatiereType::class, $course, [
        'user' => $this->getUser(),
        'sections' => $sections,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $course->setUser($this->getUser());
        $matieres = $form->get('matieres')->getData();

        $entityManager->persist($course);

        foreach ($matieres as $matiere) {
            $evalMat = new \App\Entity\EvalMat();
            $evalMat->setEvaluation($course);
            $evalMat->setMatiere($matiere);
            $entityManager->persist($evalMat);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Ajout réussi 🎉');
        return $this->redirectToRoute('app_courses');
    }

    return $this->render('pages/courses/new.html.twig', [
        'form' => $form->createView(),
        'sections' => $sections,
        'matieresBySection' => $matieresBySection, // ✅ important
    ]);
}




#[Route('/{id}', name: 'app_courses_show', requirements: ['id' => '\d+'])]
public function show(
    EvaluationMatiere $course
): Response {
    // Sécurité
    if ($course->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Vous n’avez pas accès à ce cours.');
    }

    // Matières liées à l’évaluation
    $matieres = [];
    foreach ($course->getEvalMats() as $evalMat) {
        $matiere = $evalMat->getMatiere();
        if ($matiere) {
            $matieres[] = [
                'id'  => $matiere->getId(),
                'nom' => $matiere->getNomMatiere(),
            ];
        }
    }

    // Pourcentage global (calculé AU BON ENDROIT)
    $percentage = 0;
    if ($course->getNoteMaximaleEval() > 0) {
        $percentage = round(
            ($course->getScoreEval() / $course->getNoteMaximaleEval()) * 100
        );
    }

    return $this->render('pages/courses/show.html.twig', [
        'course'     => $course,
        'matieres'   => $matieres,
        'percentage' => $percentage,
    ]);
}



    #[Route('/{id}/edit', name: 'app_courses_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    EvaluationMatiere $evaluation,
    EntityManagerInterface $entityManager,
    MatiereRepository $matiereRepository
): Response {

    // 1️⃣ Récupérer toutes les sections
    $sectionsRaw = $matiereRepository->createQueryBuilder('m')
        ->select('DISTINCT m.sectionMatiere')
        ->orderBy('m.sectionMatiere', 'ASC')
        ->getQuery()
        ->getScalarResult();
    $sections = array_map(fn($s) => $s['sectionMatiere'], $sectionsRaw);

    // 2️⃣ Récupérer toutes les matières et les grouper par section
    $allMatieres = $matiereRepository->findAll();
    $matieresBySection = [];
    foreach ($allMatieres as $matiere) {
        $matieresBySection[$matiere->getSectionMatiere()][] = $matiere;
    }

    // 3️⃣ Créer le formulaire
    $form = $this->createForm(EvaluationMatiereType::class, $evaluation, [
        'user' => $this->getUser(),
        'sections' => $sections,
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $matieres = $form->get('matieres')->getData();

        // Supprimer les matières précédentes pour éviter les doublons
        foreach ($evaluation->getEvalMats() as $evalMat) {
            $entityManager->remove($evalMat);
        }

        foreach ($matieres as $matiere) {
            $evalMat = new \App\Entity\EvalMat();
            $evalMat->setEvaluation($evaluation);
            $evalMat->setMatiere($matiere);
            $entityManager->persist($evalMat);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Évaluation mise à jour avec succès 🎉');
        return $this->redirectToRoute('app_courses');
    }

    return $this->render('pages/courses/edit.html.twig', [
        'form' => $form->createView(),
        'sections' => $sections,
        'matieresBySection' => $matieresBySection,
    ]);
}




    #[Route('/{id}/delete', name: 'app_courses_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        EvaluationMatiere $course,
        EntityManagerInterface $entityManager
    ): Response {
        if ($course->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($course);
        $entityManager->flush();

        $this->addFlash('success', 'Course deleted successfully!');
        return $this->redirectToRoute('app_courses');
    }

    #[Route('/{id}/pdf', name: 'app_courses_pdf', requirements: ['id' => '\d+'])]
    public function exportPdf(EvaluationMatiere $course): Response
{
    if ($course->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    // Calcul du pourcentage
    $percentage = 0;
    if ($course->getNoteMaximaleEval() > 0) {
        $percentage = round(($course->getScoreEval() / $course->getNoteMaximaleEval()) * 100, 2);
    }

    // Configuration Dompdf
    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    // Génération du HTML
    $html = $this->renderView('pages/courses/pdf.html.twig', [
        'course' => $course,
        'percentage' => $percentage,
    ]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
    $dompdf->output(),
    200,
    [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="evaluation_'.$course->getIdEval().'.pdf"',
    ]
);

}

}


