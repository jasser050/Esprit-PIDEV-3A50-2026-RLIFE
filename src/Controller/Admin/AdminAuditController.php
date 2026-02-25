<?php

namespace App\Controller\Admin;

use App\Repository\AdminAuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AdminAuditController extends AbstractController
{
    #[Route('/', name: 'app_admin_audit_log')]
    public function index(Request $request, AdminAuditLogRepository $auditLogRepository): Response
    {
        // Get filter parameters
        $actionType = $request->query->get('action_type');
        $adminId = $request->query->get('admin_id');
        $dateFrom = $request->query->get('date_from');
        $dateTo = $request->query->get('date_to');

        // Build query
        $queryBuilder = $auditLogRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        // Apply filters
        if ($actionType) {
            $queryBuilder->andWhere('a.actionType = :actionType')
                ->setParameter('actionType', $actionType);
        }

        if ($adminId) {
            $queryBuilder->andWhere('a.adminUser = :adminId')
                ->setParameter('adminId', $adminId);
        }

        if ($dateFrom) {
            $from = new \DateTime($dateFrom);
            $from->setTime(0, 0, 0);
            $queryBuilder->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $from);
        }

        if ($dateTo) {
            $to = new \DateTime($dateTo);
            $to->setTime(23, 59, 59);
            $queryBuilder->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $to);
        }

        $logs = $queryBuilder->getQuery()->getResult();

        // Get statistics
        $totalLogs = $auditLogRepository->count([]);
        $logsLast7Days = $auditLogRepository->getStatisticsForLastDays(7);
        $actionCounts = $auditLogRepository->countByActionType();

        return $this->render('admin/audit/log.html.twig', [
            'logs' => $logs,
            'total_logs' => $totalLogs,
            'logs_last_7_days' => $logsLast7Days,
            'action_counts' => $actionCounts,
            'filters' => [
                'action_type' => $actionType,
                'admin_id' => $adminId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    #[Route('/export/csv', name: 'app_admin_audit_export_csv')]
    public function exportCsv(Request $request, AdminAuditLogRepository $auditLogRepository): Response
    {
        $logs = $auditLogRepository->findAllOrdered();

        $csv = "ID,Admin,Action,Target Type,Target ID,Description,IP Address,Date\n";

        foreach ($logs as $log) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s\n",
                $log->getId(),
                $log->getAdminUser() ? $log->getAdminUser()->getFullName() : 'N/A',
                $log->getActionType(),
                $log->getTargetType() ?? 'N/A',
                $log->getTargetId() ?? 'N/A',
                str_replace(["\n", "\r", ","], [" ", " ", ";"], $log->getDescription() ?? ''),
                $log->getIpAddress() ?? 'N/A',
                $log->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');

        return $response;
    }
}
