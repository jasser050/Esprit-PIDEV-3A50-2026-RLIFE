<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\Seance;
use App\Entity\User;
use App\Service\GoogleCalendarClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Notification;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\UserFavoriteTeam;
use App\Service\SportsApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\UserFavoriteTeamRepository;

#[Route('/planning')]
class PlanningController extends AbstractController
{
    #[Route('', name: 'app_planning', methods: ['GET'])]
public function index(
    Request $request,
    EntityManagerInterface $em,
    GoogleCalendarClient $googleCalendar,
    UserFavoriteTeamRepository $favoriteRepo,
    SportsApiService $sportsApiService
): Response {
    $today = new \DateTimeImmutable();

    // Gestion du mois/année demandé
    $month = $request->query->getInt('month', (int) $today->format('n'));
    $year  = $request->query->getInt('year', (int) $today->format('Y'));

    $currentDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $daysInMonth = (int) $currentDate->format('t');
    $firstDayOfWeek = (int) $currentDate->format('N');

    $startOfMonth = $currentDate->setTime(0, 0, 0);
    $endOfMonth   = $currentDate->modify('last day of this month')->setTime(23, 59, 59);

    // Utilisateur connecté (toujours défini, même si null)
    $user = $this->getUser();

    // Initialisation des variables pour l'équipe favorite (évite undefined)
    $favoriteTeam = null;
    $nextMatch    = null;

    // Chargement de l'équipe favorite et prochain match (seulement si connecté)
    if ($user) {
        $favoriteTeam = $favoriteRepo->findByUser($user);

        if ($favoriteTeam && $favoriteTeam->getTeamApiId()) {
            $nextMatch = $sportsApiService->getNextMatch($favoriteTeam->getTeamApiId());
        }
    }

    // 1) Charger les plannings depuis la base (filtré par user connecté)
    if ($user instanceof User) {
        $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.dateDebut BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        $plannings = [];
    }

    // Couleurs UI
    $uiMap = [
        'indigo' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-200', 'bar' => 'bg-indigo-500', 'color' => '#4f46e5'],
        'teal'   => ['bg' => 'bg-teal-100 dark:bg-teal-900/30',     'text' => 'text-teal-700 dark:text-teal-200',     'bar' => 'bg-teal-500',   'color' => '#14b8a6'],
        'amber'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',   'text' => 'text-amber-700 dark:text-amber-200',   'bar' => 'bg-amber-500',  'color' => '#f59e0b'],
        'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',     'text' => 'text-blue-700 dark:text-blue-200',     'bar' => 'bg-blue-500',   'color' => '#3b82f6'],
        'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30',   'text' => 'text-green-700 dark:text-green-200',   'bar' => 'bg-green-500',  'color' => '#10b981'],
        'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30',       'text' => 'text-red-700 dark:text-red-200',       'bar' => 'bg-red-500',    'color' => '#ef4444'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-200', 'bar' => 'bg-purple-500', 'color' => '#a855f7'],
        'pink'   => ['bg' => 'bg-pink-100 dark:bg-pink-900/30',     'text' => 'text-pink-700 dark:text-pink-200',     'bar' => 'bg-pink-500',   'color' => '#ec4899'],
    ];

    $eventsByDate = [];
    foreach ($plannings as $p) {
        if (!$p->getDateDebut() instanceof \DateTimeInterface) {
            continue;
        }

        $dateKey = $p->getDateDebut()->format('Y-m-d');
        $eventsByDate[$dateKey] ??= [];

        $color = $p->getColor() ?? 'indigo';
        if (is_string($color) && str_starts_with($color, '#')) {
            $ui = $uiMap['indigo'];
            $ui['color'] = $color;
        } else {
            $ui = $uiMap[$color] ?? $uiMap['indigo'];
        }

        $eventsByDate[$dateKey][] = [
            'id' => $p->getId(),
            'title' => $p->getSeance() ? (string) $p->getSeance() : 'Séance',
            'date' => $dateKey,
            'start_time' => $p->getDateDebut()->format('H:i'),
            'end_time' => $p->getDateFin() instanceof \DateTimeInterface ? $p->getDateFin()->format('H:i') : null,
            'color' => $color,
            'feedback' => $p->getFeedback(),
            'ui' => $ui,
            'type' => 'class',
            'is_google' => false,
        ];
    }

    // 2) Charger les seances (pour sidebar ou autre)
    $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);

    // 3) Google Calendar : merge dans eventsByDate
    $google_events_count = 0;
    $google_load_error = null;

    if ($user instanceof User) {
        try {
            $googleEvents = $googleCalendar->listEventsForMonth($user, $year, $month);

            foreach ($googleEvents as $ge) {
                if (empty($ge['start'])) {
                    continue;
                }

                $start = new \DateTimeImmutable($ge['start']);
                $dateKey = $start->format('Y-m-d');

                $startTime = $start->format('H:i');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $ge['start'])) {
                    $startTime = 'All day';
                }

                $eventsByDate[$dateKey] ??= [];
                $eventsByDate[$dateKey][] = [
                    'id' => 'google:' . ($ge['id'] ?? uniqid('google_', true)),
                    'title' => $ge['summary'] ?? '(Google event)',
                    'date' => $dateKey,
                    'start_time' => $startTime,
                    'end_time' => null,
                    'color' => 'green',
                    'feedback' => null,
                    'ui' => [
                        'bg' => 'bg-green-100 dark:bg-green-900/30',
                        'text' => 'text-green-800 dark:text-green-200',
                        'bar' => 'bg-green-500',
                        'color' => '#34a853',
                    ],
                    'type' => 'google',
                    'is_google' => true,
                ];

                $google_events_count++;
            }

            // Tri par heure dans chaque jour (All day en premier)
            foreach ($eventsByDate as $k => $list) {
                usort($list, static function (array $a, array $b): int {
                    $ta = (string)($a['start_time'] ?? '');
                    $tb = (string)($b['start_time'] ?? '');
                    if ($ta === 'All day' && $tb !== 'All day') return -1;
                    if ($tb === 'All day' && $ta !== 'All day') return 1;
                    return strcmp($ta, $tb);
                });
                $eventsByDate[$k] = $list;
            }
        } catch (\Throwable $e) {
            $google_load_error = $e->getMessage();
        }
    }

    // 4) Upcoming events (filtrage et tri)
    $upcoming_q    = trim((string) $request->query->get('up_q', ''));
    $up_sort       = (string) $request->query->get('up_sort', 'date_asc');
    $up_start      = trim((string) $request->query->get('up_start', ''));
    $up_end        = trim((string) $request->query->get('up_end', ''));

    $upcomingAll = [];
    $todayKey    = $today->format('Y-m-d');

    foreach ($eventsByDate as $dateKey => $events) {
        if ($dateKey < $todayKey) continue;
        foreach ($events as $ev) {
            $upcomingAll[] = $ev + ['date' => $dateKey];
        }
    }

    $upcomingFiltered = $upcomingAll;

    // Filtre texte
    if ($upcoming_q !== '') {
        $needle = mb_strtolower($upcoming_q);
        $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($needle): bool {
            $title = mb_strtolower((string)($ev['title'] ?? ''));
            return str_contains($title, $needle);
        }));
    }

    // Filtre période
    if ($up_start !== '') {
        $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($up_start): bool {
            return ($ev['date'] ?? '') >= $up_start;
        }));
    }
    if ($up_end !== '') {
        $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($up_end): bool {
            return ($ev['date'] ?? '') <= $up_end;
        }));
    }

    // Tri
    usort($upcomingFiltered, static function (array $a, array $b) use ($up_sort): int {
        $ad = (string)($a['date'] ?? '');
        $bd = (string)($b['date'] ?? '');

        $at = (string)($a['start_time'] ?? '');
        $bt = (string)($b['start_time'] ?? '');

        $atNorm = ($at === 'All day' || $at === '') ? '00:00' : $at;
        $btNorm = ($bt === 'All day' || $bt === '') ? '00:00' : $bt;

        $aTitle = (string)($a['title'] ?? '');
        $bTitle = (string)($b['title'] ?? '');

        if ($up_sort === 'title_asc') {
            return strcasecmp($aTitle, $bTitle);
        }

        $cmp = strcmp($ad, $bd);
        if ($cmp === 0) {
            $cmp = strcmp($atNorm, $btNorm);
        }

        return $up_sort === 'date_desc' ? -$cmp : $cmp;
    });

    return $this->render('pages/planning/index.html.twig', [
        'current_month'      => $month,
        'current_year'       => $year,
        'days_in_month'      => $daysInMonth,
        'first_day_of_week'  => $firstDayOfWeek,
        'events_by_date'     => $eventsByDate,
        'conflicts'          => [], // à remplir si tu as une logique de conflits
        'today'              => $today->format('Y-m-d'),
        'seances'            => $seances,
        'up_sort'            => $up_sort,
        'up_start'           => $up_start,
        'up_end'             => $up_end,
        'google_events_count' => $google_events_count,
        'google_load_error'  => $google_load_error,
        'upcoming_q'         => $upcoming_q,
        'upcoming'           => $upcomingFiltered,
        'upcoming_total'     => count($upcomingAll),

        // Équipe favorite et prochain match (toujours définis, même à null)
        'favorite_team'      => $favoriteTeam,
        'next_match'         => $nextMatch,
    ]);
}


// ══════════════════════════════════════════════════════════════════
//  PlanningController::new() — VERSION COMPLÈTE CORRIGÉE
//  Fix : détection JSON robuste + setTitle() inexistant contourné
// ══════════════════════════════════════════════════════════════════

#[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    UrlGeneratorInterface $urlGenerator
): Response {
    $user = $this->getUser();
    if (!$user instanceof User) {
        if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
            return new JsonResponse(['success' => false, 'message' => 'Not logged in.'], 403);
        }
        throw $this->createAccessDeniedException('You must be logged in.');
    }

    // ════════════════════════════════════════════════════════════════
    // BLOC AJAX/JSON — Ajout d'un match depuis le bouton "Ajouter ce match"
    // ════════════════════════════════════════════════════════════════
//     $contentType = $request->headers->get('Content-Type', '');
//     $acceptHeader = $request->headers->get('Accept', '');
//     $isJsonRequest = str_contains($contentType, 'application/json')
//         || str_contains($acceptHeader, 'application/json')
//         || $request->isXmlHttpRequest();

//     if ($isJsonRequest) {
//         try {
//             $data = json_decode($request->getContent(), true);

//             if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
//                 return new JsonResponse(['success' => false, 'message' => 'Invalid JSON format.'], 400);
//             }

//             if (empty($data['title']) || empty($data['date'])) {
//                 return new JsonResponse(['success' => false, 'message' => 'Title and date are required.'], 400);
//             }

//             // ── Parse date and times ──
//             $dateStr   = trim($data['date']);
//             $startTime = trim($data['start_time'] ?? '15:00:00');
//             $endTime   = trim($data['end_time']   ?? '17:00:00');

//             // Normalize H:i → H:i:s
//             if (strlen($startTime) === 5) $startTime .= ':00';
//             if (strlen($endTime)   === 5) $endTime   .= ':00';

//             $dateDebut = \DateTime::createFromFormat('Y-m-d H:i:s', "$dateStr $startTime");
//             $dateFin   = \DateTime::createFromFormat('Y-m-d H:i:s', "$dateStr $endTime");

//             if (!$dateDebut || !$dateFin) {
//                 return new JsonResponse([
//                     'success' => false,
//                     'message' => "Invalid date format: $dateStr $startTime"
//                 ], 400);
//             }

//             if ($dateFin <= $dateDebut) {
//                 $dateFin = clone $dateDebut;
//                 $dateFin->modify('+2 hours');
//             }

//             $titleMatch = $data['title']; // ex: "PSG vs Real Madrid"

//             // ── Duplicate check: same user + same day + same start time ──
//             $existingPlanning = $em->getRepository(Planning::class)->createQueryBuilder('p')
//                 ->where('p.user = :user')
//                 ->andWhere('p.dateDebut = :dateDebut')
//                 ->setParameter('user', $user)
//                 ->setParameter('dateDebut', $dateDebut)
//                 ->setMaxResults(1)
//                 ->getQuery()
//                 ->getOneOrNullResult();

//             if ($existingPlanning !== null) {
//                 return new JsonResponse([
//                     'success' => false,
//                     'message' => 'This match is already in your planning for this date and time.',
//                     'duplicate' => true,
//                 ], 409);
//             }

//             // ── Find the "Match" Seance ──
//             // Always use the "Match" seance type — never search by match title
//             $seance = $em->getRepository(Seance::class)->findOneBy(['typeSeance' => 'Match']);

//             if (!$seance) {
//                 foreach ($em->getRepository(Seance::class)->findAll() as $s) {
//                     if (stripos((string) $s->getTypeSeance(), 'match') !== false) {
//                         $seance = $s;
//                         break;
//                     }
//                 }
//             }

//             if (!$seance) {
//                 $seance = $em->getRepository(Seance::class)->findOneBy([]);
//             }

//             // ── Create Planning ──
//             $planning = new Planning();
//             $planning->setUser($user);
//             $planning->setDateDebut($dateDebut);
//             $planning->setDateFin($dateFin);
//             $planning->setColor('indigo');

//             if ($seance) {
//                 $planning->setSeance($seance);
//             }

//             $em->persist($planning);
//             $em->flush();

//             // ── Notification ──
//             $notification = new Notification();
//             $notification->setTitle('Match added to schedule');
//             $notification->setMessage(sprintf(
//                 'The match "%s" has been added on %s at %s',
//                 $titleMatch,
//                 $dateDebut->format('m/d/Y'),
//                 $dateDebut->format('H:i')
//             ));
//             $notification->setUser($user);
//             $em->persist($notification);
//             $em->flush();

//             // ── Email ──
//             if ($user->getEmail()) {
//                 $projectDir = $this->getParameter('kernel.project_dir');
//                 $candidates = [
//                     $projectDir . '/public/image/logo.jpg',
//                     $projectDir . '/public/images/logo.jpg',
//                     $projectDir . '/public/logo.jpg',
//                 ];
//                 $logoPath = null;
//                 foreach ($candidates as $p) {
//                     if (file_exists($p) && is_readable($p)) { $logoPath = $p; break; }
//                 }

//                 $imgSrc = null;
//                 if ($logoPath) {
//                     try {
//                         $dataImg = file_get_contents($logoPath);
//                         if ($dataImg !== false) {
//                             $mime = mime_content_type($logoPath) ?: 'image/jpeg';
//                             $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($dataImg);
//                         }
//                     } catch (\Throwable $e) {}
//                 }
//                 if (!$imgSrc) {
//                     $imgSrc = rtrim($request->getSchemeAndHttpHost(), '/') . '/image/logo.jpg';
//                 }

//                 $planningUrl = $urlGenerator->generate('app_planning', [], UrlGeneratorInterface::ABSOLUTE_URL);
//                 $username    = htmlspecialchars((string) $user->getUsername(), ENT_QUOTES, 'UTF-8');
//                 $titleSafe   = htmlspecialchars($titleMatch, ENT_QUOTES, 'UTF-8');

//                 $htmlBody = <<<HTML
// <!doctype html>
// <html><head><meta charset="utf-8"></head>
// <body style="font-family:Arial,sans-serif;color:#111;background:#f7f8fb;margin:0;padding:0">
//   <div style="max-width:680px;margin:24px auto;background:#fff;padding:20px;border-radius:10px">
//     <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
//       <img src="{$imgSrc}" alt="RLIFE" style="height:56px;border-radius:6px;">
//       <div>
//         <div style="font-weight:700;font-size:18px">RLIFE — Planning</div>
//         <div style="color:#6b7280;font-size:13px">Match added to your calendar</div>
//       </div>
//     </div>
//     <h2>Match successfully added!</h2>
//     <p>Hello {$username},</p>
//     <p>Your match has been added to your planning:</p>
//     <div style="background:#f8fafc;border:1px solid #eef2ff;padding:12px;border-radius:8px">
//       <ul style="margin:0;padding-left:18px">
//         <li><strong>Match:</strong> {$titleSafe}</li>
//         <li><strong>Date:</strong> {$dateDebut->format('m/d/Y')}</li>
//         <li><strong>Time:</strong> {$dateDebut->format('H:i')} – {$dateFin->format('H:i')}</li>
//       </ul>
//     </div>
//     <p style="margin-top:18px">
//       <a href="{$planningUrl}" style="background:#4f46e5;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;">
//         View my planning
//       </a>
//     </p>
//     <p style="margin-top:18px">Enjoy the match!<br><strong>The RLIFE Team</strong></p>
//   </div>
// </body></html>
// HTML;

//                 $email = (new Email())
//                     ->from('yassine.mlaouah@cme.tn')
//                     ->to($user->getEmail())
//                     ->subject('Match added to your planning — RLIFE')
//                     ->html($htmlBody)
//                     ->text("Hello {$user->getUsername()},\n\nThe match \"{$titleMatch}\" has been added on {$dateDebut->format('m/d/Y')} at {$dateDebut->format('H:i')}.\n\nView your planning: {$planningUrl}\n\nEnjoy the match!\nThe RLIFE Team");

//                 try { $mailer->send($email); } catch (\Throwable $e) { /* silent */ }
//             }

//             return new JsonResponse([
//                 'success' => true,
//                 'message' => 'Match successfully added to your planning!',
//                 'id'      => $planning->getId(),
//             ]);

//         } catch (\Exception $e) {
//             return new JsonResponse([
//                 'success' => false,
//                 'message' => 'Server error: ' . $e->getMessage()
//             ], 500);
//         }
//     }

    // ════════════════════════════════════════════════════════════════
    // FORMULAIRE CLASSIQUE (inchangé)
    // ════════════════════════════════════════════════════════════════
    $planning = new Planning();
    $form = $this->createForm(\App\Form\PlanningType::class, $planning);
    $form->handleRequest($request);

    $today = new \DateTimeImmutable();
    $month = $request->query->getInt('month', (int) $today->format('n'));
    $year  = $request->query->getInt('year',  (int) $today->format('Y'));

    $startOfMonth   = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    $endOfMonth     = $startOfMonth->modify('last day of this month')->setTime(23, 59, 59);
    $daysInMonth    = (int) $startOfMonth->format('t');
    $firstDayOfWeek = (int) $startOfMonth->format('N');

    $plannings = $em->getRepository(Planning::class)
        ->createQueryBuilder('p')
        ->leftJoin('p.seance', 's')
        ->addSelect('s')
        ->andWhere('p.dateDebut BETWEEN :start AND :end')
        ->setParameter('start', $startOfMonth)
        ->setParameter('end', $endOfMonth)
        ->orderBy('p.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();

    $eventsByDate = [];
    foreach ($plannings as $p) {
        $dateKey = $p->getDateDebut()->format('Y-m-d');
        $eventsByDate[$dateKey] ??= [];
        $seance = $p->getSeance();
        $label  = $seance ? $seance->getTypeSeance() : '';

        $eventsByDate[$dateKey][] = [
            'id'         => $p->getId(),
            'title'      => $label,
            'is_day_off' => strtolower(trim($label)) === 'day off',
            'start_time' => $p->getDateDebut()->format('H:i'),
            'end_time'   => $p->getDateFin() ? $p->getDateFin()->format('H:i') : null,
            'feedback'   => $p->getFeedback(),
            'is_google'  => false,
        ];
    }

    if ($form->isSubmitted() && $form->isValid()) {

        $date      = $form->get('date')->getData();
        $startTime = $form->get('start_time')->getData();
        $endTime   = $form->get('end_time')->getData();
        $seance    = $planning->getSeance();

        if ($seance && strtolower(trim($seance->getTypeSeance())) === 'day off') {
            $planning->setDateDebut((clone $date)->setTime(0, 0, 0));
            $planning->setDateFin((clone $date)->setTime(23, 59, 59));
            $planning->setColor('green');
        } else {
            $planning->setDateDebut(new \DateTime(
                $date->format('Y-m-d') . ' ' . $startTime->format('H:i:s')
            ));
            $planning->setDateFin(new \DateTime(
                $date->format('Y-m-d') . ' ' . $endTime->format('H:i:s')
            ));
        }

        $planning->setUser($user);
        $em->persist($planning);
        $em->flush();

        // Notification
        $seanceLabel = $seance ? $seance->getTypeSeance() : 'Session';
        $notification = new Notification();
        $notification->setTitle('New session added');
        $notification->setMessage(sprintf(
            'Session "%s" scheduled on %s at %s',
            $seanceLabel,
            $planning->getDateDebut()->format('d/m/Y'),
            $planning->getDateDebut()->format('H:i')
        ));
        $notification->setUser($user);
        $em->persist($notification);
        $em->flush();

        // Email
        if ($user->getEmail()) {
            $projectDir = $this->getParameter('kernel.project_dir');
            $candidates = [
                $projectDir . '/public/image/logo.jpg',
                $projectDir . '/public/images/logo.jpg',
                $projectDir . '/public/logo.jpg',
            ];
            $logoPath = null;
            foreach ($candidates as $p) {
                if (file_exists($p) && is_readable($p)) { $logoPath = $p; break; }
            }

            $imgSrc = null;
            if ($logoPath) {
                try {
                    $dataImg = file_get_contents($logoPath);
                    if ($dataImg !== false) {
                        $mime   = mime_content_type($logoPath) ?: 'image/jpeg';
                        $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($dataImg);
                    }
                } catch (\Throwable $e) {}
            }
            if (!$imgSrc) {
                $imgSrc = rtrim($request->getSchemeAndHttpHost(), '/') . '/image/logo.jpg';
            }

            $planningUrl    = $urlGenerator->generate('app_planning', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $username       = htmlspecialchars((string) $user->getUsername(), ENT_QUOTES, 'UTF-8');
            $seanceSafe     = htmlspecialchars((string) $seanceLabel, ENT_QUOTES, 'UTF-8');
            $dateFormatted  = $planning->getDateDebut()->format('d/m/Y');
            $startFormatted = $planning->getDateDebut()->format('H:i');
            $endFormatted   = $planning->getDateFin()   ? $planning->getDateFin()->format('H:i') : '-';

            $htmlBody = <<<HTML
<!doctype html>
<html><head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#111;margin:0;padding:0;background:#f7f8fb">
  <div style="max-width:680px;margin:24px auto;background:#fff;padding:20px;border-radius:10px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <img src="{$imgSrc}" alt="RLIFE" style="height:56px;border-radius:6px;display:block">
      <div>
        <div style="font-weight:700;font-size:18px">RLIFE — Scheduling</div>
        <div style="color:#6b7280;font-size:13px">Professional schedule notifications</div>
      </div>
    </div>
    <h2 style="margin-top:6px">Your session has been scheduled</h2>
    <p>Hello {$username},</p>
    <p>We successfully added the following session to your calendar:</p>
    <div style="background:#f8fafc;border:1px solid #eef2ff;padding:12px;border-radius:8px">
      <ul style="margin:0;padding-left:18px">
        <li><strong>Session:</strong> {$seanceSafe}</li>
        <li><strong>Date:</strong> {$dateFormatted}</li>
        <li><strong>Start:</strong> {$startFormatted}</li>
        <li><strong>End:</strong> {$endFormatted}</li>
      </ul>
    </div>
    <p style="margin-top:18px">Thanks,<br><strong>The RLIFE Team</strong></p>
  </div>
</body></html>
HTML;

            $email = (new Email())
                ->from('yassine.mlaouah@cme.tn')
                ->to($user->getEmail())
                ->subject('Your schedule has been added — RLIFE')
                ->html($htmlBody)
                ->text("Hello {$user->getUsername()},\n\nYour session \"{$seanceLabel}\" has been scheduled on {$dateFormatted} from {$startFormatted} to {$endFormatted}.\n\nOpen your schedule: {$planningUrl}\n\nSincerely,\nThe RLIFE Team");

            try { $mailer->send($email); } catch (\Throwable $e) { /* silencieux */ }
        }

        $this->addFlash('success', 'Event created successfully!');
        return $this->redirectToRoute('app_planning');
    }

    // Notifications header
    $notifications = $em->getRepository(Notification::class)->findBy(
        ['user' => $user, 'isRead' => false],
        ['createdAt' => 'DESC'],
        5
    );

    return $this->render('pages/planning/new.html.twig', [
        'form'            => $form->createView(),
        'current_month'   => $month,
        'current_year'    => $year,
        'days_in_month'   => $daysInMonth,
        'first_day_of_week' => $firstDayOfWeek,
        'today'           => $today->format('Y-m-d'),
        'events_by_date'  => $eventsByDate,
        'notifications'   => $notifications,
    ]);
}


    #[Route('/planning/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
public function edit(int $id, Request $request, EntityManagerInterface $em): Response
{
    $planning = $em->getRepository(Planning::class)->find($id);
    if (!$planning) {
        throw $this->createNotFoundException('Event not found');
    }

    $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);
    $errors = [];

    if ($request->isMethod('POST')) {
        // 1. Lecture des champs
        $seanceId = (int) $request->request->get('seance_id', 0);
        $date = trim((string) $request->request->get('date', ''));
        $startTime = trim((string) $request->request->get('start_time', ''));
        $endTime   = trim((string) $request->request->get('end_time', ''));
        $color     = trim((string) $request->request->get('color', 'indigo'));
        $feedbackRaw = $request->request->get('feedback', '');

        // 2. Validation champs requis
        $seance = $em->getRepository(Seance::class)->find($seanceId);
        if (!$seance) {
            $errors['seance_id'] = 'Please select a valid session.';
        }
        if ($date === '') {
            $errors['date'] = 'Date is required.';
        }
        if ($startTime === '' || $endTime === '') {
            $errors['time'] = 'Start and end time are required.';
        }
        if ($color === '') {
            $errors['color'] = 'Please select a color.';
        }

        // 3. Validation de la cohérence date/heure
        $dateDebut = $dateFin = null;
        if ($date && $startTime && $endTime) {
            try {
                $dateDebut = new \DateTimeImmutable($date . ' ' . $startTime);
                $dateFin   = new \DateTimeImmutable($date . ' ' . $endTime);
            } catch (\Exception $e) {
                $errors['date'] = 'Invalid date or time format.';
            }
            if ($dateDebut && $dateFin && $dateFin <= $dateDebut) {
                $errors['date'] = 'End time must be after start time.';
            }
        }

        // 4. Collision horaire (hors feedback)
        if ($dateDebut && $dateFin && $seance && empty($errors)) {
            $conflict = $em->getRepository(Planning::class)->createQueryBuilder('p')
                ->where('p.seance = :seance')
                ->andWhere('p.dateDebut < :fin')
                ->andWhere('p.dateFin > :debut')
                ->andWhere('p.id != :id')
                ->setParameter('seance', $seance)
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin)
                ->setParameter('id', $planning->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if ($conflict !== null) {
                $errors['collision'] = 'Another event for this session already exists in this time slot.';
            }
        }

        // 5. Stocker les valeurs si tout va bien
        if (empty($errors)) {
            $planning->setSeance($seance);
            $planning->setDateDebut(\DateTime::createFromImmutable($dateDebut));
            $planning->setDateFin(\DateTime::createFromImmutable($dateFin));
            $planning->setColor($color);

            // 6. Feedback: autorisé SEULEMENT si séance finie
            $feedback = ($feedbackRaw === '' ? null : (int) $feedbackRaw);
            if ($feedback !== null && $dateFin > new \DateTimeImmutable()) {
                // La séance n'est pas terminée, feedback interdit
                $errors['feedback'] = 'Feedback can only be updated after the event is finished.';
            } else {
                $planning->setFeedback($feedback);
            }
        }

        // 7. Si pas d’erreurs, flush !
        if (empty($errors)) {
            $em->flush();
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('app_planning');
        }
    }

    // Renvoyer view avec erreurs et valeurs
    return $this->render('pages/planning/edit.html.twig', [
        'planning' => $planning,
        'seances'  => $seances,
        'errors'   => $errors,
    ]);
}
    #[Route('/{id}/delete', name: 'app_planning_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $planning = $em->getRepository(Planning::class)->find($id);
        if (!$planning) {
            throw $this->createNotFoundException('Event not found');
        }

        $em->remove($planning);
        $em->flush();

        $this->addFlash('success', 'Event deleted successfully!');
        return $this->redirectToRoute('app_planning');
    }
    #[Route('/week', name: 'app_planning_week', methods: ['GET'])]
public function week(
    Request $request,
    EntityManagerInterface $em,
    GoogleCalendarClient $googleCalendar
): Response {
    $today = new \DateTimeImmutable();

    // date=YYYY-MM-DD (un jour quelconque de la semaine)
    $dateStr = (string) $request->query->get('date', $today->format('Y-m-d'));
    try {
        $anchor = new \DateTimeImmutable($dateStr);
    } catch (\Throwable) {
        $anchor = $today;
    }

    // Lundi -> Dimanche
    $startOfWeek = $anchor->modify('monday this week')->setTime(0, 0, 0);
    $endOfWeek   = $anchor->modify('sunday this week')->setTime(23, 59, 59);

    // plage d'heures affichées
    $hourStart = max(0, min(23, (int) $request->query->get('h_start', 8)));
    $hourEnd   = max(0, min(23, (int) $request->query->get('h_end', 20)));
    if ($hourEnd < $hourStart) {
        [$hourStart, $hourEnd] = [$hourEnd, $hourStart];
    }
    $hours = range($hourStart, $hourEnd);

    // jours de la semaine
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $weekDays[] = $startOfWeek->modify("+$i day");
    }

    // UI map (repris de index)
    $uiMap = [
        'indigo' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-200', 'bar' => 'bg-indigo-500'],
        'teal'   => ['bg' => 'bg-teal-100 dark:bg-teal-900/30',     'text' => 'text-teal-700 dark:text-teal-200',     'bar' => 'bg-teal-500'],
        'amber'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',   'text' => 'text-amber-700 dark:text-amber-200',   'bar' => 'bg-amber-500'],
        'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',     'text' => 'text-blue-700 dark:text-blue-200',     'bar' => 'bg-blue-500'],
        'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30',   'text' => 'text-green-700 dark:text-green-200',   'bar' => 'bg-green-500'],
        'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30',       'text' => 'text-red-700 dark:text-red-200',       'bar' => 'bg-red-500'],
        'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-200', 'bar' => 'bg-purple-500'],
        'pink'   => ['bg' => 'bg-pink-100 dark:bg-pink-900/30',     'text' => 'text-pink-700 dark:text-pink-200',     'bar' => 'bg-pink-500'],
    ];

    // Charger plannings DB de la semaine (filtré par user connecté)
    $user = $this->getUser();
    if ($user instanceof User) {
        $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.dateDebut BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    } else {
        $plannings = [];
    }

    // events groupés par jour + heure
    $eventsByDayHour = [];

    foreach ($plannings as $p) {
        $start = $p->getDateDebut();
        if (!$start instanceof \DateTimeInterface) {
            continue;
        }
        $end = $p->getDateFin();

        $dayKey = $start->format('Y-m-d');
        $hourKey = (int) $start->format('G'); // 0-23

        $color = $p->getColor() ?? 'indigo';
        $ui = $uiMap[$color] ?? $uiMap['indigo'];

        $eventsByDayHour[$dayKey] ??= [];
        $eventsByDayHour[$dayKey][$hourKey] ??= [];

        $eventsByDayHour[$dayKey][$hourKey][] = [
            'id' => $p->getId(),
            'title' => $p->getSeance() ? (string) $p->getSeance() : 'Session',
            'date' => $dayKey,
            'start_time' => $start->format('H:i'),
            'end_time' => $end instanceof \DateTimeInterface ? $end->format('H:i') : null,
            'ui' => $ui,
            'type' => 'class',
            'is_google' => false,
        ];
    }

    // Google events semaine (optionnel, même logique que index)
    $google_events_count = 0;
    $google_load_error = null;

    if ($user instanceof User) {
        try {
            // Si tu n'as pas cette méthode, je te montre comment l'émuler après.
            $googleEvents = $googleCalendar->listEventsForRange($user, $startOfWeek, $endOfWeek);

            foreach ($googleEvents as $ge) {
                if (empty($ge['start'])) continue;

                $start = new \DateTimeImmutable($ge['start']);
                $dayKey = $start->format('Y-m-d');
                $hourKey = (int) $start->format('G');

                $eventsByDayHour[$dayKey] ??= [];
                $eventsByDayHour[$dayKey][$hourKey] ??= [];

                $eventsByDayHour[$dayKey][$hourKey][] = [
                    'id' => 'google:' . ($ge['id'] ?? uniqid('google_', true)),
                    'title' => $ge['summary'] ?? '(Google event)',
                    'date' => $dayKey,
                    'start_time' => $start->format('H:i'),
                    'end_time' => null,
                    'ui' => [
                        'bg' => 'bg-green-100 dark:bg-green-900/30',
                        'text' => 'text-green-800 dark:text-green-200',
                        'bar' => 'bg-green-500',
                    ],
                    'type' => 'google',
                    'is_google' => true,
                ];
                $google_events_count++;
            }
        } catch (\Throwable $e) {
            $google_load_error = $e->getMessage();
        }
    }

    // Trier les events dans chaque slot (heure)
    foreach ($eventsByDayHour as $dayKey => $hoursMap) {
        foreach ($hoursMap as $h => $list) {
            usort($list, static fn(array $a, array $b) => strcmp((string)$a['start_time'], (string)$b['start_time']));
            $eventsByDayHour[$dayKey][$h] = $list;
        }
    }

    return $this->render('pages/planning/week.html.twig', [
        'week_days' => $weekDays,
        'hours' => $hours,
        'events_by_day_hour' => $eventsByDayHour,
        'anchor_date' => $anchor->format('Y-m-d'),
        'h_start' => $hourStart,
        'h_end' => $hourEnd,

        'google_events_count' => $google_events_count,
        'google_load_error' => $google_load_error,
    ]);
}
#[Route('/planning/{id}/feedback/form', name: 'app_planning_feedback_form', methods: ['GET', 'POST'])]
public function feedbackForm(int $id, Request $request, EntityManagerInterface $em): Response
{
    $planning = $em->getRepository(Planning::class)->find($id);

    // Vérifie les droits, la fin de séance, etc.
    if (!$planning) {
        throw $this->createNotFoundException('Planning not found');
    }
    if ($planning->getDateFin() > new \DateTime()) {
        $this->addFlash('error', "You can only give feedback after the session has ended.");
        return $this->redirectToRoute('app_planning');
    }
    if ($planning->getUser() && $this->getUser() && $planning->getUser()->getId() !== $this->getUser()->getId()) {
        throw $this->createAccessDeniedException();
    }

    $form = $this->createFormBuilder($planning)
        ->add('feedback', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'label' => 'Your feedback',
            'choices' => [
                '😡 Very Bad' => 1,
                '😕 Bad'      => 2,
                '😐 Medium'        => 3,
                '🙂 Good'         => 4,
                '🤩 Excellent'    => 5,
            ],
            'expanded' => true,
            'required' => true,
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('feedback_success', $planning->getId());
        $this->addFlash('feedback_value', $planning->getFeedback());
        $this->addFlash('feedback_title', $planning->getSeance()?->getTypeSeance() ?? 'Session');
        $this->addFlash('success', 'Thank you for your feedback !');
        return $this->redirectToRoute('app_planning');
    }

    return $this->render('pages/planning/feedback.html.twig', [
        'planning' => $planning,
        'form' => $form->createView(),
    ]);
}
#[Route('/favorite-team/check', name: 'app_check_favorite_team')]
public function checkFavoriteTeam(EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['hasTeam' => false]);
    }

    $favoriteTeam = $em->getRepository(UserFavoriteTeam::class)
        ->findOneBy(['user' => $user]);

    if (!$favoriteTeam) {
        return new JsonResponse(['hasTeam' => false]);
    }

    return new JsonResponse([
        'hasTeam' => true,
        'team' => [
            'name' => $favoriteTeam->getTeamName(),
            'sportType' => $favoriteTeam->getSportType(),
            'logo' => $favoriteTeam->getTeamLogo(),
            'country' => $favoriteTeam->getTeamCountry(),
            'apiId' => $favoriteTeam->getTeamApiId(),
        ]
    ]);
}

/**
 * Sauvegarder l'équipe favorite
 */
private HttpClientInterface $httpClient;

public function __construct(HttpClientInterface $httpClient)
{
    $this->httpClient = $httpClient;
}

/**
 * Récupère le prochain match d'une équipe via football-data.org
 * @return array|null [home, away, date, competition, status] ou null si erreur/pas de match
 */
private function getNextMatchForTeam(int $teamApiId): ?array
{
    $apiKey = $this->getParameter('football_data_api_key'); // À définir dans services.yaml ou .env

    if (!$apiKey) {
        return null;
    }

    try {
        $response = $this->httpClient->request('GET', "https://api.football-data.org/v4/teams/{$teamApiId}/matches", [
            'query' => [
                'status' => 'SCHEDULED',
                'limit'  => 1,
            ],
            'headers' => [
                'X-Auth-Token' => $apiKey,
                'Accept'       => 'application/json',
            ],
        ]);

        $data = $response->toArray();

        if (empty($data['matches'])) {
            return null;
        }

        $match = $data['matches'][0];

        return [
            'home'        => $match['homeTeam']['shortName'] ?? $match['homeTeam']['name'] ?? '???',
            'away'        => $match['awayTeam']['shortName'] ?? $match['awayTeam']['name'] ?? '???',
            'date'        => (new \DateTime($match['utcDate']))->format('d M Y H:i'),
            'competition' => $match['competition']['name'] ?? 'Inconnue',
            'status'      => $match['status'] ?? 'SCHEDULED',
        ];
    } catch (\Exception $e) {
        // Log l'erreur si tu veux (LoggerInterface)
        return null;
    }
}
#[Route('/planning/set-favorite-team', name: 'app_set_favorite_team', methods: ['POST'])]
public function setFavoriteTeam(
    Request $request,
    EntityManagerInterface $em,
    UserFavoriteTeamRepository $favoriteRepo,
    SportsApiService $sportsApiService
): Response
{
    $teamId   = $request->request->get('team_id');
    $teamName = $request->request->get('team_name');

    // Validation de base
    if (!$teamId || !is_numeric($teamId)) {
        $this->addFlash('error', 'Veuillez choisir une équipe valide.');
        return $this->redirectToRoute('app_planning');
    }

    $user = $this->getUser();
    if (!$user) {
        $this->addFlash('error', 'please connect.');
        return $this->redirectToRoute('app_planning');
    }

    // Récupérer ou créer l'entité favorite
    $favorite = $favoriteRepo->findOneBy(['user' => $user]); // ← findOneBy au lieu de findByUser (plus standard)

    if (!$favorite) {
        $favorite = new UserFavoriteTeam();
        $favorite->setUser($user);
        $favorite->setSportType('football');
        $em->persist($favorite);
    }

    // Toujours stocker l'ID API
    $favorite->setTeamApiId((int) $teamId);

    // Tenter de récupérer le nom complet via l'API
    $apiTeamName = null;
    try {
        $apiTeamName = $sportsApiService->getTeamName((int) $teamId);
        if ($apiTeamName) {
            $favorite->setTeamName($apiTeamName);
            $this->addFlash('success', "Team updated : {$apiTeamName}");
        }
    } catch (\Exception $e) {
        // Log l'erreur (optionnel)
        // error_log("Erreur API team name: " . $e->getMessage());

        // Fallback : on garde le nom envoyé par le formulaire (ou un placeholder)
        if ($teamName && trim($teamName) !== '') {
            $favorite->setTeamName(trim($teamName));
        } else {
            $favorite->setTeamName('Team #' . $teamId); // Fallback ultime
        }

        $this->addFlash('warning', 'Nom complet non récupéré via l\'API (utilisation du nom saisi).');
    }

    $em->flush();

    // Option : forcer le rechargement des données en session si tu les caches
    // $this->get('session')->set('favorite_team_refreshed', time());

    return $this->redirectToRoute('app_planning');
}
#[Route('/add-match', name: 'app_planning_add_match', methods: ['POST'])]
public function addMatch(
    Request $request,
    EntityManagerInterface $em,
    MailerInterface $mailer,
    UrlGeneratorInterface $urlGenerator
): Response {
    $user = $this->getUser();
    if (!$user instanceof User) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_planning');
    }

    $title     = trim((string) $request->request->get('title', ''));
    $dateStr   = trim((string) $request->request->get('date', ''));
    $startTime = trim((string) $request->request->get('start_time', '15:00:00'));
    $endTime   = trim((string) $request->request->get('end_time', '17:00:00'));

    if ($title === '' || $dateStr === '') {
        $this->addFlash('error', 'Title and date are required.');
        return $this->redirectToRoute('app_planning');
    }

    if (strlen($startTime) === 5) $startTime .= ':00';
    if (strlen($endTime)   === 5) $endTime   .= ':00';

    $dateDebut = \DateTime::createFromFormat('Y-m-d H:i:s', "$dateStr $startTime");
    $dateFin   = \DateTime::createFromFormat('Y-m-d H:i:s', "$dateStr $endTime");

    if (!$dateDebut || !$dateFin) {
        $this->addFlash('error', 'Invalid date or time format.');
        return $this->redirectToRoute('app_planning');
    }

    if ($dateFin <= $dateDebut) {
        $dateFin = clone $dateDebut;
        $dateFin->modify('+2 hours');
    }

    // Duplicate check
    $existingPlanning = $em->getRepository(Planning::class)->createQueryBuilder('p')
        ->where('p.user = :user')
        ->andWhere('p.dateDebut = :dateDebut')
        ->setParameter('user', $user)
        ->setParameter('dateDebut', $dateDebut)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();

    if ($existingPlanning !== null) {
        $this->addFlash('error', 'This match is already in your planning.');
        return $this->redirectToRoute('app_planning');
    }

    // Seance "Match"
    $seance = $em->getRepository(Seance::class)->findOneBy(['typeSeance' => 'Match']);
    if (!$seance) {
        foreach ($em->getRepository(Seance::class)->findAll() as $s) {
            if (stripos((string) $s->getTypeSeance(), 'match') !== false) {
                $seance = $s;
                break;
            }
        }
    }
    if (!$seance) {
        $seance = $em->getRepository(Seance::class)->findOneBy([]);
    }

    $planning = new Planning();
    $planning->setUser($user);
    $planning->setDateDebut($dateDebut);
    $planning->setDateFin($dateFin);
    $planning->setColor('indigo');

    if ($seance) {
        $planning->setSeance($seance);
    }

    $em->persist($planning);
    $em->flush();

    $notification = new Notification();
    $notification->setTitle('Match added to schedule');
    $notification->setMessage(sprintf(
        'The match "%s" has been added on %s at %s',
        $title,
        $dateDebut->format('m/d/Y'),
        $dateDebut->format('H:i')
    ));
    $notification->setUser($user);
    $em->persist($notification);
    $em->flush();

    // Email (same as JSON)
    if ($user->getEmail()) {
        $projectDir = $this->getParameter('kernel.project_dir');
        $candidates = [
            $projectDir . '/public/image/logo.jpg',
            $projectDir . '/public/images/logo.jpg',
            $projectDir . '/public/logo.jpg',
        ];
        $logoPath = null;
        foreach ($candidates as $p) {
            if (file_exists($p) && is_readable($p)) { $logoPath = $p; break; }
        }

        $imgSrc = null;
        if ($logoPath) {
            try {
                $dataImg = file_get_contents($logoPath);
                if ($dataImg !== false) {
                    $mime = mime_content_type($logoPath) ?: 'image/jpeg';
                    $imgSrc = 'data:' . $mime . ';base64,' . base64_encode($dataImg);
                }
            } catch (\Throwable $e) {}
        }
        if (!$imgSrc) {
            $imgSrc = rtrim($request->getSchemeAndHttpHost(), '/') . '/image/logo.jpg';
        }

        $planningUrl = $urlGenerator->generate('app_planning', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $username    = htmlspecialchars((string) $user->getUsername(), ENT_QUOTES, 'UTF-8');
        $titleSafe   = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        $htmlBody = <<<HTML
<!doctype html>
<html><head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#111;background:#f7f8fb;margin:0;padding:0">
  <div style="max-width:680px;margin:24px auto;background:#fff;padding:20px;border-radius:10px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
      <img src="{$imgSrc}" alt="RLIFE" style="height:56px;border-radius:6px;">
      <div>
        <div style="font-weight:700;font-size:18px">RLIFE — Planning</div>
        <div style="color:#6b7280;font-size:13px">Match added to your calendar</div>
      </div>
    </div>
    <h2>Match successfully added!</h2>
    <p>Hello {$username},</p>
    <p>Your match has been added to your planning:</p>
    <div style="background:#f8fafc;border:1px solid #eef2ff;padding:12px;border-radius:8px">
      <ul style="margin:0;padding-left:18px">
        <li><strong>Match:</strong> {$titleSafe}</li>
        <li><strong>Date:</strong> {$dateDebut->format('m/d/Y')}</li>
        <li><strong>Time:</strong> {$dateDebut->format('H:i')} – {$dateFin->format('H:i')}</li>
      </ul>
    </div>
    <p style="margin-top:18px">
      <a href="{$planningUrl}" style="background:#4f46e5;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:700;">
        View my planning
      </a>
    </p>
    <p style="margin-top:18px">Enjoy the match!<br><strong>The RLIFE Team</strong></p>
  </div>
</body></html>
HTML;

        $email = (new Email())
            ->from('yassine.mlaouah@cme.tn')
            ->to($user->getEmail())
            ->subject('Match added to your planning — RLIFE')
            ->html($htmlBody)
            ->text("Hello {$user->getUsername()},\n\nThe match \"{$title}\" has been added on {$dateDebut->format('m/d/Y')} at {$dateDebut->format('H:i')}.\n\nView your planning: {$planningUrl}\n\nEnjoy the match!\nThe RLIFE Team");

        try { $mailer->send($email); } catch (\Throwable $e) {}
    }

    $this->addFlash('success', 'Match successfully added to your planning!');
    return $this->redirectToRoute('app_planning');
}
}
