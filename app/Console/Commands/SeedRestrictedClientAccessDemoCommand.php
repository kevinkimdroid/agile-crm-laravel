<?php

namespace App\Console\Commands;

use App\Services\ClientAccessDemoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedRestrictedClientAccessDemoCommand extends Command
{
    protected $signature = 'demo:restricted-clients {--user= : Vtiger user id to receive DEMO-R assignments}';

    protected $description = 'Seed faker DEMO-R (allowed) and DEMO-X (forbidden) clients for restricted-access POC';

    public function handle(ClientAccessDemoService $demo): int
    {
        $userId = (int) $this->option('user');
        if ($userId <= 0) {
            $userId = (int) (DB::connection('vtiger')
                ->table('vtiger_users')
                ->where('status', 'Active')
                ->orderBy('id')
                ->value('id') ?: 0);
        }

        if ($userId <= 0) {
            $this->error('No active vtiger user found.');

            return self::FAILURE;
        }

        try {
            $result = $demo->seed($userId);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Allowed (assigned): ' . implode(', ', $result['allowed']));
        $this->info('Forbidden (not assigned): ' . implode(', ', $result['forbidden']));
        $this->info('Assigned to user id: ' . $result['assigned_to']);
        $this->line('Open /demo/restricted-client-access then click Start restricted mode.');

        return self::SUCCESS;
    }
}
