<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ProjectPdfService
{
    public function __construct(
        private Environment $twig
    ) {}

    /**
     * Génère un PDF avec la liste des projets
     *
     * @param array $projects
     * @param User $user
     * @return Response
     */
    public function generateProjectListPdf(array $projects, User $user): Response
    {
        $html = $this->twig->render('pages/projects/pdf/projects_list.html.twig', [
            'projects' => $projects,
            'user'      => $user,
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

        $filename = 'projets_' . date('Y-m-d_His') . '.pdf';

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
     * Génère un PDF pour un projet spécifique
     *
     * @param Project $project
     * @param array $assignments
     * @return Response
     */
    public function generateSingleProjectPdf(Project $project, array $assignments): Response
    {
        $html = $this->twig->render('pages/projects/pdf/project_detail.html.twig', [
            'project'      => $project,
            'assignments'  => $assignments,
            'generatedAt'  => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'projet_' . $this->sanitizeFilename($project->getTitre()) . '_' . date('Y-m-d') . '.pdf';

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
     * @param array<string,mixed> $report
     * @param array $assignments
     */
    public function generateAiCompletionReportPdf(Project $project, array $assignments, array $report): Response
    {
        $html = $this->twig->render('pages/projects/pdf/ai_completion_report.html.twig', [
            'project'      => $project,
            'assignments'  => $assignments,
            'report'       => $report,
            'generatedAt'  => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'project_ai_report_' . $this->sanitizeFilename($project->getTitre()) . '_' . date('Y-m-d_His') . '.pdf';

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
