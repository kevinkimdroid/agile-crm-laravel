<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('agents');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Label used in dropdowns, e.g. "Grace Njeri (AG-1001)". */
    public function label(): string
    {
        return $this->code ? $this->name.' ('.$this->code.')' : $this->name;
    }

    /**
     * Active agents ready for a select dropdown. Returns empty if the table is absent.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function forDropdown()
    {
        if (! static::tableExists()) {
            return collect();
        }

        try {
            return static::query()->active()->orderBy('name')->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }
}
