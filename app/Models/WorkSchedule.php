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

    /**
     * The company-wide schedule (employee_id null), applied to every employee
     * that has no schedule of their own — Employee::effectiveWorkSchedule()
     * is what falls back to this. Memoized via the container rather than a
     * plain static property: several call sites (payroll runs, attendance
     * reports) call this once per time entry, and it never changes
     * mid-request — but a raw static would leak the instance from one
     * request/test into the next (each PHPUnit test gets a fresh tenant
     * database but the same PHP process), silently updating a row that no
     * longer exists in the new connection.
     */
    public static function default(): self
    {
        if (! app()->bound('work-schedule.default')) {
            app()->instance('work-schedule.default', static::firstOrCreate(['employee_id' => null]));
        }

        return app('work-schedule.default');
    }

    public function isDefault(): bool
    {
        return $this->employee_id === null;
    }

    /**
     * Whether at least one day has both a start and end time — false for a
     * schedule that exists as a row (e.g. WorkSchedule::default() always
     * firstOrCreate()s one) but was never actually filled in.
     */
    public function hasAnyDayDefined(): bool
    {
        foreach (self::DAYS as $day) {
            if ($this->{"{$day}_start"} && $this->{"{$day}_end"}) {
                return true;
            }
        }

        return false;
    }

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
