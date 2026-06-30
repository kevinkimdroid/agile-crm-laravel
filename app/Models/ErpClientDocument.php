<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ErpClientDocument extends Model
{
    protected $fillable = [
        'policy_number',
        'title',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
        'uploaded_by_name',
    ];

    public static function tableExists(): bool
    {
        return Schema::hasTable('erp_client_documents');
    }

    public function getDisplayTitleAttribute(): string
    {
        $title = trim((string) ($this->title ?? ''));

        return $title !== '' ? $title : ($this->original_filename ?? 'Document');
    }

    public function getUploadedByDisplayAttribute(): string
    {
        return trim((string) ($this->uploaded_by_name ?? '')) ?: 'Staff';
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getIsPreviewableAttribute(): bool
    {
        $mime = strtolower((string) ($this->mime_type ?? ''));

        return str_starts_with($mime, 'image/')
            || $mime === 'application/pdf';
    }

    public function getPublicUrlAttribute(): ?string
    {
        if (! $this->storage_path || ! Storage::disk('public')->exists($this->storage_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->storage_path);
    }

    /**
     * @return Collection<int, self>
     */
    public static function forPolicy(string $policyNumber, int $limit = 50): Collection
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
