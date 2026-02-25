<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$outDir = __DIR__ . '/../public/downloads';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

$fileName = 'sprint_backlog_project_assignment_ai.pdf';
$filePath = $outDir . '/' . $fileName;

$rows = [
    ['1', 'Gestion Projet', '', '', '', '', '', '', '', ''],
    ['1.1', '', 'Creer/modifier/supprimer un projet pour gerer mon travail', 100, 8, 'Sprint 1', 'Finaliser validations formulaire + gestion erreurs + messages flash + tests manuels', 'Dev Backend', 'In Progress', 3],
    ['1.2', '', 'Consulter liste/detail des projets avec filtres', 97, 6, 'Sprint 1', 'Uniformiser tri/recherche/statut + corriger edge cases pagination', 'Dev Backend', 'To Do', 6],
    ['1.3', '', 'Partager un projet avec un collaborateur', 94, 8, 'Sprint 1', 'Fiabiliser share/remove + notifications + controle acces', 'Dev Backend', 'In Progress', 5],
    ['1.4', '', 'Exporter projets en PDF', 90, 6, 'Sprint 1', 'Corriger templates PDF + styles + encodage + tests export', 'Dev Backend', 'To Do', 6],
    ['1.5', '', 'Generer des assignments IA pour un projet', 96, 9, 'Sprint 2', 'Ameliorer generation suggestions + priorites + dates + raisons', 'Dev AI/Backend', 'In Progress', 5],
    ['1.6', '', 'Appliquer les suggestions IA en masse', 91, 6, 'Sprint 2', 'Verifier apply multi-suggestions + deduplication + rollback erreurs', 'Dev Backend', 'To Do', 6],
    ['1.7', '', 'Appliquer actions IA (risk/balance/quality/sprint)', 88, 10, 'Sprint 2', 'Fiabiliser endpoints apply + coherence donnees + messages succes/erreur', 'Dev AI/Backend', 'To Do', 10],
    ['1.8', '', 'Generer un rapport final IA de projet termine', 90, 6, 'Sprint 3', 'Stabiliser generation resume/lessons/next steps + sauvegarde session', 'Dev AI/Backend', 'To Do', 6],
    ['1.9', '', 'Telecharger le rapport IA en PDF', 89, 5, 'Sprint 3', 'Finaliser template PDF IA + mise en page + controle statut projet termine', 'Dev Backend', 'To Do', 5],

    ['2', 'Gestion Assignment', '', '', '', '', '', '', '', ''],
    ['2.1', '', 'Creer/editer/supprimer des taches liees aux projets', 100, 8, 'Sprint 1', 'Verifier lien project-task + statuts + priorites + controles ownership', 'Dev Backend', 'In Progress', 4],
    ['2.2', '', 'Filtrer/trier les taches pour suivre les deadlines', 95, 6, 'Sprint 1', 'Optimiser filtres (statut/priorite/date) + coherence UI + reset filtres', 'Dev Front/Back', 'To Do', 6],
    ['2.3', '', 'Generer un plan IA avec recommandations actionnables', 98, 10, 'Sprint 2', 'Stabiliser generate + session storage + feedback UI source/model', 'Dev AI/Backend', 'In Progress', 6],
    ['2.4', '', 'Accepter une reco IA et lancer un challenge', 93, 7, 'Sprint 2', 'Verifier accept flow + creation challenge + duree/recompense', 'Dev AI/Backend', 'To Do', 7],
    ['2.5', '', 'Claim la recompense quand la tache/projet est termine', 92, 7, 'Sprint 2', 'Corriger regles de claim + anti double-claim + transaction coins', 'Dev Backend', 'To Do', 7],
    ['2.6', '', 'Ajouter tests sur flows critiques Project/Assignment/IA', 87, 10, 'Sprint 3', 'Ecrire tests fonctionnels generate/accept/claim/report + non regression', 'QA/Dev', 'To Do', 10],
    ['2.7', '', 'Securiser routes et flux critiques', 93, 8, 'Sprint 3', 'Audit CSRF + ownership + validations serveur + logs erreurs', 'Dev Backend', 'To Do', 8],
    ['2.8', '', 'Diagnostiquer les redemarrages PHP-CGI', 80, 4, 'Sprint 3', 'Ajouter checklist debug + revue logs + guardrails runtime', 'DevOps/Backend', 'To Do', 4],
];

$html = '<!doctype html><html><head><meta charset="UTF-8"><style>'
    . '@page{margin:6px;}'
    . 'body{font-family:DejaVu Sans,sans-serif;font-size:7.5px;color:#111;margin:0;}'
    . '.frame{border:2px solid #7c3aed;padding:2px;}'
    . 'h1{font-size:11px;margin:0 0 3px 0;color:#1e3a8a;}'
    . 'table{width:100%;border-collapse:collapse;table-layout:fixed;}'
    . 'th,td{border:1px solid #d1d5db;padding:1px 2px;vertical-align:top;word-wrap:break-word;line-height:1.08;}'
    . 'th{background:#ffffff;font-weight:700;text-align:left;}'
    . '.small{font-size:7px;color:#475569;margin-bottom:3px;}'
    . '</style></head><body>'
    . '<div class="frame">'
    . '<h1>Sprint Backlog - Project + Assignment + IA</h1>'
    . '<div class="small">Version sans SB-15 - format Trello ready</div>'
    . '<table><thead><tr>'
    . '<th style="width:6%">ID</th>'
    . '<th style="width:10%">Epic</th>'
    . '<th style="width:27%">User Story</th>'
    . '<th style="width:5%">Priorite</th>'
    . '<th style="width:9%">Nombre d\'heures</th>'
    . '<th style="width:6%">Sprint</th>'
    . '<th style="width:19%">Taches</th>'
    . '<th style="width:8%">Assigne a</th>'
    . '<th style="width:6%">Statut</th>'
    . '<th style="width:4%">Reste (h)</th>'
    . '</tr></thead><tbody>';

foreach ($rows as $r) {
    $isSection = strpos((string) $r[0], '.') === false;
    if ($isSection) {
        $html .= '<tr style="background:#f8fafc;font-weight:700;">';
    } else {
        $html .= '<tr>';
    }
    foreach ($r as $cell) {
        $html .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table></div></body></html>';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper([0, 0, 1180, 580], 'landscape');
$dompdf->render();
file_put_contents($filePath, $dompdf->output());

echo $filePath . PHP_EOL;
