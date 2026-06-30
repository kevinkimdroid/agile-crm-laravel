<?php

namespace App\Services;

use App\Models\SocialAccount;
use App\Models\SocialInteraction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function isSandbox(): bool
    {
        return (bool) config('whatsapp.sandbox', false);
    }

    public function isConfigured(): bool
    {
        if (!config('whatsapp.enabled')) {
            return false;
        }

        if ($this->isSandbox()) {
            return true;
        }

        return (bool) (config('whatsapp.phone_number_id') && config('whatsapp.access_token'));
    }

    public function setupInstructions(): string
    {
        if ($this->isSandbox()) {
            return 'Sandbox mode is ON. Set WHATSAPP_ENABLED=true and WHATSAPP_SANDBOX=true in .env, then run php artisan config:clear. '
                . 'Use “Simulate customer message” on the chat page to test inbound messages. Turn off WHATSAPP_SANDBOX for live Meta API.';
        }

        return 'Add WHATSAPP_ENABLED=true, WHATSAPP_PHONE_NUMBER_ID, WHATSAPP_ACCESS_TOKEN, and WHATSAPP_BUSINESS_ACCOUNT_ID from Meta Business Suite → WhatsApp → API Setup. '
            . 'Subscribe the WhatsApp product on your Meta app webhook (same URL as Facebook). Then run php artisan config:clear. '
            . 'For local testing without Meta, set WHATSAPP_SANDBOX=true instead.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getConsultation(): array
    {
        return config('whatsapp.consultation', []);
    }

    /**
     * Ensure a SocialAccount row exists for webhook routing.
     */
    public function ensureAccountRecord(): ?SocialAccount
    {
        if (!$this->isConfigured()) {
            return null;
        }

        if ($this->isSandbox()) {
            $defaults = config('whatsapp.sandbox_defaults', []);

            return SocialAccount::updateOrCreate(
                ['platform' => 'whatsapp', 'account_id' => 'sandbox'],
                [
                    'account_name' => config('whatsapp.display_phone')
                        ?: ($defaults['display_phone'] ?? 'Sandbox WhatsApp'),
                    'access_token' => 'sandbox',
                    'metadata' => [
                        'sandbox' => true,
                        'display_phone' => config('whatsapp.display_phone')
                            ?: ($defaults['display_phone'] ?? null),
                    ],
                ]
            );
        }

        $phoneId = (string) config('whatsapp.phone_number_id');
        $wabaId = config('whatsapp.business_account_id');

        return SocialAccount::updateOrCreate(
            ['platform' => 'whatsapp', 'account_id' => $phoneId],
            [
                'account_name' => config('whatsapp.display_phone') ?: 'WhatsApp Business',
                'access_token' => config('whatsapp.access_token'),
                'metadata' => array_filter([
                    'phone_number_id' => $phoneId,
                    'waba_id' => $wabaId,
                    'display_phone' => config('whatsapp.display_phone'),
                ]),
            ]
        );
    }

    /**
     * @return array{phone: string, display_phone: string|null, name: string, configured: bool}
     */
    public function getCompanyProfile(): array
    {
        $defaults = config('whatsapp.sandbox_defaults', []);
        $displayPhone = config('whatsapp.display_phone')
            ?: ($this->isSandbox() ? ($defaults['display_phone'] ?? '+254 700 000 001') : null);

        return [
            'phone' => (string) $displayPhone,
            'display_phone' => $displayPhone,
            'name' => $this->isSandbox()
                ? (config('whatsapp.business_name') ?: ($defaults['business_name'] ?? 'Kenya Orient WhatsApp'))
                : config('whatsapp.business_name', 'Kenya Orient WhatsApp'),
            'configured' => $this->isConfigured(),
            'sandbox' => $this->isSandbox(),
        ];
    }

    /**
     * Seed one demo inbound message when sandbox inbox is empty.
     */
    public function ensureSandboxDemoConversation(): void
    {
        if (!$this->isSandbox() || !$this->isConfigured()) {
            return;
        }

        if (SocialInteraction::where('platform', 'whatsapp')->exists()) {
            return;
        }

        $defaults = config('whatsapp.sandbox_defaults', []);
        $phone = $this->normalizePhone((string) ($defaults['demo_customer_phone'] ?? '254712345678'));
        $name = (string) ($defaults['demo_customer_name'] ?? 'Demo Customer');
        $message = (string) ($defaults['demo_customer_message'] ?? 'Hello, I need help.');

        if ($phone !== '') {
            $this->simulateInboundMessage($phone, $message, $name);
        }
    }

    /**
     * @return array{success: bool, error: string|null, interaction_id: int|null, message: array<string, mixed>|null}
     */
    public function simulateInboundMessage(string $phone, string $body, ?string $contactName = null): array
    {
        if (!$this->isSandbox()) {
            return ['success' => false, 'error' => 'Simulate inbound is only available in sandbox mode.', 'interaction_id' => null, 'message' => null];
        }

        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return ['success' => false, 'error' => 'Invalid phone number.', 'interaction_id' => null, 'message' => null];
        }

        $account = $this->ensureAccountRecord();
        if (!$account) {
            return ['success' => false, 'error' => 'WhatsApp account not ready.', 'interaction_id' => null, 'message' => null];
        }

        $messageId = 'sandbox-in-' . bin2hex(random_bytes(8));
        $interaction = SocialInteraction::create([
            'social_account_id' => $account->id,
            'platform' => 'whatsapp',
            'external_id' => $messageId,
            'post_external_id' => null,
            'type' => SocialInteraction::TYPE_DM,
            'author_name' => $contactName,
            'author_handle' => $phone,
            'author_phone' => $phone,
            'content' => $body,
            'post_url' => null,
            'metadata' => [
                'direction' => 'inbound',
                'webhook' => 'whatsapp_sandbox',
                'sandbox' => true,
            ],
            'interaction_at' => now(),
        ]);

        $this->processAutoTicket($interaction);

        return [
            'success' => true,
            'error' => null,
            'interaction_id' => $interaction->id,
            'message' => $this->formatMessage($interaction->fresh()),
            'ticket' => app(AutoTicketFromWhatsAppService::class)->getTicketSummaryForPhone($phone),
        ];
    }

    public function normalizePhonePublic(string $phone): string
    {
        return $this->normalizePhone($phone);
    }

    /**
     * @return array<int, array{phone: string, name: string|null, preview: string|null, last_at: string|null, last_at_human: string|null}>
     */
    public function getConversations(int $limit = 50): array
    {
        $rows = SocialInteraction::query()
            ->where('platform', 'whatsapp')
            ->whereNotNull('author_phone')
            ->where('author_phone', '!=', '')
            ->orderByDesc('interaction_at')
            ->limit(500)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $phone = (string) $row->author_phone;
            if (isset($grouped[$phone])) {
                continue;
            }

            $grouped[$phone] = [
                'phone' => $phone,
                'name' => $this->contactNameForPhone($rows, $phone),
                'display_phone' => $this->formatPhoneDisplay($phone),
                'preview' => $row->content,
                'last_at' => $row->interaction_at?->toIso8601String(),
                'last_at_human' => $row->interaction_at?->diffForHumans(),
                'ticket_id' => $this->contactTicketIdForPhone($rows, $phone),
                'ticket_no' => ($tid = $this->contactTicketIdForPhone($rows, $phone)) ? ('TT' . $tid) : null,
            ];

            if (count($grouped) >= $limit) {
                break;
            }
        }

        return array_values($grouped);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getThreadMessages(string $phone, ?string $since = null): array
    {
        $normalized = $this->normalizePhone($phone);
        if ($normalized === '') {
            return [];
        }

        $query = SocialInteraction::query()
            ->where('platform', 'whatsapp')
            ->where('author_phone', $normalized)
            ->orderBy('interaction_at');

        if ($since) {
            try {
                $query->where('interaction_at', '>', \Carbon\Carbon::parse($since));
            } catch (\Throwable) {
                // ignore invalid since
            }
        }

        return $query->get()->map(fn (SocialInteraction $m) => $this->formatMessage($m))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatMessage(SocialInteraction $interaction): array
    {
        $meta = $interaction->metadata ?? [];
        $direction = ($meta['direction'] ?? '') === 'outbound' ? 'outbound' : 'inbound';

        return [
            'id' => $interaction->id,
            'direction' => $direction,
            'body' => $interaction->content,
            'phone' => $interaction->author_phone,
            'contact_name' => $interaction->author_name,
            'at' => $interaction->interaction_at?->toIso8601String(),
            'at_display' => $interaction->interaction_at?->format('M j, g:i A'),
            'at_human' => $interaction->interaction_at?->diffForHumans(),
            'delivery_status' => $meta['delivery_status'] ?? null,
            'lead_id' => $interaction->lead_id,
            'ticket_id' => $interaction->ticket_id ?: data_get($interaction->metadata, 'ticket_id'),
            'ticket_no' => ($tid = ($interaction->ticket_id ?: data_get($interaction->metadata, 'ticket_id'))) ? ('TT' . $tid) : null,
        ];
    }

    public function contactLabel(string $phone, ?string $name = null): string
    {
        if ($name) {
            return $name;
        }

        return $this->formatPhoneDisplay($phone) ?: $phone;
    }

    public function formatPhoneDisplay(string $phone): string
    {
        $digits = $this->normalizePhone($phone);
        if ($digits === '') {
            return $phone;
        }

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return '+254 ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3) . ' ' . substr($digits, 9);
        }

        return '+' . $digits;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SocialInteraction>  $rows
     */
    private function contactTicketIdForPhone($rows, string $phone): ?int
    {
        foreach ($rows as $row) {
            if ((string) $row->author_phone !== $phone) {
                continue;
            }
            $id = $row->ticket_id ?: data_get($row->metadata, 'ticket_id');
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SocialInteraction>  $rows
     */
    private function contactNameForPhone($rows, string $phone): ?string
    {
        foreach ($rows as $row) {
            if ((string) $row->author_phone === $phone && $row->author_name) {
                return $row->author_name;
            }
        }

        return null;
    }

    /**
     * @return array{success: bool, message_id: string|null, error: string|null, interaction_id: int|null}
     */
    public function sendTextMessage(string $to, string $body, ?string $sentBy = null, bool $skipAutoTicket = false): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message_id' => null, 'error' => $this->setupInstructions(), 'interaction_id' => null];
        }

        $to = $this->normalizePhone($to);
        if ($to === '') {
            return ['success' => false, 'message_id' => null, 'error' => 'Invalid phone number. Use international format (e.g. 254712345678).', 'interaction_id' => null];
        }

        if ($this->isSandbox()) {
            $messageId = 'sandbox-out-' . bin2hex(random_bytes(8));
            $interaction = $this->storeOutboundMessage($to, $body, $messageId, $sentBy, true);

            return ['success' => true, 'message_id' => $messageId, 'error' => null, 'interaction_id' => $interaction?->id];
        }

        $version = config('whatsapp.graph_version', 'v18.0');
        $phoneNumberId = config('whatsapp.phone_number_id');

        $response = Http::withToken((string) config('whatsapp.access_token'))
            ->timeout(20)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if (!$response->successful()) {
            $err = $response->json('error.message') ?? $response->body();
            Log::warning('WhatsApp send failed: ' . $err);

            return ['success' => false, 'message_id' => null, 'error' => is_string($err) ? $err : 'Send failed', 'interaction_id' => null];
        }

        $messageId = $response->json('messages.0.id');
        $interaction = $this->storeOutboundMessage($to, $body, (string) $messageId, $sentBy);

        return ['success' => true, 'message_id' => $messageId, 'error' => null, 'interaction_id' => $interaction?->id];
    }

    private function storeOutboundMessage(string $customerPhone, string $body, string $messageId, ?string $sentBy = null, bool $sandbox = false): ?SocialInteraction
    {
        $account = $this->ensureAccountRecord();
        if (!$account) {
            return null;
        }

        $meta = [
            'direction' => 'outbound',
            'sent_by' => $sentBy,
            'company_phone' => config('whatsapp.display_phone') ?: config('whatsapp.sandbox_defaults.display_phone'),
        ];
        if ($sandbox || $this->isSandbox()) {
            $meta['sandbox'] = true;
            $meta['delivery_status'] = 'delivered';
            $meta['webhook'] = 'whatsapp_sandbox';
        }
        if ($sentBy && str_contains((string) $sentBy, 'auto-ticket')) {
            $meta['auto_ticket_reply'] = true;
        }

        return SocialInteraction::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'platform' => 'whatsapp',
                'external_id' => $messageId,
            ],
            [
                'post_external_id' => null,
                'type' => SocialInteraction::TYPE_DM,
                'author_name' => null,
                'author_handle' => $customerPhone,
                'author_phone' => $customerPhone,
                'content' => $body,
                'post_url' => null,
                'metadata' => $meta,
                'interaction_at' => now(),
            ]
        );
    }

    /**
     * Process WhatsApp Cloud API webhook payload (object: whatsapp_business_account).
     */
    public function processWebhookPayload(array $payload): int
    {
        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return 0;
        }

        $account = $this->ensureAccountRecord();
        if (!$account) {
            Log::notice('WhatsApp webhook: not configured — set WHATSAPP_* env vars');

            return 0;
        }

        $stored = 0;

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];
                foreach ($value['messages'] ?? [] as $message) {
                    if ($this->storeInboundMessage($account, $message, $value)) {
                        $stored++;
                    }
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->storeDeliveryStatus($account, $status);
                }
            }
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $value
     */
    private function storeInboundMessage(SocialAccount $account, array $message, array $value): bool
    {
        $messageId = $message['id'] ?? null;
        if (!$messageId) {
            return false;
        }

        $from = (string) ($message['from'] ?? '');
        $text = $message['text']['body'] ?? null;
        if ($text === null && ($message['type'] ?? '') !== 'text') {
            $text = '[' . ucfirst((string) ($message['type'] ?? 'message')) . ' message]';
        }

        $timestamp = $message['timestamp'] ?? null;
        $interactionAt = is_numeric($timestamp)
            ? \Carbon\Carbon::createFromTimestamp((int) $timestamp)
            : now();

        $contactName = null;
        foreach ($value['contacts'] ?? [] as $contact) {
            if (($contact['wa_id'] ?? '') === $from) {
                $contactName = $contact['profile']['name'] ?? null;
                break;
            }
        }

        SocialInteraction::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'platform' => 'whatsapp',
                'external_id' => (string) $messageId,
            ],
            [
                'post_external_id' => null,
                'type' => SocialInteraction::TYPE_DM,
                'author_name' => $contactName,
                'author_handle' => $from,
                'author_phone' => $from,
                'content' => $text,
                'post_url' => null,
                'metadata' => ['raw' => $message, 'webhook' => 'whatsapp', 'direction' => 'inbound'],
                'interaction_at' => $interactionAt,
            ]
        );

        $stored = SocialInteraction::where('platform', 'whatsapp')
            ->where('external_id', (string) $messageId)
            ->first();
        if ($stored) {
            $this->processAutoTicket($stored);
        }

        return true;
    }

    private function processAutoTicket(SocialInteraction $interaction): void
    {
        try {
            app(AutoTicketFromWhatsAppService::class)->ensureTicketForConversation($interaction->fresh());
        } catch (\Throwable $e) {
            Log::warning('WhatsApp auto-ticket hook failed: ' . $e->getMessage(), [
                'interaction_id' => $interaction->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function storeDeliveryStatus(SocialAccount $account, array $status): void
    {
        $messageId = $status['id'] ?? null;
        if (!$messageId) {
            return;
        }

        $interaction = SocialInteraction::where('platform', 'whatsapp')
            ->where('social_account_id', $account->id)
            ->where('external_id', (string) $messageId)
            ->first();

        if (!$interaction) {
            return;
        }

        $meta = $interaction->metadata ?? [];
        $meta['delivery_status'] = $status['status'] ?? null;
        $meta['delivery_at'] = now()->toIso8601String();
        $interaction->update(['metadata' => $meta]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '254' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '7')) {
            $digits = '254' . $digits;
        }

        return $digits;
    }
}
