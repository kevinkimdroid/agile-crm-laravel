<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaturityClientNotificationService
{
    public function __construct(
        protected PlainTextMailSender $mail,
        protected AdvantaSmsService $sms,
        protected ErpClientService $erp,
    ) {}

    public function tableExists(): bool
    {
        return Schema::hasTable('maturity_client_notifications');
    }

    /**
     * Read email/phone from row fields only (no ERP lookup).
     *
     * @return array{email: ?string, phone: ?string}
     */
    public function contactFromRow(object|array $row): array
    {
        $data = is_array($row) ? $row : (array) $row;

        return [
            'email' => $this->firstValidEmail($data),
            'phone' => $this->firstValidPhone($data),
        ];
    }

    /**
     * Same contact fields as Support → Clients → client details (getPolicyDetails).
     *
     * @return array{email: ?string, phone: ?string}
     */
    public function contactFromClientDetails(string $policyNumber): array
    {
        $policy = trim($policyNumber);
        if ($policy === '') {
            return ['email' => null, 'phone' => null];
        }

        $cached = $this->lookupContactsInErpCache([$policy]);
        $contact = $cached[$policy] ?? ['email' => null, 'phone' => null];

        if ($contact['email'] === null || $contact['phone'] === null) {
            $details = $this->erp->getPolicyDetails($policy);
            if (is_array($details)) {
                $contact['email'] ??= $this->firstValidEmail($details);
                $contact['phone'] ??= $this->firstValidPhone($details);
            }
        }

        return $contact;
    }

    /**
     * @return array{email: ?string, phone: ?string}
     */
    public function resolveContact(object|array $row, ?string $policyNumber = null): array
    {
        $data = is_array($row) ? $row : (array) $row;
        $email = $this->firstValidEmail($data);
        $phone = $this->firstValidPhone($data);

        $policy = trim($policyNumber ?? (string) ($data['policy_number'] ?? $data['policy_no'] ?? $data['pol_policy_no'] ?? ''));
        if ($policy === '') {
            return ['email' => $email, 'phone' => $phone];
        }

        if ($email === null || $phone === null) {
            $fromDetails = $this->contactFromClientDetails($policy);
            $email ??= $fromDetails['email'];
            $phone ??= $fromDetails['phone'];
        }

        return ['email' => $email, 'phone' => $phone];
    }

    /**
     * Batch-fill client_email / client_phone from erp_clients_cache (fast, no HTTP per row).
     *
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function enrichContactsFromClientDetails(Collection $rows, string $policyField = 'policy_number'): Collection
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $policies = $rows
            ->map(fn ($row) => $this->policyFromRow($row, $policyField))
            ->filter(fn ($p) => $p !== '')
            ->unique()
            ->values()
            ->all();

        $cacheMap = $this->lookupContactsInErpCache($policies);

        return $rows->map(function ($row) use ($policyField, $cacheMap) {
            $contact = $this->contactFromRow($row);
            $policy = $this->policyFromRow($row, $policyField);

            if ($policy !== '' && isset($cacheMap[$policy])) {
                $contact['email'] ??= $cacheMap[$policy]['email'];
                $contact['phone'] ??= $cacheMap[$policy]['phone'];
            }

            $row->client_email = $contact['email'];
            $row->client_phone = $contact['phone'];

            return $row;
        });
    }

    /**
     * @param  array<int, string>  $policyNumbers
     * @return array<string, array{email: ?string, phone: ?string}>
     */
    public function lookupContactsInErpCache(array $policyNumbers): array
    {
        $policyNumbers = array_values(array_unique(array_filter(array_map(
            fn ($p) => trim((string) $p),
            $policyNumbers
        ))));

        if ($policyNumbers === [] || ! Schema::hasTable('erp_clients_cache')) {
            return [];
        }

        $columns = ['policy_number'];
        if (Schema::hasColumn('erp_clients_cache', 'email_adr')) {
            $columns[] = 'email_adr';
        }
        if (Schema::hasColumn('erp_clients_cache', 'phone_no')) {
            $columns[] = 'phone_no';
        }

        if (count($columns) === 1) {
            return [];
        }

        $map = [];
        foreach (DB::table('erp_clients_cache')->whereIn('policy_number', $policyNumbers)->get($columns) as $row) {
            $policy = trim((string) ($row->policy_number ?? ''));
            if ($policy === '') {
                continue;
            }
            $data = (array) $row;
            $map[$policy] = [
                'email' => $this->firstValidEmail($data),
                'phone' => $this->firstValidPhone($data),
            ];
        }

        return $map;
    }

    public function defaultSubject(string $eventType, string $policyNumber, string $eventDateFormatted): string
    {
        $label = $eventType === 'renewal' ? 'renewal' : 'maturity';

        return str_replace(
            [':policy', ':date'],
            [$policyNumber, $eventDateFormatted],
            (string) config('maturities.client_notifications.email_subject_'.$label, 'Policy :policy — '.$label.' on :date')
        );
    }

    public function defaultEmailBody(
        string $eventType,
        string $clientName,
        string $policyNumber,
        string $product,
        string $eventDateFormatted
    ): string {
        $key = $eventType === 'renewal' ? 'email_body_renewal' : 'email_body_maturity';
        $template = (string) config('maturities.client_notifications.'.$key, '');

        return str_replace(
            [':name', ':policy', ':product', ':date', ':company'],
            [
                $clientName !== '' ? $clientName : 'Valued Client',
                $policyNumber,
                $product !== '' ? $product : 'your policy',
                $eventDateFormatted,
                config('branding.client_short', 'Kenya Orient'),
            ],
            $template
        );
    }

    public function defaultSmsBody(
        string $eventType,
        string $clientName,
        string $policyNumber,
        string $eventDateFormatted
    ): string {
        $key = $eventType === 'renewal' ? 'sms_renewal' : 'sms_maturity';
        $template = (string) config('maturities.client_notifications.'.$key, '');

        return str_replace(
            [':name', ':policy', ':date', ':company'],
            [
                $clientName !== '' ? $clientName : 'Client',
                $policyNumber,
                $eventDateFormatted,
                config('branding.client_short', 'Kenya Orient'),
            ],
            $template
        );
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    public function send(
        string $channel,
        string $screen,
        string $eventType,
        string $policyNumber,
        string $eventDate,
        ?string $clientName,
        ?string $product,
        ?string $toEmail,
        ?string $toPhone,
        ?string $subject,
        ?string $message,
        ?int $userId
    ): array {
        $eventDateFormatted = $this->formatDate($eventDate);
        $clientName = trim((string) $clientName);
        $product = trim((string) $product);
        $policyNumber = trim($policyNumber);

        if ($channel === 'email') {
            $toEmail = trim((string) $toEmail);
            if ($toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                return ['ok' => false, 'error' => 'A valid client email address is required.'];
            }
            $subject = trim((string) $subject) !== ''
                ? trim((string) $subject)
                : $this->defaultSubject($eventType, $policyNumber, $eventDateFormatted);
            $body = trim((string) $message) !== ''
                ? trim((string) $message)
                : $this->defaultEmailBody($eventType, $clientName, $policyNumber, $product, $eventDateFormatted);

            if (! $this->mail->send($toEmail, $clientName !== '' ? $clientName : null, $subject, $body)) {
                return ['ok' => false, 'error' => 'Email could not be sent. Check mail configuration.'];
            }

            $this->log($screen, $policyNumber, $eventDate, $eventType, 'email', $toEmail, $userId);

            return ['ok' => true, 'error' => null];
        }

        if ($channel === 'sms') {
            if (! $this->sms->isConfigured()) {
                return ['ok' => false, 'error' => 'SMS is not configured. Set Advanta credentials in .env.'];
            }
            $toPhone = trim((string) $toPhone);
            if ($toPhone === '') {
                return ['ok' => false, 'error' => 'A client phone number is required.'];
            }
            $smsBody = trim((string) $message) !== ''
                ? trim((string) $message)
                : $this->defaultSmsBody($eventType, $clientName, $policyNumber, $eventDateFormatted);

            $result = $this->sms->send($toPhone, $smsBody);
            if (! ($result['success'] ?? false)) {
                return ['ok' => false, 'error' => $result['error'] ?? 'SMS delivery failed.'];
            }

            $this->log($screen, $policyNumber, $eventDate, $eventType, 'sms', $this->sms->normalizePhone($toPhone), $userId);

            return ['ok' => true, 'error' => null];
        }

        return ['ok' => false, 'error' => 'Invalid notification channel.'];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function annotateRows(Collection $rows, string $screen, string $policyField = 'policy_number', string $dateField = 'maturity'): Collection
    {
        if ($rows->isEmpty() || ! $this->tableExists()) {
            return $rows->map(function ($row) {
                $row->client_notified_email = false;
                $row->client_notified_sms = false;

                return $row;
            });
        }

        $sent = DB::table('maturity_client_notifications')
            ->where('screen', $screen)
            ->get()
            ->groupBy(fn ($r) => $r->policy_number.'|'.$r->event_date);

        return $rows->map(function ($row) use ($sent, $policyField, $dateField) {
            $policy = $this->policyFromRow($row, $policyField);
            $date = $this->dateFromRow($row, $dateField);
            $key = $policy !== '' && $date !== '' ? $policy.'|'.$date : null;
            $events = $key !== null ? ($sent->get($key) ?? collect()) : collect();
            $row->client_notified_email = $events->contains(fn ($e) => $e->channel === 'email');
            $row->client_notified_sms = $events->contains(fn ($e) => $e->channel === 'sms');

            return $row;
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function enrichContacts(Collection $rows, string $policyField = 'policy_number'): Collection
    {
        return $rows->map(function ($row) use ($policyField) {
            $policy = $this->policyFromRow($row, $policyField);
            $contact = $this->resolveContact($row, $policy);
            $row->client_email = $contact['email'];
            $row->client_phone = $contact['phone'];

            return $row;
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int, object>
     */
    public function filterBySearch(Collection $rows, ?string $search): Collection
    {
        $term = strtolower(trim((string) $search));
        if ($term === '') {
            return $rows;
        }

        $digits = preg_replace('/\D/', '', $term);

        return $rows->filter(function ($row) use ($term, $digits) {
            $parts = [
                $row->policy_number ?? $row->policy_no ?? $row->pol_policy_no ?? '',
                $row->life_assured ?? $row->life_assur ?? $row->full_name ?? $row->client_name ?? '',
                $row->product ?? '',
                $row->pol_prepared_by ?? '',
                $row->intermediary ?? '',
                $row->client_email ?? $row->email ?? $row->email_adr ?? '',
                $row->client_phone ?? $row->phone ?? $row->phone_no ?? $row->mobile ?? '',
            ];

            if ($digits !== '' && strlen($digits) >= 4) {
                $phoneDigits = preg_replace('/\D/', '', implode(' ', array_map('strval', $parts)));
                if (str_contains($phoneDigits, $digits)) {
                    return true;
                }
            }

            return str_contains(strtolower(implode(' ', array_map('strval', $parts))), $term);
        })->values();
    }

    private function policyFromRow(object $row, string $primaryField): string
    {
        foreach ([$primaryField, 'policy_number', 'policy_no', 'pol_policy_no'] as $field) {
            $v = trim((string) ($row->{$field} ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    private function dateFromRow(object $row, string $primaryField): string
    {
        foreach ([$primaryField, 'maturity', 'pol_maturity_date', 'mendr_renewal_date'] as $field) {
            $v = $this->normalizeDate((string) ($row->{$field} ?? ''));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }

    private function log(
        string $screen,
        string $policyNumber,
        string $eventDate,
        string $eventType,
        string $channel,
        string $recipient,
        ?int $userId
    ): void {
        if (! $this->tableExists()) {
            return;
        }

        DB::table('maturity_client_notifications')->insert([
            'screen' => $screen,
            'policy_number' => $policyNumber,
            'event_date' => $this->normalizeDate($eventDate),
            'event_type' => $eventType,
            'channel' => $channel,
            'recipient' => $recipient,
            'user_id' => $userId,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstValidEmail(array $data): ?string
    {
        foreach (['email_adr', 'client_email', 'email', 'mem_email', 'EMAIL_ADR', 'CLIENT_EMAIL'] as $key) {
            $v = trim((string) ($data[$key] ?? ''));
            if ($v !== '' && filter_var($v, FILTER_VALIDATE_EMAIL)) {
                return $v;
            }
        }

        return null;
    }

    private function firstValidPhone(array $data): ?string
    {
        foreach ([
            'phone_no', 'phoneNo', 'PHONE_NO',
            'mobile', 'MOBILE', 'phone', 'PHONE',
            'client_phone', 'CLIENT_PHONE',
            'client_contact', 'CLIENT_CONTACT',
            'mem_teleph', 'MEM_TELEPH',
        ] as $key) {
            $v = trim((string) ($data[$key] ?? ''));
            if ($v !== '' && preg_match('/\d{9,}/', preg_replace('/\D/', '', $v))) {
                return $v;
            }
        }

        return null;
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatDate(string $value): string
    {
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }
}
