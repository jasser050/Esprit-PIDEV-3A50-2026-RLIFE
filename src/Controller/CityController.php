<?php

namespace App\Controller;

use App\Service\CityService;
use App\Service\StreakService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-city')]
#[IsGranted('ROLE_USER')]
class CityController extends AbstractController
{
    public function __construct(
        private CityService   $cityService,
        private StreakService  $streakService,
    ) {}

    #[Route('/', name: 'app_my_city')]
    public function index(): Response
    {
        $user     = $this->getUser();
        $cityData = $this->cityService->getCityData($user);

        return $this->render('pages/city/index.html.twig', [
            'cityData'     => $cityData,
            'allCountries' => $this->cityService->getAllCountries(),
        ]);
    }

    // API — données JSON pour le JS Three.js
    #[Route('/api/data', name: 'app_my_city_api')]
    public function apiData(): JsonResponse
    {
        $user     = $this->getUser();
        $cityData = $this->cityService->getCityData($user);

        // Sérialiser les pays (enlever les objets non-sérialisables)
        $countries = array_values($cityData['countries']);

        return $this->json([
            'coins'      => $cityData['coins'],
            'level'      => $cityData['level'],
            'xp_percent' => $cityData['xp_percent'],
            'buildings'  => $cityData['buildings'],
            'countries'  => $countries,
            'streak'     => $cityData['streak']['current'],
            'population' => $cityData['population'],
            'stats'      => $cityData['stats'],
        ]);
    }
}
