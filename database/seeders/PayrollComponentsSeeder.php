<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentsSeeder extends Seeder
{
    /**
     * Seed a starter set of pay components illustrating the three calculation
     * methods. These are generic examples, not legally accurate rates for any
     * specific country — the client's own payroll/legal team must review and
     * adjust (or replace entirely) under Paie > Rubriques before real use.
     */
    public function run(): void
    {
        $baseSalary = PayrollComponent::firstOrCreate(['code' => 'SALAIRE_BASE'], [
            'name' => 'Salaire de base',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'is_base_salary' => true,
            'order' => 1,
        ]);

        PayrollComponent::firstOrCreate(['code' => 'PRIME_TRANSPORT'], [
            'name' => 'Prime de transport',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'order' => 2,
        ]);

        PayrollComponent::firstOrCreate(['code' => 'CNPS_SALARIE'], [
            'name' => 'CNPS - Part salariale',
            'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 6.3,
            'order' => 10,
        ]);

        PayrollComponent::firstOrCreate(['code' => 'IMPOT_SALAIRE'], [
            'name' => 'Impôt sur salaire (exemple)',
            'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 10,
            'order' => 20,
        ]);

        PayrollComponent::firstOrCreate(['code' => 'INDEMNITE_LOGEMENT'], [
            'name' => 'Indemnité de logement',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_COMPONENT,
            'base_component_id' => $baseSalary->id,
            'rate' => 20,
            'order' => 3,
        ]);
    }
}
