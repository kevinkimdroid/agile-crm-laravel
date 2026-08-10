<?php

namespace App\Services;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Deterministic demo renewals for the Renewals POC.
 * Product names come from the same Orient catalogue used on Create Client.
 *
 * @see \App\Models\Client::PRODUCTS
 */
class RenewalDemoData
{
    /** Map renewal filter keys → Client::PRODUCTS group labels. */
    public const FILTER_TO_CLIENT_GROUP = [
        'individual' => 'Individual',
        'group' => 'Group',
        'pension' => 'Pension',
        'annuities' => 'Annuities',
    ];

    /** @var list<array{0: string, 1: string}> */
    protected const NAMES = [
        ['Grace', 'Njeri'],
        ['Brian', 'Otieno'],
        ['Fatuma', 'Hassan'],
        ['Samuel', 'Kiprono'],
        ['Mercy', 'Wambui'],
        ['Peter', 'Mwangi'],
        ['Aisha', 'Abdi'],
        ['James', 'Kamau'],
        ['Lucy', 'Achieng'],
        ['Daniel', 'Mutua'],
    ];

    /** @var list<string> */
    protected const AGENTS = [
        'Grace Njeri (AG-1001)',
        'Brian Otieno (AG-1002)',
        'Fatuma Hassan (AG-1003)',
        'Samuel Kiprono (AG-1004)',
        'Direct / Head Office (DIR-0000)',
        'ABC Insurance Brokers Ltd (BR-2001)',
        'Metro Bancassurance (BA-3001)',
        'Mercy Wambui (AG-1005)',
        'Peter Mwangi (AG-1006)',
        'Aisha Abdi (AG-1007)',
    ];

    /**
     * Products for a renewal filter — same list as Clients capture.
     *
     * @return list<string>
     */
    public function productsForFilter(string $filter): array
    {
        $group = self::FILTER_TO_CLIENT_GROUP[$filter] ?? 'Individual';
        $list = Client::PRODUCTS[$group] ?? [];

        return array_values($list);
    }

    /**
     * @return Collection<int, object>
     */
    public function forProduct(string $product, int $windowDays = 30, ?string $search = null): Collection
    {
        $product = strtolower($product);
        if (! array_key_exists($product, self::FILTER_TO_CLIENT_GROUP)) {
            $product = 'individual';
        }

        $catalogue = $this->productsForFilter($product);
        if ($catalogue === []) {
            $catalogue = Client::productNames();
        }

        $prefix = match ($product) {
            'individual' => 'ORI-IND',
            'group' => 'ORI-GRP',
            'pension' => 'ORI-PEN',
            'annuities' => 'ORI-ANN',
            default => 'ORI-REN',
        };

        $lifeSystem = match ($product) {
            'group' => 'group',
            'pension' => 'group_pension',
            default => 'individual',
        };

        $rows = collect();
        for ($i = 0; $i < 10; $i++) {
            [$first, $last] = self::NAMES[$i];
            $offsetDays = (int) round(($i / 9) * max(1, $windowDays - 1));
            $renewal = Carbon::now()->startOfDay()->addDays($offsetDays);
            $policy = sprintf('%s-%04d', $prefix, 1001 + $i);
            $phone = '07'.str_pad((string) (12000100 + $i), 8, '0', STR_PAD_LEFT);
            $email = strtolower($first.'.'.$last).'@example.co.ke';
            $productName = $catalogue[$i % count($catalogue)];

            $rows->push((object) [
                'policy_no' => $policy,
                'policy_number' => $policy,
                'life_assur' => $first.' '.$last,
                'client_name' => $first.' '.$last,
                'product' => $productName,
                'status' => $i === 8 ? 'FL' : 'A',
                'mendr_renewal_date' => $renewal->format('Y-m-d'),
                'maturity' => $renewal->format('Y-m-d'),
                'client_email' => $email,
                'client_phone' => $phone,
                'phone_no' => $phone,
                'email' => $email,
                'intermediary' => self::AGENTS[$i],
                'id_no' => (string) (30000000 + $i * 111),
                'pol_prepared_by' => 'Demo User',
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
