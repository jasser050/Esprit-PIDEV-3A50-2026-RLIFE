<?php

namespace App\Controller;

use App\Entity\Planning;
use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/planning')]
class PlanningController extends AbstractController
{
    #[Route('', name: 'app_planning', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
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

        // map couleur -> classes CSS utilisées par ton design
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
            $dateKey = $p->getDateDebut()->format('Y-m-d');
            $eventsByDate[$dateKey] ??= [];

            $color = $p->getColor() ?? 'indigo';
            $ui = $uiMap[$color] ?? $uiMap['indigo'];

            $eventsByDate[$dateKey][] = [
                'id' => $p->getId(),
                'title' => $p->getSeance() ? (string) $p->getSeance() : 'Séance',
                'date' => $dateKey,
                'start_time' => $p->getDateDebut()->format('H:i'),
                'end_time' => $p->getDateFin()->format('H:i'),
                'color' => $color,
                'feedback' => $p->getFeedback(),

                // IMPORTANT: on met les classes calculées ici
                'ui' => $ui,

                // ton twig utilise 'type' pour stats
                'type' => 'class',
            ];
        }

        $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);

        return $this->render('pages/planning/index.html.twig', [
            'current_month' => $month,
            'current_year' => $year,
            'days_in_month' => $daysInMonth,
            'first_day_of_week' => $firstDayOfWeek,
            'events_by_date' => $eventsByDate,
            'conflicts' => [],
            'today' => $today->format('Y-m-d'),
            'seances' => $seances,
        ]);
    }

    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
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
                return $this->redirectToRoute('app_planning_new');
            }

            if ($date === '' || $startTime === '' || $endTime === '') {
                $this->addFlash('error', 'Veuillez remplir la date et les heures.');
                return $this->redirectToRoute('app_planning_new');
            }

            try {
                $dateDebut = new \DateTimeImmutable(trim($date . ' ' . $startTime));
                $dateFin = new \DateTimeImmutable(trim($date . ' ' . $endTime));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Date/heure invalide.');
                return $this->redirectToRoute('app_planning_new');
            }

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date/heure de fin doit être après le début.');
                return $this->redirectToRoute('app_planning_new');
            }

            $planning = new Planning();
            $planning->setSeance($seance);
            $planning->setDateDebut(\DateTime::createFromImmutable($dateDebut));
            $planning->setDateFin(\DateTime::createFromImmutable($dateFin));
            $planning->setColor($color);
            $planning->setFeedback($feedback);

            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                throw $this->createAccessDeniedException('Vous devez être connecté.');
            }
            $planning->setUser($user);

            $em->persist($planning);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('app_planning');
        }

        $seances = $em->getRepository(Seance::class)->findBy([], ['id' => 'DESC']);
        $preselectedDate = $request->query->get('date');

        return $this->render('pages/planning/new.html.twig', [
            'seances' => $seances,
            'preselected_date' => $preselectedDate,
            'courses' => [],
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
}