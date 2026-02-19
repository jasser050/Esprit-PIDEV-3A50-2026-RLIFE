<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\User;
use App\Form\DeckType;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\GoogleCalendarClient;
use App\Entity\Planning;           
use App\Entity\Seance;
use App\Entity\TypeSeance;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        $totalUsers = $userRepository->count([]);
        $activeUsers = $userRepository->count(['isBanned' => false]);
        $bannedUsers = $userRepository->count(['isBanned' => true]);
        $adminUsers = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
        
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        return $this->render('admin/dashboard.html.twig', [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'banned_users' => $bannedUsers,
            'admin_users' => $adminUsers,
            'recent_users' => $recentUsers,
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function users(EntityManagerInterface $entityManager, Request $request): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        $filter = $request->query->get('filter', 'all');
        
        $queryBuilder = $userRepository->createQueryBuilder('u');
        
        if ($filter === 'banned') {
            $queryBuilder->where('u.isBanned = true');
        } elseif ($filter === 'active') {
            $queryBuilder->where('u.isBanned = false');
        } elseif ($filter === 'admin') {
            $queryBuilder->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');
        }
        
        $users = $queryBuilder->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'filter' => $filter,
        ]);
    }

    #[Route('/users/{id}/ban', name: 'app_admin_user_ban', methods: ['POST'])]
    public function banUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reason = $request->request->get('reason', 'Violation of terms of service');
        
        $user->setIsBanned(true);
        $user->setBannedAt(new \DateTimeImmutable());
        $user->setBanReason($reason);
        
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('User %s has been banned.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/unban', name: 'app_admin_user_unban', methods: ['POST'])]
    public function unbanUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsBanned(false);
        $user->setBannedAt(null);
        $user->setBanReason(null);
        
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('User %s has been unbanned.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/make-admin', name: 'app_admin_user_make_admin', methods: ['POST'])]
    public function makeAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles($roles);
            $entityManager->flush();
            
            $this->addFlash('success', sprintf('User %s is now an admin.', $user->getEmail()));
        }
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/users/{id}/remove-admin', name: 'app_admin_user_remove_admin', methods: ['POST'])]
    public function removeAdmin(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_ADMIN');
        $user->setRoles($roles);
        $entityManager->flush();
        
        $this->addFlash('success', sprintf('Admin role removed from %s.', $user->getEmail()));
        
        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/statistics', name: 'app_admin_statistics')]
    public function statistics(EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(User::class);
        
        $userGrowth = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $date->setTime(0, 0, 0);
            $nextDate = clone $date;
            $nextDate->modify('+1 day');
            
            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.createdAt >= :start')
                ->andWhere('u.createdAt < :end')
                ->setParameter('start', $date)
                ->setParameter('end', $nextDate)
                ->getQuery()
                ->getSingleScalarResult();
            
            $userGrowth[] = [
                'date' => $date->format('M d'),
                'count' => $count,
            ];
        }
        
        $genderStats = $userRepository->createQueryBuilder('u')
            ->select('u.gender, COUNT(u.id) as count')
            ->groupBy('u.gender')
            ->getQuery()
            ->getResult();
        
        $universityStats = $userRepository->createQueryBuilder('u')
            ->select('u.university, COUNT(u.id) as count')
            ->where('u.university IS NOT NULL')
            ->groupBy('u.university')
            ->orderBy('count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/statistics.html.twig', [
            'user_growth' => $userGrowth,
            'gender_stats' => $genderStats,
            'university_stats' => $universityStats,
        ]);
    }

    #[Route('/revision', name: 'app_admin_revision', methods: ['GET', 'POST'])]
    public function revision(
        Request $request,
        EntityManagerInterface $em,
        DeckRepository $deckRepository
    ): Response
    {
        $deck = new Deck();
        $form = $this->createForm(DeckType::class, $deck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/decks/';

            if ($imageFile = $form->get('imageFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move($uploadDir, $filename);
                    $deck->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
                }
            }

            if ($pdfFile = $form->get('pdfFile')->getData()) {
                $filename = md5(uniqid()) . '.' . $pdfFile->guessExtension();
                try {
                    $pdfFile->move($uploadDir, $filename);
                    $deck->setPdf($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF : ' . $e->getMessage());
                }
            }

            $deck->setUser($this->getUser());
            $deck->setDateCreation(new \DateTime());

            $em->persist($deck);
            $em->flush();

            $this->addFlash('success', 'Deck créé avec succès !');

            return $this->redirectToRoute('app_admin_revision');
        }

        $decks = $deckRepository->findAll();

        $recentChanges = [
            ['entity' => 'Deck', 'id' => 1, 'action' => 'created', 'by' => $this->getUser()->getEmail() ?? 'system', 'at' => new \DateTime('-5 minutes')],
        ];

        return $this->render('admin/revision.html.twig', [
            'form'           => $form->createView(),
            'recent_changes' => $recentChanges,
            'decks'          => $decks,
        ]);
    }
    // #[Route('/admin', name: 'app_admin_index')]
    // public function index(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     GoogleCalendarClient $googleCalendar
    // ): Response {
    //     $today = new \DateTimeImmutable();

    //     $month = $request->query->getInt('month', (int) $today->format('n'));
    //     $year  = $request->query->getInt('year', (int) $today->format('Y'));

    //     $currentDate = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    //     $daysInMonth = (int) $currentDate->format('t');
    //     $firstDayOfWeek = (int) $currentDate->format('N');

    //     $startOfMonth = $currentDate->setTime(0, 0, 0);
    //     $endOfMonth = $currentDate->modify('last day of this month')->setTime(23, 59, 59);

    //     // Charger les plannings depuis la base
    //     $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
    //         ->andWhere('p.dateDebut BETWEEN :start AND :end')
    //         ->setParameter('start', $startOfMonth)
    //         ->setParameter('end', $endOfMonth)
    //         ->orderBy('p.dateDebut', 'ASC')
    //         ->getQuery()
    //         ->getResult();

    //     // Map couleur -> classes CSS
    //     $uiMap = [
    //         'indigo' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-700 dark:text-indigo-200', 'bar' => 'bg-indigo-500'],
    //         'teal'   => ['bg' => 'bg-teal-100 dark:bg-teal-900/30',     'text' => 'text-teal-700 dark:text-teal-200',     'bar' => 'bg-teal-500'],
    //         'amber'  => ['bg' => 'bg-amber-100 dark:bg-amber-900/30',   'text' => 'text-amber-700 dark:text-amber-200',   'bar' => 'bg-amber-500'],
    //         'blue'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/30',     'text' => 'text-blue-700 dark:text-blue-200',     'bar' => 'bg-blue-500'],
    //         'green'  => ['bg' => 'bg-green-100 dark:bg-green-900/30',   'text' => 'text-green-700 dark:text-green-200',   'bar' => 'bg-green-500'],
    //         'red'    => ['bg' => 'bg-red-100 dark:bg-red-900/30',       'text' => 'text-red-700 dark:text-red-200',       'bar' => 'bg-red-500'],
    //         'purple' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-200', 'bar' => 'bg-purple-500'],
    //         'pink'   => ['bg' => 'bg-pink-100 dark:bg-pink-900/30',     'text' => 'text-pink-700 dark:text-pink-200',     'bar' => 'bg-pink-500'],
    //     ];

    //     $eventsByDate = [];
    //     foreach ($plannings as $p) {
    //         if (!$p->getDateDebut() instanceof \DateTimeInterface) {
    //             continue;
    //         }

    //         $dateKey = $p->getDateDebut()->format('Y-m-d');
    //         $eventsByDate[$dateKey] ??= [];

    //         $color = $p->getColor() ?? 'indigo';
    //         $ui = $uiMap[$color] ?? $uiMap['indigo'];

    //         $eventsByDate[$dateKey][] = [
    //             'id' => $p->getId(),
    //             'title' => $p->getSeance() ? (string) $p->getSeance() : 'Séance',
    //             'date' => $dateKey,
    //             'start_time' => $p->getDateDebut()->format('H:i'),
    //             'end_time' => $p->getDateFin() instanceof \DateTimeInterface ? $p->getDateFin()->format('H:i') : null,
    //             'color' => $color,
    //             'feedback' => $p->getFeedback(),
    //             'ui' => $ui,
    //             'type' => 'class',
    //             'is_google' => false,
    //         ];
    //     }

    //     // Google Calendar: merge dans eventsByDate
    //     $google_events_count = 0;
    //     $google_load_error = null;

    //     $user = $this->getUser();
    //     if ($user instanceof User) {
    //         try {
    //             $googleEvents = $googleCalendar->listEventsForMonth($user, $year, $month);

    //             foreach ($googleEvents as $ge) {
    //                 if (empty($ge['start'])) {
    //                     continue;
    //                 }

    //                 $start = new \DateTimeImmutable($ge['start']);
    //                 $dateKey = $start->format('Y-m-d');

    //                 $startTime = $start->format('H:i');
    //                 if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $ge['start'])) {
    //                     $startTime = 'All day';
    //                 }

    //                 $eventsByDate[$dateKey] ??= [];
    //                 $eventsByDate[$dateKey][] = [
    //                     'id' => 'google:' . ($ge['id'] ?? uniqid('google_', true)),
    //                     'title' => $ge['summary'] ?? '(Google event)',
    //                     'date' => $dateKey,
    //                     'start_time' => $startTime,
    //                     'end_time' => null,
    //                     'color' => 'green',
    //                     'feedback' => null,
    //                     'ui' => [
    //                         'bg' => 'bg-green-100 dark:bg-green-900/30',
    //                         'text' => 'text-green-800 dark:text-green-200',
    //                         'bar' => 'bg-green-500',
    //                     ],
    //                     'type' => 'google',
    //                     'is_google' => true,
    //                 ];

    //                 $google_events_count++;
    //             }

    //             // Trier par start_time
    //             foreach ($eventsByDate as $k => $list) {
    //                 usort($list, static function (array $a, array $b): int {
    //                     $ta = (string)($a['start_time'] ?? '');
    //                     $tb = (string)($b['start_time'] ?? '');
    //                     if ($ta === 'All day' && $tb !== 'All day') return -1;
    //                     if ($tb === 'All day' && $ta !== 'All day') return 1;
    //                     return strcmp($ta, $tb);
    //                 });
    //                 $eventsByDate[$k] = $list;
    //             }
    //         } catch (\Throwable $e) {
    //             $google_load_error = $e->getMessage();
    //         }
    //     }

    //     return $this->render('admin/planning/index.html.twig', [
    //         'current_month' => $month,
    //         'current_year' => $year,
    //         'days_in_month' => $daysInMonth,
    //         'first_day_of_week' => $firstDayOfWeek,
    //         'events_by_date' => $eventsByDate,
    //         'today' => $today->format('Y-m-d'),
    //         'google_events_count' => $google_events_count,
    //         'google_load_error' => $google_load_error,
    //     ]);
    // }

    // #[Route('/admin/planning', name: 'app_admin_planning')]
    // public function planning(
    //     Request $request,
    //     EntityManagerInterface $em,
    //     GoogleCalendarClient $googleCalendar
    // ): Response {
    //     // Redirige vers la même méthode index
    //     return $this->index($request, $em, $googleCalendar);
    // }
    #[Route('/admin/planning', name: 'app_admin_index')]
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

        $plannings = $em->getRepository(Planning::class)->createQueryBuilder('p')
            ->andWhere('p.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

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

        return $this->render('admin/planning/index.html.twig', [
            'current_month' => $month,
            'current_year' => $year,
            'days_in_month' => $daysInMonth,
            'first_day_of_week' => $firstDayOfWeek,
            'events_by_date' => $eventsByDate,
            'today' => $today->format('Y-m-d'),
            'google_events_count' => $google_events_count,
            'google_load_error' => $google_load_error,
        ]);
    }

    #[Route('/planning', name: 'app_admin_planning')]
    public function planning(
        Request $request,
        EntityManagerInterface $em,
        GoogleCalendarClient $googleCalendar
    ): Response {
        return $this->index($request, $em, $googleCalendar);
    }

    #[Route('/planning/new', name: 'app_admin_planning_new', methods: ['GET', 'POST'])]
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
    return $this->redirectToRoute('app_admin_planning');
}
    $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);
    return $this->render('admin/planning/new.html.twig', [
        'seances' => $seances,
        'form' => $form->createView(),
    ]);
}

    #[Route('/planning/{id}/edit', name: 'app_admin_planning_edit', methods: ['GET', 'POST'])]
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
                return $this->redirectToRoute('app_admin_planning_edit', ['id' => $id]);
            }

            if ($date === '' || $startTime === '' || $endTime === '') {
                $this->addFlash('error', 'Veuillez remplir la date et les heures.');
                return $this->redirectToRoute('app_admin_planning_edit', ['id' => $id]);
            }

            try {
                $dateDebut = new \DateTimeImmutable(trim($date . ' ' . $startTime));
                $dateFin = new \DateTimeImmutable(trim($date . ' ' . $endTime));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date/heure invalide.');
                return $this->redirectToRoute('app_admin_planning_edit', ['id' => $id]);
            }

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date/heure de fin doit être après le début.');
                return $this->redirectToRoute('app_admin_planning_edit', ['id' => $id]);
            }

            $planning->setSeance($seance);
            $planning->setDateDebut(\DateTime::createFromImmutable($dateDebut));
            $planning->setDateFin(\DateTime::createFromImmutable($dateFin));
            $planning->setColor($color);
            $planning->setFeedback($feedback);

            $em->flush();

            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('app_admin_planning');
        }

        $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/planning/edit.html.twig', [
            'planning' => $planning,
            'seances' => $seances,
        ]);
    }

    #[Route('/planning/{id}/delete', name: 'app_admin_planning_delete', methods: ['POST'])]
    public function deletePlanning(int $id, EntityManagerInterface $em): Response
    {
        $planning = $em->getRepository(Planning::class)->find($id);
        if (!$planning) {
            throw $this->createNotFoundException('Event not found');
        }

        $em->remove($planning);
        $em->flush();

        $this->addFlash('success', 'Event deleted successfully!');
        return $this->redirectToRoute('app_admin_planning');
    }

    // ==================== SEANCE ====================
    
    #[Route('/seance', name: 'app_admin_seance_index', methods: ['GET'])]
    public function seanceIndex(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $typeId = (string) $request->query->get('type', '');

        // Tri
        $sort = (string) $request->query->get('sort', 'id');  // id|titre|type
        $dir = strtolower((string) $request->query->get('dir', 'desc')); // asc|desc
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
            ->leftJoin('s.typeSeance', 't')
            ->addSelect('t');

        // Recherche: titre OU type.name
        if ($q !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $q . '%');
        }

        // Filtre type
        if ($typeId !== '' && ctype_digit($typeId)) {
            $qb->andWhere('t.id = :typeId')
               ->setParameter('typeId', (int) $typeId);
        }

        // Whitelist du tri (anti-injection)
        $sortMap = [
            'id' => 's.id',
            'titre' => 's.titre',
            'type' => 't.name',
        ];
        $sortField = $sortMap[$sort] ?? 's.id';

        $qb->orderBy($sortField, strtoupper($dir));

        $seances = $qb->getQuery()->getResult();
        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

// --- Stats (sur les séances affichées) ---
$total = count($seances);

// compter par type (par nom)
$byType = [];
foreach ($seances as $s) {
    $type = $s->getTypeSeance();
    $typeName = $type ? $type->getName() : 'Sans type';
    $byType[$typeName] = ($byType[$typeName] ?? 0) + 1;
}
arsort($byType);

$topTypeName = $total > 0 ? array_key_first($byType) : null;
$topTypeCount = $total > 0 ? ($byType[$topTypeName] ?? 0) : 0;

$stats = [
    'total' => $total,
    'typesTotal' => count($types),
    'topTypeName' => $topTypeName,
    'topTypeCount' => $topTypeCount,
    'byType' => $byType, // optionnel si tu veux afficher un mini breakdown
];
        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        return $this->render('admin/seance/index.html.twig', [
            'seances' => $seances,
            'types' => $types,
            'q' => $q,
            'typeId' => $typeId,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => $stats,
        ]);
    }
    #[IsGranted('ROLE_USER')]
    #[Route('/seance/new', name: 'app_admin_seance_new', methods: ['GET', 'POST'])]
    public function newSeance(Request $request, EntityManagerInterface $em): Response
    {
        $seance = new Seance();
        $form = $this->createForm(\App\Form\SeanceType::class, $seance);
        $form->handleRequest($request);
        $seance->setUser($this->getUser());

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($seance);
            $em->flush();

            $this->addFlash('success', 'Session created successfully!');
            return $this->redirectToRoute('app_admin_seance_index');
        }

        return $this->render('admin/seance/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/seance/{id}/edit', name: 'app_admin_seance_edit', methods: ['GET', 'POST'])]
    public function editSeance(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $seance = $em->getRepository(Seance::class)->find($id);
        if (!$seance) {
            throw $this->createNotFoundException('Séance introuvable.');
        }

        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $titre = trim((string) $request->request->get('titre', ''));
            $description = trim((string) $request->request->get('description', ''));
            $typeId = (string) $request->request->get('type_seance_id', '');

            if ($titre === '') {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->redirectToRoute('app_admin_seance_edit', ['id' => $id]);
            }

            if ($typeId === '' || !ctype_digit($typeId)) {
                $this->addFlash('error', 'Veuillez choisir un type.');
                return $this->redirectToRoute('app_admin_seance_edit', ['id' => $id]);
            }

            $type = $em->getRepository(TypeSeance::class)->find((int) $typeId);
            if (!$type) {
                $this->addFlash('error', 'Type introuvable.');
                return $this->redirectToRoute('app_admin_seance_edit', ['id' => $id]);
            }

            // Ces setters doivent exister dans ton entity Seance.
            $seance->setTitre($titre);
            $seance->setDescription($description);
            $seance->setTypeSeance($type);

            $em->flush();

            $this->addFlash('success', 'Séance modifiée.');
            return $this->redirectToRoute('app_admin_seance_index');
        }

        return $this->render('admin/seance/edit.html.twig', [
            'seance' => $seance,
            'types' => $types,
        ]);
    }

    #[Route('/seance/{id}/delete', name: 'app_admin_seance_delete', methods: ['POST'])]
    public function deleteSeance(int $id, EntityManagerInterface $em): Response
    {
        $seance = $em->getRepository(Seance::class)->find($id);
        if (!$seance) {
            throw $this->createNotFoundException('Session not found');
        }

        $em->remove($seance);
        $em->flush();

        $this->addFlash('success', 'Session deleted successfully!');
        return $this->redirectToRoute('app_admin_seance_index');
    }
    #[Route('/export/pdf', name: 'app_admin_seance_export_pdf', methods: ['GET'])]
public function exportPdf(Request $request, EntityManagerInterface $em): Response
{
    $q = trim((string) $request->query->get('q', ''));
    $typeId = (string) $request->query->get('type', '');
    $sort = (string) $request->query->get('sort', 'id');
    $dir = strtolower((string) $request->query->get('dir', 'desc'));
    $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

    $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
        ->leftJoin('s.typeSeance', 't')
        ->addSelect('t');

    if ($q !== '') {
        $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
           ->setParameter('q', '%' . $q . '%');
    }

    if ($typeId !== '' && ctype_digit($typeId)) {
        $qb->andWhere('t.id = :typeId')
           ->setParameter('typeId', (int) $typeId);
    }

    $sortMap = [
        'id' => 's.id',
        'titre' => 's.titre',
        'type' => 't.name',
    ];
    $sortField = $sortMap[$sort] ?? 's.id';
    $qb->orderBy($sortField, strtoupper($dir));

    $seances = $qb->getQuery()->getResult();
    // HTML du PDF (via Twig)
    $html = $this->renderView('admin/seance/export.pdf.html.twig', [
        'seances' => $seances,
        'generatedAt' => new \DateTimeImmutable(),
        'q' => $q,
        'typeId' => $typeId,
        'sort' => $sort,
        'dir' => $dir,
    ]);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans'); // support accents
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf = $dompdf->output();

    $filename = 'sessions-' . (new \DateTimeImmutable())->format('Y-m-d_His') . '.pdf';

    return new Response(
        $pdf,
        200,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            ),
        ]
    );
}

#[Route('/print', name: 'app_admin_seance_print', methods: ['GET'])]
public function print(Request $request, EntityManagerInterface $em): Response
{
    // même logique de sélection que index
    $q = trim((string) $request->query->get('q', ''));
    $typeId = (string) $request->query->get('type', '');
    $sort = (string) $request->query->get('sort', 'id');
    $dir = strtolower((string) $request->query->get('dir', 'desc'));
    $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

    $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
        ->leftJoin('s.typeSeance', 't')
        ->addSelect('t');

    if ($q !== '') {
        $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%');
    }

    if ($typeId !== '' && ctype_digit($typeId)) {
        $qb->andWhere('t.id = :typeId')
            ->setParameter('typeId', (int) $typeId);
    }

    $sortMap = [
        'id' => 's.id',
        'titre' => 's.titre',
        'type' => 't.name',
    ];
    $sortField = $sortMap[$sort] ?? 's.id';
    $qb->orderBy($sortField, strtoupper($dir));

    $seances = $qb->getQuery()->getResult();

    return $this->render('admin/seance/print.html.twig', [
        'seances' => $seances,
        'generatedAt' => new \DateTimeImmutable(),
    ]);
}

    // ==================== TYPE SEANCE ====================
    
    #[Route('/type-seance', name: 'app_admin_type_seance_index', methods: ['GET'])]
    public function typeSeanceIndex(EntityManagerInterface $em): Response
    {
        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        return $this->render('admin/type_seance/index.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/type-seance/new', name: 'app_admin_type_seance_new', methods: ['GET', 'POST'])]
    public function newTypeSeance(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_admin_type_seance_new');
            }

            // éviter doublon
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name]);
            if ($exists) {
                $this->addFlash('error', 'Ce type existe déjà.');
                return $this->redirectToRoute('app_admin_type_seance_new');
            }

            $type = new TypeSeance();
            $type->setName($name);

            $em->persist($type);
            $em->flush();

            $this->addFlash('success', 'Type ajouté.');
            return $this->redirectToRoute('app_admin_type_seance_index');
        }

        return $this->render('admin/type_seance/new.html.twig');
    }

    #[Route('/type-seance/{id}/edit', name: 'app_admin_type_seance_edit', methods: ['GET', 'POST'])]
    public function editTypeSeance(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type introuvable.');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_admin_type_seance_edit', ['id' => $id]);
            }

            // éviter doublon (autre id)
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name]);
            if ($exists && $exists->getId() !== $type->getId()) {
                $this->addFlash('error', 'Ce type existe déjà.');
                return $this->redirectToRoute('app_admin_type_seance_edit', ['id' => $id]);
            }

            $type->setName($name);
            $em->flush();

            $this->addFlash('success', 'Type modifié.');
            return $this->redirectToRoute('app_admin_type_seance_index');
        }

        return $this->render('admin/type_seance/edit.html.twig', [
            'type' => $type,
        ]);
    }

    #[Route('/type-seance/{id}/delete', name: 'app_admin_type_seance_delete', methods: ['POST'])]
    public function deleteTypeSeance(int $id, EntityManagerInterface $em): Response
    {
        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type is not available.');
        }

        // Si des seances utilisent ce type, la suppression peut échouer (FK)
        // Selon ta FK: ON DELETE SET NULL => OK, sinon ça bloque.
        $em->remove($type);
        $em->flush();

        $this->addFlash('success', 'Type deleted.');
        return $this->redirectToRoute('app_admin_type_seance_index');
    }
}


