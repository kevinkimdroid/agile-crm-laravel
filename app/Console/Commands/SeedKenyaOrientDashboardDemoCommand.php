<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed lightweight Orient POC prospects / leads / deals so dashboard KPIs are non-zero
 * after a CRM hard-purge.
 */
class SeedKenyaOrientDashboardDemoCommand extends Command
{
    protected $signature = 'kenya-orient:seed-dashboard-demo
                            {--force : Skip confirmation}
                            {--owner= : Vtiger user id (default: ewanguba)}';

    protected $description = 'Seed Orient POC prospects, leads and deals for dashboard KPIs';

    /** @var list<array{first:string,last:string,email:string,phone:string}> */
    protected array $prospects = [
        ['first' => 'Grace', 'last' => 'Wanjiku', 'email' => 'grace.wanjiku@orient-demo.ke', 'phone' => '0712001001'],
        ['first' => 'Daniel', 'last' => 'Ochieng', 'email' => 'daniel.ochieng@orient-demo.ke', 'phone' => '0712001002'],
        ['first' => 'Faith', 'last' => 'Mwangi', 'email' => 'faith.mwangi@orient-demo.ke', 'phone' => '0712001003'],
        ['first' => 'Peter', 'last' => 'Kamau', 'email' => 'peter.kamau@orient-demo.ke', 'phone' => '0712001004'],
        ['first' => 'Mary', 'last' => 'Achieng', 'email' => 'mary.achieng@orient-demo.ke', 'phone' => '0712001005'],
        ['first' => 'James', 'last' => 'Kiptoo', 'email' => 'james.kiptoo@orient-demo.ke', 'phone' => '0712001006'],
        ['first' => 'Ann', 'last' => 'Njeri', 'email' => 'ann.njeri@orient-demo.ke', 'phone' => '0712001007'],
        ['first' => 'Samuel', 'last' => 'Otieno', 'email' => 'samuel.otieno@orient-demo.ke', 'phone' => '0712001008'],
        ['first' => 'Lucy', 'last' => 'Chebet', 'email' => 'lucy.chebet@orient-demo.ke', 'phone' => '0712001009'],
        ['first' => 'Henry', 'last' => 'Mutua', 'email' => 'henry.mutua@orient-demo.ke', 'phone' => '0712001010'],
        ['first' => 'Irene', 'last' => 'Wambui', 'email' => 'irene.wambui@orient-demo.ke', 'phone' => '0712001011'],
        ['first' => 'Joseph', 'last' => 'Barasa', 'email' => 'joseph.barasa@orient-demo.ke', 'phone' => '0712001012'],
    ];

    /** @var list<array{first:string,last:string,company:string,source:string,email:string,phone:string}> */
    protected array $leads = [
        ['first' => 'Kevin', 'last' => 'Okello', 'company' => 'Okello Traders', 'source' => 'Web', 'email' => 'kevin.okello@orient-demo.ke', 'phone' => '0722002001'],
        ['first' => 'Nancy', 'last' => 'Auma', 'company' => 'Auma Holdings', 'source' => 'Referral', 'email' => 'nancy.auma@orient-demo.ke', 'phone' => '0722002002'],
        ['first' => 'Brian', 'last' => 'Koech', 'company' => 'Koech Logistics', 'source' => 'Agent', 'email' => 'brian.koech@orient-demo.ke', 'phone' => '0722002003'],
        ['first' => 'Esther', 'last' => 'Nyambura', 'company' => 'Nyambura Clinics', 'source' => 'Campaign', 'email' => 'esther.nyambura@orient-demo.ke', 'phone' => '0722002004'],
        ['first' => 'Victor', 'last' => 'Maina', 'company' => 'Maina Motors', 'source' => 'Web', 'email' => 'victor.maina@orient-demo.ke', 'phone' => '0722002005'],
        ['first' => 'Catherine', 'last' => 'Wekesa', 'company' => 'Wekesa Estates', 'source' => 'Referral', 'email' => 'catherine.wekesa@orient-demo.ke', 'phone' => '0722002006'],
        ['first' => 'George', 'last' => 'Odhiambo', 'company' => 'Odhiambo SACCO', 'source' => 'Agent', 'email' => 'george.odhiambo@orient-demo.ke', 'phone' => '0722002007'],
        ['first' => 'Patricia', 'last' => 'Cherono', 'company' => 'Cherono Schools', 'source' => 'Campaign', 'email' => 'patricia.cherono@orient-demo.ke', 'phone' => '0722002008'],
    ];

    /** @var list<array{name:string,amount:int,stage:string,days:int}> */
    protected array $deals = [
        ['name' => 'Orient Endowment — Wanjiku Family', 'amount' => 1250000, 'stage' => 'Qualification', 'days' => 21],
        ['name' => 'Orient Group Life — Okello Traders', 'amount' => 4800000, 'stage' => 'Proposal/Price Quote', 'days' => 35],
        ['name' => 'Orient Educator — Achieng', 'amount' => 850000, 'stage' => 'Needs Analysis', 'days' => 14],
        ['name' => 'Orient Medical Family — Maina Motors', 'amount' => 2100000, 'stage' => 'Negotiation/Review', 'days' => 28],
        ['name' => 'Orient Group Mortgage — Wekesa Estates', 'amount' => 7500000, 'stage' => 'Proposal/Price Quote', 'days' => 45],
        ['name' => 'Orient 4 Life — Kiptoo', 'amount' => 960000, 'stage' => 'Prospecting', 'days' => 10],
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Seed Orient POC prospects, leads and deals?', true)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $conn = DB::connection('vtiger');
        $ownerId = (int) ($this->option('owner') ?: 0);
        if ($ownerId <= 0) {
            $ownerId = (int) ($conn->table('vtiger_users')->where('user_name', 'ewanguba')->value('id')
                ?: $conn->table('vtiger_users')->where('status', 'Active')->orderBy('id')->value('id')
                ?: 1);
        }

        $this->line("Owner user id: {$ownerId}");

        $contactsBefore = $conn->table('vtiger_contactdetails')->count();
        $leadsBefore = $conn->table('vtiger_leaddetails')->count();
        $dealsBefore = $conn->table('vtiger_potential')->count();

        if ($contactsBefore > 0 || $leadsBefore > 0 || $dealsBefore > 0) {
            $this->warn("Existing data: contacts={$contactsBefore} leads={$leadsBefore} deals={$dealsBefore}");
            if (! $this->option('force') && ! $this->confirm('Continue and add more demo rows?', false)) {
                return self::SUCCESS;
            }
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=0');
        $conn->transaction(function () use ($conn, $ownerId) {
            foreach ($this->prospects as $p) {
                $this->insertContact($conn, $ownerId, $p);
            }
            foreach ($this->leads as $l) {
                $this->insertLead($conn, $ownerId, $l);
            }
            foreach ($this->deals as $d) {
                $this->insertDeal($conn, $ownerId, $d);
            }
            $max = (int) $conn->table('vtiger_crmentity')->max('crmid');
            if (Schema::connection('vtiger')->hasTable('vtiger_crmentity_seq')) {
                $conn->table('vtiger_crmentity_seq')->delete();
                $conn->table('vtiger_crmentity_seq')->insert(['id' => max(1, $max)]);
            }
        });
        $conn->statement('SET FOREIGN_KEY_CHECKS=1');

        foreach ([
            'agile_dashboard_stats_all',
            'agile_contacts_count',
            'agile_leads_count',
            'agile_deals_count',
            'agile_clients_count',
            'agile_pipeline_value',
            'agile_dashboard_stats',
        ] as $key) {
            Cache::forget($key);
        }
        Cache::forget('agile_dashboard_stats_' . $ownerId);
        Cache::forget('agile_pipeline_value_' . $ownerId);

        $pipeline = (float) $conn->table('vtiger_potential')
            ->join('vtiger_crmentity as e', 'vtiger_potential.potentialid', '=', 'e.crmid')
            ->where('e.deleted', 0)
            ->sum('vtiger_potential.amount');

        $this->info('Seeded dashboard demo data.');
        $this->line('Prospects: ' . $conn->table('vtiger_contactdetails')->count());
        $this->line('Leads: ' . $conn->table('vtiger_leaddetails')->count());
        $this->line('Deals: ' . $conn->table('vtiger_potential')->count());
        $this->line('Pipeline: KES ' . number_format($pipeline, 0));
        $this->line('Local clients: ' . (Schema::connection('vtiger')->hasTable('clients') ? $conn->table('clients')->count() : 0));

        return self::SUCCESS;
    }

    protected function nextCrmId($conn): int
    {
        return (int) $conn->table('vtiger_crmentity')->max('crmid') + 1;
    }

    protected function insertCrmEntity($conn, int $id, int $ownerId, string $setype, string $label, string $now): void
    {
        $conn->table('vtiger_crmentity')->insert([
            'crmid' => $id,
            'smcreatorid' => $ownerId,
            'smownerid' => $ownerId,
            'modifiedby' => $ownerId,
            'setype' => $setype,
            'description' => '',
            'createdtime' => $now,
            'modifiedtime' => $now,
            'viewedtime' => null,
            'status' => '',
            'version' => 0,
            'presence' => 1,
            'deleted' => 0,
            'smgroupid' => 0,
            'source' => 'CRM',
            'label' => $label,
        ]);
    }

    protected function insertContact($conn, int $ownerId, array $p): void
    {
        $id = $this->nextCrmId($conn);
        $now = now()->format('Y-m-d H:i:s');
        $label = trim($p['first'] . ' ' . $p['last']);
        $this->insertCrmEntity($conn, $id, $ownerId, 'Contacts', $label, $now);

        $row = [
            'contactid' => $id,
            'contact_no' => 'CON' . $id,
            'firstname' => $p['first'],
            'lastname' => $p['last'],
            'email' => $p['email'],
            'phone' => $p['phone'],
            'mobile' => $p['phone'],
        ];
        $cols = Schema::connection('vtiger')->getColumnListing('vtiger_contactdetails');
        $conn->table('vtiger_contactdetails')->insert(array_intersect_key($row, array_flip($cols)));

        if (Schema::connection('vtiger')->hasTable('vtiger_contactscf')) {
            $scf = ['contactid' => $id];
            $scfCols = Schema::connection('vtiger')->getColumnListing('vtiger_contactscf');
            $conn->table('vtiger_contactscf')->insert(array_intersect_key($scf, array_flip($scfCols)));
        }
    }

    protected function insertLead($conn, int $ownerId, array $l): void
    {
        $id = $this->nextCrmId($conn);
        $now = now()->format('Y-m-d H:i:s');
        $label = trim($l['first'] . ' ' . $l['last']);
        $this->insertCrmEntity($conn, $id, $ownerId, 'Leads', $label, $now);

        $row = [
            'leadid' => $id,
            'lead_no' => 'LD' . $id,
            'firstname' => $l['first'],
            'lastname' => $l['last'],
            'company' => $l['company'],
            'email' => $l['email'],
            'leadsource' => $l['source'],
            'leadstatus' => 'Hot',
            'converted' => 0,
        ];
        $cols = Schema::connection('vtiger')->getColumnListing('vtiger_leaddetails');
        $conn->table('vtiger_leaddetails')->insert(array_intersect_key($row, array_flip($cols)));

        if (Schema::connection('vtiger')->hasTable('vtiger_leadaddress')) {
            $addr = [
                'leadaddressid' => $id,
                'mobile' => $l['phone'],
                'phone' => $l['phone'],
            ];
            $addrCols = Schema::connection('vtiger')->getColumnListing('vtiger_leadaddress');
            $conn->table('vtiger_leadaddress')->insert(array_intersect_key($addr, array_flip($addrCols)));
        }
    }

    protected function insertDeal($conn, int $ownerId, array $d): void
    {
        $id = $this->nextCrmId($conn);
        $now = now()->format('Y-m-d H:i:s');
        $this->insertCrmEntity($conn, $id, $ownerId, 'Potentials', $d['name'], $now);

        $row = [
            'potentialid' => $id,
            'potentialname' => $d['name'],
            'potential_no' => 'POT' . $id,
            'amount' => $d['amount'],
            'sales_stage' => $d['stage'],
            'closingdate' => now()->addDays($d['days'])->format('Y-m-d'),
            'probability' => 50,
        ];
        $cols = Schema::connection('vtiger')->getColumnListing('vtiger_potential');
        $conn->table('vtiger_potential')->insert(array_intersect_key($row, array_flip($cols)));
    }
}
