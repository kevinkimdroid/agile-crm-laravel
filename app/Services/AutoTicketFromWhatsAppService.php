<?php

namespace App\Services;

use App\Models\SocialInteraction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutoTicketFromWhatsAppService
{
    public function __construct(
        protected CrmService $crm,
        protected TicketNotificationService $notifications,
        protected WhatsAppService $whatsapp,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.auto_ticket.enabled', true)
            && (bool) config('whatsapp.enabled', false);
    }

    /**
     * Create a HelpDesk ticket for this WhatsApp conversation if none exists yet.
     *
     * @return array{ticket_id: int|null, ticket_no: string|null, created: bool, auto_reply_sent: bool, error: string|null}
     */
    public function ensureTicketForConversation(SocialInteraction $interaction): array
    {
        if (!$this->isEnabled()) {
            return ['ticket_id' => null, 'ticket_no' => null, 'created' => false, 'auto_reply_sent' => false, 'error' => 'disabled'];
        }

        $phone = (string) ($interaction->author_phone ?? '');
        if ($phone === '') {
            return ['ticket_id' => null, 'ticket_no' => null, 'created' => false, 'auto_reply_sent' => false, 'error' => 'No phone'];
        }

        $existingId = $this->findTicketIdForPhone($phone);
        if ($existingId) {
            $this->linkTicketToConversation($phone, $existingId, $interaction);

            return [
                'ticket_id' => $existingId,
                'ticket_no' => 'TT' . $existingId,
                'created' => false,
                'auto_reply_sent' => false,
                'error' => null,
            ];
        }

        try {
            $contactId = $this->resolveOrCreateContact($phone, $interaction->author_name);
            if (!$contactId) {
                return ['ticket_id' => null, 'ticket_no' => null, 'created' => false, 'auto_reply_sent' => false, 'error' => 'Could not resolve contact'];
            }

            $snippet = Str::limit(trim((string) $interaction->content), 80) ?: 'WhatsApp inquiry';
            $label = $interaction->author_name ?: $this->whatsapp->formatPhoneDisplay($phone);
            $title = 'WhatsApp: ' . $label . ' — ' . $snippet;

            $description = $this->buildDescription($interaction, $phone, $label);
            $ticketId = $this->createTicket([
                'title' => Str::limit($title, 255),
                'description' => $description,
                'contact_id' => $contactId,
                'source' => config('whatsapp.auto_ticket.source', 'WHATSAPP'),
            ]);

            $ticketNo = 'TT' . $ticketId;
            $this->linkTicketToConversation($phone, $ticketId, $interaction);

            $ownerId = (int) config('whatsapp.auto_ticket.assign_to_user_id', 1);
            try {
                $this->notifications->sendTicketCreatedNotification(
                    $ticketId,
                    $ticketNo,
                    Str::limit($title, 255),
                    $ownerId,
                    $contactId,
                    null,
                    false
                );
            } catch (\Throwable $e) {
                Log::warning('WhatsApp auto-ticket: staff notification failed', ['ticket_id' => $ticketId, 'error' => $e->getMessage()]);
            }

            $autoReplySent = $this->sendWhatsAppAutoReply($phone, $ticketNo, $snippet);

            Cache::forget('agile_ticket_counts_by_status');
            Cache::forget('agile_tickets_count');

            return [
                'ticket_id' => $ticketId,
                'ticket_no' => $ticketNo,
                'created' => true,
                'auto_reply_sent' => $autoReplySent,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp auto-ticket failed', [
                'phone' => $phone,
                'interaction_id' => $interaction->id,
                'error' => $e->getMessage(),
            ]);

            return ['ticket_id' => null, 'ticket_no' => null, 'created' => false, 'auto_reply_sent' => false, 'error' => $e->getMessage()];
        }
    }

    public function findTicketIdForPhone(string $phone): ?int
    {
        $phone = $this->whatsapp->normalizePhonePublic($phone);
        if ($phone === '') {
            return null;
        }

        $hasTicketColumn = Schema::connection(config('database.default'))->hasColumn('social_interactions', 'ticket_id');

        $row = SocialInteraction::query()
            ->where('platform', 'whatsapp')
            ->where('author_phone', $phone)
            ->where(function ($q) use ($hasTicketColumn) {
                if ($hasTicketColumn) {
                    $q->whereNotNull('ticket_id');
                }
                $q->orWhereNotNull('metadata->ticket_id');
            })
            ->orderByDesc('interaction_at')
            ->first();

        if (!$row) {
            return null;
        }

        $id = ($hasTicketColumn ? $row->ticket_id : null) ?: data_get($row->metadata, 'ticket_id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array{ticket_id: int|null, ticket_no: string|null}|null
     */
    public function getTicketSummaryForPhone(string $phone): ?array
    {
        $ticketId = $this->findTicketIdForPhone($phone);
        if (!$ticketId) {
            return null;
        }

        return ['ticket_id' => $ticketId, 'ticket_no' => 'TT' . $ticketId];
    }

    protected function linkTicketToConversation(string $phone, int $ticketId, SocialInteraction $interaction): void
    {
        $phone = $this->whatsapp->normalizePhonePublic($phone);

        SocialInteraction::query()
            ->where('platform', 'whatsapp')
            ->where('author_phone', $phone)
            ->orderBy('id')
            ->each(function (SocialInteraction $row) use ($ticketId) {
                if ($this->interactionTicketId($row)) {
                    return;
                }
                $meta = $row->metadata ?? [];
                $meta['ticket_id'] = $ticketId;
                $payload = ['metadata' => $meta];
                if (Schema::connection(config('database.default'))->hasColumn('social_interactions', 'ticket_id')) {
                    $payload['ticket_id'] = $ticketId;
                }
                $row->update($payload);
            });
    }

    protected function interactionTicketId(SocialInteraction $interaction): ?int
    {
        $id = null;
        if (Schema::connection(config('database.default'))->hasColumn('social_interactions', 'ticket_id')) {
            $id = $interaction->ticket_id;
        }
        $id = $id ?: data_get($interaction->metadata, 'ticket_id');

        return $id ? (int) $id : null;
    }

    protected function resolveOrCreateContact(string $phone, ?string $name): ?int
    {
        $normalized = $this->whatsapp->normalizePhonePublic($phone);
        $contact = $this->crm->findContactByPhoneOrEmail($normalized, null);
        if ($contact) {
            return (int) $contact->contactid;
        }

        $name = trim((string) $name) ?: $this->whatsapp->formatPhoneDisplay($normalized);
        $parts = explode(' ', $name, 2);

        return $this->crm->createContactFromErpClient([
            'first_name' => $parts[0] ?: 'WhatsApp',
            'last_name' => $parts[1] ?? 'Customer',
            'name' => $name,
            'mobile' => $normalized,
            'phone' => $normalized,
            'client_name' => $name,
        ]);
    }

    protected function buildDescription(SocialInteraction $interaction, string $phone, string $label): string
    {
        $lines = [
            'Received via WhatsApp from ' . $label . ' (' . $this->whatsapp->formatPhoneDisplay($phone) . ')',
            '',
            'Message:',
            trim((string) $interaction->content) ?: '(no text)',
        ];

        if ($this->whatsapp->isSandbox()) {
            $lines[] = '';
            $lines[] = '[Sandbox mode — ticket created from simulated or test WhatsApp chat]';
        }

        return implode("\n", $lines);
    }

    protected function createTicket(array $data): int
    {
        $userId = \Illuminate\Support\Facades\Auth::guard('vtiger')->id() ?? 1;
        $ownerId = (int) config('whatsapp.auto_ticket.assign_to_user_id', $userId);
        $category = config('whatsapp.auto_ticket.category', 'Other');
        $now = now()->format('Y-m-d H:i:s');
        $id = (int) DB::connection('vtiger')->table('vtiger_crmentity')->max('crmid') + 1;

        DB::connection('vtiger')->transaction(function () use ($data, $userId, $ownerId, $category, $now, $id) {
            DB::connection('vtiger')->table('vtiger_crmentity')->insert([
                'crmid' => $id,
                'smcreatorid' => $userId,
                'smownerid' => $ownerId,
                'modifiedby' => $userId,
                'setype' => 'HelpDesk',
                'description' => $data['description'] ?? '',
                'createdtime' => $now,
                'modifiedtime' => $now,
                'viewedtime' => null,
                'status' => 1,
                'version' => 0,
                'presence' => 1,
                'deleted' => 0,
                'smgroupid' => 0,
                'source' => $data['source'] ?? 'WHATSAPP',
                'label' => $data['title'],
            ]);

            DB::connection('vtiger')->table('vtiger_troubletickets')->insert([
                'ticketid' => $id,
                'ticket_no' => 'TT' . $id,
                'title' => $data['title'],
                'status' => 'Open',
                'priority' => 'Normal',
                'severity' => null,
                'category' => $category,
                'contact_id' => $data['contact_id'],
                'product_id' => null,
                'parent_id' => null,
                'hours' => null,
                'days' => null,
            ]);
        });

        return $id;
    }

    protected function sendWhatsAppAutoReply(string $phone, string $ticketNo, string $snippet): bool
    {
        if (!config('whatsapp.auto_ticket.auto_reply_enabled', true)) {
            return false;
        }

        if (!$this->whatsapp->isConfigured()) {
            return false;
        }

        $body = trim((string) config('whatsapp.auto_ticket.auto_reply_body', ''));
        if ($body === '') {
            $body = "Thank you for contacting us on WhatsApp.\n\nWe have logged your request as ticket {ticket_no}.\n\nOur team will respond shortly.";
        }
        $body = str_replace(['{ticket_no}', '{{ticket_number}}'], $ticketNo, $body);

        $result = $this->whatsapp->sendTextMessage($phone, $body, 'System (auto-ticket)', true);

        return (bool) ($result['success'] ?? false);
    }
}
