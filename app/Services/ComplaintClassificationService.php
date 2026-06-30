<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintClassificationService
{
    /**
     * @return array{
     *     is_complaint: bool,
     *     score: int,
     *     register_status: string,
     *     reason: string,
     *     suggested_nature: string|null
     * }
     */
    public function assess(?string $subject, ?string $body, ?string $source = null, ?string $fromAddress = null): array
    {
        $subject = trim((string) $subject);
        $body = trim((string) $body);
        $text = trim($subject . "\n" . $body);
        $lower = strtolower($text);
        $fromAddress = strtolower(trim((string) $fromAddress));

        if ($text === '') {
            return $this->result(false, 0, Complaint::REGISTER_EXCLUDED, 'Empty content', null);
        }

        if ($fromAddress !== '' && $this->isExcludedSender($fromAddress)) {
            return $this->result(false, 5, Complaint::REGISTER_EXCLUDED, 'Sender is a system or partner notification address', null);
        }

        foreach ($this->excludePatterns() as $pattern) {
            if (preg_match($pattern, $lower)) {
                return $this->result(false, 5, Complaint::REGISTER_EXCLUDED, 'Automated or non-complaint message', null);
            }
        }

        $notification = $this->detectNotification($lower);
        if ($notification !== null && ! $this->hasStrongComplaintSignal($lower)) {
            return $this->result(false, 5, Complaint::REGISTER_EXCLUDED, $notification, null);
        }

        $score = 0;
        $hits = [];

        foreach ($this->strongComplaintPhrases() as $phrase => $points) {
            if (str_contains($lower, $phrase)) {
                $score += $points;
                $hits[] = $phrase;
            }
        }

        foreach ($this->complaintKeywords() as $word => $points) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
                $score += $points;
                $hits[] = $word;
            }
        }

        if (preg_match('/\b(re:|fw:|fwd:)\s*/i', $subject) && $score > 0) {
            $score += 5;
        }

        foreach ($this->inquiryOnlyPhrases() as $phrase) {
            if (str_contains($lower, $phrase) && $score < 25) {
                $score = max(0, $score - 20);
                $hits[] = 'inquiry:' . $phrase;
            }
        }

        $score = max(0, min(100, $score));
        $minCreate = (int) config('complaints.classification.min_create_score', 35);
        $minActive = (int) config('complaints.classification.min_active_score', 60);

        $suggestedNature = $this->suggestNature($lower);
        $reason = $hits !== []
            ? 'Matched: ' . implode(', ', array_slice(array_unique($hits), 0, 4))
            : 'No clear complaint indicators';

        if ($score < $minCreate) {
            return $this->result(false, $score, Complaint::REGISTER_EXCLUDED, $reason, $suggestedNature);
        }

        if ($score < $minActive) {
            return $this->result(true, $score, Complaint::REGISTER_REVIEW, $reason, $suggestedNature);
        }

        return $this->result(true, $score, Complaint::REGISTER_ACTIVE, $reason, $suggestedNature);
    }

    public function classifyComplaint(Complaint $complaint, bool $persist = true): array
    {
        if ($complaint->source !== 'Email') {
            $assessment = $this->result(true, 95, Complaint::REGISTER_ACTIVE, 'Manually registered', $complaint->nature);
        } else {
            $parts = $this->splitSubjectBody($complaint->description);
            $assessment = $this->assess(
                $parts['subject'],
                $parts['body'],
                'Email',
                $complaint->complainant_email
            );
        }

        if ($persist) {
            $complaint->update([
                'register_status' => $assessment['register_status'],
                'classification_score' => $assessment['score'],
                'classification_reason' => $assessment['reason'],
            ]);
        }

        return $assessment;
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function splitSubjectBody(?string $description): array
    {
        $description = trim((string) $description);
        if ($description === '') {
            return ['subject' => '', 'body' => ''];
        }

        $lines = preg_split("/\r\n|\n|\r/", $description);
        $subject = trim($lines[0] ?? '');
        $body = trim(implode("\n", array_slice($lines, 1)));

        return ['subject' => $subject, 'body' => $body !== '' ? $body : $subject];
    }

    public function summary(?string $description, int $limit = 120): string
    {
        $clean = AutoComplaintFromEmailService::cleanDescriptionForExport($description);
        $clean = preg_replace("/\s+/", ' ', $clean) ?? $clean;

        return \Illuminate\Support\Str::limit(trim($clean), $limit);
    }

    /**
     * @return array<string, int>
     */
    protected function strongComplaintPhrases(): array
    {
        return array_merge([
            'formal complaint' => 40,
            ' lodge a complaint' => 40,
            ' lodging a complaint' => 40,
            'file a complaint' => 40,
            'raise a complaint' => 35,
            'customer complaint' => 35,
            'complaint against' => 35,
            'very dissatisfied' => 30,
            'extremely dissatisfied' => 30,
            'poor service' => 28,
            'unacceptable service' => 28,
            'mis-sold' => 30,
            'mis sold' => 30,
            'mis-selling' => 30,
            'insurance regulatory authority' => 35,
            'escalate to ira' => 35,
            'ombudsman' => 30,
        ], config('complaints.classification.strong_phrases', []));
    }

    /**
     * @return array<string, int>
     */
    protected function complaintKeywords(): array
    {
        return array_merge([
            'complaint' => 25,
            'complain' => 20,
            'complaining' => 20,
            'dissatisfied' => 18,
            'unhappy' => 15,
            'frustrated' => 12,
            'disappointed' => 12,
            'dispute' => 15,
            'disputed' => 15,
            'denied' => 12,
            'rejected' => 12,
            'delay' => 10,
            'delayed' => 10,
            'unfair' => 12,
            'negligence' => 15,
            'fraud' => 18,
            'scam' => 15,
            'refund' => 10,
            'compensation' => 10,
            'escalate' => 12,
            'unprofessional' => 12,
            'incompetent' => 12,
            'terrible' => 8,
            'awful' => 8,
            'unresolved' => 12,
            'still waiting' => 10,
            'no response' => 10,
            'ignored' => 10,
        ], config('complaints.classification.keywords', []));
    }

    /**
     * @return array<int, string>
     */
    protected function excludePatterns(): array
    {
        $patterns = [
            '/out of office/i',
            '/automatic reply/i',
            '/auto[\s-]?reply/i',
            '/delivery status notification/i',
            '/mail delivery failed/i',
            '/undeliverable/i',
            '/mailer-daemon/i',
            '/noreply@/i',
            '/no-reply@/i',
            '/newsletter/i',
            '/unsubscribe/i',
            '/special offer/i',
            '/promotional/i',
            '/your ticket (number|no|#)/i',
            '/ticket number:\s*tt/i',
            '/payment received/i',
            '/premium reminder/i',
            '/maturity notice/i',
            '/statement attached/i',
            '/meeting invitation/i',
            '/invitation:\s/i',
            '/microsoft teams/i',
            '/zoom meeting/i',
            '/calendar invitation/i',
            '/job application/i',
            '/cv attached/i',
            '/salary advance/i',
            // Bank / payment / transaction notifications
            '/approve transaction/i',
            '/transaction\s*(?:#|no\.?|number)\s*[\d,]+/i',
            '/gabnet@/i',
            '/@gab\.co\.ke\b/i',
            '/dear customer,\s*date:/i',
            '/transaction (?:alert|notification|confirmation|approved|successful)/i',
            '/payment (?:alert|notification|confirmation|received|successful)/i',
            '/account (?:alert|notification)/i',
            '/(?:successfully|has been)\s+(?:processed|completed|transferred|debited|credited)/i',
            '/mpesa(?:[\s-]?(?:confirm|receipt|notification|payment))?/i',
            '/stk push/i',
            '/this is an automated (?:message|email|notification)/i',
            '/do not reply to this (?:email|message)/i',
            '/(?:one[\s-]?time|verification)\s+(?:password|code|pin)/i',
            '/(?:otp|auth(?:orization|entication))\s*(?:code|pin)/i',
            '/balance (?:alert|notification|update)/i',
            '/receipt for (?:your )?payment/i',
            '/transaction reference/i',
            '/funds (?:have been|were) (?:transferred|sent|received)/i',
            '/swift confirmation/i',
            '/rtgs (?:confirmation|notification|alert)/i',
            '/eft (?:confirmation|notification|alert)/i',
            // Clear automated / system-only (safe to hard-exclude)
            '/password reset/i',
            '/reset your password/i',
            '/payslip (?:for|available|attached)/i',
            '/leave (?:application|approval|request) (?:approved|submitted)/i',
            '/timesheet (?:submitted|approved)/i',
            '/ticket (?:has been|was) (?:updated|closed|resolved|assigned)/i',
            '/status changed to (?:closed|resolved|completed)/i',
            '/please do not reply/i',
            '/this email (?:was )?sent automatically/i',
            '/read receipt/i',
            '/delivery confirmation/i',
            '/message has been delivered/i',
            '/docusign/i',
            '/shared (?:a|the) (?:file|document|folder) with you/i',
            '/(?:accepted|declined|tentative):\s/i',
            '/pesalink/i',
            '/lipa na mpesa/i',
            '/paybill(?: number)?/i',
            '/till number/i',
            '/wallet (?:credited|debited|top.?up)/i',
            '/(?:equity|kcb|co-?op(?:erative)?|stanbic|absa|ncba|dtb|family bank|national bank)\s+(?:alert|notification|confirmation)/i',
        ];

        foreach (config('complaints.classification.exclude_patterns', []) as $pattern) {
            $patterns[] = $pattern;
        }

        return $patterns;
    }

    protected function isExcludedSender(string $address): bool
    {
        foreach (config('email-service.excluded_sender_domains', []) as $domain) {
            if ($domain && str_ends_with($address, '@' . strtolower($domain))) {
                return true;
            }
        }

        foreach (config('complaints.classification.notification_sender_domains', []) as $domain) {
            if ($domain && str_ends_with($address, '@' . strtolower($domain))) {
                return true;
            }
        }

        foreach ($this->notificationSenderLocalParts() as $localPart) {
            if (str_starts_with($address, $localPart . '@')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function notificationSenderLocalParts(): array
    {
        return array_merge([
            'noreply',
            'no-reply',
            'donotreply',
            'do-not-reply',
            'mailer-daemon',
            'postmaster',
            'gabnet',
            'alerts',
            'alert',
            'notifications',
            'notification',
            'system',
            'automated',
            'billing',
            'payments',
            'payment',
            'finance',
            'treasury',
            'collections',
            'statements',
            'servicing',
            'underwriting',
            'claimsnotify',
            'claims-notification',
            'helpdesk',
            'supportdesk',
            'ithelp',
            'hr',
            'payroll',
        ], config('complaints.classification.notification_sender_local_parts', []));
    }

    protected function detectNotification(string $lower): ?string
    {
        foreach ($this->notificationPatterns() as $pattern => $reason) {
            if (preg_match($pattern, $lower)) {
                return $reason;
            }
        }

        return null;
    }

    protected function hasStrongComplaintSignal(string $lower): bool
    {
        foreach (array_keys($this->strongComplaintPhrases()) as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        foreach (['complaint', 'complain', 'complaining', 'dissatisfied', 'dispute', 'escalate', 'ombudsman'] as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string> pattern => reason
     */
    protected function notificationPatterns(): array
    {
        $patterns = [
            '/approve transaction/i' => 'Transaction approval notification',
            '/transaction\s*(?:#|no\.?|number)\s*[\d,]+(?:\.\d{2})?/i' => 'Transaction reference notification',
            '/(?:kes|ksh\.?)\s*[\d,]+(?:\.\d{2})?\s*(?:debited|credited|transferred|received)/i' => 'Amount transaction notification',
            '/dear customer,\s*date:/i' => 'Automated customer notification',
            '/dear (?:customer|client|policyholder),?\s*(?:your|we|this|please)/i' => 'Automated customer notification',
            '/from:\s*gabnet@/i' => 'GABNet banking notification',
            '/@gab\.co\.ke\b/i' => 'GABNet / banking notification',
            '/transaction (?:alert|notification|confirmation|approved|successful|authorized|authorised)/i' => 'Transaction notification',
            '/payment (?:alert|notification|confirmation|successful|processed)/i' => 'Payment notification',
            '/(?:successfully|has been)\s+(?:processed|completed|transferred|debited|credited|approved)/i' => 'Payment processing notification',
            '/mpesa(?:[\s-]?(?:confirm|receipt|notification|payment|paybill|till))?/i' => 'M-Pesa notification',
            '/pesalink|lipa na mpesa|paybill|till number/i' => 'Mobile money notification',
            '/this is an automated (?:message|email|notification)/i' => 'Automated system message',
            '/please do not reply|do not reply to this (?:email|message)/i' => 'Automated system message',
            '/policy (?:schedule|document|certificate)/i' => 'Policy document notification',
            '/certificate of insurance|endorsement (?:issued|attached)/i' => 'Policy documentation notification',
            '/premium (?:due|statement|invoice|reminder)/i' => 'Premium billing notification',
            '/renewal (?:notice|reminder)/i' => 'Policy renewal notification',
            '/claim (?:acknowledgement|acknowledgment|has been registered|reference number)/i' => 'Claim acknowledgement notification',
            '/maturity (?:notice|proceeds|payout)/i' => 'Maturity notification',
            '/ticket (?:has been|was) (?:updated|closed|resolved)/i' => 'Ticket status notification',
            '/password reset|reset your password/i' => 'Account security notification',
            '/payslip (?:for|available|attached)/i' => 'HR / payroll notification',
            '/(?:equity|kcb|stanbic|absa|ncba|dtb)\s+(?:alert|notification|confirmation)/i' => 'Bank alert notification',
            '/policy (?:schedule|document|certificate) (?:attached|enclosed|available)/i' => 'Policy document notification',
            '/certificate of insurance|endorsement (?:issued|attached)/i' => 'Policy documentation notification',
            '/premium (?:due|statement|invoice|debit note|reminder)/i' => 'Premium billing notification',
            '/renewal (?:notice|reminder|due date)/i' => 'Policy renewal notification',
            '/your policy (?:will expire|has expired|is due for renewal)/i' => 'Policy renewal notification',
            '/claim (?:acknowledgement|acknowledgment|has been registered|reference number)/i' => 'Claim acknowledgement notification',
            '/benefit (?:statement|payment notice|payout notice)/i' => 'Benefit payment notification',
            '/maturity (?:proceeds|payout|benefit)/i' => 'Maturity payout notification',
            '/investment (?:statement|maturity notice|performance report)/i' => 'Investment notification',
            '/direct debit (?:notice|confirmation)/i' => 'Direct debit notification',
            '/standing order (?:notice|confirmation)/i' => 'Standing order notification',
            '/account (?:locked|verification|activation)/i' => 'Account security notification',
            '/case (?:number|ref).*(?:closed|resolved)/i' => 'Case closure notification',
        ];

        foreach (config('complaints.classification.notification_patterns', []) as $pattern => $reason) {
            $patterns[$pattern] = $reason;
        }

        return $patterns;
    }

    /**
     * @return array<int, string>
     */
    protected function inquiryOnlyPhrases(): array
    {
        return [
            'general inquiry',
            'quick question',
            'please advise',
            'kindly assist',
            'request for information',
            'need information',
            'how do i',
            'what is the',
            'can you send me',
            'please send',
            'quote request',
            'premium quote',
        ];
    }

    protected function suggestNature(string $lower): ?string
    {
        if (preg_match('/\b(claim|settlement|payout|benefit)\b/u', $lower)) {
            return str_contains($lower, 'amount') || str_contains($lower, 'underpaid')
                ? 'Settlement amount'
                : (str_contains($lower, 'delay') ? 'Claim delay' : 'Settlement dispute');
        }
        if (preg_match('/\b(premium|billing|debit|payment|overcharge)\b/u', $lower)) {
            return 'Premium billing';
        }
        if (preg_match('/\b(document|certificate|policy document|endorsement)\b/u', $lower)) {
            return 'Documentation';
        }
        if (preg_match('/\b(mis-sold|mis sold|mis-selling|misleading|mis sold)\b/u', $lower)) {
            return 'Product mis-selling';
        }
        if (preg_match('/\b(policy|servicing|renewal|lapse|reinstate)\b/u', $lower)) {
            return 'Policy servicing';
        }

        return null;
    }

    /**
     * @return array{is_complaint: bool, score: int, register_status: string, reason: string, suggested_nature: string|null}
     */
    protected function result(bool $isComplaint, int $score, string $registerStatus, string $reason, ?string $nature): array
    {
        return [
            'is_complaint' => $isComplaint,
            'score' => $score,
            'register_status' => $registerStatus,
            'reason' => $reason,
            'suggested_nature' => $nature,
        ];
    }
}
