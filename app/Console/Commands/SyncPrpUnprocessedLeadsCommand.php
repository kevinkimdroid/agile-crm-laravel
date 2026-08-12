<?php

namespace App\Console\Commands;

use App\Services\PrpUnprocessedLeadsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncPrpUnprocessedLeadsCommand extends Command
{
    protected $signature = 'prp-leads:sync
                            {--oracle : Fetch directly from Oracle (skip HTTP API)}
                            {--replace : Remove cache rows not returned in this sync}';

    protected $description = 'Sync active PRP policies with unprocessed receipts from LMS into prp_unprocessed_leads_cache';

    public function handle(PrpUnprocessedLeadsService $service): int
    {
        if (! config('erp.prp_leads_enabled', true)) {
            $this->error('PRP leads are disabled (PRP_LEADS_ENABLED=false).');

            return self::FAILURE;
        }

        if (! Schema::hasTable('prp_unprocessed_leads_cache')) {
            $this->error('prp_unprocessed_leads_cache table does not exist. Run: php artisan migrate');

            return self::FAILURE;
        }

        $rows = [];

        if (! $this->option('oracle') && config('erp.prp_leads_http_url')) {
            $this->info('Fetching PRP unprocessed leads from ERP HTTP API...');
            $offset = 0;
            while (true) {
                $batch = $service->fetchFromHttpApi(500, $offset);
                if ($batch === []) {
                    break;
                }
                $rows = array_merge($rows, $this->normalizeRows($batch));
                if (count($batch) < 500) {
                    break;
                }
                $offset += 500;
            }
        }

        if ($rows === []) {
            $this->info('Fetching PRP unprocessed leads from Oracle...');
            $rows = $service->fetchFromOracle();
        }

        if ($rows === []) {
            $this->warn('No PRP policies with unprocessed receipts found.');
            if ($this->option('replace')) {
                DB::table('prp_unprocessed_leads_cache')->truncate();
            }

            return self::SUCCESS;
        }

        $polCodes = array_values(array_unique(array_filter(array_map(
            static fn ($r) => (int) ($r['pol_code'] ?? 0),
            $rows
        ))));

        if ($this->option('replace')) {
            DB::table('prp_unprocessed_leads_cache')
                ->whereNotIn('pol_code', $polCodes)
                ->delete();
        }

        $written = $service->upsertCacheRows($rows);
        $total = DB::table('prp_unprocessed_leads_cache')->count();

        $this->components->info("Sync complete. Upserted {$written} rows (cache total: {$total}).");

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     * @return list<array<string, mixed>>
     */
    protected function normalizeRows(array $batch): array
    {
        $out = [];
        foreach ($batch as $row) {
            $policy = trim((string) ($row['policy_number'] ?? $row['pol_policy_no'] ?? ''));
            if ($policy === '') {
                continue;
            }
            $out[] = [
                'pol_code' => (int) ($row['pol_code'] ?? 0),
                'policy_number' => $policy,
                'unprocessed_rct' => (int) ($row['unprocessed_rct'] ?? $row['unprocessed_rct_count'] ?? 0),
                'paid_to' => isset($row['paid_to']) ? substr((string) $row['paid_to'], 0, 10) : null,
                'client_name' => trim((string) ($row['client_name'] ?? $row['clients_names'] ?? '')),
                'prp_tel' => trim((string) ($row['prp_tel'] ?? $row['PRP_TEL'] ?? '')),
            ];
        }

        return $out;
    }
}
