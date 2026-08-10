<?php

namespace App\Console\Commands;

use App\Models\UserClientAssignment;
use App\Services\ClientAccessDemoService;
use App\Services\ProfileAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipe Geminia operational CRM data on the shared DB, keep Kenya Orient POC setup
 * and only users ewanguba + kkmutai.
 */
class PrepareKenyaOrientDatabaseCommand extends Command
{
    protected $signature = 'kenya-orient:prepare-db
                            {--dry-run : Show what would change without writing}
                            {--force : Skip confirmation}';

    protected $description = 'Clear Geminia CRM data on .72; keep ewanguba/kkmutai, faker POC data, and setup';

    /** @var list<string> */
    protected array $keepUsernames = ['ewanguba', 'kkmutai'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->warn('Target DB: ' . config('database.connections.vtiger.host') . ' / ' . config('database.connections.vtiger.database'));
        $this->line('Keep users: ' . implode(', ', $this->keepUsernames));
        $this->line('Keep: FAQ KB, DEMO clients, agents, departments, SLA/TAT, profile/role setup');
        $this->line('Clear: contacts/leads/tickets/activities/emails, other users (inactive), work tickets, complaints, SMS logs, non-demo clients');

        if (! $dry && ! $force && ! $this->confirm('This is destructive. Continue?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $keepIds = DB::connection('vtiger')
            ->table('vtiger_users')
            ->whereIn('user_name', $this->keepUsernames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($keepIds) < 2) {
            $found = DB::connection('vtiger')
                ->table('vtiger_users')
                ->whereIn('user_name', $this->keepUsernames)
                ->pluck('user_name')
                ->all();
            $this->error('Could not find both keep users. Found: ' . implode(', ', $found ?: ['(none)']));

            return self::FAILURE;
        }

        $primaryOwner = (int) (DB::connection('vtiger')
            ->table('vtiger_users')
            ->where('user_name', 'ewanguba')
            ->value('id') ?: $keepIds[0]);

        $this->info('Keep user ids: ' . implode(', ', $keepIds) . " (owner={$primaryOwner})");

        DB::connection('vtiger')->disableQueryLog();
        DB::disableQueryLog();

        $this->stepUsers($dry, $keepIds);
        $this->stepSoftDeleteCrmModules($dry);
        $this->stepClearLocalOperational($dry, $primaryOwner);
        $this->stepReassignDemoAccess($dry, $primaryOwner);
        $this->stepEnsureKeepUsersActive($dry, $keepIds);

        if ($dry) {
            $this->warn('Dry run complete — no changes written.');
        } else {
            $this->info('Kenya Orient prepare complete.');
            $this->line('Active users should be only: ewanguba, kkmutai');
            $this->line('Demo clients DEMO-R / DEMO-X retained; FAQ/agents/SLA retained.');
        }

        return self::SUCCESS;
    }

    protected function stepUsers(bool $dry, array $keepIds): void
    {
        $this->info('1) Deactivate users other than ewanguba / kkmutai…');
        $q = DB::connection('vtiger')->table('vtiger_users')->whereNotIn('id', $keepIds);
        $count = (clone $q)->count();
        $this->line("   Users to deactivate: {$count}");
        if (! $dry && $count > 0) {
            $q->update([
                'status' => 'Inactive',
            ]);
        }
    }

    protected function stepEnsureKeepUsersActive(bool $dry, array $keepIds): void
    {
        $this->info('6) Ensure keep users are Active admins…');
        if ($dry) {
            return;
        }
        DB::connection('vtiger')->table('vtiger_users')
            ->whereIn('id', $keepIds)
            ->update([
                'status' => 'Active',
                'is_admin' => 'on',
            ]);
    }

    protected function stepSoftDeleteCrmModules(bool $dry): void
    {
        $this->info('2) Soft-delete CRM business records (vtiger_crmentity.deleted=1)…');

        $setypes = [
            'Contacts',
            'Leads',
            'Accounts',
            'Potentials',
            'HelpDesk',
            'Ticket',
            'Calendar',
            'Emails',
            'Faq',
            'Documents',
            'Campaigns',
            'Vendors',
            'Products',
            'Services',
            'PriceBooks',
            'Invoice',
            'Quotes',
            'SalesOrder',
            'PurchaseOrder',
            'Project',
            'ProjectTask',
            'ProjectMilestone',
            'SMSNotifier',
            'ModComments',
            'PBXManager',
            'MailManager Attachment',
            'Emails Attachment',
            'Documents Attachment',
            'ModComments Attachment',
            'Task',
            'Events',
        ];

        $active = DB::connection('vtiger')
            ->table('vtiger_crmentity')
            ->where('deleted', 0)
            ->whereIn('setype', $setypes)
            ->count();
        $this->line("   Active CRM rows matching modules: {$active}");

        if ($dry || $active === 0) {
            return;
        }

        // Chunk by crmid to avoid long locks.
        $updated = 0;
        DB::connection('vtiger')
            ->table('vtiger_crmentity')
            ->where('deleted', 0)
            ->whereIn('setype', $setypes)
            ->orderBy('crmid')
            ->select('crmid')
            ->chunkById(2000, function ($rows) use (&$updated) {
                $ids = $rows->pluck('crmid')->all();
                if ($ids === []) {
                    return;
                }
                $n = DB::connection('vtiger')->table('vtiger_crmentity')
                    ->whereIn('crmid', $ids)
                    ->update(['deleted' => 1, 'modifiedtime' => now()->format('Y-m-d H:i:s')]);
                $updated += $n;
                $this->line("   soft-deleted {$updated}…");
            }, 'crmid');

        $this->line("   Soft-deleted total: {$updated}");
    }

    protected function stepClearLocalOperational(bool $dry, int $primaryOwner): void
    {
        $this->info('3) Clear local operational tables (keep setup + faker)…');

        $truncate = [
            'work_ticket_updates',
            'work_tickets',
            'sms_logs',
            'ticket_reassignments',
            'ticket_comments',
            'erp_client_comments',
            'erp_client_documents',
            'erp_client_consents',
            'mpesa_stk_transactions',
            'mail_manager_messages',
            'mail_manager_attachments',
            'bounced_emails',
        ];

        foreach ($truncate as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->count();
            $this->line("   {$table}: {$count}");
            if (! $dry && $count > 0) {
                DB::table($table)->delete();
            }
        }

        // Complaints table name variants
        foreach (['complaints', 'complaint_registers'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->count();
            $this->line("   {$table}: {$count}");
            if (! $dry && $count > 0) {
                DB::table($table)->delete();
            }
        }

        // Non-demo local clients
        if (Schema::hasTable('clients')) {
            $q = DB::table('clients')
                ->where(function ($w) {
                    $w->where('policy_no', 'not like', 'DEMO-%')
                        ->where(function ($w2) {
                            $w2->whereNull('source')->orWhere('source', '!=', 'demo');
                        });
                });
            // Keep DEMO-* always
            $q = DB::table('clients')->where('policy_no', 'not like', 'DEMO-%');
            $count = (clone $q)->count();
            $this->line("   non-DEMO clients to remove: {$count}");
            if (! $dry && $count > 0) {
                $q->delete();
            }
            $this->line('   DEMO clients kept: ' . DB::table('clients')->where('policy_no', 'like', 'DEMO-%')->count());
        }

        // user_departments for inactive users
        if (Schema::hasTable('user_departments')) {
            $keepIds = DB::connection('vtiger')
                ->table('vtiger_users')
                ->whereIn('user_name', $this->keepUsernames)
                ->pluck('id')
                ->all();
            $count = DB::table('user_departments')->whereNotIn('user_id', $keepIds)->count();
            $this->line("   user_departments rows to remove: {$count}");
            if (! $dry && $count > 0) {
                DB::table('user_departments')->whereNotIn('user_id', $keepIds)->delete();
            }
        }

        // reporting lines
        if (Schema::hasTable('user_reporting_lines')) {
            $keepIds = DB::connection('vtiger')
                ->table('vtiger_users')
                ->whereIn('user_name', $this->keepUsernames)
                ->pluck('id')
                ->all();
            $count = DB::table('user_reporting_lines')
                ->where(function ($q) use ($keepIds) {
                    $q->whereNotIn('user_id', $keepIds)->orWhereNotIn('manager_id', $keepIds);
                })
                ->count();
            $this->line("   user_reporting_lines to clean: {$count}");
            if (! $dry && $count > 0) {
                DB::table('user_reporting_lines')
                    ->where(function ($q) use ($keepIds) {
                        $q->whereNotIn('user_id', $keepIds)->orWhereNotIn('manager_id', $keepIds);
                    })
                    ->delete();
            }
        }

        unset($primaryOwner); // used in next step
    }

    protected function stepReassignDemoAccess(bool $dry, int $primaryOwner): void
    {
        $this->info('4) Point DEMO client assignments at ewanguba…');
        if (! Schema::hasTable('agile_user_client_assignments')) {
            $this->line('   assignments table missing — skip');

            return;
        }

        $demoCount = DB::table('agile_user_client_assignments')
            ->where(function ($q) {
                $q->where('policy_number', 'like', 'DEMO-%')
                    ->orWhere('policy_number', 'like', ClientAccessDemoService::ALLOWED_PREFIX . '%')
                    ->orWhere('policy_number', 'like', ClientAccessDemoService::FORBIDDEN_PREFIX . '%');
            })
            ->count();
        $this->line("   demo assignment rows: {$demoCount}");

        if ($dry) {
            return;
        }

        // Remove assignments for other users; re-seed for primary owner.
        DB::table('agile_user_client_assignments')->delete();
        try {
            app(ClientAccessDemoService::class)->seed($primaryOwner);
            $this->line("   Re-seeded DEMO-R assignments for user id {$primaryOwner}");
        } catch (\Throwable $e) {
            $this->warn('   Demo reseed failed: ' . $e->getMessage());
        }
        app(ProfileAccessService::class)->clearClientAssignmentCacheForUser($primaryOwner);
    }
}
