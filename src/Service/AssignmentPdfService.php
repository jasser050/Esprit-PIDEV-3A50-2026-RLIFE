<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AssignmentPdfService
{
    public function __construct(
        private Environment $twig
    ) {}

    /**
     * Génère un PDF avec la liste des assignments
     *
     * @param array $assignments
     * @param User $user
     * @return Response
     */
    public function generateAssignmentListPdf(array $assignments, User $user): Response
    {
        $html = $this->twig->render('pages/assignments/pdf/assignments_list.html.twig', [
            'assignments' => $assignments,
            'user'        => $user,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'taches_' . date('Y-m-d_His') . '.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Génère un PDF pour un assignment spécifique
     *
     * @param Assignment $assignment
     * @return Response
     */
    public function generateSingleAssignmentPdf(Assignment $assignment): Response
    {
        $html = $this->twig->render('pages/assignments/pdf/assignment_detail.html.twig', [
            'assignment'  => $assignment,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'tache_' . $this->sanitizeFilename($assignment->getTitre()) . '_' . date('Y-m-d') . '.pdf';

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Nettoie le nom de fichier
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = transliterator_transliterate('Any-Latin; Latin-ASCII', $filename);
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        $filename = trim($filename, '_');

        return substr($filename, 0, 50);
    }
}