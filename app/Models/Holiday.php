<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function isHoliday(Carbon $date): bool
    {
        return static::whereDate('date', $date->toDateString())->exists();
    }

    /**
     * Count of holidays falling on a weekday within [start, end] inclusive —
     * weekend holidays don't reduce a work schedule that's already off then.
     */
    public static function weekdayCountBetween(Carbon $start, Carbon $end): int
    {
        return static::whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->filter(fn (Holiday $holiday) => ! $holiday->date->isWeekend())
            ->count();
    }
}
