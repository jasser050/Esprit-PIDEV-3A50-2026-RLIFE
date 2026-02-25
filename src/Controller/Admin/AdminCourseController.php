<?php

namespace App\Controller\Admin;

use App\Entity\Matiere;
use App\Form\MatiereType;
use App\Repository\MatiereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/courses')]
#[IsGranted('ROLE_ADMIN')]
class AdminCourseController extends AbstractController
{
    #[Route('/', name: 'app_admin_courses')]
    public function index(MatiereRepository $matiereRepository, Request $request): Response
    {
        $filter = $request->query->get('filter', 'all');
        $section = $request->query->get('section');
        $sort = $request->query->get('sort', 'newest');
        $search = $request->query->get('search');
        
        $queryBuilder = $matiereRepository->createQueryBuilder('m');
        
        if ($section) {
            $queryBuilder->where('m.sectionMatiere = :section')
                ->setParameter('section', $section);
        }
        
        if ($search) {
            $queryBuilder->andWhere('m.nomMatiere LIKE :search OR m.code LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Sorting
        switch ($sort) {
            case 'az':
                $queryBuilder->orderBy('m.nomMatiere', 'ASC');
                break;
            case 'za':
                $queryBuilder->orderBy('m.nomMatiere', 'DESC');
                break;
            case 'newest':
            default:
                $queryBuilder->orderBy('m.createdAt', 'DESC');
                break;
        }
        
        $matieres = $queryBuilder->getQuery()->getResult();
        
        // Get all sections for filter
        $sections = $matiereRepository->createQueryBuilder('m')
            ->select('DISTINCT m.sectionMatiere')
            ->orderBy('m.sectionMatiere', 'ASC')
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/courses/index.html.twig', [
            'matieres' => $matieres,
            'sections' => array_column($sections, 'sectionMatiere'),
            'current_section' => $section,
            'current_sort' => $sort,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'app_admin_courses_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $matiere = new Matiere();
        $form = $this->createForm(MatiereType::class, $matiere);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($matiere);
            $entityManager->flush();

            $this->addFlash('success', 'Course created successfully.');
            return $this->redirectToRoute('app_admin_courses');
        }

        return $this->render('admin/courses/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_courses_show', requirements: ['id' => '\d+'])]
    public function show(Matiere $matiere, MatiereRepository $matiereRepository): Response
    {
        // Count how many users have this course
        $userCount = $matiereRepository->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.user)')
            ->where('m.code = :code')
            ->setParameter('code', $matiere->getCode())
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get users who have this course
        $users = $matiereRepository->createQueryBuilder('m')
            ->select('DISTINCT u')
            ->join('m.user', 'u')
            ->where('m.code = :code')
            ->setParameter('code', $matiere->getCode())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        return $this->render('admin/courses/show.html.twig', [
            'matiere' => $matiere,
            'user_count' => $userCount,
            'users' => $users,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_courses_edit', requirements: ['id' => '\d+'])]
    public function edit(Matiere $matiere, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MatiereType::class, $matiere);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Course updated successfully.');
            return $this->redirectToRoute('app_admin_courses');
        }

        return $this->render('admin/courses/edit.html.twig', [
            'matiere' => $matiere,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_courses_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Matiere $matiere, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $matiere->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('app_admin_courses');
        }

        try {
            $entityManager->remove($matiere);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Course "%s" has been deleted.', $matiere->getNomMatiere()));
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            $this->addFlash('error', 'Cannot delete this course because it is linked to other data.');
        }

        return $this->redirectToRoute('app_admin_courses');
    }

    #[Route('/bulk-delete', name: 'app_admin_courses_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, EntityManagerInterface $entityManager, MatiereRepository $matiereRepository): Response
    {
        $ids = $request->request->all('ids');

        if (empty($ids)) {
            $this->addFlash('error', 'No courses selected.');
            return $this->redirectToRoute('app_admin_courses');
        }

        $count = 0;
        foreach ($ids as $id) {
            $matiere = $matiereRepository->find($id);
            if ($matiere) {
                try {
                    $entityManager->remove($matiere);
                    $count++;
                } catch (\Exception $e) {
                    $this->addFlash('error', sprintf('Could not delete course "%s".', $matiere->getNomMatiere()));
                }
            }
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf('%d course(s) deleted successfully.', $count));
        return $this->redirectToRoute('app_admin_courses');
    }
}
