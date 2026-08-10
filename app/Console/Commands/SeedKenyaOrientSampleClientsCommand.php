<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\CrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Seed local clients (with policy numbers) for Orient POC ticket/client demos.
 */
class SeedKenyaOrientSampleClientsCommand extends Command
{
    protected $signature = 'kenya-orient:seed-sample-clients
                            {--force : Skip confirmation}
                            {--link-crm : Also create/link CRM contacts for ticket creation}';

    protected $description = 'Seed sample local clients with policies for POC (Clients + ticket picker)';

    /** @var list<array<string, mixed>> */
    protected array $clients = [
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
    ];

    public function handle(CrmService $crm): int
    {
        if (! Client::tableExists()) {
            $this->error('clients table missing. Run migrations first.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Seed sample clients with policies for POC?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $linkCrm = (bool) $this->option('link-crm');
        $created = 0;
        $updated = 0;
        $linked = 0;

        foreach ($this->clients as $row) {
            $existing = Client::where('policy_no', $row['policy_no'])->first();
            $payload = array_merge($row, [
                'source' => 'seed',
                'created_by_name' => $existing->created_by_name ?? 'Orient POC Seed',
            ]);

            if ($existing) {
                $existing->fill($payload);
                $existing->save();
                $client = $existing;
                $updated++;
            } else {
                $client = Client::create($payload);
                $created++;
            }

            if ($linkCrm) {
                $contact = $crm->findContactByPolicyNumber($client->policy_no);
                if (! $contact) {
                    $contactId = $crm->createContactFromErpClient([
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                        'email' => $client->email,
                        'phone' => $client->phone,
                        'mobile' => $client->phone,
                        'policy_number' => $client->policy_no,
                        'policy_no' => $client->policy_no,
                        'id_no' => $client->id_no,
                        'product' => $client->product,
                    ]);
                    if ($contactId) {
                        $linked++;
                    }
                }
            }
        }

        Cache::forget('agile_clients_count');
        Cache::forget('agile_dashboard_stats_all');
        // Ticket picker caches contact search briefly — flush common keys via tag-less forget prefix is hard;
        // short TTL (10–60s) means a refresh shortly after seed is enough.

        $this->info("Sample clients ready. created={$created} updated={$updated}".($linkCrm ? " crm_linked={$linked}" : ''));
        $this->line('Total local clients: '.Client::count());
        $this->line('Example policies: KOL-IND-10001, KOL-GRP-20001, KOL-MOR-30001, KOL-PEN-40001');
        if (! $linkCrm) {
            $this->comment('Tip: re-run with --link-crm so ticket create can pick these clients.');
        }

        return self::SUCCESS;
    }
}
