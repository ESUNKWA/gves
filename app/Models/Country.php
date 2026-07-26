<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Country extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Names selectable in a <select>: active countries, plus $current even if
     * it has since been deactivated (or is a legacy free-text value from
     * before this list existed) so existing records don't lose their value.
     */
    public static function options(?string $current = null): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->when($current, fn ($q) => $q->orWhere('name', $current))
            ->orderBy('name')
            ->pluck('name');
    }
}
