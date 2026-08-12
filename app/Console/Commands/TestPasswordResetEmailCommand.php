<?php

namespace App\Console\Commands;

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

    protected $description = 'Test password setup / reset email delivery (Graph → SendGrid API → SMTP)';

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

        $this->info('Mail delivery paths');
        $this->table(['Key', 'Value'], [
            ['Microsoft Graph', $graph->isConfigured() ? 'configured' : 'off'],
            ['SendGrid API (HTTPS)', $sendGrid->isConfigured() ? 'configured' : 'off — set SENDGRID_API_KEY'],
            ['SMTP fallback', config('mail.mailers.smtp.host') . ':' . config('mail.mailers.smtp.port')],
            ['MAIL_FROM', config('mail.from.address')],
            ['password_reset_tokens', Schema::connection($connection)->hasTable('password_reset_tokens') ? 'yes' : 'NO — run migrate'],
        ]);

        if (! $graph->isConfigured() && ! $sendGrid->isConfigured() && (bool) $this->option('smtp-only') === false) {
            $this->warn('DigitalOcean often blocks SMTP (25/465/587). Set SENDGRID_API_KEY or MSGRAPH_* for HTTPS delivery.');
        }

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
