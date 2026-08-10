<?php

namespace Database\Seeders;

use App\Services\ClientAccessDemoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestrictedClientAccessDemoSeeder extends Seeder
{
    public function run(): void
    {
        $userId = (int) env('RESTRICTED_DEMO_USER_ID', 0);
        if ($userId <= 0) {
            try {
                $userId = (int) (DB::connection('vtiger')
                    ->table('vtiger_users')
                    ->where('status', 'Active')
                    ->where('user_name', '!=', 'admin')
                    ->orderBy('id')
                    ->value('id') ?: 0);
            } catch (\Throwable $e) {
                $userId = 0;
            }
        }
        if ($userId <= 0) {
            try {
                $userId = (int) (DB::connection('vtiger')
                    ->table('vtiger_users')
                    ->where('status', 'Active')
                    ->orderBy('id')
                    ->value('id') ?: 0);
            } catch (\Throwable $e) {
                $userId = 0;
            }
        }

        if ($userId <= 0) {
            $this->command?->error('No vtiger user found to assign demo clients to.');

            return;
        }

        $result = app(ClientAccessDemoService::class)->seed($userId);
        $this->command?->info('Seeded allowed: ' . implode(', ', $result['allowed']));
        $this->command?->info('Seeded forbidden: ' . implode(', ', $result['forbidden']));
        $this->command?->info('Assigned allowed policies to user id ' . $userId);
    }
}
