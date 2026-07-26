<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'accrual_days_per_month',
        'is_paid',
        'requires_approval',
        'max_carry_over_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'accrual_days_per_month' => 'decimal:2',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function accruesAutomatically(): bool
    {
        return $this->accrual_days_per_month !== null && (float) $this->accrual_days_per_month > 0;
    }
}
