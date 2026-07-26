<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'carried_over_days',
        'manual_adjustment_days',
        'adjustment_note',
    ];

    protected function casts(): array
    {
        return [
            'carried_over_days' => 'decimal:2',
            'manual_adjustment_days' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public static function forEmployee(Employee $employee, LeaveType $leaveType, int $year): self
    {
        return static::firstOrCreate([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
        ]);
    }

    /**
     * Days accrued so far this year, prorated in whole months from the later of
     * (hire date, start of year) up to the earlier of (today, end of year).
     * Returns 0 for leave types without automatic accrual (sick leave, unpaid, etc.).
     */
    public function accruedDays(): float
    {
        if (! $this->leaveType->accruesAutomatically()) {
            return 0.0;
        }

        $yearStart = Carbon::create($this->year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($this->year, 12, 31)->endOfDay();

        $hireDate = $this->employee->hire_date;
        $periodStart = ($hireDate && $hireDate->greaterThan($yearStart)) ? $hireDate->copy()->startOfDay() : $yearStart;
        $periodEnd = now()->lessThan($yearEnd) ? now() : $yearEnd;

        if ($periodEnd->lessThan($periodStart)) {
            return 0.0;
        }

        // Whole calendar months touched by the period, inclusive (e.g. Jan 1 to Jul 15 = 7).
        // Carbon's diffInMonths() can return a fractional value depending on version, which
        // would silently corrupt this calculation, so plain integer arithmetic is used instead.
        $months = min(12, ($periodEnd->year - $periodStart->year) * 12 + ($periodEnd->month - $periodStart->month) + 1);

        return round($months * (float) $this->leaveType->accrual_days_per_month, 2);
    }

    public function usedDays(): float
    {
        return (float) $this->employee->leaveRequests()
            ->where('leave_type_id', $this->leave_type_id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $this->year)
            ->sum('days_count');
    }

    public function pendingDays(): float
    {
        return (float) $this->employee->leaveRequests()
            ->where('leave_type_id', $this->leave_type_id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereYear('start_date', $this->year)
            ->sum('days_count');
    }

    public function availableDays(): float
    {
        return round(
            (float) $this->carried_over_days
            + $this->accruedDays()
            + (float) $this->manual_adjustment_days
            - $this->usedDays(),
            2
        );
    }
}
