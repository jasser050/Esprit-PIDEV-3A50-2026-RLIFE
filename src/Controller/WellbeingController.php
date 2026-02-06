<?php

namespace App\Controller;

use App\Data\SampleData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/wellbeing')]
class WellbeingController extends AbstractController
{
    #[Route('', name: 'app_wellbeing')]
    public function index(): Response
    {
        $checkins = SampleData::getStressCheckins();
        $tools = SampleData::getCopingTools();

        // Calculate averages
        $avgStress = array_sum(array_column($checkins, 'stress_level')) / count($checkins);
        $avgEnergy = array_sum(array_column($checkins, 'energy_level')) / count($checkins);
        $avgSleep = array_sum(array_column($checkins, 'sleep_hours')) / count($checkins);

        return $this->render('pages/wellbeing/index.html.twig', [
            'checkins' => $checkins,
            'tools' => $tools,
            'avg_stress' => round($avgStress, 1),
            'avg_energy' => round($avgEnergy, 1),
            'avg_sleep' => round($avgSleep, 1),
        ]);
    }

    #[Route('/checkins', name: 'app_wellbeing_checkins')]
    public function checkins(): Response
    {
        $checkins = SampleData::getStressCheckins();

        // Calculate averages
        $avgStress = count($checkins) > 0 ? array_sum(array_column($checkins, 'stress_level')) / count($checkins) : 0;
        $avgEnergy = count($checkins) > 0 ? array_sum(array_column($checkins, 'energy_level')) / count($checkins) : 0;
        $avgSleep = count($checkins) > 0 ? array_sum(array_column($checkins, 'sleep_hours')) / count($checkins) : 0;

        return $this->render('pages/wellbeing/checkins.html.twig', [
            'checkins' => $checkins,
            'avg_stress' => round($avgStress, 1),
            'avg_energy' => round($avgEnergy, 1),
            'avg_sleep' => round($avgSleep, 1),
        ]);
    }

    #[Route('/checkins/new', name: 'app_wellbeing_checkin_new', methods: ['GET', 'POST'])]
    public function checkinNew(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // In a real app, we would save the check-in to the database
            // For now, just show a success message
            $this->addFlash('success', 'Check-in saved successfully!');
            return $this->redirectToRoute('app_wellbeing_checkins');
        }

        return $this->render('pages/wellbeing/checkin_new.html.twig');
    }

    #[Route('/tools', name: 'app_wellbeing_tools')]
    public function tools(): Response
    {
        $tools = SampleData::getCopingTools();

        return $this->render('pages/wellbeing/tools.html.twig', [
            'tools' => $tools,
        ]);
    }
}
