<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Complaint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed IRA Complaint Register rows for Orient POC demos.
 */
class SeedKenyaOrientComplaintsDemoCommand extends Command
{
    protected $signature = 'kenya-orient:seed-complaints-demo
                            {--force : Skip confirmation}';

    protected $description = 'Seed sample complaints for Orient POC (Complaint Register)';

    /**
     * Demo marker used to skip duplicates on re-run.
     * Descriptions always start with this prefix.
     */
    protected const DEMO_MARKER = '[POC DEMO]';

    /** @var list<array<string, mixed>> */
    protected array $complaints = [
        [
            'policy' => 'KOL-IND-10001',
            'days_ago' => 12,
            'nature' => 'Premium billing',
            'source' => 'Phone',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'description' => "Premium deduction of KES 3,500 was taken from M-Pesa but policy account still shows arrears. Client requests reconciliation and confirmation SMS.",
        ],
        [
            'policy' => 'KOL-IND-10002',
            'days_ago' => 8,
            'nature' => 'Documentation',
            'source' => 'Email',
            'status' => 'Pending Response',
            'priority' => 'Medium',
            'description' => "Policy document reprint requested twice with no response. Client needs soft copy for school fee loan application.",
        ],
        [
            'policy' => 'KOL-IND-10003',
            'days_ago' => 21,
            'nature' => 'Claim delay',
            'source' => 'In person',
            'status' => 'Escalated to IRA',
            'priority' => 'High',
            'description' => "Hospital cash claim lodged over 30 days ago. No settlement advice received despite complete documentation.",
        ],
        [
            'policy' => 'KOL-GRP-20001',
            'days_ago' => 5,
            'nature' => 'Policy servicing',
            'source' => 'Written letter',
            'status' => 'Received',
            'priority' => 'Medium',
            'description' => "Group Life scheme administrator reports delay adding new employees to cover after payroll cut-off.",
        ],
        [
            'policy' => 'KOL-MOR-30001',
            'days_ago' => 15,
            'nature' => 'Settlement dispute',
            'source' => 'IRA referral',
            'status' => 'Under Investigation',
            'priority' => 'High',
            'description' => "Mortgage cover cancellation refund amount disputed. Client says quoted surrender value differs from bank statement credit.",
        ],
        [
            'policy' => 'KOL-PEN-40001',
            'days_ago' => 30,
            'nature' => 'Settlement amount',
            'source' => 'Email',
            'status' => 'Resolved',
            'priority' => 'Medium',
            'resolved_days_ago' => 3,
            'resolution_notes' => 'Statement reissued; difference explained as tax deduction on early withdrawal. Client accepted.',
            'description' => "Pension partial withdrawal paid less than expected. Client asked for breakdown of tax and fees.",
        ],
        [
            'policy' => 'KOL-IND-10004',
            'days_ago' => 3,
            'nature' => 'Product mis-selling',
            'source' => 'Phone',
            'status' => 'Received',
            'priority' => 'High',
            'description' => "Client alleges intermediary promised cashback after 12 months that is not in the policy document.",
        ],
        [
            'policy' => 'KOL-GRP-20002',
            'days_ago' => 45,
            'nature' => 'Other',
            'source' => 'Email',
            'status' => 'Closed',
            'priority' => 'Low',
            'resolved_days_ago' => 10,
            'resolution_notes' => 'Member schedule corrected; confirmation letter sent to HR.',
            'description' => "Wrong beneficiary details captured on group last-expense certificate for one employee.",
        ],
    ];

    public function handle(): int
    {
        if (! Schema::connection('vtiger')->hasTable('complaints')) {
            $this->error('Table `complaints` is missing on the vtiger connection. Run migrations / complaints.sql first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Seed sample complaints for the POC register?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($this->complaints as $row) {
            $client = Client::query()->where('policy_no', $row['policy'])->first();
            $name = $client
                ? trim(($client->first_name ?? '').' '.($client->last_name ?? ''))
                : 'Demo Complainant';
            $phone = $client->phone ?? null;
            $email = $client->email ?? null;

            $description = self::DEMO_MARKER.' '.$row['description'];

            $exists = Complaint::query()
                ->where('policy_number', $row['policy'])
                ->where('description', 'like', self::DEMO_MARKER.'%')
                ->where('nature', $row['nature'])
                ->exists();

            if ($exists) {
                $this->line("Skip existing demo: {$row['policy']} / {$row['nature']}");
                $skipped++;
                continue;
            }

            $contactId = $this->resolveContactId($row['policy']);
            $dateReceived = now()->subDays((int) $row['days_ago'])->startOfDay();

            $payload = [
                'complaint_ref' => Complaint::generateRef(),
                'date_received' => $dateReceived->toDateString(),
                'complainant_name' => $name !== '' ? $name : 'Demo Complainant',
                'complainant_phone' => $phone,
                'complainant_email' => $email,
                'contact_id' => $contactId,
                'policy_number' => $row['policy'],
                'nature' => $row['nature'],
                'description' => $description,
                'source' => $row['source'],
                'status' => $row['status'],
                'register_status' => Complaint::REGISTER_ACTIVE,
                'classification_score' => 95,
                'classification_reason' => 'Manually registered (POC demo seed)',
                'priority' => $row['priority'],
                'assigned_to' => 'Compliance Desk',
                'date_resolved' => null,
                'resolution_notes' => null,
            ];

            if (! empty($row['resolved_days_ago'])) {
                $payload['date_resolved'] = now()->subDays((int) $row['resolved_days_ago'])->toDateString();
                $payload['resolution_notes'] = $row['resolution_notes'] ?? 'Resolved during POC demo.';
            }

            $complaint = Complaint::create($payload);
            $this->info("Created {$complaint->complaint_ref} — {$row['policy']} ({$row['status']})");
            $created++;
        }

        $this->newLine();
        $this->info("Complaints demo: created={$created} skipped={$skipped}");
        $this->line('Open Compliance → Complaint Register');

        return self::SUCCESS;
    }

    protected function resolveContactId(string $policy): ?int
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

        $conn = DB::connection('vtiger');
        if (! Schema::connection('vtiger')->hasTable('vtiger_contactscf')) {
            return null;
        }

        $row = $conn->table('vtiger_contactdetails as c')
            ->join('vtiger_crmentity as e', 'c.contactid', '=', 'e.crmid')
            ->leftJoin('vtiger_contactscf as cf', 'c.contactid', '=', 'cf.contactid')
            ->where('e.deleted', 0)
            ->where('cf.cf_860', $policy)
            ->value('c.contactid');

        return $row ? (int) $row : null;
    }
}
