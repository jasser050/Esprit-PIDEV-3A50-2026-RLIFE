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
                $this->addFlash('error', 'Type is required.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            // Avoid duplicate
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name]);
            if ($exists) {
                $this->addFlash('error', 'This type already exists.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            $type = new TypeSeance();
            $type->setName($name);

            $em->persist($type);
            $em->flush();

            $this->addFlash('success', 'Type added.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        return $this->render('pages/type_seance/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_type_seance_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type not found.');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Type is required.');
                return $this->redirectToRoute('app_type_seance_edit', ['id' => $id]);
            }

            // Avoid duplicate (different id)
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
            throw $this->createNotFoundException('Type not found.');
        }

        // If sessions use this type, deletion may fail (FK)
        // According to your FK: ON DELETE SET NULL => OK, otherwise it blocks.
        $em->remove($type);
        $em->flush();

        $this->addFlash('success', 'Type deleted.');
        return $this->redirectToRoute('app_type_seance_index');
    }
}