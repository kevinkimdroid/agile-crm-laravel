<?php

namespace App\Console\Commands;

use App\Models\VtigerUser;
use App\Services\PlainTextMailSender;
use App\Services\UserManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TraceUserSetupEmailCommand extends Command
{
    protected $signature = 'users:trace-setup-email
                            {email : User email address (e.g. evans.wanguba@geminialife.co.ke)}
                            {--send : Actually send a setup email (default: dry-run)}';

    protected $description = 'Trace password setup email for a CRM user (DB, token, mail config, logs)';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        $this->info('=== User setup email trace: ' . $email . ' ===');
        $this->newLine();

        $conn = config('services.password_reset.connection') ?? config('database.default');
        $this->table(['Setting', 'Value'], [
            ['APP_URL', config('app.url', '(not set)')],
            ['PASSWORD_RESET_BASE_URL', config('services.password_reset.base_url') ?: '(uses APP_URL)'],
            ['MAIL_FROM', config('mail.from.address')],
            ['SendGrid', trim((string) config('services.sendgrid.api_key', '')) !== '' ? 'configured' : 'off'],
            ['SMTP', config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port')],
            ['Token DB connection', $conn],
        ]);
        $this->newLine();

        $user = VtigerUser::on('vtiger')
            ->whereRaw('LOWER(email1) = ?', [$email])
            ->first();

        if (! $user) {
            $this->warn('No vtiger_users row with email1 = ' . $email);
            $similar = DB::connection('vtiger')
                ->table('vtiger_users')
                ->where('email1', 'like', '%' . explode('@', $email)[0] . '%')
                ->limit(5)
                ->get(['id', 'user_name', 'email1', 'status']);
            if ($similar->isNotEmpty()) {
                $this->line('Similar usernames/emails:');
                foreach ($similar as $row) {
                    $this->line("  id={$row->id} user={$row->user_name} email={$row->email1} status={$row->status}");
                }
            }
        } else {
            $this->info('User found');
            $this->table(['Field', 'Value'], [
                ['id', (string) $user->id],
                ['user_name', (string) $user->user_name],
                ['email1', (string) $user->email1],
                ['status', (string) ($user->status ?? '')],
                ['name', $user->full_name ?? ''],
            ]);
            $eloquentOk = VtigerUser::on('vtiger')->find($user->id) !== null;
            $this->line($eloquentOk ? 'Eloquent find(id): OK' : 'Eloquent find(id): FAILED');
        }

        $this->newLine();
        $hasTokens = Schema::connection($conn)->hasTable('password_reset_tokens');
        $this->line('password_reset_tokens table: ' . ($hasTokens ? 'yes' : 'MISSING — run php artisan migrate'));

        if ($hasTokens) {
            $token = DB::connection($conn)
                ->table('password_reset_tokens')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($token) {
                $this->info('Reset token on file (link was generated at least once)');
                $this->line('  created_at: ' . ($token->created_at ?? '(unknown)'));
            } else {
                $this->warn('No reset token stored for this email — setup email may never have been attempted.');
            }
        }

        $this->newLine();
        $this->info('Recent log lines (UserManagement / SendGrid / this email)');
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            $lines = $this->matchingLogLines($logPath, $email, 25);
            if ($lines === []) {
                $this->line('  (none found — app may not have logged this address yet)');
            } else {
                foreach ($lines as $line) {
                    $this->line('  ' . $line);
                }
            }
        } else {
            $this->warn('Log file not found: ' . $logPath);
        }

        if (! $user) {
            $this->newLine();
            $this->comment('Create the user in Settings → Users first, then run this command again.');

            return self::FAILURE;
        }

        if (! $this->option('send')) {
            $this->newLine();
            $this->comment('Dry run only. To send a setup email now:');
            $this->line('  php artisan users:trace-setup-email ' . $email . ' --send');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Sending setup email…');
        $service = app(UserManagementService::class);
        $sent = $service->sendPasswordResetEmail($user, true);

        if ($sent) {
            $this->info('App reports: SENT (SendGrid/SMTP accepted the message).');
            $this->newLine();
            $this->line('If the inbox is empty:');
            $this->line('  1. Check spam/junk/quarantine on ' . $email);
            $this->line('  2. Corporate mail (geminialife.co.ke) may block mail FROM info@agilecraft.co.ke');
            $this->line('  3. SendGrid → Activity — search for ' . $email . ' (delivered / deferred / bounce)');
            $this->line('  4. Ask IT to whitelist info@agilecraft.co.ke and SendGrid');

            return self::SUCCESS;
        }

        $err = app(PlainTextMailSender::class)->getLastError();
        $this->error('App reports: FAILED');
        $this->line('  Error: ' . ($err ?: '(no detail — check storage/logs/laravel.log)'));

        return self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    protected function matchingLogLines(string $path, string $email, int $limit): array
    {
        $content = File::get($path);
        $all = explode("\n", $content);
        $keywords = ['UserManagement', 'SendGrid', 'PlainTextMailSender', $email];
        $out = [];

        foreach (array_reverse($all) as $line) {
            if (count($out) >= $limit) {
                break;
            }
            foreach ($keywords as $kw) {
                if (stripos($line, $kw) !== false) {
                    $out[] = trim($line);
                    break;
                }
            }
        }

        return array_reverse($out);
    }
}
