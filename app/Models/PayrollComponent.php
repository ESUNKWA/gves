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
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:3',
            'ceiling_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'is_base_salary' => 'boolean',
            'is_subject_to_contributions' => 'boolean',
        ];
    }

    public function usesPercentage(): bool
    {
        return in_array($this->calculation_method, [
            self::METHOD_PERCENTAGE_OF_COMPONENT,
            self::METHOD_PERCENTAGE_OF_GROSS,
        ], true);
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
     *                                              (gains are always computed before deductions, so a percentage-of-
     *                                              component reference only ever resolves against a gain's value).
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
