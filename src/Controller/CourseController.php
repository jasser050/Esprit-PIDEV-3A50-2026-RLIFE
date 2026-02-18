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
use App\Service\GoogleSearchService;
use App\Entity\EvalMat;
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
        $evaluation = new EvaluationMatiere();
        $allMatieres = $matiereRepository->findAll();

        $form = $this->createForm(EvaluationMatiereType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->addFlash('info', '✅ Formulaire soumis');

            // Vérifier si le formulaire est valide
            if (!$form->isValid()) {
                $this->addFlash('error', '❌ Formulaire invalide');

                // Afficher TOUTES les erreurs
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', '🔴 ' . $error->getMessage());
                }
            } else {
                $this->addFlash('info', '✅ Formulaire valide');
            }

            $matiereId = $request->request->get('matiere_id');
            $this->addFlash('info', 'Matière ID: ' . ($matiereId ?: 'VIDE'));

            if (!$matiereId) {
                $this->addFlash('error', '❌ Pas de matière sélectionnée');
            } else {
                $matiere = $matiereRepository->find((int)$matiereId);
                $this->addFlash('info', 'Matière: ' . ($matiere ? $matiere->getNomMatiere() : 'NULL'));

                if ($matiere && $form->isValid()) {
                    try {
                        $this->addFlash('info', '🚀 Début enregistrement...');

                        $evaluation->setUser($this->getUser());
                        $this->addFlash('info', '✅ User défini');

                        $entityManager->persist($evaluation);
                        $entityManager->flush();
                        $this->addFlash('info', '✅ Evaluation enregistrée - ID: ' . $evaluation->getIdEval());

                        $evalMat = new EvalMat();
                        $evalMat->setEvaluation($evaluation);
                        $evalMat->setMatiere($matiere);
                        $entityManager->persist($evalMat);
                        $entityManager->flush();
                        $this->addFlash('info', '✅ EvalMat enregistré - ID: ' . $evalMat->getId());

                        $this->addFlash('success', '🎉 Évaluation ajoutée avec succès !');
                        return $this->redirectToRoute('app_courses');

                    } catch (\Exception $e) {
                        $this->addFlash('error', '💥 Erreur SQL: ' . $e->getMessage());
                    }
                }
            }
        }

        return $this->render('pages/courses/new.html.twig', [
            'form' => $form->createView(),
            'allMatieres' => $allMatieres,
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
        $allMatieres = $matiereRepository->findAll();

        $form = $this->createForm(EvaluationMatiereType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $matiereId = $request->request->get('matiere_id');

            if ($matiereId) {
                $matiere = $matiereRepository->find((int)$matiereId);

                if ($matiere) {
                    foreach ($evaluation->getEvalMats() as $evalMat) {
                        $entityManager->remove($evalMat);
                    }

                    $evalMat = new EvalMat();
                    $evalMat->setEvaluation($evaluation);
                    $evalMat->setMatiere($matiere);
                    $entityManager->persist($evalMat);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Évaluation mise à jour avec succès 🎉');
            return $this->redirectToRoute('app_courses');
        }

        return $this->render('pages/courses/edit.html.twig', [
            'form' => $form->createView(),
            'evaluation' => $evaluation,
            'allMatieres' => $allMatieres,
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

    #[Route('/courses/{id}/recommendations', name: 'app_courses_recommendations')]
    public function recommendations(
        EvaluationMatiere $course,
        GoogleSearchService $searchService
    ): Response {
        $recommendations = [];
        $totalResults = 0;

        // Récupérer les matières de l'évaluation
        foreach ($course->getEvalMats() as $evalMat) {
            $matiere = $evalMat->getMatiere();
            $matiereName = $matiere->getNomMatiere();

            // Rechercher des cours pour chaque matière
            $courses = $searchService->searchCourses($matiereName, 5);

            // Obtenir le nombre total de résultats
            $searchInfo = $searchService->getSearchInfo("cours {$matiereName}");

            if (!empty($courses)) {
                $recommendations[] = [
                    'matiere' => $matiereName,
                    'code' => $matiere->getCode(),
                    'courses' => $courses,
                    'totalResults' => $searchInfo['totalResults'] ?? 0,
                ];

                $totalResults += count($courses);
            }
        }

        return $this->render('pages/courses/recommendations.html.twig', [
            'evaluation' => $course,
            'recommendations' => $recommendations,
            'totalResults' => $totalResults,
        ]);
    }

    /**
     * Recherche personnalisée par matière
     */
    #[Route('/courses/search/{matiere}', name: 'app_courses_search_matiere')]
    public function searchByMatiere(
        string $matiere,
        GoogleSearchService $searchService
    ): Response {
        // Recherche sur des sites spécifiques
        $sites = ['coursera.org', 'edx.org', 'khanacademy.org'];
        $results = $searchService->searchOnSites($matiere, $sites);

        return $this->render('pages/courses/search_results.html.twig', [
            'matiere' => $matiere,
            'results' => $results,
        ]);
    }

    /**
     * Recherche de ressources PDF
     */
    #[Route('/courses/{id}/pdf-resources', name: 'app_courses_pdf_resources')]
    public function pdfResources(
        EvaluationMatiere $course,
        GoogleSearchService $searchService
    ): Response {
        $pdfResources = [];

        foreach ($course->getEvalMats() as $evalMat) {
            $matiere = $evalMat->getMatiere()->getNomMatiere();

            // Rechercher uniquement des PDFs
            $results = $searchService->searchWithFilters("cours {$matiere}", [
                'fileType' => 'pdf',
                'num' => 3
            ]);

            if (!empty($results)) {
                $pdfResources[] = [
                    'matiere' => $matiere,
                    'pdfs' => $results,
                ];
            }
        }

        return $this->render('pages/courses/pdf_resources.html.twig', [
            'evaluation' => $course,
            'pdfResources' => $pdfResources,
        ]);
    }

}

