<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\CrmService;
use App\Services\OrientPocCatalog;
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
    protected array $clients = [];

    public function __construct()
    {
        parent::__construct();
        $this->clients = OrientPocCatalog::sampleClients();
    }

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
