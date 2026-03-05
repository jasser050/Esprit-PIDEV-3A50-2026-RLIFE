<?php

namespace App\Controller;

use App\Entity\TypeSeance;
use App\Entity\User;
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
        $user = $this->getUser();
        if ($user instanceof User) {
            $qb = $em->getRepository(TypeSeance::class)->createQueryBuilder('t');
            $types = $qb
                ->andWhere('t.user = :user OR t.user IS NULL')
                ->setParameter('user', $user)
                ->orderBy('t.name', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $types = [];
        }

        return $this->render('pages/type_seance/index.html.twig', [
            'types' => $types,
        ]);
    }

    #[Route('/new', name: 'app_type_seance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            // éviter doublon
            $exists = $em->getRepository(TypeSeance::class)->findOneBy(['name' => $name, 'user' => $user]);
            if ($exists) {
                $this->addFlash('error', 'Ce type existe déjà.');
                return $this->redirectToRoute('app_type_seance_new');
            }

            $type = new TypeSeance();
            $type->setName($name);
            $type->setUser($user);

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
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type introuvable.');
        }
        if ($type->getUser() === null) {
            throw $this->createAccessDeniedException();
        }
        if ($type->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name', ''));

            if ($name === '') {
                $this->addFlash('error', 'Le type est obligatoire.');
                return $this->redirectToRoute('app_type_seance_edit', ['id' => $id]);
            }

            // éviter doublon (autre id)
            $exists = $em->getRepository(TypeSeance::class)->findOneBy([
                'name' => $name,
                'user' => $this->getUser(),
            ]);
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
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_type_seance_index');
        }

        $type = $em->getRepository(TypeSeance::class)->find($id);
        if (!$type) {
            throw $this->createNotFoundException('Type introuvable.');
        }
        if ($type->getUser() === null) {
            throw $this->createAccessDeniedException();
        }
        if ($type->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // Si des seances utilisent ce type, la suppression peut échouer (FK)
        // Selon ta FK: ON DELETE SET NULL => OK, sinon ça bloque.
        $em->remove($type);
        $em->flush();

        $this->addFlash('success', 'Type supprimé.');
        return $this->redirectToRoute('app_type_seance_index');
    }
}
