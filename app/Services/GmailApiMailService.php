<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send mail via Gmail HTTP API (port 443). Works on DigitalOcean where SMTP :465 is blocked.
 */
class GmailApiMailService
{
    protected ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->refreshToken() !== '';
    }

    public function fromAddress(): string
    {
        $from = trim((string) config('services.gmail.from', 'kelvinkimutai1@gmail.com'), " \t\n\r\0\x0B\"'");

        return $from !== '' ? $from : 'kelvinkimutai1@gmail.com';
    }

    public function sendMail(
        string $toAddress,
        ?string $toName,
        string $subject,
        string $body,
        bool $bodyIsHtml = false,
        ?string $htmlBody = null
    ): bool {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            return false;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $from = $this->fromAddress();
        $fromName = (string) config('mail.from.name', 'Kenya Orient CRM');
        $raw = $this->buildRawMessage($from, $fromName, $toAddress, $toName, $subject, $body, $bodyIsHtml, $htmlBody);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(25)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $this->base64Url($raw),
            ]);

        if (! $response->successful()) {
            $this->lastError = 'HTTP ' . $response->status() . ' ' . $response->body();
            Log::warning('GmailApiMailService: send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $toAddress,
            ]);

            return false;
        }

        Log::info('GmailApiMailService: sent', [
            'to' => $toAddress,
            'from' => $from,
            'subject' => $subject,
        ]);

        return true;
    }

    public function authorizationUrl(): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/gmail.send',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    public function exchangeCode(string $code): ?string
    {
        $this->lastError = null;
        $response = Http::asForm()
            ->timeout(20)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => trim($code),
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            $this->lastError = 'HTTP ' . $response->status() . ' ' . $response->body();
            Log::warning('GmailApiMailService: code exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = trim((string) $response->json('refresh_token', ''));
        if ($token === '') {
            $this->lastError = 'Google did not return a refresh_token. Re-authorize with prompt=consent.';

            return null;
        }

        return $token;
    }

    protected function accessToken(): ?string
    {
        $response = Http::asForm()
            ->timeout(20)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'refresh_token' => $this->refreshToken(),
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            $this->lastError = 'token refresh HTTP ' . $response->status() . ' ' . $response->body();
            Log::warning('GmailApiMailService: token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $token = trim((string) $response->json('access_token', ''));
        if ($token === '') {
            $this->lastError = 'Google did not return an access_token.';

            return null;
        }

        return $token;
    }

    protected function buildRawMessage(
        string $from,
        string $fromName,
        string $to,
        ?string $toName,
        string $subject,
        string $body,
        bool $bodyIsHtml,
        ?string $htmlBody
    ): string {
        $useHtml = $bodyIsHtml || (is_string($htmlBody) && trim($htmlBody) !== '');
        $html = $useHtml ? (string) ($htmlBody ?: $body) : '';
        $plain = $bodyIsHtml && $htmlBody === null ? strip_tags($body) : $body;

        $headers = [
            'From: ' . $this->formatAddress($from, $fromName),
            'To: ' . $this->formatAddress($to, $toName),
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Date: ' . date('r'),
        ];

        if ($useHtml) {
            $boundary = 'crm' . bin2hex(random_bytes(8));
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            return implode("\r\n", $headers) . "\r\n\r\n"
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($plain))
                . '--' . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html))
                . '--' . $boundary . "--\r\n";
        }

        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($plain));
    }

    protected function formatAddress(string $email, ?string $name): string
    {
        $email = trim($email);
        $name = trim((string) $name);
        if ($name === '') {
            return $email;
        }

        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    protected function encodeHeader(string $value): string
    {
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }

        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    protected function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    protected function clientId(): string
    {
        return trim((string) config('services.gmail.client_id', ''));
    }

    protected function clientSecret(): string
    {
        return trim((string) config('services.gmail.client_secret', ''));
    }

    protected function refreshToken(): string
    {
        return trim((string) config('services.gmail.refresh_token', ''));
    }

    public function redirectUri(): string
    {
        return trim((string) config('services.gmail.redirect_uri', 'http://127.0.0.1'));
    }
}
