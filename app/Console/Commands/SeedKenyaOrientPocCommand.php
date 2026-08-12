<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-shot Orient POC setup: aligned sample clients, CRM contacts, and broken SLA tickets.
 */
class SeedKenyaOrientPocCommand extends Command
{
    protected $signature = 'kenya-orient:seed-poc
                            {--force : Skip confirmations}
                            {--skip-sla : Only seed local clients + CRM contacts}';

    protected $description = 'Seed aligned Orient POC data (clients, CRM contacts, broken SLAs)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $flags = $force ? ['--force' => true] : [];

        $this->info('1/2 Seeding aligned sample clients (KOL-* policies) + CRM contacts…');
        $clientCode = $this->call('kenya-orient:seed-sample-clients', array_merge($flags, [
            '--link-crm' => true,
        ]));
        if ($clientCode !== self::SUCCESS) {
            return $clientCode;
        }

        if ($this->option('skip-sla')) {
            $this->comment('Skipped SLA seed (--skip-sla).');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('2/2 Seeding broken-SLA client + work tickets…');
        $slaCode = $this->call('kenya-orient:seed-sla-demo', $flags);
        if ($slaCode !== self::SUCCESS) {
            return $slaCode;
        }

        $this->newLine();
        $this->info('POC data ready.');
        $this->line('  Clients: KOL-IND-10001, KOL-INV-50001, KOL-PEN-40001');
        $this->line('  Investment maturities: Support → Investment maturities (demo mode)');
        $this->line('  Broken SLAs: Reports → Client Tickets – Broken SLA');

        return self::SUCCESS;
    }
}
