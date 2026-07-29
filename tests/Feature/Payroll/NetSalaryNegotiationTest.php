<?php

namespace Tests\Feature\Payroll;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetSalaryNegotiationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CountrySeeder::class);

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

    public function test_payslip_generation_solves_the_gross_base_salary_to_hit_the_negotiated_net(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);

        $cnps = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS',
            'type' => PayrollComponent::TYPE_DEDUCTION, 'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 10, 'order' => 10,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $cnps->id, 'is_active' => true]);

        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 500000,
        ]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        // gross - 10% of gross = net  =>  gross = net / 0.9.
        $this->assertEqualsWithDelta(500000.0, (float) $payslip->net_amount, 0.01);
        $this->assertEqualsWithDelta(555555.56, (float) $payslip->gross_amount, 0.01);
    }

    public function test_the_resolved_gross_is_synced_back_to_the_pay_component_and_the_contract(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);

        $assignment = $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 1, 'is_active' => true]);

        $contract = $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 500000,
        ]);

        Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->assertEqualsWithDelta(500000.0, (float) $assignment->fresh()->amount, 0.01);
        $this->assertEqualsWithDelta(500000.0, (float) $contract->fresh()->base_salary, 0.01);
    }

    public function test_gross_mode_contracts_are_unaffected_by_net_solving(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 300000, 'is_active' => true]);

        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_GROSS,
            'base_salary' => 300000,
        ]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->assertSame(300000.0, (float) $payslip->gross_amount);
        $this->assertSame(300000.0, (float) $payslip->net_amount);
    }

    public function test_hr_creating_a_net_negotiated_contract_derives_the_base_salary(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $cnps = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS',
            'type' => PayrollComponent::TYPE_DEDUCTION, 'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 20, 'order' => 10,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 1, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $cnps->id, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.contracts.store', $employee), [
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->toDateString(),
            'currency' => 'XOF',
            'status' => Contract::STATUS_DRAFT,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 400000,
        ]);

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $contract = $employee->contracts()->firstOrFail();
        $this->assertSame(Contract::SALARY_MODE_NET, $contract->salary_mode);
        $this->assertEqualsWithDelta(400000.0, (float) $contract->net_salary_target, 0.01);
        // gross - 20% of gross = net  =>  gross = net / 0.8 = 500000.
        $this->assertEqualsWithDelta(500000.0, (float) $contract->base_salary, 0.01);
    }

    public function test_switching_a_contract_back_to_gross_mode_clears_the_net_target(): void
    {
        $employee = $this->makeEmployee();

        $contract = $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 500000,
            'base_salary' => 500000,
        ]);

        $response = $this->actingAs($this->admin)->put(route('organisation.employees.contracts.update', [$employee, $contract]), [
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => $contract->start_date->toDateString(),
            'currency' => 'XOF',
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_GROSS,
            'base_salary' => 450000,
        ]);

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $contract->refresh();
        $this->assertSame(Contract::SALARY_MODE_GROSS, $contract->salary_mode);
        $this->assertNull($contract->net_salary_target);
        $this->assertEqualsWithDelta(450000.0, (float) $contract->base_salary, 0.01);
    }

    public function test_base_salary_falls_back_to_the_net_target_when_no_pay_components_are_assigned_yet(): void
    {
        $employee = $this->makeEmployee();

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.contracts.store', $employee), [
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->toDateString(),
            'currency' => 'XOF',
            'status' => Contract::STATUS_DRAFT,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 400000,
        ]);

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $contract = $employee->contracts()->firstOrFail();
        $this->assertEqualsWithDelta(400000.0, (float) $contract->base_salary, 0.01);
    }

    public function test_net_salary_target_is_required_when_salary_mode_is_net(): void
    {
        $employee = $this->makeEmployee();

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.contracts.store', $employee), [
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->toDateString(),
            'currency' => 'XOF',
            'status' => Contract::STATUS_DRAFT,
            'salary_mode' => Contract::SALARY_MODE_NET,
        ]);

        $response->assertSessionHasErrors(['net_salary_target']);
        $this->assertSame(0, $employee->contracts()->count());
    }

    public function test_assigned_base_salary_is_shown_read_only_when_the_contract_is_net_mode(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 500000, 'is_active' => true]);
        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_NET,
            'net_salary_target' => 500000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('organisation.employees.show', $employee));

        $response->assertOk();
        $response->assertSee('Calculé automatiquement pour un net visé de');
        $response->assertDontSee('name="amount"', false);
    }

    public function test_assigned_base_salary_stays_editable_when_the_contract_is_gross_mode(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 500000, 'is_active' => true]);
        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now()->subMonths(2),
            'status' => Contract::STATUS_ACTIVE,
            'salary_mode' => Contract::SALARY_MODE_GROSS,
            'base_salary' => 500000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('organisation.employees.show', $employee));

        $response->assertOk();
        $response->assertDontSee('Calculé automatiquement pour un net visé de');
        $response->assertSee('name="amount"', false);
    }
}
