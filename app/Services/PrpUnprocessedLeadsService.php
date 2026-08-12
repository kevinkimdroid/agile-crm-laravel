<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Active LMS policies (proposers / PRP) with unprocessed receipts — surfaced as leads.
 */
class PrpUnprocessedLeadsService
{
    public function isEnabled(): bool
    {
        return config('erp.enabled', true) && config('erp.prp_leads_enabled', true);
    }

    public function hasCacheTable(): bool
    {
        return Schema::hasTable('prp_unprocessed_leads_cache');
    }

    public function getCount(?string $search = null): int
    {
        if (! $this->isEnabled() || ! $this->hasCacheTable()) {
            return 0;
        }

        try {
            return $this->baseQuery($search)->count();
        } catch (\Throwable $e) {
            Log::warning('PrpUnprocessedLeadsService::getCount: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * @return Collection<int, object>
     */
    public function get(int $limit, int $offset, ?string $search = null): Collection
    {
        if (! $this->isEnabled() || ! $this->hasCacheTable()) {
            return collect();
        }

        try {
            return $this->baseQuery($search)
                ->orderByDesc('unprocessed_rct')
                ->orderBy('policy_number')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(fn ($row) => $this->mapRowToLeadObject((array) $row));
        } catch (\Throwable $e) {
            Log::warning('PrpUnprocessedLeadsService::get: ' . $e->getMessage());

            return collect();
        }
    }

    /**
     * Combined pagination: PRP rows first, then CRM leads (see LeadController).
     *
     * @return array{items: Collection, prp_count: int, crm_count: int, total: int}
     */
    public function paginateCombined(
        int $perPage,
        int $page,
        ?string $search,
        callable $fetchCrmLeads,
        callable $fetchCrmCount
    ): array {
        $prpCount = $this->getCount($search);
        $crmCount = (int) $fetchCrmCount($search);
        $total = $prpCount + $crmCount;
        $offset = max(0, ($page - 1) * $perPage);

        $items = collect();

        if ($offset < $prpCount) {
            $prpTake = min($perPage, $prpCount - $offset);
            $items = $items->merge($this->get($prpTake, $offset, $search));
            $crmTake = $perPage - $items->count();
            if ($crmTake > 0) {
                $items = $items->merge($fetchCrmLeads($crmTake, 0, $search));
            }
        } else {
            $crmOffset = $offset - $prpCount;
            $items = $items->merge($fetchCrmLeads($perPage, $crmOffset, $search));
        }

        return [
            'items' => $items,
            'prp_count' => $prpCount,
            'crm_count' => $crmCount,
            'total' => $total,
        ];
    }

    public function makePaginator(Collection $items, int $total, int $perPage, int $page, string $path, array $query = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $path, 'query' => $query]
        );
    }

    /**
     * @return object|null
     */
    public function findByPolicyNumber(string $policyNumber): ?object
    {
        if (! $this->isEnabled() || ! $this->hasCacheTable()) {
            return null;
        }

        $policyNumber = trim($policyNumber);
        if ($policyNumber === '') {
            return null;
        }

        try {
            $row = DB::table('prp_unprocessed_leads_cache')
                ->where('policy_number', $policyNumber)
                ->first();

            return $row ? $this->mapRowToLeadObject((array) $row) : null;
        } catch (\Throwable $e) {
            Log::warning('PrpUnprocessedLeadsService::findByPolicyNumber: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Fetch from ERP HTTP API (erp-clients-api /clients/prp-unprocessed-leads).
     *
     * @return list<array<string, mixed>>
     */
    public function fetchFromHttpApi(int $limit = 500, int $offset = 0): array
    {
        $url = config('erp.prp_leads_http_url');
        if ($url === '') {
            return [];
        }

        try {
            $response = Http::withOptions(['connect_timeout' => 3])
                ->timeout(60)
                ->get($url, ['limit' => min($limit, 500), 'offset' => $offset]);

            if (! $response->successful()) {
                Log::warning('PRP leads HTTP fetch failed', ['status' => $response->status()]);

                return [];
            }

            $body = $response->json();
            $rows = $body['data'] ?? [];

            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            Log::warning('PRP leads HTTP fetch error: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Fetch directly from Oracle using the LMS PRP / unprocessed receipts query.
     *
     * @return list<array<string, mixed>>
     */
    public function fetchFromOracle(): array
    {
        if (! config('erp.enabled', true)) {
            return [];
        }

        $schema = trim((string) config('database.connections.erp.schema', config('erp.view_schema', 'TQ_LMS')));
        $qualify = static fn (string $table) => $schema !== '' ? "{$schema}.{$table}" : $table;
        $fn = $schema !== '' ? "{$schema}.lms_process_receipts.unprocessed_receipts" : 'lms_process_receipts.unprocessed_receipts';

        $pol = $qualify('LMS_POLICIES');
        $prod = $qualify('LMS_PRODUCTS');
        $prp = $qualify('LMS_PROPOSERS');

        $inner = "SELECT POL_CODE,
                         TO_CHAR(POL_POLICY_NO) AS POL_POLICY_NO,
                         {$fn}(POL_CODE) AS UNPROCESSED_RCT,
                         TO_CHAR(POL_PAID_TO_DATE, 'YYYY-MM-DD') AS PAID_TO,
                         TRIM(PRP_FIRST_NAME || '   ' || PRP_SURNAME || '   ' || PRP_OTHER_NAMES) AS CLIENTS_NAMES,
                         PRP_TEL
                    FROM {$pol},
                         {$prod},
                         {$prp}
                   WHERE POL_STATUS = 'A'
                     AND POL_PROD_CODE = PROD_CODE
                     AND PROD_TYPE NOT IN ('IN')
                     AND PRP_CODE = POL_PRP_CODE
                GROUP BY POL_CODE,
                         POL_POLICY_NO,
                         POL_STATUS,
                         PROD_TYPE,
                         POL_PROD_CODE,
                         PROD_CODE,
                         POL_PAID_TO_DATE,
                         PRP_FIRST_NAME,
                         PRP_SURNAME,
                         PRP_OTHER_NAMES,
                         PRP_TEL
                  HAVING {$fn}(POL_CODE) > 0
                  ORDER BY UNPROCESSED_RCT DESC, POL_POLICY_NO ASC";

        $pageSize = (int) config('erp.prp_leads_oracle_page_size', 25);
        $pageSql = "SELECT * FROM (SELECT a.*, ROWNUM rn FROM ({$inner}) a WHERE ROWNUM <= ?) WHERE rn > ?";

        $rows = [];
        $lower = 0;
        $maxRows = 50000;

        while ($lower < $maxRows) {
            $upper = $lower + $pageSize;
            $page = null;

            for ($try = 1; $try <= 4; $try++) {
                try {
                    $page = DB::connection('erp')->select($pageSql, [$upper, $lower]);
                    break;
                } catch (\Throwable $e) {
                    DB::purge('erp');
                    if ($try === 4) {
                        Log::warning('PRP leads Oracle page failed', [
                            'offset' => $lower,
                            'message' => $e->getMessage(),
                        ]);
                    }
                    usleep(300000);
                }
            }

            if ($page === null || $page === []) {
                break;
            }

            foreach ($page as $r) {
                $rows[] = [
                    'pol_code' => $r->POL_CODE ?? $r->pol_code ?? null,
                    'policy_number' => trim((string) ($r->POL_POLICY_NO ?? $r->pol_policy_no ?? '')),
                    'unprocessed_rct' => (int) ($r->UNPROCESSED_RCT ?? $r->unprocessed_rct ?? 0),
                    'paid_to' => $this->parseDate($r->PAID_TO ?? $r->paid_to ?? null),
                    'client_name' => trim((string) ($r->CLIENTS_NAMES ?? $r->clients_names ?? '')),
                    'prp_tel' => trim((string) ($r->PRP_TEL ?? $r->prp_tel ?? '')),
                ];
            }

            if (count($page) < $pageSize) {
                break;
            }
            $lower = $upper;
        }

        return array_values(array_filter($rows, fn ($r) => ! empty($r['policy_number'])));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function upsertCacheRows(array $rows): int
    {
        if (! $this->hasCacheTable() || $rows === []) {
            return 0;
        }

        $syncedAt = now();
        $written = 0;

        foreach (array_chunk($rows, 100) as $chunk) {
            foreach ($chunk as $row) {
                $polCode = (int) ($row['pol_code'] ?? 0);
                $policy = trim((string) ($row['policy_number'] ?? ''));
                if ($polCode <= 0 || $policy === '') {
                    continue;
                }

                DB::table('prp_unprocessed_leads_cache')->updateOrInsert(
                    ['pol_code' => $polCode],
                    [
                        'policy_number' => $policy,
                        'unprocessed_rct' => (int) ($row['unprocessed_rct'] ?? 0),
                        'paid_to' => $row['paid_to'] ?? null,
                        'client_name' => $row['client_name'] ?? null,
                        'prp_tel' => $row['prp_tel'] ?? null,
                        'synced_at' => $syncedAt,
                        'updated_at' => $syncedAt,
                        'created_at' => $syncedAt,
                    ]
                );
                $written++;
            }
        }

        return $written;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function mapRowToLeadObject(array $row): object
    {
        $fullName = trim((string) ($row['client_name'] ?? ''));
        $parts = preg_split('/\s+/', $fullName, 2) ?: [];
        $policy = trim((string) ($row['policy_number'] ?? ''));

        return (object) [
            'leadid' => 'prp:' . ($row['pol_code'] ?? $policy),
            'pol_code' => (int) ($row['pol_code'] ?? 0),
            'policy_number' => $policy,
            'firstname' => $parts[0] ?? $fullName,
            'lastname' => $parts[1] ?? '',
            'company' => $policy,
            'email' => null,
            'phone' => $row['prp_tel'] ?? null,
            'mobile' => $row['prp_tel'] ?? null,
            'leadstatus' => 'Unprocessed Premium',
            'leadsource' => 'PRP — Unprocessed Receipts',
            'unprocessed_rct' => (int) ($row['unprocessed_rct'] ?? 0),
            'paid_to' => $row['paid_to'] ?? null,
            '_prp_source' => true,
            'full_name' => $fullName ?: $policy,
        ];
    }

    protected function baseQuery(?string $search)
    {
        $query = DB::table('prp_unprocessed_leads_cache');

        $term = trim((string) ($search ?? ''));
        if ($term !== '') {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('policy_number', 'like', $like)
                    ->orWhere('client_name', 'like', $like)
                    ->orWhere('prp_tel', 'like', $like);
            });
        }

        return $query;
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
