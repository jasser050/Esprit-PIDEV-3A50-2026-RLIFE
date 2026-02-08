<?php

namespace App\Controller;

use App\Data\SampleData;
use App\Entity\WellBeing;
use App\Repository\WellBeingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\CopingSession;
use App\Repository\CopingSessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/wellbeing')]
class WellbeingController extends AbstractController
{
    #[Route('', name: 'app_wellbeing', methods: ['GET'])]
    public function index(WellBeingRepository $repo): Response
    {
        $checkins = $repo->findBy([], ['entryDate_well' => 'DESC'], 10);

        $count = count($checkins);
        $avgStress = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getStressLevelWell(), $checkins)) / $count : 0;
        $avgEnergy = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getEnergyLevelWell(), $checkins)) / $count : 0;
        $avgSleep  = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getSleepHoursWell(), $checkins)) / $count : 0;

        return $this->render('pages/wellbeing/index.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => round($avgStress, 1),
            'avg_energy' => round($avgEnergy, 1),
            'avg_sleep' => round($avgSleep, 1),
            'tools' => SampleData::getCopingTools(), // optionnel
        ]);
    }

    #[Route('/checkins', name: 'app_wellbeing_checkins', methods: ['GET'])]
    public function checkins(WellBeingRepository $repo): Response
    {
        $checkins = $repo->findBy([], ['entryDate_well' => 'DESC']);

        $count = count($checkins);
        $avgStress = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getStressLevelWell(), $checkins)) / $count : 0;
        $avgEnergy = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getEnergyLevelWell(), $checkins)) / $count : 0;
        $avgSleep  = $count ? array_sum(array_map(fn(WellBeing $c) => $c->getSleepHoursWell(), $checkins)) / $count : 0;

        return $this->render('pages/wellbeing/checkins.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => round($avgStress, 1),
            'avg_energy' => round($avgEnergy, 1),
            'avg_sleep' => round($avgSleep, 1),
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
            $wb->setEntryDateWell(new \DateTime());
            $wb->setMoodWell((string)$request->request->get('mood', 'good'));
            $wb->setStressLevelWell((int)$request->request->get('stress_level', 5));
            $wb->setEnergyLevelWell((int)$request->request->get('energy_level', 7));
            $wb->setSleepHoursWell((float)$request->request->get('sleep_hours', 7));
            $wb->setNoteWell($request->request->get('notes'));
            $wb->setCreatedAtWell(new \DateTime());

            $em->persist($wb);
            $em->flush();

            // ✅ retourne au dashboard wellbeing (comme ton image 3)
            return $this->redirectToRoute('app_wellbeing');
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

            $checkin->setMoodWell((string)$request->request->get('mood', $checkin->getMoodWell()));
            $checkin->setStressLevelWell((int)$request->request->get('stress_level', $checkin->getStressLevelWell()));
            $checkin->setEnergyLevelWell((int)$request->request->get('energy_level', $checkin->getEnergyLevelWell()));
            $checkin->setSleepHoursWell((float)$request->request->get('sleep_hours', $checkin->getSleepHoursWell()));
            $checkin->setNoteWell($request->request->get('notes'));
            $checkin->setUpdatedAtWell(new \DateTime());

            $em->flush();

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
        }

        return $this->redirectToRoute('app_wellbeing_checkins');
    }

   
    
}
