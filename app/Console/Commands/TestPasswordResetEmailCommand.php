<?php

namespace App\Console\Commands;

use App\Services\PlainTextMailSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TestPasswordResetEmailCommand extends Command
{
    protected $signature = 'mail:test-password-reset
                            {--to= : Recipient email address (required)}';

    protected $description = 'Test SMTP config used for password setup / reset emails';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid recipient: php artisan mail:test-password-reset --to=user@example.com');

            return 1;
        }

        $connection = config('services.password_reset.connection') ?? config('database.default');
        $this->info('Mail config');
        $this->table(['Key', 'Value'], [
            ['MAIL_MAILER', config('mail.default')],
            ['MAIL_HOST', config('mail.mailers.smtp.host')],
            ['MAIL_PORT', config('mail.mailers.smtp.port')],
            ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption') ?: '(none — required for port 465)'],
            ['MAIL_FROM', config('mail.from.address')],
            ['password_reset DB', $connection],
            ['password_reset_tokens table', Schema::connection($connection)->hasTable('password_reset_tokens') ? 'yes' : 'NO — run php artisan migrate'],
        ]);

        $this->newLine();
        $this->info("Sending test password email to {$to}...");

        $sender = app(PlainTextMailSender::class);
        $sent = $sender->sendViaSmtp(
            $to,
            'Test User',
            'Password email test — ' . config('app.name'),
            "This is a test of the password setup email path.\n\nIf you received this, SMTP is working.\n\nSent at: " . now()
        );

        if ($sent) {
            $this->info('SUCCESS — check inbox and spam folder.');

            return 0;
        }

        $this->error('FAILED: ' . ($sender->getLastError() ?: 'unknown error'));
        $this->line('Also check storage/logs/laravel.log');

        return 1;
    }
}
