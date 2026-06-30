<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UserClientAssignment extends Model
{
    protected $table = 'agile_user_client_assignments';

    protected $fillable = [
        'userid',
        'policy_number',
        'client_label',
        'system',
        'assigned_by',
    ];

    protected $casts = [
        'userid' => 'integer',
        'assigned_by' => 'integer',
    ];

    public static function tableExists(): bool
    {
        return Schema::hasTable('agile_user_client_assignments');
    }

    public static function normalizePolicyNumber(?string $policy): string
    {
        return strtoupper(trim((string) $policy));
    }
}
