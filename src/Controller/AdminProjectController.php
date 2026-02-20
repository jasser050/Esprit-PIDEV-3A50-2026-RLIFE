<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminProjectController extends AbstractController
{
    private const ITEMS_PER_PAGE = 20;

    #[Route('/projects', name: 'admin_projects_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProjectRepository $projectRepository
    ): Response {
        $sort = $request->query->getString('sort', 'createdAt');
        $direction = $request->query->getString('direction', 'DESC');
        $statut = $request->query->getString('statut', '');
        $userEmail = $request->query->getString('userEmail', '');
        $search = $request->query->getString('search', '');

        $requestedPage = max(1, $request->query->getInt('page', 1));
        $limit = self::ITEMS_PER_PAGE;
        $offset = ($requestedPage - 1) * $limit;

        $result = $projectRepository->findAllWithFilters(
            $sort,
            $direction,
            $statut,
            $userEmail,
            $search,
            $limit,
            $offset
        );

        $projects = $result['projects'] ?? [];
        $total = $result['total'] ?? 0;
        $totalPages = max(1, (int) ceil($total / $limit));
        $currentPage = min($requestedPage, $totalPages);

        if ($currentPage !== $requestedPage) {
            $result = $projectRepository->findAllWithFilters(
                $sort,
                $direction,
                $statut,
                $userEmail,
                $search,
                $limit,
                ($currentPage - 1) * $limit
            );
            $projects = $result['projects'] ?? [];
        }

        return $this->render('admin/projects/index.html.twig', [
            'projects' => $projects,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'sort' => $sort,
            'direction' => $direction,
            'statut' => $statut,
            'userEmail' => $userEmail,
            'search' => $search,
            'totalProjects' => $total,
        ]);
    }
}
