<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles admin-initiated password resets and user management.
 */
class UserManagementService
{
    protected int $tokenExpiryMinutes = 60;

    protected function tokensConnection(): string
    {
        return config('services.password_reset.connection') ?? config('database.default');
    }

    /**
     * Generate a password reset token for a user and send the reset email.
     *
     * @param  bool  $isNewAccount  Welcome / first-time password setup (e.g. after admin creates the user).
     */
    public function sendPasswordResetEmail(object $user, bool $isNewAccount = false): bool
    {
        $email = trim($user->email1 ?? '');
        if ($email === '') {
            Log::warning('UserManagementService: user has no email', ['user_id' => $user->id ?? null]);

            return false;
        }

        Log::info('UserManagementService: sending setup/reset email', [
            'to' => $email,
            'user_id' => $user->id ?? null,
            'new_account' => $isNewAccount,
        ]);

        $token = Str::random(64);
        $hashedToken = hash('sha256', $token);

        try {
            DB::connection($this->tokensConnection())
                ->table('password_reset_tokens')
                ->updateOrInsert(
                    ['email' => $email],
                    [
                        'token' => $hashedToken,
                        'created_at' => now(),
                    ]
                );
        } catch (\Throwable $e) {
            Log::error('UserManagementService: failed to store reset token', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->user_name;
        $loginUsername = $user->user_name ?? '';
        $baseUrl = rtrim(config('services.password_reset.base_url') ?? config('app.url', ''), '/');
        $resetUrl = $baseUrl . '/password/reset?token=' . urlencode($token) . '&email=' . urlencode($email);
        $brandName = config('branding.client_name', config('app.name', 'CRM'));
        $brandShort = config('branding.client_short', 'Kenya Orient');
        $appName = $brandShort . ' CRM';

        if ($isNewAccount) {
            $subject = $brandShort . ' CRM — set your password';
            $body = "Hello {$displayName},\n\n"
                . "An account has been created for you on {$appName}. "
                . "Your username for sign-in is: {$loginUsername}\n\n"
                . "Use the link below to choose your password:\n\n"
                . "{$resetUrl}\n\n"
                . "This link expires in {$this->tokenExpiryMinutes} minutes. "
                . "If you were not expecting this message, contact your administrator.\n\n"
                . "Kind regards,\n{$brandName}";
        } else {
            $subject = $brandShort . ' CRM — password reset';
            $body = "Hello {$displayName},\n\n"
                . "A password reset was requested for your {$appName} account ({$email}).\n\n"
                . "Set a new password using this link (expires in {$this->tokenExpiryMinutes} minutes):\n\n"
                . "{$resetUrl}\n\n"
                . "If you did not request this, ignore this email. Your password will not change.\n\n"
                . "Kind regards,\n{$brandName}";
        }

        $html = view('emails.password-reset', [
            'subject' => $subject,
            'displayName' => $displayName,
            'loginUsername' => $loginUsername,
            'email' => $email,
            'resetUrl' => $resetUrl,
            'expiryMinutes' => $this->tokenExpiryMinutes,
            'isNewAccount' => $isNewAccount,
            'brandName' => $brandName,
            'brandShort' => $brandShort,
        ])->render();

        return $this->send($email, $displayName, $subject, $body, $html);
    }

    /**
     * Verify a password reset token and return the email if valid.
     */
    public function verifyToken(string $token, string $email): bool
    {
        $conn = $this->tokensConnection();
        $record = DB::connection($conn)
            ->table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record || ! hash_equals($record->token, hash('sha256', $token))) {
            return false;
        }

        $createdAt = $record->created_at ? \Carbon\Carbon::parse($record->created_at) : null;
        if ($createdAt && $createdAt->addMinutes($this->tokenExpiryMinutes)->isPast()) {
            DB::connection($conn)->table('password_reset_tokens')->where('email', $email)->delete();
            return false;
        }

        return true;
    }

    /**
     * Update user password and delete reset token.
     */
    public function resetPassword(string $email, string $password): bool
    {
        DB::connection('vtiger')
            ->table('vtiger_users')
            ->where('email1', $email)
            ->update([
                'user_password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

        DB::connection($this->tokensConnection())
            ->table('password_reset_tokens')
            ->where('email', $email)
            ->delete();
        return true;
    }

    protected function send(string $to, ?string $toName, string $subject, string $body, ?string $html = null): bool
    {
        $sender = app(PlainTextMailSender::class);
        if ($sender->sendForAuth($to, $toName, $subject, $body, $html)) {
            Log::info('UserManagementService: setup/reset email sent', [
                'to' => $to,
                'subject' => $subject,
            ]);
            return true;
        }

        Log::warning('UserManagementService: send failed', [
            'to' => $to,
            'error' => $sender->getLastError(),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_encryption' => config('mail.mailers.smtp.encryption'),
        ]);

        return false;
    }
}
