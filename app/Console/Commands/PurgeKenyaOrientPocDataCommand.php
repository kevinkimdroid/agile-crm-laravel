<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ContactComment;
use App\Services\OrientPocCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove Orient POC / demo seed data from a Geminia CRM database.
 * Use --host=10.1.1.64 to clean the shared/production DB; POC work uses 10.1.1.72.
 */
class PurgeKenyaOrientPocDataCommand extends Command
{
    protected $signature = 'kenya-orient:purge-poc-data
                            {--dry-run : Show counts only}
                            {--force : Skip confirmation}
                            {--host= : Override DB host (e.g. 10.1.1.64 or 10.1.1.72 POC)}
                            {--hard-delete : Physically remove POC rows (including already soft-deleted)}';

    protected $description = 'Remove POC/demo test data (KOL-*, DEMO-*, orient-demo.ke) from Geminia CRM';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $hardDelete = (bool) $this->option('hard-delete');
        $hostOverride = trim((string) ($this->option('host') ?? ''));

        if ($hostOverride !== '') {
            $cfg = config('database.connections.vtiger');
            config(['database.connections.vtiger' => array_merge($cfg, ['host' => $hostOverride])]);
            DB::purge('vtiger');
        }

        $host = config('database.connections.vtiger.host');
        $database = config('database.connections.vtiger.database');

        $this->warn("Target: {$host} / {$database}");
        $this->line('Removes: KOL-* & DEMO-* local clients, orient-demo.ke CRM contacts/leads, POC demo tickets, related app rows.');
        $this->line('Keeps: real client/policy data not matching demo patterns.');
        if ($hardDelete) {
            $this->warn('--hard-delete: permanently removes matching CRM rows (including recycle-bin / deleted=1).');
        }

        if (! $dry && ! $force && ! $this->confirm('Remove POC/demo test data from this database?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $vtiger = DB::connection('vtiger');
        $stats = [
            'clients' => 0,
            'assignments' => 0,
            'contacts' => 0,
            'leads' => 0,
            'tickets' => 0,
            'work_tickets' => 0,
            'contact_comments' => 0,
            'erp_comments' => 0,
            'erp_documents' => 0,
            'mpesa_tx' => 0,
        ];

        // --- Local clients table ---
        if (Schema::hasTable('clients')) {
            $q = DB::table('clients')->where(function ($w) {
                foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                    $w->orWhere('policy_no', 'like', $prefix.'%');
                }
                $w->orWhere('source', 'seed')
                    ->orWhere('source', 'demo')
                    ->orWhere('email', 'like', '%@orient-demo.%');
            });
            $stats['clients'] = (clone $q)->count();
            $this->line("clients (POC/demo): {$stats['clients']}");
            if (! $dry && $stats['clients'] > 0) {
                $q->delete();
            }
        }

        // --- Client assignments ---
        if (Schema::hasTable('agile_user_client_assignments')) {
            $q = DB::table('agile_user_client_assignments')->where(function ($w) {
                foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                    $w->orWhere('policy_number', 'like', $prefix.'%');
                }
            });
            $stats['assignments'] = (clone $q)->count();
            $this->line("agile_user_client_assignments: {$stats['assignments']}");
            if (! $dry && $stats['assignments'] > 0) {
                $q->delete();
            }
        }

        // --- ERP client app rows by policy ---
        foreach (['erp_client_comments' => 'erp_comments', 'erp_client_documents' => 'erp_documents', 'erp_client_consents' => 'erp_consents'] as $table => $key) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $q = DB::table($table)->where(function ($w) {
                foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                    $w->orWhere('policy_number', 'like', $prefix.'%');
                }
            });
            $count = (clone $q)->count();
            if ($key === 'erp_comments') {
                $stats['erp_comments'] = $count;
            } elseif ($key === 'erp_documents') {
                $stats['erp_documents'] = $count;
            }
            $this->line("{$table}: {$count}");
            if (! $dry && $count > 0) {
                $q->delete();
            }
        }

        if (Schema::hasTable('mpesa_stk_transactions')) {
            $q = DB::table('mpesa_stk_transactions')->where(function ($w) {
                foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                    $w->orWhere('policy_number', 'like', $prefix.'%');
                }
            });
            $stats['mpesa_tx'] = (clone $q)->count();
            $this->line("mpesa_stk_transactions: {$stats['mpesa_tx']}");
            if (! $dry && $stats['mpesa_tx'] > 0) {
                $q->delete();
            }
        }

        // --- CRM contact ids to remove ---
        $contactIds = $this->findDemoContactIds($vtiger, $hardDelete);
        $this->line('CRM contacts (demo): '.count($contactIds));

        if ($contactIds !== []) {
            $ticketIds = $this->findTicketIdsForContacts($vtiger, $contactIds, $hardDelete);
            $stats['tickets'] = count($ticketIds);
            $this->line("CRM tickets linked to demo contacts: {$stats['tickets']}");

            $pocTicketIds = $this->findPocDemoTicketIds($vtiger, $hardDelete);
            $ticketIds = array_values(array_unique(array_merge($ticketIds, $pocTicketIds)));
            $stats['tickets'] = count($ticketIds);
            $this->line("CRM tickets total (demo + POC titles): {$stats['tickets']}");

            if (! $dry) {
                if ($ticketIds !== []) {
                    if ($hardDelete) {
                        $this->hardDeleteTickets($vtiger, $ticketIds);
                    } else {
                        $vtiger->table('vtiger_crmentity')
                            ->whereIn('crmid', $ticketIds)
                            ->update(['deleted' => 1, 'modifiedtime' => now()->format('Y-m-d H:i:s')]);
                    }
                }
                if ($hardDelete) {
                    $this->hardDeleteContacts($vtiger, $contactIds);
                } else {
                    $vtiger->table('vtiger_crmentity')
                        ->whereIn('crmid', $contactIds)
                        ->update(['deleted' => 1, 'modifiedtime' => now()->format('Y-m-d H:i:s')]);
                }
                $stats['contacts'] = count($contactIds);
            } else {
                $stats['contacts'] = count($contactIds);
            }

            if (Schema::hasTable('contact_comments') && class_exists(ContactComment::class)) {
                $q = ContactComment::query()->whereIn('contact_id', $contactIds);
                $stats['contact_comments'] = (clone $q)->count();
                $this->line("contact_comments: {$stats['contact_comments']}");
                if (! $dry && $stats['contact_comments'] > 0) {
                    $q->delete();
                }
            }
        } else {
            $pocTicketIds = $this->findPocDemoTicketIds($vtiger, $hardDelete);
            $stats['tickets'] = count($pocTicketIds);
            $this->line("CRM tickets (POC titles only): {$stats['tickets']}");
            if (! $dry && $pocTicketIds !== []) {
                if ($hardDelete) {
                    $this->hardDeleteTickets($vtiger, $pocTicketIds);
                } else {
                    $vtiger->table('vtiger_crmentity')
                        ->whereIn('crmid', $pocTicketIds)
                        ->update(['deleted' => 1, 'modifiedtime' => now()->format('Y-m-d H:i:s')]);
                }
            }
        }

        // --- Demo leads ---
        if (Schema::connection('vtiger')->hasTable('vtiger_leaddetails')) {
            $leadQuery = $vtiger->table('vtiger_leaddetails as l')
                ->join('vtiger_crmentity as e', 'l.leadid', '=', 'e.crmid');
            if (! $hardDelete) {
                $leadQuery->where('e.deleted', 0);
            }
            $leadIds = $leadQuery->where(function ($q) {
                    $q->where('l.email', 'like', '%@orient-demo.%')
                        ->orWhere('l.company', 'like', '%orient-demo%');
                })
                ->pluck('l.leadid')
                ->map(fn ($id) => (int) $id)
                ->all();
            $stats['leads'] = count($leadIds);
            $this->line("CRM leads (orient-demo): {$stats['leads']}");
            if (! $dry && $leadIds !== []) {
                if ($hardDelete) {
                    $this->hardDeleteLeads($vtiger, $leadIds);
                } else {
                    $vtiger->table('vtiger_crmentity')
                        ->whereIn('crmid', $leadIds)
                        ->update(['deleted' => 1, 'modifiedtime' => now()->format('Y-m-d H:i:s')]);
                }
            }
        }

        // --- Work tickets ---
        if (Schema::hasTable('work_tickets')) {
            $q = DB::table('work_tickets')->where(function ($w) {
                $w->where('description', 'like', '%POC demo%')
                    ->orWhere('title', 'like', '%POC demo%');
                foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                    $w->orWhere('description', 'like', '%'.$prefix.'%')
                        ->orWhere('title', 'like', '%'.$prefix.'%');
                }
            });
            $stats['work_tickets'] = (clone $q)->count();
            $this->line("work_tickets: {$stats['work_tickets']}");
            if (! $dry && $stats['work_tickets'] > 0) {
                if (Schema::hasTable('work_ticket_updates')) {
                    $ids = (clone $q)->pluck('id')->all();
                    if ($ids !== []) {
                        DB::table('work_ticket_updates')->whereIn('work_ticket_id', $ids)->delete();
                    }
                }
                $q->delete();
            }
        }

        Cache::flush();

        if ($dry) {
            $this->warn('Dry run — no changes written.');
        } else {
            $this->info('POC/demo test data removed.');
            $this->table(['Type', 'Removed'], collect($stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
            $this->line('Demo modes: set INVESTMENT_MATURITIES_DEMO=false and RENEWALS_DEMO=false in .env for live ERP data.');
        }

        return self::SUCCESS;
    }

    /** @return list<int> */
    protected function findDemoContactIds($vtiger, bool $includeSoftDeleted = false): array
    {
        if (! Schema::connection('vtiger')->hasTable('vtiger_contactdetails')) {
            return [];
        }

        $query = $vtiger->table('vtiger_contactdetails as c')
            ->join('vtiger_crmentity as e', 'c.contactid', '=', 'e.crmid')
            ->select('c.contactid');
        if (! $includeSoftDeleted) {
            $query->where('e.deleted', 0);
        }

        $hasScf = Schema::connection('vtiger')->hasTable('vtiger_contactscf');
        if ($hasScf) {
            $query->leftJoin('vtiger_contactscf as scf', 'c.contactid', '=', 'scf.contactid');
        }

        $query->where(function ($w) use ($hasScf) {
            $w->where('c.email', 'like', '%@orient-demo.%');
            if ($hasScf) {
                foreach (['cf_860', 'cf_856', 'cf_872'] as $col) {
                    if (Schema::connection('vtiger')->hasColumn('vtiger_contactscf', $col)) {
                        foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                            $w->orWhere('scf.'.$col, 'like', $prefix.'%');
                        }
                    }
                }
            }
        });

        return $query->pluck('contactid')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** @param  list<int>  $contactIds
     * @return list<int>
     */
    protected function findTicketIdsForContacts($vtiger, array $contactIds, bool $includeSoftDeleted = false): array
    {
        if ($contactIds === [] || ! Schema::connection('vtiger')->hasTable('vtiger_troubletickets')) {
            return [];
        }

        $query = $vtiger->table('vtiger_troubletickets as t')
            ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid')
            ->whereIn('t.contact_id', $contactIds);
        if (! $includeSoftDeleted) {
            $query->where('e.deleted', 0);
        }

        return $query
            ->pluck('t.ticketid')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return list<int> */
    protected function findPocDemoTicketIds($vtiger, bool $includeSoftDeleted = false): array
    {
        if (! Schema::connection('vtiger')->hasTable('vtiger_troubletickets')) {
            return [];
        }

        $query = $vtiger->table('vtiger_troubletickets as t')
            ->join('vtiger_crmentity as e', 't.ticketid', '=', 'e.crmid');
        if (! $includeSoftDeleted) {
            $query->where('e.deleted', 0);
        }

        $titles = OrientPocCatalog::demoTicketTitlePatterns();
        $query->where(function ($w) use ($titles) {
            $w->where('e.description', 'like', '%POC demo%');
            foreach ($titles as $title) {
                if ($title !== '') {
                    $w->orWhere('t.title', $title);
                }
            }
            foreach (OrientPocCatalog::demoPolicyPrefixes() as $prefix) {
                $w->orWhere('e.description', 'like', '%'.$prefix.'%');
            }
        });

        return $query->pluck('t.ticketid')->map(fn ($id) => (int) $id)->all();
    }

    /** @param  list<int>  $ticketIds */
    protected function hardDeleteTickets($vtiger, array $ticketIds): void
    {
        if ($ticketIds === []) {
            return;
        }

        foreach (['vtiger_ticketcomments', 'vtiger_ticketcf', 'vtiger_troubletickets'] as $table) {
            if (Schema::connection('vtiger')->hasTable($table)) {
                $vtiger->table($table)->whereIn('ticketid', $ticketIds)->delete();
            }
        }

        $this->hardDeleteCrmentity($vtiger, $ticketIds);
    }

    /** @param  list<int>  $contactIds */
    protected function hardDeleteContacts($vtiger, array $contactIds): void
    {
        if ($contactIds === []) {
            return;
        }

        $tables = [
            'vtiger_contactscf' => 'contactid',
            'vtiger_contactaddress' => 'contactaddressid',
            'vtiger_contactsubdetails' => 'contactsubscriptionid',
            'vtiger_customerdetails' => 'customerid',
            'vtiger_contactdetails' => 'contactid',
        ];

        foreach ($tables as $table => $column) {
            if (! Schema::connection('vtiger')->hasTable($table)) {
                continue;
            }
            if (! Schema::connection('vtiger')->hasColumn($table, $column)) {
                continue;
            }
            $vtiger->table($table)->whereIn($column, $contactIds)->delete();
        }

        $this->hardDeleteCrmentity($vtiger, $contactIds);
    }

    /** @param  list<int>  $leadIds */
    protected function hardDeleteLeads($vtiger, array $leadIds): void
    {
        if ($leadIds === []) {
            return;
        }

        $tables = [
            'vtiger_leadscf' => 'leadid',
            'vtiger_leadaddress' => 'leadaddressid',
            'vtiger_leadsubdetails' => 'leadsubscriptionid',
            'vtiger_leaddetails' => 'leadid',
        ];

        foreach ($tables as $table => $column) {
            if (! Schema::connection('vtiger')->hasTable($table)) {
                continue;
            }
            if (! Schema::connection('vtiger')->hasColumn($table, $column)) {
                continue;
            }
            $vtiger->table($table)->whereIn($column, $leadIds)->delete();
        }

        $this->hardDeleteCrmentity($vtiger, $leadIds);
    }

    /** @param  list<int>  $crmIds */
    protected function hardDeleteCrmentity($vtiger, array $crmIds): void
    {
        if ($crmIds === []) {
            return;
        }

        if (Schema::connection('vtiger')->hasTable('vtiger_crmentityrel')) {
            $vtiger->table('vtiger_crmentityrel')
                ->where(function ($w) use ($crmIds) {
                    $w->whereIn('crmid', $crmIds)->orWhereIn('relcrmid', $crmIds);
                })
                ->delete();
        }

        if (Schema::connection('vtiger')->hasTable('vtiger_seactivityrel')) {
            $vtiger->table('vtiger_seactivityrel')->whereIn('crmid', $crmIds)->delete();
        }

        $vtiger->table('vtiger_crmentity')->whereIn('crmid', $crmIds)->delete();
    }
}
