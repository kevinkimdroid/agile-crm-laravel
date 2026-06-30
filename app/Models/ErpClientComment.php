<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ErpClientComment extends Model
{
    protected $fillable = [
        'policy_number',
        'user_id',
        'author_name',
        'body',
    ];

    public static function tableExists(): bool
    {
        return Schema::hasTable('erp_client_comments');
    }

    public function getAuthorDisplayAttribute(): string
    {
        return trim((string) ($this->author_name ?? '')) ?: 'Staff';
    }

    /**
     * @return Collection<int, self>
     */
    public static function forPolicy(string $policyNumber, int $limit = 30): Collection
    {
        $policy = trim($policyNumber);
        if ($policy === '' || ! self::tableExists()) {
            return collect();
        }

        return self::query()
            ->where('policy_number', $policy)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
