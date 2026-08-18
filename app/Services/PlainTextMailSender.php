<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends plain-text outbound mail (Graph when configured, else Laravel mail).
 */
class PlainTextMailSender
{
    protected ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Password / login emails. Skips Microsoft Graph / Geminia Outlook.
     * Gmail API (HTTPS) first, then SendGrid, then SMTP.
     * DigitalOcean blocks SMTP :465, so HTTPS is required on the droplet.
     */
    public function sendForAuth(string $to, ?string $toName, string $subject, string $body, ?string $htmlBody = null): bool
    {
        $this->lastError = null;
        $useHtml = is_string($htmlBody) && trim($htmlBody) !== '';
        $htmlOrText = $useHtml ? $htmlBody : $body;
        $from = $this->mailFromAddress();

        if ($this->deliverViaGmailApi($to, $toName, $subject, $body, $useHtml, $htmlBody)) {
            Log::info('PlainTextMailSender: auth email via Gmail API', [
                'to' => $to,
                'from' => $from,
            ]);

            return true;
        }

        if ($this->deliverViaSendGridApi($to, $toName, $subject, $htmlOrText, $useHtml)) {
            Log::info('PlainTextMailSender: auth email via SendGrid', [
                'to' => $to,
                'from' => $from,
            ]);

            return true;
        }

        Log::info('PlainTextMailSender: auth email via SMTP', [
            'to' => $to,
            'from' => $from,
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
        ]);

        return $this->deliverViaLaravelMail($to, $toName, $subject, $body, [], $htmlBody);
    }

    /**
     * Send via Laravel SMTP only (skips Graph and SendGrid API).
     */
    public function sendViaSmtp(string $to, ?string $toName, string $subject, string $body): bool
    {
        $this->lastError = null;

        return $this->deliverViaLaravelMail($to, $toName, $subject, $body, []);
    }

    /**
     * @param  array<int, array{name: string, contentType?: string, content: string}>  $attachments
     */
    public function send(string $to, ?string $toName, string $subject, string $body, array $attachments = [], ?string $htmlBody = null): bool
    {
        $this->lastError = null;
        $useHtml = is_string($htmlBody) && trim($htmlBody) !== '';
        $graphBody = $useHtml ? $htmlBody : $body;
        $graphIsHtml = $useHtml;

        $graph = app(MicrosoftGraphMailService::class);
        $graphConfigured = $graph->isConfigured();

        if ($attachments !== []) {
            // For Office 365 environments, Graph attachment sends are usually more reliable than SMTP auth.
            $graphAttachmentMaxBytes = max(1024 * 1024, (int) config('mass_broadcast.graph_attachment_max_bytes', 3 * 1024 * 1024));
            $totalAttachmentBytes = 0;
            foreach ($attachments as $attachment) {
                $totalAttachmentBytes += strlen((string) ($attachment['content'] ?? ''));
            }

            if ($graphConfigured && $totalAttachmentBytes <= $graphAttachmentMaxBytes) {
                if ($graph->sendMail($to, $toName, $subject, $body, false, $attachments)) {
                    return true;
                }
                $this->lastError = 'Graph: sendMail failed for attachment send.';
                Log::warning('PlainTextMailSender: Graph attachment send failed, falling back to Laravel Mail', [
                    'to' => $to,
                    'attachment_bytes' => $totalAttachmentBytes,
                ]);
            } elseif ($graphConfigured && $totalAttachmentBytes > $graphAttachmentMaxBytes) {
                $this->lastError = 'Graph: attachment too large for direct Graph send (' . $totalAttachmentBytes . ' bytes).';
                Log::warning('PlainTextMailSender: Graph skipped for large attachment', [
                    'to' => $to,
                    'attachment_bytes' => $totalAttachmentBytes,
                    'graph_attachment_max_bytes' => $graphAttachmentMaxBytes,
                ]);
            }

            return $this->deliverViaSendGridApi($to, $toName, $subject, $body, false)
                || $this->deliverViaLaravelMail($to, $toName, $subject, $body, $attachments);
        }

        if ($graphConfigured) {
            if ($graph->sendMail($to, $toName, $subject, $graphBody, $graphIsHtml, [])) {
                return true;
            }
            $this->lastError = 'Graph: sendMail failed.';
            Log::warning('PlainTextMailSender: Graph send failed, trying SendGrid API / SMTP', ['to' => $to]);
        }

        if ($this->deliverViaSendGridApi($to, $toName, $subject, $graphBody, $graphIsHtml)) {
            return true;
        }

        return $this->deliverViaLaravelMail($to, $toName, $subject, $body, [], $htmlBody);
    }

    protected function deliverViaGmailApi(
        string $to,
        ?string $toName,
        string $subject,
        string $body,
        bool $bodyIsHtml,
        ?string $htmlBody
    ): bool {
        $gmail = app(GmailApiMailService::class);
        if (! $gmail->isConfigured()) {
            return false;
        }

        if ($gmail->sendMail($to, $toName, $subject, $body, $bodyIsHtml, $htmlBody)) {
            return true;
        }

        $this->lastError = 'Gmail API: ' . ($gmail->getLastError() ?: 'send failed');

        return false;
    }

    protected function deliverViaSendGridApi(string $to, ?string $toName, string $subject, string $body, bool $bodyIsHtml): bool
    {
        $sendGrid = app(SendGridApiMailService::class);
        if (! $sendGrid->isConfigured()) {
            return false;
        }

        if ($sendGrid->sendMail($to, $toName, $subject, $body, $bodyIsHtml, $this->mailFromAddress(), config('mail.from.name', config('app.name')))) {
            return true;
        }

        $this->lastError = 'SendGrid API: send failed (check SENDGRID_API_KEY and sender verification).';

        return false;
    }

    /**
     * @param  array<int, array{name: string, contentType?: string, content: string}>  $attachments
     */
    protected function deliverViaLaravelMail(string $to, ?string $toName, string $subject, string $body, array $attachments, ?string $htmlBody = null): bool
    {
        $mailer = config('mail.default');
        if ($mailer === 'log') {
            Log::warning('PlainTextMailSender: MAIL_MAILER=log – email will not be delivered. Set MAIL_MAILER=smtp (or configure Graph) for actual delivery.');
        }

        $maxAttempts = max(1, (int) config('mass_broadcast.smtp_retry_attempts', 3));
        $retryDelayMs = max(100, (int) config('mass_broadcast.smtp_retry_delay_ms', 800));
        $useHtml = is_string($htmlBody) && trim($htmlBody) !== '';
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Mail::purge($mailer);
                $from = $this->mailFromAddress();
                $fromName = config('mail.from.name', config('app.name'));
                Mail::raw($body, function ($message) use ($to, $toName, $subject, $from, $fromName, $attachments, $body, $htmlBody, $useHtml) {
                    $message->to($to, $toName)->from($from, $fromName)->subject($subject);
                    if ($useHtml) {
                        $message->setBody($htmlBody, 'text/html');
                        $message->addPart($body, 'text/plain');
                    }
                    foreach ($attachments as $attachment) {
                        if (empty($attachment['name']) || ! isset($attachment['content'])) {
                            continue;
                        }
                        $message->attachData(
                            $attachment['content'],
                            (string) $attachment['name'],
                            ['mime' => (string) ($attachment['contentType'] ?? 'application/octet-stream')]
                        );
                    }
                });

                return true;
            } catch (\Throwable $e) {
                $msg = (string) $e->getMessage();
                $this->lastError = 'SMTP: ' . $msg;
                $transient = str_contains($msg, 'errno=10054')
                    || str_contains($msg, 'timed out')
                    || str_contains($msg, 'Connection could not be established');
                Log::warning('PlainTextMailSender: send failed', [
                    'to' => $to,
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'transient' => $transient,
                    'error' => $msg,
                ]);
                if ($attempt < $maxAttempts && $transient) {
                    usleep($retryDelayMs * 1000);
                    continue;
                }

                return false;
            }
        }

        return false;
    }

    protected function mailFromAddress(): string
    {
        $gmail = app(GmailApiMailService::class);
        if ($gmail->isConfigured()) {
            return $gmail->fromAddress();
        }

        $from = config('mail.from.address') ?: config('email-service.sender', 'info@agilecraft.co.ke');
        $from = trim((string) $from, " \t\n\r\0\x0B\"'");

        return $from !== '' ? $from : 'info@agilecraft.co.ke';
    }

    /**
     * Send plain-text email with a single PDF attachment (Graph when configured, else Laravel Mail).
     *
     * @param  string  $pdfBinary  Raw PDF bytes
     */
    public function sendWithPdfAttachment(
        string $to,
        ?string $toName,
        string $subject,
        string $body,
        string $pdfFilename,
        string $pdfBinary
    ): bool {
        return $this->send($to, $toName, $subject, $body, [
            ['name' => $pdfFilename, 'contentType' => 'application/pdf', 'content' => $pdfBinary],
        ]);
    }
}
