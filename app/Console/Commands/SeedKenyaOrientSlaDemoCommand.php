<?php

namespace App\Console\Commands;

use App\Models\WorkTicket;
use App\Services\TicketSlaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed open client tickets (and work tickets) that are past TAT for the Broken SLA reports.
 */
class SeedKenyaOrientSlaDemoCommand extends Command
{
    protected $signature = 'kenya-orient:seed-sla-demo
                            {--force : Skip confirmation}
                            {--owner= : Vtiger user id to assign tickets to}';

    protected $description = 'Seed sample broken-SLA client/work tickets for Orient POC reports';

    /** @var list<array{title:string,category:string,priority:string,status:string,hours_ago:int,policy:string}> */
    protected array $clientTickets = [
        [
            'title' => 'Premium deduction not reflecting — KOL-IND-10001',
            'category' => 'Premium',
            'priority' => 'High',
            'status' => 'Open',
            'hours_ago' => 72,
            'policy' => 'KOL-IND-10001',
        ],
        [
            'title' => 'Policy document reprint request — KOL-IND-10002',
            'category' => 'General',
            'priority' => 'Normal',
            'status' => 'In Progress',
            'hours_ago' => 48,
            'policy' => 'KOL-IND-10002',
        ],
        [
            'title' => 'Claim status follow-up — KOL-IND-10003',
            'category' => 'Claims',
            'priority' => 'High',
            'status' => 'Wait For Response',
            'hours_ago' => 96,
            'policy' => 'KOL-IND-10003',
        ],
        [
            'title' => 'Group member addition delay — KOL-GRP-20001',
            'category' => 'Support',
            'priority' => 'Normal',
            'status' => 'Open',
            'hours_ago' => 60,
            'policy' => 'KOL-GRP-20001',
        ],
        [
            'title' => 'Mortgage cover confirmation overdue — KOL-MOR-30001',
            'category' => 'Other',
            'priority' => 'Urgent',
            'status' => 'Open',
            'hours_ago' => 36,
            'policy' => 'KOL-MOR-30001',
        ],
        [
            'title' => 'Pension statement not received — KOL-PEN-40001',
            'category' => 'Feature',
            'priority' => 'Normal',
            'status' => 'In Progress',
            'hours_ago' => 120,
            'policy' => 'KOL-PEN-40001',
        ],
    ];

    public function handle(TicketSlaService $sla): int
    {
        if (! $this->option('force') && ! $this->confirm('Seed sample broken-SLA tickets for reports?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->ensureTatDefaults($sla);

        $conn = DB::connection('vtiger');
        $ownerId = (int) ($this->option('owner') ?: 0);
        if ($ownerId <= 0) {
            $ownerId = (int) ($conn->table('vtiger_users')->where('user_name', 'ewanguba')->value('id')
                ?: $conn->table('vtiger_users')->where('status', 'Active')->orderBy('id')->value('id')
                ?: 1);
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->clientTickets as $row) {
            $contactId = $this->resolveContactId($conn, $row['policy']);
            if (! $contactId) {
                $this->warn("No CRM contact for policy {$row['policy']} — skipped.");
                $skipped++;
                continue;
            }

            // Avoid duplicate demo titles for the same contact
            $exists = $conn->table('vtiger_troubletickets as t')
                ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid')
                ->where('e.deleted', 0)
                ->where('t.contact_id', $contactId)
                ->where('t.title', $row['title'])
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $createdAt = now()->subHours($row['hours_ago'])->format('Y-m-d H:i:s');
            $id = (int) $conn->table('vtiger_crmentity')->max('crmid') + 1;
            $description = "Related policy: {$row['policy']}\n\nPOC demo ticket for Broken SLA report.\nOpened {$row['hours_ago']} hours ago to exceed TAT.";

            $conn->transaction(function () use ($conn, $id, $ownerId, $row, $contactId, $createdAt, $description) {
                $conn->table('vtiger_crmentity')->insert([
                    'crmid' => $id,
                    'smcreatorid' => $ownerId,
                    'smownerid' => $ownerId,
                    'modifiedby' => $ownerId,
                    'setype' => 'HelpDesk',
                    'description' => $description,
                    'createdtime' => $createdAt,
                    'modifiedtime' => $createdAt,
                    'viewedtime' => null,
                    'status' => 1,
                    'version' => 0,
                    'presence' => 1,
                    'deleted' => 0,
                    'smgroupid' => 0,
                    'source' => 'CRM',
                    'label' => $row['title'],
                ]);

                $conn->table('vtiger_troubletickets')->insert([
                    'ticketid' => $id,
                    'ticket_no' => 'TT'.$id,
                    'title' => $row['title'],
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'severity' => null,
                    'category' => $row['category'],
                    'contact_id' => $contactId,
                    'product_id' => null,
                    'parent_id' => null,
                    'hours' => null,
                    'days' => null,
                ]);

                try {
                    if (! $conn->table('vtiger_ticketcf')->where('ticketid', $id)->exists()) {
                        $conn->table('vtiger_ticketcf')->insert([
                            'ticketid' => $id,
                            'from_portal' => 0,
                        ]);
                    }
                } catch (\Throwable $e) {
                    // optional
                }

                if (Schema::connection('vtiger')->hasTable('vtiger_crmentity_seq')) {
                    $conn->table('vtiger_crmentity_seq')->delete();
                    $conn->table('vtiger_crmentity_seq')->insert(['id' => $id]);
                }
            });

            $created++;
            $this->line("Created {$row['title']} (TT{$id}, {$row['hours_ago']}h ago)");
        }

        $workCreated = $this->seedWorkTickets($ownerId);

        Cache::forget('reports:sla-broken-clients:view');
        Cache::forget('reports:sla-broken-work:view');
        Cache::forget('agile_ticket_counts_by_status');
        Cache::forget('agile_tickets_count');
        Cache::forget('tickets_list_default');

        $broken = $sla->getBrokenSlaTickets(20)->count();
        $this->info("Client SLA demo tickets: created={$created} skipped={$skipped}");
        $this->info("Work SLA demo tickets: created={$workCreated}");
        $this->line("Broken client SLAs now in report: {$broken}");
        $this->line('Open Reports → Client Tickets – Broken SLA');

        return self::SUCCESS;
    }

    protected function ensureTatDefaults(TicketSlaService $sla): void
    {
        // Short TATs so overdue demo tickets clearly breach
        foreach ([
            'Premium' => 24,
            'Claims' => 24,
            'General' => 24,
            'Support' => 24,
            'Other' => 24,
            'Feature' => 48,
            'Bug' => 48,
        ] as $category => $hours) {
            try {
                $sla->setCategoryTat($category, $hours);
            } catch (\Throwable $e) {
                // category table may not exist — department fallback
                try {
                    $sla->setDepartmentTat($category, $hours);
                } catch (\Throwable $e2) {
                    // ignore
                }
            }
        }
    }

    protected function resolveContactId($conn, string $policy): ?int
    {
        $policy = trim($policy);
        if ($policy === '') {
            return null;
        }

        try {
            $id = app(\App\Services\CrmService::class)->findContactByPolicyNumber($policy)?->contactid ?? null;
            if ($id) {
                return (int) $id;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        $row = $conn->table('vtiger_contactdetails as c')
            ->join('vtiger_crmentity as e', 'c.contactid', '=', 'e.crmid')
            ->leftJoin('vtiger_contactscf as cf', 'c.contactid', '=', 'cf.contactid')
            ->where('e.deleted', 0)
            ->where('cf.cf_860', $policy)
            ->value('c.contactid');

        return $row ? (int) $row : null;
    }

    protected function seedWorkTickets(int $ownerId): int
    {
        if (! Schema::hasTable('work_tickets')) {
            $this->warn('work_tickets table missing — skipped work SLA samples.');

            return 0;
        }

        $samples = [
            [
                'title' => 'Reconcile M-Pesa collections — overdue',
                'priority' => 'High',
                'status' => 'In Progress',
                'tat_hours' => 24,
                'hours_ago' => 48,
            ],
            [
                'title' => 'Update FAQ for premium FAQs — overdue',
                'priority' => 'Normal',
                'status' => 'Open',
                'tat_hours' => 48,
                'hours_ago' => 80,
            ],
            [
                'title' => 'Client statement pack for agents — late close',
                'priority' => 'Normal',
                'status' => 'Done',
                'tat_hours' => 24,
                'hours_ago' => 72,
                'completed_after_hours' => 40,
            ],
        ];

        $created = 0;
        foreach ($samples as $row) {
            $exists = WorkTicket::query()->where('title', $row['title'])->exists();
            if ($exists) {
                continue;
            }

            $started = now()->subHours($row['hours_ago']);
            $due = $started->copy()->addHours($row['tat_hours']);
            $completedAt = null;
            $breachedAt = null;
            if (($row['status'] ?? '') === 'Done') {
                $completedAt = $started->copy()->addHours($row['completed_after_hours'] ?? ($row['tat_hours'] + 12));
                $breachedAt = $due->copy();
            } elseif ($due->lt(now())) {
                $breachedAt = $due->copy();
            }

            $ticket = WorkTicket::create([
                'ticket_no' => 'WT-POC-'.strtoupper(substr(md5($row['title']), 0, 6)),
                'title' => $row['title'],
                'description' => 'POC demo work ticket for Broken Work SLA report.',
                'status' => $row['status'],
                'priority' => $row['priority'],
                'assignee_id' => $ownerId,
                'created_by' => $ownerId,
                'due_date' => $due->toDateString(),
                'tat_hours' => $row['tat_hours'],
                'tat_due_at' => $due,
                'tat_breached_at' => $breachedAt,
                'started_at' => $started,
                'completed_at' => $completedAt,
            ]);

            // Backdate created_at so the record looks overdue
            DB::table('work_tickets')->where('id', $ticket->id)->update([
                'created_at' => $started,
                'updated_at' => $started,
            ]);

            $created++;
            $this->line("Created work ticket {$ticket->ticket_no}");
        }

        return $created;
    }
}
