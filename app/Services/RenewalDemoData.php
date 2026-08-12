<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Deterministic demo renewals for the Renewals POC.
 * Uses the same KOL policy catalogue as sample clients and investment maturities.
 *
 * @see OrientPocCatalog
 */
class RenewalDemoData
{
    /** Map renewal filter keys → OrientPocCatalog renewal groups. */
    public const FILTER_TO_CLIENT_GROUP = [
        'individual' => 'Individual',
        'group' => 'Group',
        'pension' => 'Pension',
        'annuities' => 'Annuities',
    ];

    /**
     * @return Collection<int, object>
     */
    public function forProduct(string $product, int $windowDays = 30, ?string $search = null): Collection
    {
        $product = strtolower($product);
        if (! array_key_exists($product, self::FILTER_TO_CLIENT_GROUP)) {
            $product = 'individual';
        }

        $clients = OrientPocCatalog::clientsForRenewalFilter($product);
        $windowDays = max(1, $windowDays);
        $today = Carbon::now()->startOfDay();
        $rows = collect();

        foreach ($clients as $i => $client) {
            $offsetDays = (int) round(($i / max(1, count($clients) - 1)) * max(1, $windowDays - 1));
            $renewal = $today->copy()->addDays($offsetDays);
            $policy = (string) ($client['policy_no'] ?? '');
            $fullName = trim(($client['first_name'] ?? '').' '.($client['last_name'] ?? ''));
            $lifeSystem = match ($product) {
                'group' => 'group',
                'pension' => 'group_pension',
                default => (string) ($client['system'] ?? 'individual'),
            };

            $rows->push((object) [
                'policy_no' => $policy,
                'policy_number' => $policy,
                'life_assur' => $fullName,
                'client_name' => $fullName,
                'product' => $client['product'] ?? '',
                'status' => ($client['status'] ?? 'A') === 'FL' ? 'FL' : 'A',
                'mendr_renewal_date' => $renewal->format('Y-m-d'),
                'maturity' => $renewal->format('Y-m-d'),
                'client_email' => $client['email'] ?? '',
                'client_phone' => $client['phone'] ?? '',
                'phone_no' => $client['phone'] ?? '',
                'email' => $client['email'] ?? '',
                'intermediary' => $client['intermediary'] ?? '',
                'id_no' => $client['id_no'] ?? '',
                'pol_prepared_by' => 'Orient POC Seed',
                'life_system' => $lifeSystem,
                '_demo_renewal' => true,
            ]);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $q = mb_strtolower($search);
            $rows = $rows->filter(function ($r) use ($q) {
                $hay = mb_strtolower(implode(' ', [
                    $r->policy_no ?? '',
                    $r->life_assur ?? '',
                    $r->product ?? '',
                    $r->client_phone ?? '',
                    $r->client_email ?? '',
                ]));

                return str_contains($hay, $q);
            })->values();
        }

        return $rows->sortBy('mendr_renewal_date')->values();
    }

    /**
     * @return array{total: int, today: int, this_week: int, pending_notify: int}
     */
    public function statsFor(Collection $rows): array
    {
        $today = Carbon::now()->startOfDay();
        $weekEnd = $today->copy()->addDays(7);

        return [
            'total' => $rows->count(),
            'today' => $rows->filter(function ($r) use ($today) {
                try {
                    return Carbon::parse($r->mendr_renewal_date)->isSameDay($today);
                } catch (\Throwable $e) {
                    return false;
                }
            })->count(),
            'this_week' => $rows->filter(function ($r) use ($today, $weekEnd) {
                try {
                    $d = Carbon::parse($r->mendr_renewal_date)->startOfDay();

                    return $d->betweenIncluded($today, $weekEnd);
                } catch (\Throwable $e) {
                    return false;
                }
            })->count(),
            'pending_notify' => $rows->count(),
        ];
    }
}
