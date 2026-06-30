<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ErpClientConsent extends Model
{
    protected $fillable = [
        'policy_number',
        'consent_granted',
        'consented_at',
        'consented_by_user_id',
        'consented_by_name',
        'updated_by_user_id',
        'updated_by_name',
    ];

    protected $casts = [
        'consent_granted' => 'boolean',
        'consented_at' => 'datetime',
    ];

    public static function tableExists(): bool
    {
        return Schema::hasTable('erp_client_consents');
    }

    public static function forPolicy(string $policyNumber): ?self
    {
        $policy = trim($policyNumber);
        if ($policy === '' || ! self::tableExists()) {
            return null;
        }

        return self::query()->where('policy_number', $policy)->first();
    }

    /**
     * @param  object|null  $user  Authenticated vtiger user
     */
    public static function setForPolicy(string $policyNumber, bool $granted, ?object $user = null): self
    {
        if (! self::tableExists()) {
            throw new \RuntimeException('Client consent storage is not available. Run database migrations.');
        }

        $policy = trim($policyNumber);
        $userId = $user && isset($user->id) ? (int) $user->id : null;
        $userName = self::resolveUserName($user);

        $record = self::query()->firstOrNew(['policy_number' => $policy]);
        $record->consent_granted = $granted;
        $record->updated_by_user_id = $userId;
        $record->updated_by_name = $userName;

        if ($granted) {
            $record->consented_at = now();
            $record->consented_by_user_id = $userId;
            $record->consented_by_name = $userName;
        } else {
            $record->consented_at = null;
            $record->consented_by_user_id = null;
            $record->consented_by_name = null;
        }

        $record->save();

        return $record;
    }

    protected static function resolveUserName(?object $user): ?string
    {
        if (! $user) {
            return null;
        }

        foreach (['user_name', 'userName', 'name', 'email1', 'email'] as $key) {
            $value = trim((string) ($user->{$key} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return isset($user->id) ? 'User #' . $user->id : null;
    }
}
