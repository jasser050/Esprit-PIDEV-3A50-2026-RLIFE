<?php

namespace App\Controller;

use App\Entity\Matiere;
use App\Entity\Seance;
use App\Entity\Planning;
use App\Entity\TypeSeance;
use App\Service\AIPlanningService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AIPlanningController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private AIPlanningService $aiPlanningService;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, AIPlanningService $aiPlanningService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->aiPlanningService = $aiPlanningService;
        $this->logger = $logger;
    }

    #[Route('/ai/planning', name: 'app_ai_planning')]
    public function index(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->render('ai_planning/index.html.twig', [
                'planningData' => ['error' => 'User not connected'],
                'startDate' => '',
                'endDate' => '',
                'sessionTypes' => $this->entityManager->getRepository(TypeSeance::class)->findAll()
            ]);
        }

        $startDate = $request->query->get('start_date', '');
        $endDate   = $request->query->get('end_date', '');

        $planningData = [
            'message' => 'Please choose a start date and end date to generate AI suggestions.',
            'suggestions' => [],
            'priorities' => [],
            'stats' => null
        ];

        $sessionTypes = $this->entityManager->getRepository(TypeSeance::class)->findAll();

        if (!empty($startDate) && !empty($endDate)) {
            try {
                $start = new \DateTime($startDate);
                $end   = new \DateTime($endDate);
                $planningData = $this->aiPlanningService->generateSmartPlanning($start, $end, true, []);
            } catch (\Throwable $e) {
                $planningData = ['error' => 'Invalid date format. Use YYYY-MM-DD.'];
            }
        }

        return $this->render('ai_planning/index.html.twig', [
            'planningData' => $planningData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sessionTypes' => $sessionTypes
        ]);
    }

    #[Route('/ai/planning/suggestions', name: 'app_ai_planning_suggestions', methods: ['GET'])]
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) return new JsonResponse(['success' => false, 'error' => 'User not connected'], 401);

            $startDate = $request->query->get('start_date', '');
            $endDate   = $request->query->get('end_date', '');
            if (empty($startDate) || empty($endDate)) return new JsonResponse(['success' => false, 'error' => 'Start date and end date are required'], 400);

            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => 'Invalid dates: ' . $e->getMessage()], 400);
            }

            // read options from query
            $opts = [
                'seed' => $request->query->get('seed', null),
                'noise' => (float)$request->query->get('noise', 0.06),
                'slotUseRatio' => (float)$request->query->get('slotUseRatio', 0.7),
                'maxPerDay' => (int)$request->query->get('maxPerDay', 2)
            ];

            $planning = $this->aiPlanningService->generateSmartPlanning($start, $end, true, $opts);

            if (isset($planning['error'])) {
                return new JsonResponse(['success' => false, 'error' => $planning['error']], 500);
            }

            // map suggestions to safe array
            $out = [];
            foreach ($planning['suggestions'] as $s) {
                $out[] = [
                    'type' => $s['type'] ?? 'session',
                    'priority' => $s['priority'] ?? 0,
                    'priorityLevel' => $s['priorityLevel'] ?? 'Medium',
                    'color' => $s['color'] ?? '#4f46e5',
                    'suggestedTitle' => $s['suggestedTitle'] ?? '',
                    'suggestedDescription' => $s['suggestedDescription'] ?? '',
                    'slot' => [
                        'date' => isset($s['slot']['date']) && $s['slot']['date'] instanceof \DateTimeInterface ? $s['slot']['date']->format('Y-m-d') : (string)($s['slot']['date'] ?? ''),
                        'start' => isset($s['slot']['start']) && $s['slot']['start'] instanceof \DateTimeInterface ? $s['slot']['start']->format('Y-m-d H:i:s') : (string)($s['slot']['start'] ?? ''),
                        'end' => isset($s['slot']['end']) && $s['slot']['end'] instanceof \DateTimeInterface ? $s['slot']['end']->format('Y-m-d H:i:s') : (string)($s['slot']['end'] ?? ''),
                        'slot' => $s['slot']['slot'] ?? ''
                    ],
                    'subject' => isset($s['subject']) && is_object($s['subject']) && method_exists($s['subject'],'getId') ? ['id'=>$s['subject']->getId(), 'nom'=> (method_exists($s['subject'],'getNomMatiere') ? $s['subject']->getNomMatiere() : '')] : null,
                    'sessionType' => isset($s['sessionType']) && is_object($s['sessionType']) && method_exists($s['sessionType'],'getId') ? ['id'=>$s['sessionType']->getId(),'name'=> (method_exists($s['sessionType'],'getName') ? $s['sessionType']->getName() : '')] : null
                ];
            }

            return new JsonResponse([
                'success' => true,
                'suggestions' => $out,
                'priorities' => $planning['priorities'] ?? [],
                'stats' => $planning['stats'] ?? null,
                'freeSlotsCount' => $planning['freeSlotsCount'] ?? 0
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('suggestions error: '.$e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['success' => false, 'error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }
    #[Route('/ai/planning/subjects', name: 'app_ai_planning_subjects', methods: ['GET'])]
    public function getSubjects(): JsonResponse
    {
        $subjects = $this->entityManager->getRepository(Matiere::class)->findBy(['user' => $this->getUser()]);
        
        $data = array_map(function($subject) {
            return [
                'id' => $subject->getId(),
                'nom' => $subject->getNomMatiere(),
                'coefficient' => $subject->getCoefficientMatiere()
            ];
        }, $subjects);
        
        return new JsonResponse(['success' => true, 'subjects' => $data]);
    }
    #[Route('/ai/planning/get-bids-for-period', name: 'app_ai_planning_get_bids_for_period', methods: ['GET'])]
    public function getBidsForPeriod(Request $request): JsonResponse
    {
        try {
            $user = $this->getUser();
            if (!$user) return new JsonResponse(['success' => false, 'error' => 'User not connected'], 401);

            $startDate = $request->query->get('start_date');
            $endDate = $request->query->get('end_date');

            if (!$startDate || !$endDate) return new JsonResponse(['success' => false, 'error' => 'Start date and end date required'], 400);

            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);

            $bids = $this->aiPlanningService->getPendingBidsForPeriod($user, $start, $end);

            return new JsonResponse(['success' => true, 'bids' => $bids]);
        } catch (\Throwable $e) {
            $this->logger->error('getBidsForPeriod error: '.$e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['success' => false, 'error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    // keep your working acceptSuggestion SQL method (unchanged)
    #[Route('/ai/planning/accept-suggestion', name: 'app_ai_planning_accept', methods: ['POST'])]
public function acceptSuggestion(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    
    $conn = $this->entityManager->getConnection();
    $conn->beginTransaction();
    
    try {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'User not connected'], 401);
        }
        
        $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 0");
        
        // Insérer la séance
        $conn->executeStatement(
            "INSERT INTO seance (user_id, titre, description, matiere_id, type_seance_id, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $user->getId(),
                $data['titre'],
                $data['description'] ?? '',
                $data['matiere_id'] ?? null,
                $data['type_seance_id'] ?? null
            ]
        );
        
        $seanceId = $conn->lastInsertId();
        
        // Déterminer la couleur selon le type
        if (isset($data['type']) && $data['type'] === 'dayoff') {
            $color = '#10b981'; // Vert pour Day Off
        } elseif (isset($data['type']) && $data['type'] === 'revision') {
            $color = '#f59e0b'; // Orange pour Révision
        } elseif (isset($data['is_exam']) && $data['is_exam']) {
            $color = '#dc2626'; // Rouge pour Examen
        } else {
            $color = $this->generateColor($data['matiere_id'] ?? null, 'Medium');
        }
        
        // Insérer le planning
        $conn->executeStatement(
            "INSERT INTO planning (user_id, seance_id, date_debut, date_fin, color, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [
                $user->getId(),
                $seanceId,
                $data['date_debut'],
                $data['date_fin'],
                $color
            ]
        );
        
        $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 1");
        $conn->commit();
        
        return new JsonResponse(['success' => true, 'message' => 'Session added to planning successfully!']);
        
    } catch (\Exception $e) {
        $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 1");
        $conn->rollBack();
        return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}


    // helper color
    private function generateColor($matiereId = null, $priorityLevel = null): string
    {
        $colors = ['#4f46e5', '#7c3aed', '#2563eb', '#db2777', '#ea580c', '#16a34a', '#9333ea', '#dc2626'];
        if ($matiereId === null) return $colors[0];
        return $colors[(int)$matiereId % count($colors)];
    }
}