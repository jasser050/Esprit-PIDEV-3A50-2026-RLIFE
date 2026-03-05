<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Matiere;
use App\Entity\Planning;
use App\Entity\TypeSeance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class AIPlanningService
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    private const MIDDAY_START = 12;
    private const MIDDAY_END   = 14;
    private const WORK_DAYS_BEFORE_DAYOFF = 6;
    private const MIN_DAYS_BETWEEN_DAYOFFS = 3;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security      = $security;
    }

    public function generateSmartPlanning(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        bool $considerPendingBids = true,
        array $opts = []
    ): array {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return ['error' => 'User not connected'];
        }

        $seedKey = $user->getId() . '_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd');
        mt_srand(crc32($seedKey));

        $subjects           = $this->entityManager->getRepository(Matiere::class)->findBy(['user' => $user]);
        $pendingBids        = $considerPendingBids ? $this->getPendingBidsForPeriod($user, $startDate, $endDate) : [];
        $examDates          = $this->extractExamDatesFromBids($pendingBids);
        $dayOffDates        = $this->extractDayOffDatesFromBids($pendingBids);
        $preciseSessionBids = $this->extractPreciseSessionBids($pendingBids);
        $existingSessions   = $this->getSessionsInPeriod($user, $startDate, $endDate);

        $autoDayOffDates = $this->generateAutoDayOffDates($startDate, $endDate, $dayOffDates, $examDates);
        $allDayOffDates  = array_unique(array_merge($dayOffDates, $autoDayOffDates));

        $freeSlots = $this->generateFreeSlots(
            $startDate, $endDate,
            $existingSessions, $examDates, $allDayOffDates, $preciseSessionBids
        );

        $priorities = $this->calculateSubjectPriorities($subjects, $user, $examDates);

        $suggestions = [];

        // ÉTAPE 1 : Examens bidés → auto-enregistrés, sans boutons
        $examSuggestions = $this->insertExamBids($pendingBids);
        $this->autoSaveBidSuggestions($examSuggestions, $user);
        $suggestions = array_merge($suggestions, $examSuggestions);

        // ÉTAPE 2 : Day off bidés → auto-enregistrés, sans boutons
        $dayOffBidSuggestions = $this->insertDayOffBids($pendingBids);
        $this->autoSaveBidSuggestions($dayOffBidSuggestions, $user);
        $suggestions = array_merge($suggestions, $dayOffBidSuggestions);

        // ÉTAPE 3 : Sessions précises bidées → auto-enregistrées, sans boutons
        $preciseSuggestions = $this->insertPreciseSessionBids($preciseSessionBids);
        $this->autoSaveBidSuggestions($preciseSuggestions, $user);
        $suggestions = array_merge($suggestions, $preciseSuggestions);

        // ÉTAPE 4 : Day off auto IA
        $suggestions = array_merge($suggestions, $this->insertAutoDayOffs($autoDayOffDates));

        // ÉTAPE 5 : Révisions avant examens bidés
        $suggestions = array_merge($suggestions, $this->generateRevisionSessions($examDates, $freeSlots, $subjects));

        // ÉTAPE 6 : Sessions normales
        $suggestions = array_merge($suggestions, $this->generateNormalSessions($priorities, $freeSlots, $startDate, $endDate, $allDayOffDates, $examDates));

        usort($suggestions, function ($a, $b) {
            return $a['slot']['start']->getTimestamp() <=> $b['slot']['start']->getTimestamp();
        });

        return [
            'priorities'     => $priorities,
            'suggestions'    => $suggestions,
            'exams'          => $examDates,
            'stats'          => $this->getStatistics($user),
            'freeSlotsCount' => count($freeSlots)
        ];
    }

    // ========================================
    // AUTO-SAVE
    // ========================================

    private function autoSaveBidSuggestions(array $suggestions, User $user): void
    {
        $conn = $this->entityManager->getConnection();

        foreach ($suggestions as $s) {
            if (!($s['isBid'] ?? false)) continue;

            $dateDebut = $s['slot']['start'] instanceof \DateTimeInterface
                ? $s['slot']['start']->format('Y-m-d H:i:s')
                : (string)$s['slot']['start'];

            $dateFin = $s['slot']['end'] instanceof \DateTimeInterface
                ? $s['slot']['end']->format('Y-m-d H:i:s')
                : (string)$s['slot']['end'];

            try {
                $existing = $conn->executeQuery(
                    "SELECT COUNT(*) FROM planning WHERE user_id = :uid AND date_debut = :debut",
                    ['uid' => $user->getId(), 'debut' => $dateDebut]
                )->fetchOne();

                if ((int)$existing > 0) continue;

                $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 0");

                $titre       = $s['suggestedTitle'] ?? 'Session';
                $description = $s['suggestedDescription'] ?? '';
                $matiereId   = isset($s['subject']) && is_object($s['subject']) ? $s['subject']->getId() : null;
                $typeId      = isset($s['sessionType']) && is_object($s['sessionType']) ? $s['sessionType']->getId() : null;
                $color       = $s['color'] ?? '#4f46e5';

                $conn->executeStatement(
                    "INSERT INTO seance (user_id, titre, description, matiere_id, type_seance_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                    [$user->getId(), $titre, $description, $matiereId, $typeId]
                );

                $seanceId = $conn->lastInsertId();

                $conn->executeStatement(
                    "INSERT INTO planning (user_id, seance_id, date_debut, date_fin, color, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [$user->getId(), $seanceId, $dateDebut, $dateFin, $color]
                );

                $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 1");

            } catch (\Throwable $e) {
                try { $conn->executeStatement("SET FOREIGN_KEY_CHECKS = 1"); } catch (\Throwable $ignored) {}
                continue;
            }
        }
    }

    // ========================================
    // BIDS
    // ========================================

    public function getPendingBidsForPeriod(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        try {
            $conn = $this->entityManager->getConnection();
            $sql  = "SELECT * FROM student_bids
                     WHERE user_id = :user_id
                       AND status = 'pending'
                       AND (
                           (date_debut IS NOT NULL AND DATE(date_debut) BETWEEN :start AND :end)
                           OR (date_evaluation IS NOT NULL AND DATE(date_evaluation) BETWEEN :start AND :end)
                       )
                     ORDER BY COALESCE(date_debut, date_evaluation) ASC";

            return $conn->executeQuery($sql, [
                'user_id' => $user->getId(),
                'start'   => $start->format('Y-m-d'),
                'end'     => $end->format('Y-m-d'),
            ])->fetchAllAssociative();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ========================================
    // DAY OFF AUTO
    // ========================================

    private function generateAutoDayOffDates(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $studentDayOffDates,
        array $examDates
    ): array {
        $autoDayOffs  = [];
        $interval     = new \DateInterval('P1D');
        $period       = new \DatePeriod($start, $interval, (clone $end)->modify('+1 day'));
        $workDayCount = 0;

        $examDatesStr     = array_map(fn($e) => (new \DateTime($e['date']))->format('Y-m-d'), $examDates);
        $examEvesDatesStr = array_map(fn($e) => (new \DateTime($e['date']))->modify('-1 day')->format('Y-m-d'), $examDates);
        $allBlockedDates  = array_merge($studentDayOffDates, $examDatesStr);

        $lastDayOffDate = null;
        if (!empty($studentDayOffDates)) {
            $sorted = $studentDayOffDates;
            sort($sorted);
            $lastDayOffDate = new \DateTime(end($sorted));
        }

        foreach ($period as $day) {
            if ((int)$day->format('N') >= 6) continue;

            $dateStr = $day->format('Y-m-d');

            if (in_array($dateStr, $allBlockedDates, true)) continue;
            if (in_array($dateStr, $examEvesDatesStr, true)) continue;

            $workDayCount++;

            if ($workDayCount >= self::WORK_DAYS_BEFORE_DAYOFF) {
                $tooClose = false;
                if ($lastDayOffDate !== null) {
                    $diffDays = (int)$lastDayOffDate->diff($day)->format('%r%a');
                    if (abs($diffDays) < self::MIN_DAYS_BETWEEN_DAYOFFS) {
                        $tooClose = true;
                    }
                }

                if (!$tooClose) {
                    $autoDayOffs[]     = $dateStr;
                    $allBlockedDates[] = $dateStr;
                    $lastDayOffDate    = clone $day;
                    $workDayCount      = 0;
                }
            }
        }

        return $autoDayOffs;
    }

    // ========================================
    // EXTRACTION
    // ========================================

    private function extractExamDatesFromBids(array $bids): array
    {
        $examDates = [];
        foreach ($bids as $bid) {
            if (($bid['type'] ?? '') === 'exam' && !empty($bid['date_evaluation'])) {
                $examDates[] = [
                    'matiere_id' => $bid['matiere_id'] ?? null,
                    'date'       => $bid['date_evaluation'],
                    'duree'      => $bid['duree_evaluation'] ?? 120
                ];
            }
        }
        return $examDates;
    }

    private function extractDayOffDatesFromBids(array $bids): array
    {
        $dayOffDates = [];
        foreach ($bids as $bid) {
            if (($bid['type'] ?? '') === 'dayoff' && !empty($bid['date_debut'])) {
                $dayOffDates[] = (new \DateTime($bid['date_debut']))->format('Y-m-d');
            }
        }
        return $dayOffDates;
    }

    private function extractPreciseSessionBids(array $bids): array
    {
        $sessions = [];
        foreach ($bids as $bid) {
            $type = $bid['type'] ?? '';
            if (in_array($type, ['exam', 'dayoff'], true)) continue;
            if (!empty($bid['date_debut']) && !empty($bid['heure_debut'])) {
                $sessions[] = [
                    'matiere_id'  => $bid['matiere_id'] ?? null,
                    'type'        => $type,
                    'date_debut'  => $bid['date_debut'],
                    'heure_debut' => $bid['heure_debut'],
                    'heure_fin'   => $bid['heure_fin'] ?? null,
                    'duree'       => $bid['duree'] ?? 120
                ];
            }
        }
        return $sessions;
    }

    // ========================================
    // INSERTION BIDS
    // ========================================

    private function insertExamBids(array $bids): array
    {
        $suggestions = [];
        $user = $this->security->getUser();
        $examType = $user instanceof User ? $this->findTypeByNameForUser($user, 'Exam') : null;

        foreach ($bids as $bid) {
            if (($bid['type'] ?? '') !== 'exam' || empty($bid['date_evaluation'])) continue;

            $examDate = new \DateTime($bid['date_evaluation']);
            $duree    = (int)($bid['duree_evaluation'] ?? 120);
            $subject  = !empty($bid['matiere_id'])
                ? $this->entityManager->getRepository(Matiere::class)->find($bid['matiere_id'])
                : null;

            $suggestions[] = [
                'slot' => [
                    'date'  => $examDate,
                    'start' => clone $examDate,
                    'end'   => (clone $examDate)->modify("+{$duree} minutes"),
                    'slot'  => 'Exam'
                ],
                'subject'             => $subject,
                'sessionType'         => $examType,
                'priority'            => 1.0,
                'priorityLevel'       => 'Urgent',
                'type'                => 'Exam',
                'color'               => '#ef4444',
                'suggestedTitle'      => ($subject ? $subject->getNomMatiere() . ' - ' : '') . 'Exam',
                'suggestedDescription'=> 'Student-scheduled exam',
                'isBid'               => true,
                'showActions'         => false
            ];
        }

        return $suggestions;
    }

    private function insertDayOffBids(array $bids): array
    {
        $suggestions = [];
        $user = $this->security->getUser();
        $dayOffType = $user instanceof User ? $this->findTypeByNameForUser($user, 'DAY OFF') : null;

        foreach ($bids as $bid) {
            if (($bid['type'] ?? '') !== 'dayoff' || empty($bid['date_debut'])) continue;

            $dayOffDate = new \DateTime($bid['date_debut']);
            $dateStr    = $dayOffDate->format('Y-m-d');

            $suggestions[] = [
                'slot' => [
                    'date'  => $dayOffDate,
                    'start' => new \DateTime($dateStr . ' 00:00:00'),
                    'end'   => new \DateTime($dateStr . ' 23:59:59'),
                    'slot'  => 'Full Day'
                ],
                'subject'             => null,
                'sessionType'         => $dayOffType,
                'priority'            => 0.0,
                'priorityLevel'       => 'Recovery',
                'type'                => 'DAY OFF',
                'color'               => '#10b981',
                'suggestedTitle'      => 'Day Off',
                'suggestedDescription'=> 'Your scheduled rest day',
                'isBid'               => true,
                'showActions'         => false
            ];
        }

        return $suggestions;
    }

    private function insertAutoDayOffs(array $autoDayOffDates): array
    {
        $suggestions = [];
        $user = $this->security->getUser();
        $dayOffType = $user instanceof User ? $this->findTypeByNameForUser($user, 'DAY OFF') : null;

        foreach ($autoDayOffDates as $dateStr) {
            $dayOffDate = new \DateTime($dateStr);

            $suggestions[] = [
                'slot' => [
                    'date'  => $dayOffDate,
                    'start' => new \DateTime($dateStr . ' 00:00:00'),
                    'end'   => new \DateTime($dateStr . ' 23:59:59'),
                    'slot'  => 'Full Day'
                ],
                'subject'             => null,
                'sessionType'         => $dayOffType,
                'priority'            => 0.0,
                'priorityLevel'       => 'Recovery',
                'type'                => 'DAY OFF',
                'color'               => '#10b981',
                'suggestedTitle'      => 'Day Off',
                'suggestedDescription'=> 'AI-suggested rest day — take a break!',
                'isBid'               => false,
                'isAutoDayOff'        => true,
                'showActions'         => true
            ];
        }

        return $suggestions;
    }

    private function insertPreciseSessionBids(array $sessions): array
    {
        $suggestions = [];
        $user = $this->security->getUser();

        foreach ($sessions as $session) {
            $startDateTime = new \DateTime($session['date_debut'] . ' ' . $session['heure_debut']);
            $endDateTime   = !empty($session['heure_fin'])
                ? new \DateTime($session['date_debut'] . ' ' . $session['heure_fin'])
                : (clone $startDateTime)->modify('+' . (int)($session['duree'] ?? 120) . ' minutes');

            $subject     = !empty($session['matiere_id'])
                ? $this->entityManager->getRepository(Matiere::class)->find($session['matiere_id'])
                : null;
            $sessionType = null;
            if (!empty($session['type']) && $user instanceof User) {
                $sessionType = $this->findTypeByNameForUser($user, ucfirst($session['type']));
            }

            $suggestions[] = [
                'slot' => [
                    'date'  => $startDateTime,
                    'start' => $startDateTime,
                    'end'   => $endDateTime,
                    'slot'  => 'Precise'
                ],
                'subject'             => $subject,
                'sessionType'         => $sessionType,
                'priority'            => 0.8,
                'priorityLevel'       => 'High',
                'type'                => ucfirst($session['type'] ?? 'Session'),
                'color'               => $subject ? $this->generateColor($subject->getId()) : '#6b7280',
                'suggestedTitle'      => ($subject ? $subject->getNomMatiere() . ' - ' : '') . ucfirst($session['type'] ?? 'Session'),
                'suggestedDescription'=> 'Your scheduled session',
                'isBid'               => true,
                'showActions'         => false
            ];
        }

        return $suggestions;
    }

    // ========================================
    // RÉVISIONS — VERSION CORRIGÉE
    // ========================================

    private function generateRevisionSessions(array $examDates, array &$freeSlots, array $subjects): array
    {
        // Aucun examen bidé → aucune révision
        if (empty($examDates)) {
            return [];
        }

        $revisions = [];

        // ✅ Cherche le TypeSeance Revision sous toutes les casses possibles
        // Si introuvable → null, mais on génère quand même les révisions
        $user = $this->security->getUser();
        $revisionType = null;
        if ($user instanceof User) {
            $revisionType = $this->findTypeByNameForUser($user, 'Revision')
                ?? $this->findTypeByNameForUser($user, 'revision')
                ?? $this->findTypeByNameForUser($user, 'REVISION');
        }

        foreach ($examDates as $exam) {
            if (empty($exam['date'])) continue;

            $examDate  = new \DateTime($exam['date']);
            $matiereId = $exam['matiere_id'] ?? null;

            // ✅ Cherche la matière de l'examen
            // Si matiere_id est null ou introuvable → prend la première matière disponible
            $subject = null;
            if (!empty($matiereId)) {
                $subject = $this->entityManager->getRepository(Matiere::class)->find($matiereId);
            }
            if ($subject === null && !empty($subjects)) {
                $subject = $subjects[0];
            }

            // Si toujours pas de matière → impossible de créer la révision
            if ($subject === null) continue;

            // ✅ Génère J-3, J-2, J-1 avant l'examen
            for ($dayBefore = 3; $dayBefore >= 1; $dayBefore--) {
                $revisionDate    = (clone $examDate)->modify("-{$dayBefore} days");
                $revisionDateStr = $revisionDate->format('Y-m-d');

                // Chercher un créneau libre ce jour-là
                $slotFound = null;
                $slotIndex = null;
                foreach ($freeSlots as $idx => $slot) {
                    if ($slot['date']->format('Y-m-d') === $revisionDateStr) {
                        $slotFound = $slot;
                        $slotIndex = $idx;
                        break;
                    }
                }

                // ✅ Si aucun créneau libre → on force un créneau Morning
                // La révision est prioritaire sur les sessions normales
                if ($slotFound === null) {
                    $slotFound = [
                        'date'  => clone $revisionDate,
                        'start' => new \DateTime($revisionDateStr . ' 08:00:00'),
                        'end'   => new \DateTime($revisionDateStr . ' 12:00:00'),
                        'slot'  => 'Morning'
                    ];
                } else {
                    // Retirer le créneau utilisé pour qu'il ne soit pas réutilisé
                    unset($freeSlots[$slotIndex]);
                }

                $revisions[] = [
                    'slot'                => $slotFound,
                    'subject'             => $subject,
                    'sessionType'         => $revisionType,
                    'priority'            => 0.95,
                    'priorityLevel'       => 'Urgent',
                    'type'                => 'Revision',
                    'color'               => '#f59e0b',
                    'suggestedTitle'      => $subject->getNomMatiere() . ' — Révision J-' . $dayBefore,
                    'suggestedDescription'=> 'Révision avant examen · J-' . $dayBefore,
                    'isBid'               => false,
                    'showActions'         => true
                ];
            }
        }

        $freeSlots = array_values($freeSlots);

        return $revisions;
    }

    // ========================================
    // SESSIONS NORMALES
    // ========================================

    private function generateNormalSessions(
        array $priorities,
        array $freeSlots,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        array $allDayOffDates,
        array $examDates
    ): array {
        $suggestions = [];

        if (empty($priorities)) {
            return [];
        }

        $user = $this->security->getUser();
        $allTypes = [];
        if ($user instanceof User) {
            $allTypes = $this->entityManager->getRepository(TypeSeance::class)->createQueryBuilder('t')
                ->andWhere('t.user = :user OR t.user IS NULL')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
        }
        $normalTypes = array_values(array_filter(
            $allTypes,
            fn($t) => !in_array($t->getName(), ['Exam', 'Revision', 'DAY OFF', 'Midday Break'], true)
        ));

        $maxPerDay       = 2;
        $scheduledPerDay = [];

        $slotsByDate = [];
        foreach ($freeSlots as $slot) {
            $d = $slot['date']->format('Y-m-d');
            if (!isset($slotsByDate[$d])) $slotsByDate[$d] = [];
            $slotsByDate[$d][] = $slot;
        }

        $interval     = new \DateInterval('P1D');
        $period       = new \DatePeriod($startDate, $interval, (clone $endDate)->modify('+1 day'));
        $examDatesStr = array_map(fn($e) => (new \DateTime($e['date']))->format('Y-m-d'), $examDates);

        foreach ($period as $day) {
            if ((int)$day->format('N') >= 6) continue;

            $dateStr = $day->format('Y-m-d');

            if (in_array($dateStr, $allDayOffDates, true)) continue;
            if (in_array($dateStr, $examDatesStr, true)) continue;

            $scheduledPerDay[$dateStr] = 0;

            if (!empty($slotsByDate[$dateStr])) {
                foreach ($slotsByDate[$dateStr] as $slot) {
                    if ($scheduledPerDay[$dateStr] >= $maxPerDay) break;

                    $slotHash      = md5($dateStr . $slot['slot']);
                    $priorityIndex = hexdec(substr($slotHash, 0, 8)) % count($priorities);
                    $priorityData  = $priorities[$priorityIndex];

                    $sessionType = null;
                    if (!empty($normalTypes)) {
                        $typeIndex   = hexdec(substr($slotHash, 8, 8)) % count($normalTypes);
                        $sessionType = $normalTypes[$typeIndex];
                    }

                    $suggestions[] = [
                        'slot'                => $slot,
                        'subject'             => $priorityData['subject'],
                        'sessionType'         => $sessionType,
                        'priority'            => $priorityData['score'],
                        'priorityLevel'       => $priorityData['priorityLevel'],
                        'type'                => $sessionType ? $sessionType->getName() : 'Course',
                        'color'               => $this->generateColor($priorityData['subject']->getId()),
                        'suggestedTitle'      => $priorityData['subject']->getNomMatiere() . ' - ' . ($sessionType ? $sessionType->getName() : 'Course'),
                        'suggestedDescription'=> 'AI-generated session based on priority',
                        'isBid'               => false,
                        'showActions'         => true
                    ];

                    $scheduledPerDay[$dateStr]++;
                }
            } else {
                $forcedStart = new \DateTime($dateStr . ' 08:00:00');
                $forcedEnd   = new \DateTime($dateStr . ' 12:00:00');

                $slotHash      = md5($dateStr . 'forced');
                $priorityIndex = hexdec(substr($slotHash, 0, 8)) % count($priorities);
                $priorityData  = $priorities[$priorityIndex];

                $sessionType = null;
                if (!empty($normalTypes)) {
                    $typeIndex   = hexdec(substr($slotHash, 8, 8)) % count($normalTypes);
                    $sessionType = $normalTypes[$typeIndex];
                }

                $suggestions[] = [
                    'slot' => [
                        'date'  => clone $day,
                        'start' => $forcedStart,
                        'end'   => $forcedEnd,
                        'slot'  => 'Morning'
                    ],
                    'subject'             => $priorityData['subject'],
                    'sessionType'         => $sessionType,
                    'priority'            => $priorityData['score'],
                    'priorityLevel'       => $priorityData['priorityLevel'],
                    'type'                => $sessionType ? $sessionType->getName() : 'Course',
                    'color'               => $this->generateColor($priorityData['subject']->getId()),
                    'suggestedTitle'      => $priorityData['subject']->getNomMatiere() . ' - ' . ($sessionType ? $sessionType->getName() : 'Course'),
                    'suggestedDescription'=> 'AI-generated session (no free slot available)',
                    'isBid'               => false,
                    'showActions'         => true
                ];
            }
        }

        return $suggestions;
    }

    // ========================================
    // HELPERS
    // ========================================

    private function getSessionsInPeriod(User $user, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('p')
            ->from(Planning::class, 'p')
            ->where('p.user = :user')
            ->andWhere('p.dateDebut < :end')
            ->andWhere('p.dateFin > :start')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function generateFreeSlots(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $existingSessions,
        array $examDates,
        array $allDayOffDates,
        array $preciseSessionBids
    ): array {
        $freeSlots = [];
        $interval  = new \DateInterval('P1D');
        $period    = new \DatePeriod($start, $interval, (clone $end)->modify('+1 day'));

        $timeSlots = [
            ['start' => 8,  'end' => 12, 'name' => 'Morning'],
            ['start' => 14, 'end' => 18, 'name' => 'Afternoon'],
            ['start' => 20, 'end' => 22, 'name' => 'Evening']
        ];

        $examDatesStr = array_map(fn($e) => (new \DateTime($e['date']))->format('Y-m-d'), $examDates);

        foreach ($period as $day) {
            if ((int)$day->format('N') >= 6) continue;

            $dateStr = $day->format('Y-m-d');

            if (in_array($dateStr, $examDatesStr, true)) continue;
            if (in_array($dateStr, $allDayOffDates, true)) continue;

            foreach ($timeSlots as $slotDef) {
                $slotStart  = new \DateTime($dateStr . ' ' . sprintf('%02d:00:00', $slotDef['start']));
                $slotEnd    = new \DateTime($dateStr . ' ' . sprintf('%02d:00:00', $slotDef['end']));
                $isOccupied = false;

                foreach ($existingSessions as $session) {
                    try {
                        if ($session->getDateDebut() < $slotEnd && $session->getDateFin() > $slotStart) {
                            $isOccupied = true;
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                if ($isOccupied) continue;

                foreach ($preciseSessionBids as $bid) {
                    $bidStart = new \DateTime($bid['date_debut'] . ' ' . $bid['heure_debut']);
                    $bidEnd   = !empty($bid['heure_fin'])
                        ? new \DateTime($bid['date_debut'] . ' ' . $bid['heure_fin'])
                        : (clone $bidStart)->modify('+' . (int)($bid['duree'] ?? 120) . ' minutes');

                    if ($bidStart < $slotEnd && $bidEnd > $slotStart) {
                        $isOccupied = true;
                        break;
                    }
                }

                if ($isOccupied) continue;

                $freeSlots[] = [
                    'date'  => clone $day,
                    'start' => $slotStart,
                    'end'   => $slotEnd,
                    'slot'  => $slotDef['name']
                ];
            }
        }

        return $freeSlots;
    }

    private function calculateSubjectPriorities(array $subjects, User $user, array $examDates): array
    {
        $priorities = [];
        $now        = new \DateTime();
        $conn       = $this->entityManager->getConnection();

        foreach ($subjects as $subject) {
            $matiereId = $subject->getId();
            $coef      = (float)$subject->getCoefficientMatiere();

            $avgScore = 0.0;
            try {
                $avg      = $conn->executeQuery(
                    "SELECT AVG(score) FROM evaluation_matiere WHERE matiere_id = :id",
                    ['id' => $matiereId]
                )->fetchOne();
                $avgScore = $avg !== null ? (float)$avg : 0.0;
            } catch (\Throwable $e) {
                $avgScore = 0.0;
            }

            $daysLeft = null;
            foreach ($examDates as $exam) {
                if ((int)($exam['matiere_id'] ?? 0) === (int)$matiereId) {
                    try {
                        $examDate = new \DateTime($exam['date']);
                        $daysLeft = (int)$now->diff($examDate)->format('%r%a');
                    } catch (\Throwable $e) {
                        continue;
                    }
                    break;
                }
            }

            $examFactor = 0.0;
            if ($daysLeft !== null && $daysLeft > 0) {
                $examFactor = max(0, (30 - $daysLeft) / 30);
            }

            $score = (0.5 * min(1, $coef / 5))
                + (0.35 * $examFactor)
                + (0.15 * max(0, (100 - $avgScore) / 100));
            $score = min(1.0, max(0.0, $score));

            if ($score >= 0.85)     $level = 'Urgent';
            elseif ($score >= 0.7)  $level = 'High';
            elseif ($score >= 0.45) $level = 'Medium';
            else                    $level = 'Low';

            $priorities[] = [
                'subject'       => $subject,
                'score'         => $score,
                'priorityLevel' => $level,
                'daysLeft'      => $daysLeft,
                'averageScore'  => $avgScore
            ];
        }

        usort($priorities, fn($a, $b) => $b['score'] <=> $a['score']);

        return $priorities;
    }

    private function getStatistics(User $user): array
    {
        try {
            return [
                'totalSubjects' => $this->entityManager->getRepository(Matiere::class)->count(['user' => $user]),
                'upcomingExams' => 0
            ];
        } catch (\Throwable $e) {
            return ['totalSubjects' => 0, 'upcomingExams' => 0];
        }
    }

    private function generateColor(?int $matiereId = null): string
    {
        $colors = ['#4f46e5', '#7c3aed', '#2563eb', '#db2777', '#ea580c', '#16a34a', '#9333ea', '#dc2626'];
        if ($matiereId === null) return $colors[0];
        return $colors[(int)$matiereId % count($colors)];
    }

    private function findTypeByNameForUser(User $user, string $name): ?TypeSeance
    {
        $type = $this->entityManager
            ->getRepository(TypeSeance::class)
            ->findOneBy(['name' => $name, 'user' => $user]);
        if ($type) {
            return $type;
        }
        return $this->entityManager
            ->getRepository(TypeSeance::class)
            ->findOneBy(['name' => $name, 'user' => null]);
    }
}
