<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypesSeeder extends Seeder
{
    /**
     * Seed a starter set of leave types. These are editable defaults, not fixed
     * legal rules — accrual rates and policies vary by country and should be
     * adjusted by the client under Congés > Types de congés.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Congé payé',
                'code' => 'CP',
                'accrual_days_per_month' => 2.2,
                'is_paid' => true,
                'requires_approval' => true,
                'max_carry_over_days' => null,
            ],
            [
                'name' => 'Congé maladie',
                'code' => 'CM',
                'accrual_days_per_month' => null,
                'is_paid' => true,
                'requires_approval' => true,
                'max_carry_over_days' => 0,
            ],
            [
                'name' => 'Congé sans solde',
                'code' => 'CSS',
                'accrual_days_per_month' => null,
                'is_paid' => false,
                'requires_approval' => true,
                'max_carry_over_days' => 0,
            ],
            [
                'name' => 'Congé maternité',
                'code' => 'MAT',
                'accrual_days_per_month' => null,
                'is_paid' => true,
                'requires_approval' => true,
                'max_carry_over_days' => 0,
            ],
            [
                'name' => 'Congé paternité',
                'code' => 'PAT',
                'accrual_days_per_month' => null,
                'is_paid' => true,
                'requires_approval' => true,
                'max_carry_over_days' => 0,
            ],
            [
                'name' => 'Événement familial',
                'code' => 'EF',
                'accrual_days_per_month' => null,
                'is_paid' => true,
                'requires_approval' => true,
                'max_carry_over_days' => 0,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
