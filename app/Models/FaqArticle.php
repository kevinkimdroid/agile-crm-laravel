<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqArticle extends Model
{
    use HasFactory;

    public const STATUSES = [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'faq_category_id',
        'question',
        'answer',
        'tags',
        'status',
        'views',
        'helpful_count',
        'created_by',
        'created_by_name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    /** @return array<int, string> */
    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
