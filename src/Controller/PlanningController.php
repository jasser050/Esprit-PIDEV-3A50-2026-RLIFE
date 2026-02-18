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

#[Route('/planning')]
class PlanningController extends AbstractController
{
    #[Route('', name: 'app_planning', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        GoogleCalendarClient $googleCalendar
    ): Response {
        $today = new \DateTimeImmutable();

        $month = $request->query->getInt('month', (int) $today->format('n'));
        $year  = $request->query->getInt('year', (int) $today->format('Y'));

        $currentDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth = (int) $currentDate->format('t');
        $firstDayOfWeek = (int) $currentDate->format('N');

        $startOfMonth = $currentDate->setTime(0, 0, 0);
        $endOfMonth = $currentDate->modify('last day of this month')->setTime(23, 59, 59);

        // 1) Charger les plannings depuis la base (ancienne logique)
        $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
            ->andWhere('p.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        // Map couleur -> classes CSS utilisées par ton design
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

        $eventsByDate = [];
        foreach ($plannings as $p) {
            if (!$p->getDateDebut() instanceof \DateTimeInterface) {
                continue;
            }

            $dateKey = $p->getDateDebut()->format('Y-m-d');
            $eventsByDate[$dateKey] ??= [];

            $color = $p->getColor() ?? 'indigo';
            $ui = $uiMap[$color] ?? $uiMap['indigo'];

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

        // 2) Charger les seances (sidebar)
        $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);

        // 3) Google Calendar: merge dans eventsByDate (sans casser la DB)
        $google_events_count = 0;
        $google_load_error = null;

        $user = $this->getUser();
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
                        ],
                        'type' => 'google',
                        'is_google' => true,
                    ];

                    $google_events_count++;
                }

                // Optionnel: trier une journée par start_time (All day en premier)
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
        $upcoming_q = trim((string) $request->query->get('up_q', ''));
$up_sort = (string) $request->query->get('up_sort', 'date_asc');
$up_start = trim((string) $request->query->get('up_start', ''));     // YYYY-MM-DD
$up_end   = trim((string) $request->query->get('up_end', ''));       // YYYY-MM-DD
$up_tstart = trim((string) $request->query->get('up_tstart', ''));   // HH:MM
$up_tend   = trim((string) $request->query->get('up_tend', ''));     // HH:MM

// Flatten upcoming (>= today) depuis eventsByDate
$upcomingAll = [];
$todayKey = $today->format('Y-m-d');

foreach ($eventsByDate as $dateKey => $events) {
    if ($dateKey < $todayKey) {
        continue;
    }
    foreach ($events as $ev) {
        $upcomingAll[] = $ev + ['date' => $dateKey];
    }
}

// Start filtered list
$upcoming_q = trim((string) $request->query->get('up_q', ''));
$up_sort = (string) $request->query->get('up_sort', 'date_asc');

// Période choisie (YYYY-MM-DD)
$up_start = trim((string) $request->query->get('up_start', ''));
$up_end   = trim((string) $request->query->get('up_end', ''));

// Flatten upcoming (>= today) depuis eventsByDate
$upcomingAll = [];
$todayKey = $today->format('Y-m-d');

foreach ($eventsByDate as $dateKey => $events) {
    if ($dateKey < $todayKey) {
        continue;
    }
    foreach ($events as $ev) {
        $upcomingAll[] = $ev + ['date' => $dateKey];
    }
}

$upcomingFiltered = $upcomingAll;

// Filtre texte (optionnel)
if ($upcoming_q !== '') {
    $needle = mb_strtolower($upcoming_q);
    $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($needle): bool {
        $title = mb_strtolower((string)($ev['title'] ?? ''));
        return str_contains($title, $needle);
    }));
}

// Filtre période (si l'utilisateur choisit une date)
if ($up_start !== '') {
    $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($up_start): bool {
        return ((string)($ev['date'] ?? '')) >= $up_start;
    }));
}
if ($up_end !== '') {
    $upcomingFiltered = array_values(array_filter($upcomingFiltered, static function (array $ev) use ($up_end): bool {
        return ((string)($ev['date'] ?? '')) <= $up_end;
    }));
}

// Tri
usort($upcomingFiltered, static function (array $a, array $b) use ($up_sort): int {
    $ad = (string)($a['date'] ?? '');
    $bd = (string)($b['date'] ?? '');

    $at = (string)($a['start_time'] ?? '');
    $bt = (string)($b['start_time'] ?? '');

    // Normaliser l'heure : mettre "All day" tout en haut
    $atNorm = ($at === 'All day' || $at === '') ? '00:00' : $at;
    $btNorm = ($bt === 'All day' || $bt === '') ? '00:00' : $bt;

    $aTitle = (string)($a['title'] ?? '');
    $bTitle = (string)($b['title'] ?? '');

    if ($up_sort === 'title_asc') {
        return strcasecmp($aTitle, $bTitle);
    }

    // Tri par date + heure
    $cmp = strcmp($ad, $bd);
    if ($cmp === 0) {
        $cmp = strcmp($atNorm, $btNorm);
    }

    if ($up_sort === 'date_desc') {
        return -$cmp;
    }

    return $cmp; // date_asc
});

        return $this->render('pages/planning/index.html.twig', [
            'current_month' => $month,
            'current_year' => $year,
            'days_in_month' => $daysInMonth,
            'first_day_of_week' => $firstDayOfWeek,
            'events_by_date' => $eventsByDate,
            'conflicts' => [],
            'today' => $today->format('Y-m-d'),
            'seances' => $seances,
            'up_sort' => $up_sort,
            'up_start' => $up_start,
            'up_end' => $up_end,
            // debug google
            'google_events_count' => $google_events_count,
            'google_load_error' => $google_load_error,
            'upcoming_q' => $upcoming_q,
            'upcoming' => $upcomingFiltered,
            'upcoming_total' => count($upcomingAll),


        ]);
    }
#[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $planning = new Planning();
    $form = $this->createForm(\App\Form\PlanningType::class, $planning);
    $form->handleRequest($request);


    // Validation manuelle "Choose a session"
    if ($form->isSubmitted() && $form->isValid()) {
    $date      = $form->get('date')->getData();
    $startTime = $form->get('start_time')->getData();
    $endTime   = $form->get('end_time')->getData();

    // PATCH pour DateTimeImmutable -> DateTime
    if ($date && $startTime && $endTime) {
        $dateDebut = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
        $dateFin   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' ' . $endTime->format('H:i:s'));
        $planning->setDateDebut(\DateTime::createFromImmutable($dateDebut));
        $planning->setDateFin(\DateTime::createFromImmutable($dateFin));
    }

    // PATCH pour l'utilisateur
    $user = $this->getUser();
    if (!$user instanceof User) {
        throw $this->createAccessDeniedException('You must be logged in.');
    }
    $planning->setUser($user);

    $em->persist($planning);
    $em->flush();
    $this->addFlash('success', 'Event created!');
    return $this->redirectToRoute('app_planning');
}
    $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);
    return $this->render('pages/planning/new.html.twig', [
        'seances' => $seances,
        'form' => $form->createView(),
    ]);
}

    #[Route('/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $planning = $em->getRepository(Planning::class)->find($id);
        if (!$planning) {
            throw $this->createNotFoundException('Event not found');
        }

        if ($request->isMethod('POST')) {
            $seanceId = (int) $request->request->get('seance_id', 0);
            $date = (string) $request->request->get('date', '');
            $startTime = (string) $request->request->get('start_time', '');
            $endTime = (string) $request->request->get('end_time', '');
            $color = (string) $request->request->get('color', 'indigo');

            $feedbackRaw = $request->request->get('feedback');
            $feedback = ($feedbackRaw === null || $feedbackRaw === '') ? null : (int) $feedbackRaw;

            $seance = $em->getRepository(Seance::class)->find($seanceId);
            if (!$seance) {
                $this->addFlash('error', 'Séance introuvable.');
                return $this->redirectToRoute('app_planning_edit', ['id' => $id]);
            }

            if ($date === '' || $startTime === '' || $endTime === '') {
                $this->addFlash('error', 'Veuillez remplir la date et les heures.');
                return $this->redirectToRoute('app_planning_edit', ['id' => $id]);
            }

            try {
                $dateDebut = new \DateTimeImmutable(trim($date . ' ' . $startTime));
                $dateFin = new \DateTimeImmutable(trim($date . ' ' . $endTime));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date/heure invalide.');
                return $this->redirectToRoute('app_planning_edit', ['id' => $id]);
            }

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date/heure de fin doit être après le début.');
                return $this->redirectToRoute('app_planning_edit', ['id' => $id]);
            }

            $planning->setSeance($seance);
            $planning->setDateDebut(\DateTime::createFromImmutable($dateDebut));
            $planning->setDateFin(\DateTime::createFromImmutable($dateFin));
            $planning->setColor($color);
            $planning->setFeedback($feedback);

            $em->flush();

            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('app_planning');
        }

        $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);

        return $this->render('pages/planning/edit.html.twig', [
            'planning' => $planning,
            'seances' => $seances,
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

    // Charger plannings DB de la semaine
    $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
        ->andWhere('p.dateDebut BETWEEN :start AND :end')
        ->setParameter('start', $startOfWeek)
        ->setParameter('end', $endOfWeek)
        ->orderBy('p.dateDebut', 'ASC')
        ->getQuery()
        ->getResult();

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

    $user = $this->getUser();
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
}