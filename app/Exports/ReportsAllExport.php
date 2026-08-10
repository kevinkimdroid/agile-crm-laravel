<?php

namespace App\Exports;

use App\Services\CrmService;
use App\Services\TicketSlaService;
use App\Services\UserDepartmentService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsAllExport implements WithMultipleSheets
{
    public function __construct(
        protected CrmService $crm,
        protected TicketSlaService $sla,
        protected int $ticketAgingDays = 7,
        protected ?UserDepartmentService $userDept = null
    ) {
        $this->userDept = $userDept ?? app(UserDepartmentService::class);
    }

    public function sheets(): array
    {
        $slaTickets = $this->sla->getBrokenSlaTickets(500);
        $slaUserIds = $slaTickets->pluck('smownerid')->filter()->unique()->values()->all();
        $slaDepts = $this->userDept->getDepartmentsForUsers($slaUserIds);
        $workSlaTickets = $this->sla->getBrokenWorkSlaTickets(500);

        $agingTickets = $this->crm->getTicketAgingReport($this->ticketAgingDays, 500);
        $agingUserIds = $agingTickets->pluck('smownerid')->filter()->unique()->values()->all();
        $agingDepts = $this->userDept->getDepartmentsForUsers($agingUserIds);

        return [
            'Summary' => new ReportsSummarySheet($this->crm),
            'Client Broken SLA' => new SlaBrokenExport(
                $slaTickets->map(fn ($t) => [
                    $t->ticket_no ?? 'TT' . $t->ticketid,
                    $t->title ?? '',
                    $t->category ?? 'General',
                    $t->status ?? '',
                    trim(($t->owner_first ?? '') . ' ' . ($t->owner_last ?? '')) ?: 'Unassigned',
                    $slaDepts[$t->smownerid ?? 0] ?? '',
                    trim(($t->contact_first ?? '') . ' ' . ($t->contact_last ?? '')) ?: '',
                    $t->createdtime ?? '',
                    isset($t->due_at) ? $t->due_at->format('Y-m-d H:i:s') : '',
                    ($t->status ?? '') === 'Closed' && isset($t->breached_at) ? $t->breached_at->format('Y-m-d H:i:s') : 'Still open',
                    $t->tat_hours ?? 24,
                    $t->hours_overdue ?? 0,
                ])->toArray()
            ),
            'Work Broken SLA' => new SlaBrokenExport(
                $workSlaTickets->map(fn ($t) => [
                    $t->ticket_no ?? 'WT' . ($t->id ?? ''),
                    $t->title ?? '',
                    $t->priority ?? '',
                    $t->status ?? '',
                    $t->assignee_name ?? 'Unassigned',
                    $t->owner_department ?? '',
                    optional($t->created_at)->format('Y-m-d H:i:s') ?? '',
                    isset($t->due_at) ? $t->due_at->format('Y-m-d H:i:s') : '',
                    in_array((string) ($t->status ?? ''), ['Done', 'Closed'], true) && isset($t->completed_at)
                        ? $t->completed_at->format('Y-m-d H:i:s')
                        : 'Still open',
                    $t->tat_hours ?? '',
                    $t->hours_overdue ?? 0,
                ])->toArray(),
                ['Ticket', 'Title', 'Priority', 'Status', 'Assigned to', 'User Dept', 'Created', 'Due by', 'Completed at', 'TAT (h)', 'Hours Overdue']
            ),
            'Ticket Aging' => new TicketAgingExport(
                $agingTickets->map(fn ($t) => [
                    $t->ticket_no ?? 'TT' . $t->ticketid,
                    $t->title ?? '',
                    $t->status ?? '',
                    $t->category ?? 'General',
                    trim(($t->firstname ?? '') . ' ' . ($t->lastname ?? '')) ?: '',
                    $t->createdtime ?? '',
                    trim(($t->owner_first ?? '') . ' ' . ($t->owner_last ?? '')) ?: 'Unassigned',
                    $agingDepts[$t->smownerid ?? 0] ?? '',
                ])->toArray()
            ),
            'Sales by Person' => new SalesByPersonExport(
                $this->crm->getSalesByPerson(100)->map(fn ($r) => [trim($r->name) ?: 'Unassigned', $r->total])->toArray()
            ),
            'Pipeline by Stage' => new PipelineByStageExport(
                collect($this->crm->getPipelineByStage())->map(fn ($d, $stage) => [$stage, $d['count'], $d['amount']])->values()->toArray()
            ),
            'Reassignment Audit' => new ReassignmentAuditExport(
                \App\Models\TicketReassignment::orderByDesc('created_at')
                    ->limit(2000)
                    ->get()
                    ->map(fn ($r) => [
                        'TT' . $r->ticket_id,
                        $r->from_user_name ?? 'Unassigned',
                        $r->to_user_name ?? '—',
                        $r->reassigned_by_name ?? '—',
                        $r->created_at?->format('Y-m-d H:i:s') ?? '',
                    ])
                    ->toArray()
            ),
        ];
    }
}
