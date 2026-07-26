<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipIvorianModelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    private function makeEmployee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ], $attributes));
    }

    public function test_seniority_label_is_computed_correctly(): void
    {
        $employee = $this->makeEmployee(['hire_date' => Carbon::create(2024, 10, 1)]);

        $label = $employee->seniorityLabel(Carbon::create(2026, 7, 15));

        $this->assertSame('1 an(s) et 9 mois', $label);
    }

    public function test_seniority_label_is_null_for_a_future_hire_date(): void
    {
        $employee = $this->makeEmployee(['hire_date' => Carbon::create(2027, 1, 1)]);

        $this->assertNull($employee->seniorityLabel(Carbon::create(2026, 7, 15)));
    }

    public function test_monthly_contractual_hours_are_computed_from_the_weekly_schedule(): void
    {
        $employee = $this->makeEmployee();
        $schedule = WorkSchedule::create([
            'employee_id' => $employee->id,
            'monday_start' => '08:00', 'monday_end' => '17:00',
            'tuesday_start' => '08:00', 'tuesday_end' => '17:00',
            'wednesday_start' => '08:00', 'wednesday_end' => '17:00',
            'thursday_start' => '08:00', 'thursday_end' => '17:00',
            'friday_start' => '08:00', 'friday_end' => '17:00',
        ]);

        // 5 days x 9h = 45h/week x 52 / 12 = 195 monthly hours.
        $this->assertSame(195.0, $schedule->monthlyContractualHours());
    }

    public function test_a_non_taxable_gain_is_excluded_from_total_subject_to_contributions(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $nonTaxableTransport = PayrollComponent::create([
            'name' => 'Prime transport non imposable', 'code' => 'TRANS_NI',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED,
            'is_subject_to_contributions' => false, 'order' => 2,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $nonTaxableTransport->id, 'amount' => 30000, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
        $payslip->load('lines.payrollComponent');

        $this->assertSame(130000.0, (float) $payslip->gross_amount);
        $this->assertSame(100000.0, $payslip->totalSubjectToContributions());
    }

    public function test_payslip_lines_record_quantity_and_base_amount(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $cnps = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS',
            'type' => PayrollComponent::TYPE_DEDUCTION, 'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 6.3, 'order' => 10,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $cnps->id, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $gainLine = $payslip->lines()->where('payroll_component_id', $baseSalary->id)->first();
        $deductionLine = $payslip->lines()->where('payroll_component_id', $cnps->id)->first();

        $this->assertSame(31.0, (float) $gainLine->quantity); // July has 31 days.
        $this->assertNull($gainLine->base_amount);
        $this->assertNull($deductionLine->quantity);
        $this->assertSame(100000.0, (float) $deductionLine->base_amount);
    }

    public function test_hr_can_set_the_new_payroll_related_employee_fields(): void
    {
        $employee = $this->makeEmployee();

        $response = $this->actingAs($this->admin)->put(
            route('organisation.employees.update', $employee),
            [
                'employee_number' => $employee->employee_number,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'hire_date' => $employee->hire_date->toDateString(),
                'status' => Employee::STATUS_ACTIVE,
                'social_security_number' => '325631',
                'category' => 'Cadre Classe 4.3',
                'qualification' => 'DITC05',
                'tax_shares' => 2.5,
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'social_security_number' => '325631',
            'category' => 'Cadre Classe 4.3',
            'qualification' => 'DITC05',
        ]);
    }
}
