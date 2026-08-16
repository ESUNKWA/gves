<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    public const SOURCE_SELF = 'self';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_in_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out',
        'clock_out_ip',
        'clock_out_latitude',
        'clock_out_longitude',
        'source',
        'corrected_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_out' => 'datetime',
            'clock_out_latitude' => 'decimal:7',
            'clock_out_longitude' => 'decimal:7',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function isComplete(): bool
    {
        return (bool) ($this->clock_in && $this->clock_out);
    }

    public function workedMinutes(): ?int
    {
        return $this->isComplete() ? $this->clock_in->diffInMinutes($this->clock_out) : null;
    }

    /**
     * Minutes late relative to the employee's expected start time for this
     * day, or 0 if there's no schedule for the day, no clock-in yet, or the
     * employee arrived on time or early.
     */
    public function lateMinutes(?WorkSchedule $schedule): int
    {
        if (! $this->clock_in || ! $schedule) {
            return 0;
        }

        $range = $schedule->rangeFor($this->date);

        if (! $range) {
            return 0;
        }

        $expectedStart = Carbon::parse($this->date->toDateString().' '.$range['start']);

        return $this->clock_in->greaterThan($expectedStart) ? $expectedStart->diffInMinutes($this->clock_in) : 0;
    }

    /**
     * Minutes worked beyond the expected daily duration under the employee's
     * schedule, or 0 if incomplete or there's no schedule for the day.
     */
    public function overtimeMinutes(?WorkSchedule $schedule): int
    {
        if (! $this->isComplete() || ! $schedule) {
            return 0;
        }

        $range = $schedule->rangeFor($this->date);

        if (! $range) {
            return 0;
        }

        $expectedMinutes = Carbon::parse($range['start'])->diffInMinutes(Carbon::parse($range['end']));

        return max(0, $this->workedMinutes() - $expectedMinutes);
    }
}
