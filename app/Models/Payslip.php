<?php

namespace App\Models;

use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Payslip extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VALIDATED = 'validated';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_VALIDATED => 'Validé',
        ];
    }

    protected $fillable = [
        'employee_id',
        'reference',
        'period',
        'gross_amount',
        'deductions_amount',
        'employer_charges_amount',
        'net_amount',
        'status',
        'pdf_path',
        'generated_by',
        'validated_by',
        'validated_at',
        'payment_method',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'gross_amount' => 'decimal:2',
            'deductions_amount' => 'decimal:2',
            'employer_charges_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'validated_at' => 'datetime',
            'payment_date' => 'date',
        ];
    }

    public function periodLabel(): string
    {
        return $this->period->translatedFormat('F Y');
    }

    public function employerCost(): float
    {
        return round((float) $this->gross_amount + (float) $this->employer_charges_amount, 2);
    }

    /**
     * Gross gains subject to social/tax contributions — excludes gain lines
     * whose component is flagged "is_subject_to_contributions = false" (e.g.
     * a non-taxable transport allowance), so it can differ from total gross.
     */
    public function totalSubjectToContributions(): float
    {
        return round(
            (float) $this->lines
                ->where('type', PayrollComponent::TYPE_GAIN)
                ->filter(fn (PayslipLine $line) => $line->payrollComponent?->is_subject_to_contributions ?? true)
                ->sum('amount'),
            2
        );
    }

    public function verificationUrl(): string
    {
        return route('verification.payslip', $this->reference);
    }

    /**
     * A scannable QR code encoding this payslip's public verification URL, as
     * a base64 data URI for direct embedding in the PDF.
     */
    public function qrCodeDataUri(): string
    {
        $qrCode = new QrCode($this->verificationUrl());
        $writer = new PngWriter;

        return $writer->write($qrCode)->getDataUri();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * (Re)compute a draft payslip for one employee/period from their active pay
     * structure. Gains are computed first so percentage-of-component and
     * percentage-of-gross deductions/employer charges can reference final
     * values. Manually added lines (payroll_component_id null) are left
     * untouched — only lines tied to a component are replaced.
     *
     * If the employee's latest contract negotiates a NET salary
     * (Contract::SALARY_MODE_NET), the base salary gain is solved backwards
     * so that gross - deductions lands exactly on that target net, however
     * the deduction rules are configured — see solveGrossForNet().
     */
    public static function generateFor(Employee $employee, Carbon $period, ?int $generatedBy = null): self
    {
        $period = $period->copy()->startOfMonth();

        $assignments = static::activeAssignments($employee);

        $baseSalaryOverride = null;
        $contract = $employee->latestContract;

        if ($contract
            && $contract->salary_mode === Contract::SALARY_MODE_NET
            && $contract->net_salary_target !== null
            && $assignments->contains(fn (EmployeePayComponent $a) => $a->payrollComponent->is_base_salary)
        ) {
            $baseSalaryOverride = static::solveGrossForNetUsingAssignments($assignments, (float) $contract->net_salary_target);
        }

        // A plain firstOrNew(['period' => ...]) would compare against the raw
        // stored value, which Eloquent's date cast serializes as a full
        // "Y-m-d H:i:s" datetime rather than a bare date — whereDate() handles
        // that mismatch correctly regardless of stored precision.
        $payslip = static::where('employee_id', $employee->id)
            ->whereDate('period', $period->toDateString())
            ->first() ?? new static([
                'employee_id' => $employee->id,
                'period' => $period->toDateString(),
                'reference' => 'BUL-'.$period->format('Ym').'-'.str_pad((string) $employee->id, 5, '0', STR_PAD_LEFT),
            ]);

        abort_if($payslip->exists && $payslip->status === self::STATUS_VALIDATED, 400, 'Ce bulletin est déjà validé et ne peut plus être recalculé.');

        [$values, $gross, $deductions, $employerCharges] = static::computeAmounts($assignments, $baseSalaryOverride);

        if ($baseSalaryOverride !== null) {
            // Keep the assigned "Salaire de base" rubrique and the contract's
            // displayed gross in sync with the resolved value, so both remain
            // accurate outside of payslip generation too (payroll tab,
            // document templates using {{contrat.salaire_base}}).
            $baseAssignment = $assignments->first(fn (EmployeePayComponent $a) => $a->payrollComponent->is_base_salary);
            $baseAssignment->update(['amount' => round($baseSalaryOverride, 2)]);
            $contract->update(['base_salary' => round($baseSalaryOverride, 2)]);
        }

        $payslip->fill([
            'status' => self::STATUS_DRAFT,
            'generated_by' => $generatedBy,
        ]);
        $payslip->save();

        $payslip->lines()->whereNotNull('payroll_component_id')->delete();

        foreach ($assignments as $assignment) {
            $component = $assignment->payrollComponent;
            $payslip->lines()->create([
                'payroll_component_id' => $component->id,
                'label' => $component->name,
                // "Nombre" traditionally shows the period's day count against
                // gain lines (salary paid for N days this month) and is left
                // blank for deduction/employer-charge lines, matching common
                // payslip conventions.
                'quantity' => $component->type === PayrollComponent::TYPE_GAIN ? $period->daysInMonth : null,
                'base_amount' => $component->baseAmountFor($values, $gross),
                'type' => $component->type,
                'amount' => $values[$component->id] ?? 0,
            ]);
        }

        $manualSum = fn (string $type) => (float) $payslip->lines()
            ->whereNull('payroll_component_id')
            ->where('type', $type)
            ->sum('amount');

        $manualGains = $manualSum(PayrollComponent::TYPE_GAIN);
        $manualDeductions = $manualSum(PayrollComponent::TYPE_DEDUCTION);
        $manualEmployerCharges = $manualSum(PayrollComponent::TYPE_EMPLOYER_CHARGE);

        $payslip->update([
            'gross_amount' => round($gross + $manualGains, 2),
            'deductions_amount' => round($deductions + $manualDeductions, 2),
            'employer_charges_amount' => round($employerCharges + $manualEmployerCharges, 2),
            'net_amount' => round($gross + $manualGains - $deductions - $manualDeductions, 2),
        ]);

        return $payslip;
    }

    /**
     * An employee's currently active, in-order pay component assignments —
     * the same set generateFor() builds a payslip from.
     */
    private static function activeAssignments(Employee $employee): Collection
    {
        return $employee->payComponents()
            ->where('is_active', true)
            ->with('payrollComponent')
            ->get()
            ->filter(fn (EmployeePayComponent $a) => $a->payrollComponent && $a->payrollComponent->is_active)
            ->sortBy(fn (EmployeePayComponent $a) => $a->payrollComponent->order)
            ->values();
    }

    /**
     * Pure (non-persisting) two-pass computation of gain/deduction/employer-
     * charge amounts for a set of assignments — the same logic generateFor()
     * applies, factored out so it can also be run repeatedly, in-memory, by
     * solveGrossForNetUsingAssignments() without touching the database.
     *
     * @return array{0: array<int, float>, 1: float, 2: float, 3: float} [values, gross, deductions, employerCharges]
     */
    private static function computeAmounts(Collection $assignments, ?float $baseSalaryOverride = null): array
    {
        $values = [];
        $gross = 0.0;

        foreach ($assignments as $assignment) {
            if ($assignment->payrollComponent->type !== PayrollComponent::TYPE_GAIN) {
                continue;
            }

            $amount = $baseSalaryOverride !== null && $assignment->payrollComponent->is_base_salary
                ? round($baseSalaryOverride, 2)
                : $assignment->payrollComponent->computeAmount($assignment, $values, 0.0);

            $values[$assignment->payroll_component_id] = $amount;
            $gross += $amount;
        }

        $deductions = 0.0;
        $employerCharges = 0.0;

        foreach ($assignments as $assignment) {
            if ($assignment->payrollComponent->type === PayrollComponent::TYPE_DEDUCTION) {
                $amount = $assignment->payrollComponent->computeAmount($assignment, $values, $gross);
                $values[$assignment->payroll_component_id] = $amount;
                $deductions += $amount;
            } elseif ($assignment->payrollComponent->type === PayrollComponent::TYPE_EMPLOYER_CHARGE) {
                $amount = $assignment->payrollComponent->computeAmount($assignment, $values, $gross);
                $values[$assignment->payroll_component_id] = $amount;
                $employerCharges += $amount;
            }
        }

        return [$values, $gross, $deductions, $employerCharges];
    }

    /**
     * The gross "Salaire de base" that makes gross - deductions equal
     * $targetNet, for an employee's currently assigned pay components.
     *
     * Solved by bisection rather than algebraically: deduction rules
     * (percentage-of-gross, percentage-of-component, ceilings) make net a
     * non-decreasing but not necessarily linear function of the base salary,
     * so bisection stays correct however those rules evolve, without needing
     * to invert each calculation method by hand.
     */
    public static function solveGrossForNet(Employee $employee, float $targetNet): float
    {
        return static::solveGrossForNetUsingAssignments(static::activeAssignments($employee), $targetNet);
    }

    private static function solveGrossForNetUsingAssignments(Collection $assignments, float $targetNet): float
    {
        // No "Salaire de base" rubrique assigned yet (e.g. the contract is
        // being saved before pay components are set up) — there is nothing
        // for the override to attach to, so a base salary above this couldn't
        // ever move the computed gross. Assume zero deductions as a starting
        // value; it self-corrects once the rubrique is assigned and a payslip
        // is generated.
        if (! $assignments->contains(fn (EmployeePayComponent $a) => $a->payrollComponent->is_base_salary)) {
            return round($targetNet, 2);
        }

        $netFor = function (float $base) use ($assignments) {
            [, $gross, $deductions] = static::computeAmounts($assignments, $base);

            return $gross - $deductions;
        };

        $low = 0.0;
        $high = max($targetNet * 1.5, 1.0);

        // Net can never exceed gross (deductions are never negative), so this
        // always terminates once $high itself is large enough to be the base
        // salary — i.e. when even zero deductions would already clear the target.
        while ($netFor($high) < $targetNet && $high < 1_000_000_000) {
            $high *= 2;
        }

        for ($i = 0; $i < 60; $i++) {
            $mid = ($low + $high) / 2;

            if ($netFor($mid) < $targetNet) {
                $low = $mid;
            } else {
                $high = $mid;
            }
        }

        return round($high, 2);
    }

    /**
     * Attendance figures for this payslip's month: worked days, late arrivals,
     * absences (expected work weekdays per the employee's schedule with no
     * clock-in, no approved leave, and not a holiday), and overtime hours.
     */
    public function attendanceSummary(): array
    {
        $start = $this->period->copy()->startOfMonth();
        $end = $this->period->copy()->endOfMonth();
        $schedule = $this->employee->effectiveWorkSchedule();

        $entries = $this->employee->timeEntries()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get();

        $entryDates = $entries->map(fn (TimeEntry $e) => $e->date->toDateString())->all();
        $leaveDates = $this->approvedLeaveDatesWithin($start, $end);

        $absences = 0;

        if ($schedule) {
            $cursor = $start->copy();
            $today = now()->startOfDay();

            while ($cursor->lessThanOrEqualTo($end) && $cursor->lessThanOrEqualTo($today)) {
                if ($schedule->rangeFor($cursor)
                    && ! Holiday::isHoliday($cursor)
                    && ! in_array($cursor->toDateString(), $entryDates, true)
                    && ! in_array($cursor->toDateString(), $leaveDates, true)
                ) {
                    $absences++;
                }
                $cursor->addDay();
            }
        }

        return [
            'worked_days' => $entries->filter(fn (TimeEntry $e) => $e->clock_in)->count(),
            'late_count' => $entries->filter(fn (TimeEntry $e) => $e->lateMinutes($schedule) > 0)->count(),
            'absences' => $absences,
            'overtime_hours' => round($entries->sum(fn (TimeEntry $e) => $e->overtimeMinutes($schedule)) / 60, 1),
            'holidays' => Holiday::weekdayCountBetween($start, $end),
        ];
    }

    /**
     * Leave balance movement for this payslip's month, for the first active
     * automatically-accruing leave type (typically paid annual leave). Null
     * if no such leave type is configured.
     */
    public function leaveSummary(): ?array
    {
        $leaveType = LeaveType::where('is_active', true)
            ->whereNotNull('accrual_days_per_month')
            ->where('accrual_days_per_month', '>', 0)
            ->orderBy('id')
            ->first();

        if (! $leaveType) {
            return null;
        }

        $monthStart = $this->period->copy()->startOfMonth();
        $monthEnd = $this->period->copy()->endOfMonth();

        $balance = LeaveBalance::forEmployee($this->employee, $leaveType, $this->period->year);

        $takenThisMonth = (float) $this->employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '>=', $monthStart->toDateString())
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->sum('days_count');

        $accruedThisMonth = (float) $leaveType->accrual_days_per_month;
        $remaining = $balance->availableDays();

        return [
            'leave_type' => $leaveType->name,
            'previous_balance' => round($remaining - $accruedThisMonth + $takenThisMonth, 2),
            'accrued_this_month' => $accruedThisMonth,
            'taken_this_month' => $takenThisMonth,
            'remaining' => $remaining,
        ];
    }

    /**
     * Year-to-date cumulative figures across all validated payslips for this
     * employee up to and including this one's period.
     */
    public function yearToDateSummary(): array
    {
        $validatedPayslips = static::where('employee_id', $this->employee_id)
            ->where('status', self::STATUS_VALIDATED)
            ->whereYear('period', $this->period->year)
            ->whereDate('period', '<=', $this->period->toDateString())
            ->with('lines.payrollComponent')
            ->get();

        $bonusCumulative = 0.0;
        $contributionsCumulative = 0.0;
        $taxCumulative = 0.0;

        foreach ($validatedPayslips as $payslip) {
            foreach ($payslip->lines as $line) {
                if ($line->type === PayrollComponent::TYPE_GAIN && ! $line->payrollComponent?->is_base_salary) {
                    $bonusCumulative += (float) $line->amount;
                } elseif ($line->type === PayrollComponent::TYPE_DEDUCTION) {
                    if ($line->payrollComponent?->deduction_category === PayrollComponent::DEDUCTION_CATEGORY_COTISATION) {
                        $contributionsCumulative += (float) $line->amount;
                    } elseif ($line->payrollComponent?->deduction_category === PayrollComponent::DEDUCTION_CATEGORY_IMPOT) {
                        $taxCumulative += (float) $line->amount;
                    }
                }
            }
        }

        $leaveType = LeaveType::where('is_active', true)
            ->whereNotNull('accrual_days_per_month')
            ->where('accrual_days_per_month', '>', 0)
            ->orderBy('id')
            ->first();
        $leaveBalance = $leaveType ? LeaveBalance::forEmployee($this->employee, $leaveType, $this->period->year) : null;

        return [
            'gross_cumulative' => round((float) $validatedPayslips->sum('gross_amount'), 2),
            'net_cumulative' => round((float) $validatedPayslips->sum('net_amount'), 2),
            'bonus_cumulative' => round($bonusCumulative, 2),
            'overtime_hours_cumulative' => round($validatedPayslips->sum(fn (self $p) => $p->attendanceSummary()['overtime_hours']), 1),
            'contributions_cumulative' => round($contributionsCumulative, 2),
            'tax_cumulative' => round($taxCumulative, 2),
            'leave_accrued' => $leaveBalance?->accruedDays() ?? 0,
            'leave_taken' => $leaveBalance?->usedDays() ?? 0,
            'leave_remaining' => $leaveBalance?->availableDays() ?? 0,
        ];
    }

    private function approvedLeaveDatesWithin(Carbon $start, Carbon $end): array
    {
        $dates = [];

        $requests = $this->employee->leaveRequests()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($requests as $request) {
            $cursor = $request->start_date->copy();

            while ($cursor->lessThanOrEqualTo($request->end_date)) {
                if ($cursor->greaterThanOrEqualTo($start) && $cursor->lessThanOrEqualTo($end)) {
                    $dates[] = $cursor->toDateString();
                }
                $cursor->addDay();
            }
        }

        return $dates;
    }
}
