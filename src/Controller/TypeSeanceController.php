<?php

namespace App\Controller;

use App\Entity\TypeSeance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/type-seance')]
class TypeSeanceController extends AbstractController
{
    #[Route('', name: 'app_type_seance_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        return $this->render('pages/type_seance/index.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/new', name: 'app_type_seance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            // éviter doublon
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name]);
            if ($exists) {
                $this->addFlash('error', 'Ce type existe déjà.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            $type = new TypeSeance();
            $type->setName($name);

            $em->persist($type);
            $em->flush();

            $this->addFlash('success', 'Type ajouté.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        return $this->render('pages/type_seance/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_type_seance_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type introuvable.');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_type_seance_edit', ['id' => $id]);
            }

            // éviter doublon (autre id)
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name]);
            if ($exists && $exists->getId() !== $type->getId()) {
                $this->addFlash('error', 'Ce type existe déjà.');
                return $this->redirectToRoute('app_type_seance_edit', ['id' => $id]);
            }

            $type->setName($name);
            $em->flush();

            $this->addFlash('success', 'Type modifié.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        return $this->render('pages/type_seance/edit.html.twig', [
            'type' => $type,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_type_seance_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type introuvable.');
        }

        // Si des seances utilisent ce type, la suppression peut échouer (FK)
        // Selon ta FK: ON DELETE SET NULL => OK, sinon ça bloque.
        $em->remove($type);
        $em->flush();

        $this->addFlash('success', 'Type supprimé.');
        return $this->redirectToRoute('app_type_seance_index');
    }
}