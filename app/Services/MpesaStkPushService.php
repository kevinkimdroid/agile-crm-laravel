<?php

namespace App\Services;

use App\Models\MpesaStkTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaStkPushService
{
    public function isSandboxSimulate(): bool
    {
        if (! config('mpesa.enabled', false)) {
            return false;
        }

        if (config('mpesa.environment') !== 'sandbox') {
            return false;
        }

        return (bool) config('mpesa.sandbox_simulate', false);
    }

    public function isConfigured(): bool
    {
        return $this->configurationError() === null;
    }

    public function isLive(): bool
    {
        return config('mpesa.enabled', false)
            && ! $this->isSandboxSimulate()
            && $this->isConfigured();
    }

    public function configurationError(): ?string
    {
        if ($this->isSandboxSimulate()) {
            return null;
        }

        if (! config('mpesa.enabled', false) || ! $this->hasDarajaCredentials()) {
            return self::userUnavailableMessage();
        }

        return null;
    }

    public static function userUnavailableMessage(): string
    {
        return 'M-Pesa payments are not available at the moment. Please use another payment channel or contact support.';
    }

    /** Internal operator hint — not for end-user UI. */
    public static function setupInstructions(): string
    {
        return 'Do the setup. Add MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_PASSKEY, MPESA_SHORTCODE from the Safaricom Daraja portal, then run php artisan config:clear.';
    }

    protected function hasDarajaCredentials(): bool
    {
        return trim((string) config('mpesa.consumer_key')) !== ''
            && trim((string) config('mpesa.consumer_secret')) !== ''
            && trim((string) config('mpesa.passkey')) !== ''
            && trim((string) config('mpesa.shortcode')) !== '';
    }

    public function callbackUrl(): string
    {
        $configured = trim((string) config('mpesa.callback_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return rtrim((string) config('app.url'), '/').'/webhooks/mpesa/stk-callback';
    }

    /**
     * @return array{ok: bool, transaction: ?MpesaStkTransaction, error: ?string}
     */
    public function initiate(
        string $policyNumber,
        string $phone,
        float $amount,
        ?string $clientName,
        ?string $description,
        ?int $userId
    ): array {
        if (! $this->isConfigured()) {
            Log::warning('M-Pesa STK push unavailable', ['setup' => self::setupInstructions()]);

            return [
                'ok' => false,
                'transaction' => null,
                'error' => self::userUnavailableMessage(),
            ];
        }

        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            $phoneHint = $this->isSandboxSimulate()
                ? 'Enter a phone number with at least 7 digits.'
                : 'A valid Kenyan mobile number is required (e.g. 07xx xxx xxx).';

            return ['ok' => false, 'transaction' => null, 'error' => $phoneHint];
        }

        if ($this->isSandboxSimulate()) {
            return $this->initiateSimulated(
                $policyNumber,
                $phone,
                $amount,
                $clientName,
                $description,
                $userId
            );
        }

        $amount = round($amount, 0);
        if ($amount < 1) {
            return ['ok' => false, 'transaction' => null, 'error' => 'Amount must be at least KES 1.'];
        }

        $policyNumber = trim($policyNumber);
        $accountReference = $this->accountReference($policyNumber);
        $description = $this->truncate($description ?: (string) config('mpesa.default_description', 'Premium payment'), 100);

        $transaction = MpesaStkTransaction::create([
            'policy_number' => $policyNumber,
            'client_name' => $clientName !== null ? trim($clientName) : null,
            'phone' => $phone,
            'amount' => $amount,
            'account_reference' => $accountReference,
            'description' => $description,
            'status' => MpesaStkTransaction::STATUS_PENDING,
            'user_id' => $userId,
        ]);

        try {
            $token = $this->accessToken();
            $timestamp = now()->format('YmdHis');
            $shortcode = trim((string) config('mpesa.shortcode'));
            $password = base64_encode($shortcode.config('mpesa.passkey').$timestamp);

            $response = Http::withToken($token)
                ->withOptions(['connect_timeout' => (int) config('mpesa.connect_timeout', 5)])
                ->timeout((int) config('mpesa.http_timeout', 30))
                ->post($this->stkPushUrl(), [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => (string) config('mpesa.transaction_type', 'CustomerPayBillOnline'),
                    'Amount' => (int) $amount,
                    'PartyA' => $phone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->callbackUrl(),
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => $this->truncate($description, 13),
                ]);

            $body = $response->json();
            if (! $response->successful() || ! is_array($body)) {
                $detail = is_array($body)
                    ? (string) ($body['errorMessage'] ?? $body['errorCode'] ?? json_encode($body))
                    : trim($response->body());
                Log::error('M-Pesa STK push HTTP error', [
                    'policy' => $policyNumber,
                    'status' => $response->status(),
                    'body' => $detail,
                ]);
                $transaction->update([
                    'status' => MpesaStkTransaction::STATUS_FAILED,
                    'result_desc' => 'STK request failed (HTTP '.$response->status().')',
                    'completed_at' => now(),
                ]);

                return [
                    'ok' => false,
                    'transaction' => $transaction,
                    'error' => 'M-Pesa could not start the payment prompt. '.$detail,
                ];
            }

            $responseCode = (string) ($body['ResponseCode'] ?? '');
            $responseDescription = (string) ($body['ResponseDescription'] ?? 'Unknown M-Pesa response');

            if ($responseCode !== '0') {
                $transaction->update([
                    'status' => MpesaStkTransaction::STATUS_FAILED,
                    'result_code' => is_numeric($responseCode) ? (int) $responseCode : null,
                    'result_desc' => $responseDescription,
                    'completed_at' => now(),
                ]);

                return ['ok' => false, 'transaction' => $transaction, 'error' => $responseDescription];
            }

            $transaction->update([
                'merchant_request_id' => $body['MerchantRequestID'] ?? null,
                'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
            ]);

            return ['ok' => true, 'transaction' => $transaction->fresh(), 'error' => null];
        } catch (\Throwable $e) {
            Log::error('M-Pesa STK push failed', ['policy' => $policyNumber, 'error' => $e->getMessage()]);
            $transaction->update([
                'status' => MpesaStkTransaction::STATUS_FAILED,
                'result_desc' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return ['ok' => false, 'transaction' => $transaction, 'error' => 'M-Pesa request failed: '.$e->getMessage()];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleCallback(array $payload): void
    {
        $callback = data_get($payload, 'Body.stkCallback');
        if (! is_array($callback)) {
            return;
        }

        $checkoutRequestId = (string) ($callback['CheckoutRequestID'] ?? '');
        if ($checkoutRequestId === '') {
            return;
        }

        $transaction = MpesaStkTransaction::query()
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();

        if (! $transaction) {
            Log::warning('M-Pesa callback for unknown checkout request', ['checkout_request_id' => $checkoutRequestId]);

            return;
        }

        $resultCode = (int) ($callback['ResultCode'] ?? -1);
        $resultDesc = (string) ($callback['ResultDesc'] ?? '');
        $metadata = $this->parseCallbackMetadata($callback['CallbackMetadata']['Item'] ?? []);

        $transaction->update([
            'result_code' => $resultCode,
            'result_desc' => $resultDesc !== '' ? $resultDesc : null,
            'mpesa_receipt_number' => $metadata['MpesaReceiptNumber'] ?? $transaction->mpesa_receipt_number,
            'status' => $resultCode === 0 ? MpesaStkTransaction::STATUS_SUCCESS : $this->statusFromResultCode($resultCode),
            'callback_payload' => $payload,
            'completed_at' => now(),
        ]);
    }

    public function syncPendingTransaction(MpesaStkTransaction $transaction): MpesaStkTransaction
    {
        if (! $transaction->isPending() || $this->isSandboxSimulate() || ! $this->isConfigured()) {
            return $transaction;
        }

        $checkoutRequestId = trim((string) $transaction->checkout_request_id);
        if ($checkoutRequestId === '') {
            return $transaction;
        }

        try {
            $token = $this->accessToken();
            $timestamp = now()->format('YmdHis');
            $shortcode = trim((string) config('mpesa.shortcode'));
            $password = base64_encode($shortcode.config('mpesa.passkey').$timestamp);

            $response = Http::withToken($token)
                ->withOptions(['connect_timeout' => (int) config('mpesa.connect_timeout', 5)])
                ->timeout((int) config('mpesa.http_timeout', 30))
                ->post($this->stkQueryUrl(), [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            $body = $response->json();
            if (! $response->successful() || ! is_array($body) || ! array_key_exists('ResultCode', $body)) {
                return $transaction;
            }

            $resultCode = (int) $body['ResultCode'];
            $resultDesc = (string) ($body['ResultDesc'] ?? '');

            if ($resultCode === 1037 || $resultDesc === 'DS timeout user cannot be reached.') {
                return $transaction;
            }

            $transaction->update([
                'result_code' => $resultCode,
                'result_desc' => $resultDesc !== '' ? $resultDesc : null,
                'status' => $resultCode === 0
                    ? MpesaStkTransaction::STATUS_SUCCESS
                    : $this->statusFromResultCode($resultCode),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('M-Pesa STK query failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $transaction->fresh();
    }

    public function normalizePhone(string $phone): string
    {
        if ($this->isSandboxSimulate()) {
            return $this->normalizePhoneSandbox($phone);
        }

        $phone = preg_replace('/\D/', '', trim($phone));
        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '254'.substr($phone, 1);
        } elseif (str_starts_with($phone, '7') && strlen($phone) === 9) {
            $phone = '254'.$phone;
        } elseif (str_starts_with($phone, '254') && strlen($phone) === 12) {
            // ok
        } elseif (str_starts_with($phone, '254') && strlen($phone) > 12) {
            $phone = substr($phone, 0, 12);
        } else {
            return '';
        }

        return preg_match('/^2547\d{8}$/', $phone) ? $phone : '';
    }

    protected function normalizePhoneSandbox(string $phone): string
    {
        $digits = preg_replace('/\D/', '', trim($phone));
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = '254'.ltrim($digits, '0');
        } elseif (! str_starts_with($digits, '254') && strlen($digits) <= 10) {
            $digits = '254'.$digits;
        }

        return substr($digits, 0, 15);
    }

    /**
     * @return array{ok: bool, transaction: ?MpesaStkTransaction, error: ?string}
     */
    protected function initiateSimulated(
        string $policyNumber,
        string $phone,
        float $amount,
        ?string $clientName,
        ?string $description,
        ?int $userId
    ): array {
        $amount = round($amount, 0);
        if ($amount < 1) {
            return ['ok' => false, 'transaction' => null, 'error' => 'Amount must be at least KES 1.'];
        }

        $policyNumber = trim($policyNumber);
        $accountReference = $this->accountReference($policyNumber);
        $description = $this->truncate($description ?: (string) config('mpesa.default_description', 'Premium payment'), 100);
        $receipt = 'SB'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $transaction = MpesaStkTransaction::create([
            'policy_number' => $policyNumber,
            'client_name' => $clientName !== null ? trim($clientName) : null,
            'phone' => $phone,
            'amount' => $amount,
            'account_reference' => $accountReference,
            'description' => $description,
            'merchant_request_id' => 'sandbox_'.uniqid(),
            'checkout_request_id' => 'ws_CO_'.strtoupper(bin2hex(random_bytes(8))),
            'mpesa_receipt_number' => $receipt,
            'result_code' => 0,
            'result_desc' => 'The service request is processed successfully. (sandbox simulate)',
            'status' => MpesaStkTransaction::STATUS_SUCCESS,
            'user_id' => $userId,
            'callback_payload' => ['sandbox_simulate' => true],
            'completed_at' => now(),
        ]);

        Log::info('M-Pesa STK sandbox simulate', [
            'policy' => $policyNumber,
            'phone' => $phone,
            'amount' => $amount,
            'receipt' => $receipt,
        ]);

        return ['ok' => true, 'transaction' => $transaction, 'error' => null];
    }

    protected function accessToken(): string
    {
        $cacheKey = 'mpesa_oauth_token_'.sha1((string) config('mpesa.environment'));

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            $response = Http::withBasicAuth(
                (string) config('mpesa.consumer_key'),
                (string) config('mpesa.consumer_secret')
            )
                ->withOptions(['connect_timeout' => (int) config('mpesa.connect_timeout', 5)])
                ->timeout((int) config('mpesa.http_timeout', 30))
                ->get($this->oauthUrl(), ['grant_type' => 'client_credentials']);

            if (! $response->successful()) {
                throw new \RuntimeException('M-Pesa OAuth failed (HTTP '.$response->status().').');
            }

            $token = (string) ($response->json('access_token') ?? '');
            if ($token === '') {
                throw new \RuntimeException('M-Pesa OAuth returned no access token.');
            }

            return $token;
        });
    }

    protected function oauthUrl(): string
    {
        $configured = trim((string) config('mpesa.oauth_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return config('mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke/oauth/v1/generate'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate';
    }

    protected function stkPushUrl(): string
    {
        $configured = trim((string) config('mpesa.stk_push_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return config('mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    }

    protected function stkQueryUrl(): string
    {
        $configured = trim((string) config('mpesa.stk_query_url', ''));
        if ($configured !== '') {
            return $configured;
        }

        return config('mpesa.environment') === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
    }

    protected function accountReference(string $policyNumber): string
    {
        $policyNumber = strtoupper(preg_replace('/\s+/', '', $policyNumber));

        return $this->truncate($policyNumber !== '' ? $policyNumber : 'POLICY', 12);
    }

    protected function truncate(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    protected function parseCallbackMetadata(array $items): array
    {
        $metadata = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $name = (string) ($item['Name'] ?? '');
            if ($name !== '') {
                $metadata[$name] = $item['Value'] ?? null;
            }
        }

        return $metadata;
    }

    protected function statusFromResultCode(int $resultCode): string
    {
        if ($resultCode === 1032) {
            return MpesaStkTransaction::STATUS_CANCELLED;
        }

        return MpesaStkTransaction::STATUS_FAILED;
    }
}
