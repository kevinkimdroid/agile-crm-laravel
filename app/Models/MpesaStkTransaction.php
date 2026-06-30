<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaStkTransaction extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'policy_number',
        'client_name',
        'phone',
        'amount',
        'account_reference',
        'description',
        'merchant_request_id',
        'checkout_request_id',
        'mpesa_receipt_number',
        'result_code',
        'result_desc',
        'status',
        'user_id',
        'callback_payload',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'callback_payload' => 'array',
        'completed_at' => 'datetime',
    ];

    public function scopeForPolicy($query, string $policyNumber)
    {
        return $query->where('policy_number', trim($policyNumber));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
