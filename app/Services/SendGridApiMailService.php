<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send mail via SendGrid HTTP API (port 443). Use when SMTP ports are blocked (e.g. DigitalOcean).
 */
class SendGridApiMailService
{
    public function isConfigured(): bool
    {
        return trim((string) config('services.sendgrid.api_key', '')) !== '';
    }

    public function sendMail(
        string $toAddress,
        ?string $toName,
        string $subject,
        string $body,
        bool $bodyIsHtml = false,
        ?string $fromAddress = null,
        ?string $fromName = null
    ): bool {
        $apiKey = trim((string) config('services.sendgrid.api_key', ''));
        if ($apiKey === '') {
            return false;
        }

        $fromAddress = trim((string) ($fromAddress ?: config('mail.from.address', 'info@agilecraft.co.ke')), " \t\n\r\0\x0B\"'");
        $fromName = trim((string) ($fromName ?: config('mail.from.name', config('app.name', 'Agile CRM'))));

        if ($fromAddress === '' || ! filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            Log::warning('SendGridApiMailService: invalid from address', ['from' => $fromAddress]);

            return false;
        }

        $payload = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $toAddress,
                            'name' => $toName ?: null,
                        ],
                    ],
                ],
            ],
            'from' => [
                'email' => $fromAddress,
                'name' => $fromName !== '' ? $fromName : null,
            ],
            'subject' => $subject,
            'content' => [
                [
                    'type' => $bodyIsHtml ? 'text/html' : 'text/plain',
                    'value' => $body,
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->withOptions(['connect_timeout' => 10])
            ->timeout(25)
            ->post('https://api.sendgrid.com/v3/mail/send', $payload);

        if (! $response->successful()) {
            Log::warning('SendGridApiMailService send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $toAddress,
            ]);

            return false;
        }

        return true;
    }
}
