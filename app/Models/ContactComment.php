<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContactComment extends Model
{
    protected $table = 'contact_comments';

    protected $fillable = [
        'contact_id',
        'user_id',
        'author_name',
        'body',
        'attachment_path',
        'attachment_name',
    ];

    public function getAuthorDisplayAttribute(): string
    {
        return $this->author_name ?: 'Unknown';
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }
}
