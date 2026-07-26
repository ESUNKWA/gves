<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollComponentManagementTest extends TestCase
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

    public function test_hr_can_create_a_fixed_pay_component(): void
    {
        $response = $this->actingAs($this->admin)->post(route('payroll.components.store'), [
            '_modal' => 'component-create',
            'name' => 'Salaire de base',
            'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'order' => 1,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertDatabaseHas('payroll_components', ['code' => 'BASE']);
    }

    public function test_percentage_of_gross_requires_a_rate(): void
    {
        $response = $this->actingAs($this->admin)->post(route('payroll.components.store'), [
            'name' => 'CNPS',
            'code' => 'CNPS',
            'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'order' => 10,
        ]);

        $response->assertSessionHasErrors(['rate']);
    }

    public function test_a_component_already_used_cannot_be_deleted(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $component->id, 'amount' => 100000]);

        $response = $this->actingAs($this->admin)->delete(route('payroll.components.destroy', $component));

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertDatabaseHas('payroll_components', ['id' => $component->id]);
    }

    public function test_hr_can_assign_a_component_to_an_employee(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.pay-components.store', $employee),
            ['payroll_component_id' => $component->id, 'amount' => 150000]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id,
            'payroll_component_id' => $component->id,
            'amount' => 150000,
        ]);
    }

    public function test_a_user_without_payroll_permission_cannot_assign_components(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $component = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)->post(
            route('organisation.employees.pay-components.store', $employee),
            ['payroll_component_id' => $component->id, 'amount' => 150000]
        )->assertForbidden();
    }
}
