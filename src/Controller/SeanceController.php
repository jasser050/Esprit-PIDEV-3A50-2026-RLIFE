<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Entity\TypeSeance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/seance')]
class SeanceController extends AbstractController
{
    #[Route('', name: 'app_seance_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $typeId = (string) $request->query->get('type', '');

        // Tri
        $sort = (string) $request->query->get('sort', 'id');  // id|titre|type
        $dir = strtolower((string) $request->query->get('dir', 'desc')); // asc|desc
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
            ->leftJoin('s.typeSeance', 't')
            ->addSelect('t');

        // Recherche: titre OU type.name
        if ($q !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $q . '%');
        }

        // Filtre type
        if ($typeId !== '' && ctype_digit($typeId)) {
            $qb->andWhere('t.id = :typeId')
               ->setParameter('typeId', (int) $typeId);
        }

        // Whitelist du tri (anti-injection)
        $sortMap = [
            'id' => 's.id',
            'titre' => 's.titre',
            'type' => 't.name',
        ];
        $sortField = $sortMap[$sort] ?? 's.id';

        $qb->orderBy($sortField, strtoupper($dir));

        $seances = $qb->getQuery()->getResult();
        $types = $em->getRepository(TypeSeance::class)->findBy([], ['name' => 'ASC']);

        // --- Stats (sur les séances affichées) ---
        $total = count($seances);

        // compter par type (par nom)
        $byType = [];
        foreach ($seances as $s) {
            $type = $s->getTypeSeance();
            $typeName = $type ? $type->getName() : 'Sans type';
            $byType[$typeName] = ($byType[$typeName] ?? 0) + 1;
        }
        arsort($byType);

        $topTypeName = $total > 0 ? array_key_first($byType) : null;
        $topTypeCount = $total > 0 ? ($byType[$topTypeName] ?? 0) : 0;

        $stats = [
            'total' => $total,
            'typesTotal' => count($types),
            'topTypeName' => $topTypeName,
            'topTypeCount' => $topTypeCount,
            'byType' => $byType,
        ];

        return $this->render('pages/seance/index.html.twig', [
            'seances' => $seances,
            'types' => $types,
            'q' => $q,
            'typeId' => $typeId,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => $stats,
        ]);
    }
#[IsGranted('ROLE_USER')]
    #[Route('/new', name: 'app_seance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $seance = new Seance();
        $seance->setUser($this->getUser());

        // Maintenant vous pouvez utiliser SeanceType::class
        $form = $this->createForm(\App\Form\SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($seance);
            $em->flush();

            $this->addFlash('success', 'Session created successfully!');
            return $this->redirectToRoute('app_seance_index');
        }

        return $this->render('pages/seance/new.html.twig', [
            'form' => $form,
        ]);
    }
    #[Route('/{id}/edit', name: 'app_seance_edit', methods: ['GET', 'POST'])]
public function edit(int $id, Request $request, EntityManagerInterface $em): Response
{
    $seance = $em->getRepository(Seance::class)->find($id);
    if (!$seance) {
        throw $this->createNotFoundException('Session not found.');
    }

    $form = $this->createForm(\App\Form\SeanceType::class, $seance);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();

        $this->addFlash('success', 'Session updated successfully!');
        return $this->redirectToRoute('app_seance_index');
    }

    return $this->render('pages/seance/edit.html.twig', [
        'form' => $form,
        'seance' => $seance,
    ]);
}

    #[Route('/{id}/delete', name: 'app_seance_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $seance = $em->getRepository(Seance::class)->find($id);
        if (!$seance) {
            throw $this->createNotFoundException('Session not found.');
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_seance_' . $id, $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $em->remove($seance);
        $em->flush();

        $this->addFlash('success', 'Session deleted successfully!');
        return $this->redirectToRoute('app_seance_index');
    }

    #[Route('/export/pdf', name: 'app_seance_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $typeId = (string) $request->query->get('type', '');
        $sort = (string) $request->query->get('sort', 'id');
        $dir = strtolower((string) $request->query->get('dir', 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
            ->leftJoin('s.typeSeance', 't')
            ->addSelect('t');

        if ($q !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($typeId !== '' && ctype_digit($typeId)) {
            $qb->andWhere('t.id = :typeId')
               ->setParameter('typeId', (int) $typeId);
        }

        $sortMap = [
            'id' => 's.id',
            'titre' => 's.titre',
            'type' => 't.name',
        ];
        $sortField = $sortMap[$sort] ?? 's.id';
        $qb->orderBy($sortField, strtoupper($dir));

        $seances = $qb->getQuery()->getResult();

        $html = $this->renderView('pages/seance/export.pdf.html.twig', [
            'seances' => $seances,
            'generatedAt' => new \DateTimeImmutable(),
            'q' => $q,
            'typeId' => $typeId,
            'sort' => $sort,
            'dir' => $dir,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf = $dompdf->output();

        $filename = 'sessions-' . (new \DateTimeImmutable())->format('Y-m-d_His') . '.pdf';

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $filename
                ),
            ]
        );
    }

    #[Route('/print', name: 'app_seance_print', methods: ['GET'])]
    public function print(Request $request, EntityManagerInterface $em): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $typeId = (string) $request->query->get('type', '');
        $sort = (string) $request->query->get('sort', 'id');
        $dir = strtolower((string) $request->query->get('dir', 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $qb = $em->getRepository(Seance::class)->createQueryBuilder('s')
            ->leftJoin('s.typeSeance', 't')
            ->addSelect('t');

        if ($q !== '') {
            $qb->andWhere('LOWER(s.titre) LIKE LOWER(:q) OR LOWER(t.name) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($typeId !== '' && ctype_digit($typeId)) {
            $qb->andWhere('t.id = :typeId')
                ->setParameter('typeId', (int) $typeId);
        }

        $sortMap = [
            'id' => 's.id',
            'titre' => 's.titre',
            'type' => 't.name',
        ];
        $sortField = $sortMap[$sort] ?? 's.id';
        $qb->orderBy($sortField, strtoupper($dir));

        $seances = $qb->getQuery()->getResult();

        return $this->render('pages/seance/print.html.twig', [
            'seances' => $seances,
            'generatedAt' => new \DateTimeImmutable(),
        ]);
    }
}