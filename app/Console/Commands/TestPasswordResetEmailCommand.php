<?php

namespace App\Console\Commands;

use App\Services\GmailApiMailService;
use App\Services\MicrosoftGraphMailService;
use App\Services\PlainTextMailSender;
use App\Services\SendGridApiMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class TestPasswordResetEmailCommand extends Command
{
    protected $signature = 'mail:test-password-reset
                            {--to= : Recipient email address (required)}
                            {--smtp-only : Skip Graph/SendGrid API and test SMTP only}';

    protected $description = 'Test password setup / reset email (Gmail API, SendGrid, or SMTP)';

    public function handle(): int
    {
        $to = trim((string) $this->option('to'));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid recipient: php artisan mail:test-password-reset --to=user@example.com');

            return 1;
        }

        $connection = config('services.password_reset.connection') ?? config('database.default');
        $graph = app(MicrosoftGraphMailService::class);
        $sendGrid = app(SendGridApiMailService::class);
        $gmail = app(GmailApiMailService::class);

        $this->info('Password email: Gmail API, then SendGrid, then SMTP. Graph is skipped.');
        $this->table(['Key', 'Value'], [
            ['From', $gmail->isConfigured() ? $gmail->fromAddress() : config('mail.from.address')],
            ['Gmail API', $gmail->isConfigured() ? 'configured (kelvinkimutai1@gmail.com)' : 'off — run php artisan mail:gmail-auth'],
            ['SendGrid', $sendGrid->isConfigured() ? 'configured' : 'off'],
            ['SMTP', config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port')],
            ['Microsoft Graph', $graph->isConfigured() ? 'configured but skipped' : 'off'],
            ['password_reset_tokens', Schema::connection($connection)->hasTable('password_reset_tokens') ? 'yes' : 'NO — run migrate'],
        ]);

        $this->newLine();
        $this->info("Sending test password email to {$to}...");

        $sender = app(PlainTextMailSender::class);
        $subject = 'Password email test — ' . config('app.name');
        $body = "This is a test of the password setup email path.\n\nIf you received this, mail delivery is working.\n\nSent at: " . now();

        $sent = (bool) $this->option('smtp-only')
            ? $sender->sendViaSmtp($to, 'Test User', $subject, $body)
            : $sender->sendForAuth($to, 'Test User', $subject, $body);

        if ($sent) {
            $this->info('SUCCESS — check inbox and spam folder.');

            return 0;
        }

        $this->error('FAILED: ' . ($sender->getLastError() ?: 'unknown error'));
        $this->line('Also check storage/logs/laravel.log');

        return 1;
    }
}
