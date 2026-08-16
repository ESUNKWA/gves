<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends Model
{
    public const TYPE_GAIN = 'gain';

    public const TYPE_DEDUCTION = 'deduction';

    public const TYPE_EMPLOYER_CHARGE = 'employer_charge';

    public const METHOD_FIXED = 'fixed';

    public const METHOD_PERCENTAGE_OF_COMPONENT = 'percentage_of_component';

    public const METHOD_PERCENTAGE_OF_GROSS = 'percentage_of_gross';

    public const DEDUCTION_CATEGORY_COTISATION = 'cotisation';

    public const DEDUCTION_CATEGORY_IMPOT = 'impot';

    public const DEDUCTION_CATEGORY_AUTRE = 'autre';

    public static function types(): array
    {
        return [
            self::TYPE_GAIN => 'Gain',
            self::TYPE_DEDUCTION => 'Retenue',
            self::TYPE_EMPLOYER_CHARGE => 'Charge patronale',
        ];
    }

    public static function deductionCategories(): array
    {
        return [
            self::DEDUCTION_CATEGORY_COTISATION => 'Cotisation sociale',
            self::DEDUCTION_CATEGORY_IMPOT => 'Impôt',
            self::DEDUCTION_CATEGORY_AUTRE => 'Autre',
        ];
    }

    public static function calculationMethods(): array
    {
        return [
            self::METHOD_FIXED => 'Montant fixe',
            self::METHOD_PERCENTAGE_OF_COMPONENT => "Pourcentage d'une autre rubrique",
            self::METHOD_PERCENTAGE_OF_GROSS => 'Pourcentage du brut',
        ];
    }

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_base_salary',
        'is_subject_to_contributions',
        'calculation_method',
        'deduction_category',
        'base_component_id',
        'rate',
        'ceiling_amount',
        'order',
        'is_active',
        'assign_to_all_employees',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'ceiling_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_base_salary' => 'boolean',
            'is_subject_to_contributions' => 'boolean',
            'assign_to_all_employees' => 'boolean',
        ];
    }

    public function usesPercentage(): bool
    {
        return in_array($this->calculation_method, [
            self::METHOD_PERCENTAGE_OF_COMPONENT,
            self::METHOD_PERCENTAGE_OF_GROSS,
        ], true);
    }

    /**
     * Assign this component to every currently active employee who doesn't
     * already have it — called right after assign_to_all_employees is turned
     * on (PayrollComponentController), so the effect is immediate rather than
     * only applying to employees hired afterward. firstOrCreate: never
     * overwrites an amount/is_active an HR admin already set by hand for a
     * given employee.
     */
    public function assignToAllCurrentEmployees(): void
    {
        Employee::where('status', Employee::STATUS_ACTIVE)
            ->whereDoesntHave('payComponents', fn ($query) => $query->where('payroll_component_id', $this->id))
            ->each(fn (Employee $employee) => EmployeePayComponent::create([
                'employee_id' => $employee->id,
                'payroll_component_id' => $this->id,
                'is_active' => true,
            ]));
    }

    /**
     * Every component flagged assign_to_all_employees, applied to one newly
     * created employee — called from every place an Employee gets created
     * (EmployeeController, EmployeeOnboardingRequestController,
     * Platform\TenantController's first admin) so onboarding never depends on
     * HR remembering to assign the standard rubriques by hand.
     */
    public static function assignDefaultsTo(Employee $employee): void
    {
        static::where('assign_to_all_employees', true)
            ->where('is_active', true)
            ->get()
            ->each(fn (self $component) => EmployeePayComponent::firstOrCreate(
                ['employee_id' => $employee->id, 'payroll_component_id' => $component->id],
                ['is_active' => true]
            ));
    }

    public function baseComponent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'base_component_id');
    }

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeePayComponent::class);
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }

    /**
     * Compute this component's amount for one payslip run.
     *
     * @param  array<int, float>  $computedValues  Amounts already computed this run, keyed by payroll_component_id
     *                                             (gains are always computed before deductions, so a percentage-of-
     *                                             component reference only ever resolves against a gain's value).
     */
    public function computeAmount(EmployeePayComponent $assignment, array $computedValues, float $gross): float
    {
        $amount = match ($this->calculation_method) {
            self::METHOD_FIXED => (float) ($assignment->amount ?? 0),
            self::METHOD_PERCENTAGE_OF_COMPONENT => ($computedValues[$this->base_component_id] ?? 0.0) * ((float) $this->rate / 100),
            self::METHOD_PERCENTAGE_OF_GROSS => $gross * ((float) $this->rate / 100),
            default => 0.0,
        };

        if ($this->ceiling_amount !== null) {
            $amount = min($amount, (float) $this->ceiling_amount);
        }

        return round($amount, 2);
    }

    /**
     * The base amount a percentage calculation was applied to (for display on
     * the payslip line), or null for a fixed-amount component.
     */
    public function baseAmountFor(array $computedValues, float $gross): ?float
    {
        return match ($this->calculation_method) {
            self::METHOD_PERCENTAGE_OF_COMPONENT => round($computedValues[$this->base_component_id] ?? 0.0, 2),
            self::METHOD_PERCENTAGE_OF_GROSS => round($gross, 2),
            default => null,
        };
    }
}
