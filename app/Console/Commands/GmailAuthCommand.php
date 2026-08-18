<?php

namespace App\Console\Commands;

use App\Services\GmailApiMailService;
use Illuminate\Console\Command;

class GmailAuthCommand extends Command
{
    protected $signature = 'mail:gmail-auth {--code= : Authorization code from Google}';

    protected $description = 'Authorize Gmail API so the CRM can send as kelvinkimutai1@gmail.com over HTTPS';

    public function handle(GmailApiMailService $gmail): int
    {
        $clientId = trim((string) config('services.gmail.client_id', ''));
        $clientSecret = trim((string) config('services.gmail.client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set GMAIL_CLIENT_ID and GMAIL_CLIENT_SECRET in .env first.');
            $this->line('Google Cloud → APIs & Services → Credentials → Create OAuth client (Desktop app).');
            $this->line('Enable Gmail API. Add redirect URI: ' . $gmail->redirectUri());

            return 1;
        }

        $code = trim((string) $this->option('code'));
        if ($code === '') {
            $this->info('Sign in as kelvinkimutai1@gmail.com and allow gmail.send:');
            $this->newLine();
            $this->line($gmail->authorizationUrl());
            $this->newLine();
            $this->line('Then run:');
            $this->line('  php artisan mail:gmail-auth --code=PASTE_CODE_HERE');

            return 0;
        }

        $refresh = $gmail->exchangeCode($code);
        if ($refresh === null) {
            $this->error('Could not get refresh token: ' . ($gmail->getLastError() ?: 'unknown error'));

            return 1;
        }

        $this->info('Success. Add this to .env (then php artisan config:clear):');
        $this->newLine();
        $this->line('GMAIL_REFRESH_TOKEN=' . $refresh);

        return 0;
    }
}
