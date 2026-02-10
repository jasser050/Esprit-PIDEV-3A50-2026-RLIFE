<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Entity\TypeSeance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/seance')]
class SeanceController extends AbstractController
{
    #[Route('/new', name: 'app_seance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $titre = trim((string) $request->request->get('titre', ''));
            $typeId = $request->request->get('type_seance_id');
            $description = trim((string) $request->request->get('description', ''));

            if ($titre === '') {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->redirectToRoute('app_seance_new');
            }

            $type = null;
            if ($typeId !== null && $typeId !== '') {
                $type = $em->getRepository(TypeSeance::class)->find((int) $typeId);
            }

            $seance = new Seance();
            $seance->setUser($user);
            $seance->setTitre($titre);
            $seance->setTypeSeance($type);
            $seance->setDescription($description !== '' ? $description : null);

            $em->persist($seance);
            $em->flush();

            $this->addFlash('success', 'Séance créée.');
            return $this->redirectToRoute('app_planning');
        }

        return $this->render('pages/seance/new.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_seance_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $seance = $em->getRepository(Seance::class)->find($id);
        if (!$seance) {
            throw $this->createNotFoundException('Seance not found');
        }

        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $titre = trim((string) $request->request->get('titre', ''));
            $typeId = $request->request->get('type_seance_id');
            $description = trim((string) $request->request->get('description', ''));

            if ($titre === '') {
                $this->addFlash('error', 'Le titre est obligatoire.');
                return $this->redirectToRoute('app_seance_edit', ['id' => $id]);
            }

            $type = null;
            if ($typeId !== null && $typeId !== '') {
                $type = $em->getRepository(TypeSeance::class)->find((int) $typeId);
            }

            $seance->setTitre($titre);
            $seance->setTypeSeance($type);
            $seance->setDescription($description !== '' ? $description : null);

            $em->flush();

            $this->addFlash('success', 'Séance modifiée.');
            return $this->redirectToRoute('app_planning');
        }

        return $this->render('pages/seance/edit.html.twig', [
            'seance' => $seance,
            'types' => $types,
        ]);
    }
    #[Route('/{id}/delete', name: 'app_seance_delete', methods: ['POST'])]
public function delete(int $id, EntityManagerInterface $em): Response
{
    $seance = $em->getRepository(Seance::class)->find($id);
    if (!$seance) {
        throw $this->createNotFoundException('Seance not found');
    }

    $em->remove($seance);
    $em->flush();

    $this->addFlash('success', 'Séance supprimée.');
    return $this->redirectToRoute('app_planning');
}
}