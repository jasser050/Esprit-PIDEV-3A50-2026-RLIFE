<?php

namespace App\Controller;

use App\Data\SampleData;
use App\Entity\WellBeing;
use App\Repository\WellBeingRepository;
use App\Service\WellbeingAiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/wellbeing')]
class WellbeingController extends AbstractController
{
    #[Route('', name: 'app_wellbeing', methods: ['GET'])]
    public function index(WellBeingRepository $repo, Request $request): Response
    {
        // Get sort parameter
        $sort = $request->query->get('sort', 'entryDate');
        
        // Define sort options
        $sortOptions = [
            'entryDate' => ['entryDate' => 'DESC'],
            'entryDate_asc' => ['entryDate' => 'ASC'],
            'stressLevel' => ['stressLevel' => 'ASC'],
            'stressLevel_desc' => ['stressLevel' => 'DESC'],
            'energyLevel' => ['energyLevel' => 'ASC'],
            'energyLevel_desc' => ['energyLevel' => 'DESC'],
        ];
        
        // Get order by or default to entryDate DESC
        $orderBy = $sortOptions[$sort] ?? ['entryDate' => 'DESC'];
        
        $checkins = $repo->findBy([], $orderBy, 10);
        
        $stats = $this->calculateStats($checkins);
        
        // Calculate trend
        $trend = 'stable';
        if (count($checkins) >= 2) {
            $first = $checkins[count($checkins) - 1]->getStressLevel();
            $last = $checkins[0]->getStressLevel();
            if ($last < $first) {
                $trend = 'improving';
            } elseif ($last > $first) {
                $trend = 'declining';
            }
        }
        
        // Mood distribution
        $moodCounts = [];
        foreach ($checkins as $checkin) {
            $mood = $checkin->getMood();
            $moodCounts[$mood] = ($moodCounts[$mood] ?? 0) + 1;
        }

        return $this->render('pages/wellbeing/index.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => $stats['avg_stress'],
            'avg_energy' => $stats['avg_energy'],
            'avg_sleep' => $stats['avg_sleep'],
            'trend' => $trend,
            'mood_counts' => $moodCounts,
            'tools' => SampleData::getCopingTools(),
        ]);
    }

    #[Route('/checkins', name: 'app_wellbeing_checkins', methods: ['GET'])]
    public function checkins(WellBeingRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $mood = $request->query->get('mood', '');
        $sort = $request->query->get('sort', 'entryDate');
        $order = $request->query->get('order', 'DESC');
        
        $qb = $repo->createQueryBuilder('w');
        
        if ($search) {
            $qb->andWhere('w.note LIKE :search OR w.mood LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($mood) {
            $qb->andWhere('w.mood = :mood')
               ->setParameter('mood', $mood);
        }
        
        $allowedSortFields = ['entryDate', 'stressLevel', 'energyLevel', 'mood'];
        if (!in_array($sort, $allowedSortFields)) {
            $sort = 'entryDate';
        }
        
        $qb->orderBy('w.' . $sort, $order);
        
        $checkins = $qb->getQuery()->getResult();
        $stats = $this->calculateStats($checkins);

        return $this->render('pages/wellbeing/checkins.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => $stats['avg_stress'],
            'avg_energy' => $stats['avg_energy'],
            'avg_sleep' => $stats['avg_sleep'],
            'search' => $search,
            'mood_filter' => $mood,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/checkins/new', name: 'app_wellbeing_checkin_new', methods: ['GET', 'POST'])]
    public function checkinNew(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('wellbeing_new', (string)$request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $wb = new WellBeing();
            $wb->setEntryDate(new \DateTime());
            $wb->setMood((string)$request->request->get('mood', 'good'));
            $wb->setStressLevel((int)$request->request->get('stress_level', 5));
            $wb->setEnergyLevel((int)$request->request->get('energy_level', 7));
            $wb->setSleepHours((float)$request->request->get('sleep_hours', 7));
            $wb->setNote($request->request->get('notes'));
            $wb->setCreatedAt(new \DateTime());

            $em->persist($wb);
            $em->flush();
            
            $this->addFlash('success', 'Check-in added successfully!');
            return $this->redirectToRoute('app_wellbeing_checkins');
        }

        return $this->render('pages/wellbeing/checkin_new.html.twig', [
            'edit_mode' => false,
        ]);
    }

    #[Route('/checkins/{id}/edit', name: 'app_wellbeing_checkin_edit', methods: ['GET', 'POST'])]
    public function edit(WellBeing $checkin, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('wellbeing_edit_'.$checkin->getId(), (string)$request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $checkin->setMood((string)$request->request->get('mood', $checkin->getMood()));
            $checkin->setStressLevel((int)$request->request->get('stress_level', $checkin->getStressLevel()));
            $checkin->setEnergyLevel((int)$request->request->get('energy_level', $checkin->getEnergyLevel()));
            $checkin->setSleepHours((float)$request->request->get('sleep_hours', $checkin->getSleepHours()));
            $checkin->setNote($request->request->get('notes'));
            $checkin->setUpdatedAt(new \DateTime());

            $em->flush();
            
            $this->addFlash('success', 'Check-in updated successfully!');
            return $this->redirectToRoute('app_wellbeing_checkins');
        }

        return $this->render('pages/wellbeing/checkin_new.html.twig', [
            'checkin' => $checkin,
            'edit_mode' => true,
        ]);
    }

    #[Route('/checkins/{id}/delete', name: 'app_wellbeing_checkin_delete', methods: ['POST'])]
    public function delete(WellBeing $checkin, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('wellbeing_delete_'.$checkin->getId(), (string)$request->request->get('_token'))) {
            $em->remove($checkin);
            $em->flush();
            $this->addFlash('success', 'Check-in deleted successfully!');
        }

        return $this->redirectToRoute('app_wellbeing_checkins');
    }

    #[Route('/export/pdf', name: 'app_wellbeing_export_pdf', methods: ['GET'])]
    public function exportPdf(WellBeingRepository $repo, Request $request): Response
    {
        $checkins = $repo->findBy([], ['entryDate' => 'DESC']);
        $stats = $this->calculateStats($checkins);
        
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        
        $html = $this->renderView('pages/wellbeing/pdf.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => $stats['avg_stress'],
            'avg_energy' => $stats['avg_energy'],
            'avg_sleep' => $stats['avg_sleep'],
            'exportDate' => new \DateTime(),
            'start_date' => null,
            'end_date' => null,
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="wellbeing_report.pdf"',
            ]
        );
    }

    private function calculateStats(array $checkins): array
    {
        $count = count($checkins);
        if ($count === 0) {
            return ['avg_stress' => 0, 'avg_energy' => 0, 'avg_sleep' => 0];
        }
        
        $avgStress = array_sum(array_map(fn($c) => $c->getStressLevel(), $checkins)) / $count;
        $avgEnergy = array_sum(array_map(fn($c) => $c->getEnergyLevel(), $checkins)) / $count;
        $avgSleep = array_sum(array_map(fn($c) => $c->getSleepHours(), $checkins)) / $count;
        
        return [
            'avg_stress' => round($avgStress, 1),
            'avg_energy' => round($avgEnergy, 1),
            'avg_sleep' => round($avgSleep, 1),
        ];
    }
}