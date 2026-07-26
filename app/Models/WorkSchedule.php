<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public static function dayLabels(): array
    {
        return [
            'monday' => 'Lundi',
            'tuesday' => 'Mardi',
            'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi',
            'friday' => 'Vendredi',
            'saturday' => 'Samedi',
            'sunday' => 'Dimanche',
        ];
    }

    protected $fillable = [
        'employee_id',
        'monday_start', 'monday_end',
        'tuesday_start', 'tuesday_end',
        'wednesday_start', 'wednesday_end',
        'thursday_start', 'thursday_end',
        'friday_start', 'friday_end',
        'saturday_start', 'saturday_end',
        'sunday_start', 'sunday_end',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * The expected start/end time-of-day for a given date, or null if that
     * weekday isn't a work day under this schedule.
     */
    public function rangeFor(Carbon $date): ?array
    {
        $day = strtolower($date->format('l'));
        $start = $this->{"{$day}_start"};
        $end = $this->{"{$day}_end"};

        if (! $start || ! $end) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Average monthly contractual hours implied by this weekly schedule
     * (weekly hours × 52 weeks / 12 months) — the standard way payslips in
     * many countries express a monthly-paid employee's contractual hours.
     */
    public function monthlyContractualHours(): float
    {
        $weeklyMinutes = 0;

        foreach (self::DAYS as $day) {
            $start = $this->{"{$day}_start"};
            $end = $this->{"{$day}_end"};

            if ($start && $end) {
                $weeklyMinutes += Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
            }
        }

        return round(($weeklyMinutes / 60) * 52 / 12, 2);
    }
}
