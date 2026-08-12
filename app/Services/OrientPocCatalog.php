<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Single source of truth for Kenya Orient POC demo records.
 * Policy numbers, names, contacts and products stay aligned across
 * clients, renewals, investment maturities and SLA report seeds.
 */
class OrientPocCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function sampleClients(): array
    {
        return [
            [
                'policy_no' => 'KOL-IND-10001',
                'first_name' => 'Amina',
                'last_name' => 'Hassan',
                'id_no' => '29876543',
                'kra_pin' => 'A012345678B',
                'date_of_birth' => '1988-03-12',
                'gender' => 'Female',
                'email' => 'amina.hassan@orient-demo.ke',
                'phone' => '0711111001',
                'address' => 'Ngong Road',
                'city' => 'Nairobi',
                'postal_code' => '00100',
                'occupation' => 'Teacher',
                'product' => 'Orient Endowment Plan',
                'intermediary' => 'Grace Njeri (AG-1001)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-IND-10002',
                'first_name' => 'Brian',
                'last_name' => 'Otieno',
                'id_no' => '28765432',
                'kra_pin' => 'A023456789C',
                'date_of_birth' => '1992-07-21',
                'gender' => 'Male',
                'email' => 'brian.otieno@orient-demo.ke',
                'phone' => '0711111002',
                'address' => 'Tom Mboya St',
                'city' => 'Kisumu',
                'postal_code' => '40100',
                'occupation' => 'Engineer',
                'product' => 'Orient Educator',
                'intermediary' => 'Direct / Head Office (DIR-0000)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-IND-10003',
                'first_name' => 'Carol',
                'last_name' => 'Wambui',
                'id_no' => '27654321',
                'kra_pin' => 'A034567890D',
                'date_of_birth' => '1985-11-05',
                'gender' => 'Female',
                'email' => 'carol.wambui@orient-demo.ke',
                'phone' => '0711111003',
                'address' => 'Moi Avenue',
                'city' => 'Mombasa',
                'postal_code' => '80100',
                'occupation' => 'Accountant',
                'product' => 'Orient 4 Life',
                'intermediary' => 'Grace Njeri (AG-1001)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-IND-10004',
                'first_name' => 'John',
                'last_name' => 'Kamau',
                'id_no' => '20987654',
                'kra_pin' => 'A101234567L',
                'date_of_birth' => '1991-02-28',
                'gender' => 'Male',
                'email' => 'john.kamau@orient-demo.ke',
                'phone' => '0711111004',
                'address' => 'Westlands',
                'city' => 'Nairobi',
                'postal_code' => '00600',
                'occupation' => 'Engineer',
                'product' => 'Orient Smart Asset',
                'intermediary' => 'Grace Njeri (AG-1001)',
                'system' => 'individual',
                'status' => 'FL',
            ],
            [
                'policy_no' => 'KOL-GRP-20001',
                'first_name' => 'David',
                'last_name' => 'Kiptoo',
                'id_no' => '26543210',
                'kra_pin' => 'A045678901E',
                'date_of_birth' => '1979-01-30',
                'gender' => 'Male',
                'email' => 'david.kiptoo@orient-demo.ke',
                'phone' => '0722222001',
                'address' => 'Kenyatta Ave',
                'city' => 'Nakuru',
                'postal_code' => '20100',
                'occupation' => 'Business Owner',
                'product' => 'Orient Group Life',
                'intermediary' => 'Agency East (AG-2200)',
                'system' => 'group',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-GRP-20002',
                'first_name' => 'Esther',
                'last_name' => 'Nyambura',
                'id_no' => '25432109',
                'kra_pin' => 'A056789012F',
                'date_of_birth' => '1990-09-18',
                'gender' => 'Female',
                'email' => 'esther.nyambura@orient-demo.ke',
                'phone' => '0722222002',
                'address' => 'Industrial Area',
                'city' => 'Nairobi',
                'postal_code' => '00500',
                'occupation' => 'Civil Servant',
                'product' => 'Group Last Expense',
                'intermediary' => 'Agency East (AG-2200)',
                'system' => 'group',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-MOR-30001',
                'first_name' => 'Francis',
                'last_name' => 'Mwangi',
                'id_no' => '24321098',
                'kra_pin' => 'A067890123G',
                'date_of_birth' => '1983-04-09',
                'gender' => 'Male',
                'email' => 'francis.mwangi@orient-demo.ke',
                'phone' => '0733333001',
                'address' => 'Thika Road',
                'city' => 'Thika',
                'postal_code' => '01000',
                'occupation' => 'Farmer',
                'product' => 'Orient Group Mortgage',
                'intermediary' => 'Mortgage Desk (AG-3300)',
                'system' => 'mortgage',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-MOR-30002',
                'first_name' => 'Grace',
                'last_name' => 'Achieng',
                'id_no' => '23210987',
                'kra_pin' => 'A078901234H',
                'date_of_birth' => '1995-12-01',
                'gender' => 'Female',
                'email' => 'grace.achieng@orient-demo.ke',
                'phone' => '0733333002',
                'address' => 'Kisii Town',
                'city' => 'Kisii',
                'postal_code' => '40200',
                'occupation' => 'Medical Practitioner',
                'product' => 'Orient Group Mortgage',
                'intermediary' => 'Mortgage Desk (AG-3300)',
                'system' => 'mortgage',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-PEN-40001',
                'first_name' => 'Henry',
                'last_name' => 'Mutua',
                'id_no' => '22109876',
                'kra_pin' => 'A089012345J',
                'date_of_birth' => '1975-06-22',
                'gender' => 'Male',
                'email' => 'henry.mutua@orient-demo.ke',
                'phone' => '0744444001',
                'address' => 'Upper Hill',
                'city' => 'Nairobi',
                'postal_code' => '00200',
                'occupation' => 'Accountant',
                'product' => 'Orient Personal Retirement Plan',
                'intermediary' => 'Pension Desk (AG-4400)',
                'system' => 'group_pension',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-PEN-40002',
                'first_name' => 'Irene',
                'last_name' => 'Chebet',
                'id_no' => '21098765',
                'kra_pin' => 'A090123456K',
                'date_of_birth' => '1987-08-14',
                'gender' => 'Female',
                'email' => 'irene.chebet@orient-demo.ke',
                'phone' => '0744444002',
                'address' => 'Eldoret CBD',
                'city' => 'Eldoret',
                'postal_code' => '30100',
                'occupation' => 'Teacher',
                'product' => 'Orient Umbrella Plan',
                'intermediary' => 'Pension Desk (AG-4400)',
                'system' => 'group_pension',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-INV-50001',
                'first_name' => 'Fatuma',
                'last_name' => 'Hassan',
                'id_no' => '20123456',
                'kra_pin' => 'A112345678M',
                'date_of_birth' => '1989-05-17',
                'gender' => 'Female',
                'email' => 'fatuma.hassan@orient-demo.ke',
                'phone' => '0755555001',
                'address' => 'Eastleigh',
                'city' => 'Nairobi',
                'postal_code' => '00100',
                'occupation' => 'Business Owner',
                'product' => 'Jipange Smart',
                'intermediary' => 'Brian Otieno (AG-1002)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-INV-50002',
                'first_name' => 'Samuel',
                'last_name' => 'Kiprono',
                'id_no' => '19987654',
                'kra_pin' => 'A123456789N',
                'date_of_birth' => '1984-10-03',
                'gender' => 'Male',
                'email' => 'samuel.kiprono@orient-demo.ke',
                'phone' => '0755555002',
                'address' => 'Kericho Town',
                'city' => 'Kericho',
                'postal_code' => '20200',
                'occupation' => 'Farmer',
                'product' => 'Orient Smart Educator',
                'intermediary' => 'Samuel Kiprono (AG-1004)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-INV-50003',
                'first_name' => 'Mercy',
                'last_name' => 'Njeri',
                'id_no' => '19876543',
                'kra_pin' => 'A134567890P',
                'date_of_birth' => '1993-01-22',
                'gender' => 'Female',
                'email' => 'mercy.njeri@orient-demo.ke',
                'phone' => '0755555003',
                'address' => 'Ruiru',
                'city' => 'Kiambu',
                'postal_code' => '00232',
                'occupation' => 'Teacher',
                'product' => 'Orient Endowment',
                'intermediary' => 'Mercy Wambui (AG-1005)',
                'system' => 'individual',
                'status' => 'A',
            ],
            [
                'policy_no' => 'KOL-ANN-60001',
                'first_name' => 'Peter',
                'last_name' => 'Ochieng',
                'id_no' => '19765432',
                'kra_pin' => 'A145678901Q',
                'date_of_birth' => '1968-11-30',
                'gender' => 'Male',
                'email' => 'peter.ochieng@orient-demo.ke',
                'phone' => '0766666001',
                'address' => 'Milimani',
                'city' => 'Kisumu',
                'postal_code' => '40100',
                'occupation' => 'Civil Servant',
                'product' => 'Orient Smart Annuity',
                'intermediary' => 'Direct / Head Office (DIR-0000)',
                'system' => 'individual',
                'status' => 'A',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function clientByPolicy(string $policyNo): ?array
    {
        foreach (self::sampleClients() as $client) {
            if (($client['policy_no'] ?? '') === $policyNo) {
                return $client;
            }
        }

        return null;
    }

    /**
     * Policies used on the investment maturities POC screen.
     *
     * @return list<string>
     */
    public static function investmentMaturityPolicyNumbers(): array
    {
        return [
            'KOL-IND-10001',
            'KOL-IND-10002',
            'KOL-IND-10004',
            'KOL-PEN-40001',
            'KOL-PEN-40002',
            'KOL-INV-50001',
            'KOL-INV-50002',
            'KOL-INV-50003',
            'KOL-ANN-60001',
            'KOL-IND-10003',
            'KOL-GRP-20001',
            'KOL-GRP-20002',
        ];
    }

    /**
     * @return list<int>
     */
    public static function maturityDayOffsets(int $windowDays, int $count = 12): array
    {
        $windowDays = max(1, $windowDays);
        $offsets = [-1, 0, 1, 2, 4, 6, 7, 9, 11, 13, 10, 5];
        if ($windowDays < 14) {
            $offsets = array_values(array_filter($offsets, fn ($d) => $d >= -1 && $d <= $windowDays));
        }
        while (count($offsets) < $count) {
            $offsets[] = min($windowDays, count($offsets));
        }

        return array_slice($offsets, 0, $count);
    }

    /**
     * @return Collection<int, object>
     */
    public static function investmentMaturityRows(int $days = 14, ?string $search = null): Collection
    {
        $days = max(1, min(30, $days));
        $today = Carbon::now()->startOfDay();
        $rows = collect();
        $policies = self::investmentMaturityPolicyNumbers();
        $offsets = self::maturityDayOffsets($days, count($policies));

        foreach ($policies as $i => $policyNo) {
            $client = self::clientByPolicy($policyNo);
            if (! $client) {
                continue;
            }
            $maturity = $today->copy()->addDays($offsets[$i] ?? $i);
            $fullName = trim(($client['first_name'] ?? '').' '.($client['last_name'] ?? ''));

            $rows->push((object) [
                'pol_policy_no' => $policyNo,
                'pol_maturity_date' => $maturity->format('Y-m-d'),
                'full_name' => $fullName,
                'product' => $client['product'] ?? '',
                'phone_no' => $client['phone'] ?? '',
                'email_adr' => $client['email'] ?? '',
                'client_email' => $client['email'] ?? '',
                'client_phone' => $client['phone'] ?? '',
                '_demo_investment_maturity' => true,
            ]);
        }

        $search = trim((string) $search);
        if ($search !== '') {
            $q = mb_strtolower($search);
            $rows = $rows->filter(function ($r) use ($q) {
                $hay = mb_strtolower(implode(' ', [
                    $r->pol_policy_no ?? '',
                    $r->full_name ?? '',
                    $r->product ?? '',
                    $r->client_phone ?? '',
                    $r->client_email ?? '',
                ]));

                return str_contains($hay, $q);
            })->values();
        }

        return $rows->sortBy([
            ['pol_maturity_date', 'asc'],
            ['pol_policy_no', 'asc'],
        ])->values();
    }

    /**
     * Renewal filter keys → client system values in sample data.
     *
     * @return list<array<string, mixed>>
     */
    public static function clientsForRenewalFilter(string $filter): array
    {
        $filter = strtolower(trim($filter));
        $map = [
            'individual' => ['individual'],
            'group' => ['group'],
            'pension' => ['group_pension'],
            'annuities' => ['individual'],
        ];
        $systems = $map[$filter] ?? ['individual'];
        $clients = array_values(array_filter(self::sampleClients(), function ($c) use ($filter, $systems) {
            if (! in_array($c['system'] ?? '', $systems, true)) {
                return false;
            }
            if ($filter === 'annuities') {
                return str_contains(strtolower((string) ($c['product'] ?? '')), 'annuity');
            }
            if ($filter === 'individual') {
                return ! str_contains(strtolower((string) ($c['product'] ?? '')), 'annuity');
            }

            return true;
        }));

        return $clients !== [] ? $clients : self::sampleClients();
    }

    /**
     * Broken-SLA client ticket seeds (policies must exist in sampleClients).
     *
     * @return list<array{title:string,category:string,priority:string,status:string,hours_ago:int,policy:string}>
     */
    public static function brokenSlaClientTickets(): array
    {
        return [
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
            [
                'title' => 'Investment maturity discharge voucher — KOL-IND-10001',
                'category' => 'General',
                'priority' => 'High',
                'status' => 'Open',
                'hours_ago' => 55,
                'policy' => 'KOL-IND-10001',
            ],
            [
                'title' => 'Maturity payout options query — KOL-INV-50001',
                'category' => 'General',
                'priority' => 'Normal',
                'status' => 'In Progress',
                'hours_ago' => 80,
                'policy' => 'KOL-INV-50001',
            ],
            [
                'title' => 'Endowment maturity reminder not sent — KOL-INV-50003',
                'category' => 'Support',
                'priority' => 'Normal',
                'status' => 'Open',
                'hours_ago' => 52,
                'policy' => 'KOL-INV-50003',
            ],
        ];
    }
}
