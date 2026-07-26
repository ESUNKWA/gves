<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    protected $fillable = [
        'payslip_id',
        'payroll_component_id',
        'label',
        'quantity',
        'base_amount',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class);
    }
}
