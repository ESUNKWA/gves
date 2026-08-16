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
            ['payroll_component_ids' => [$component->id], 'amounts' => [$component->id => 150000]]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id,
            'payroll_component_id' => $component->id,
            'amount' => 150000,
        ]);
    }

    public function test_hr_can_assign_several_components_to_an_employee_in_one_submit(): void
    {
        $base = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $transport = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 2,
        ]);
        $cnps = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS', 'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS, 'rate' => 6.3, 'order' => 3,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.pay-components.store', $employee),
            [
                'payroll_component_ids' => [$base->id, $transport->id, $cnps->id],
                'amounts' => [$base->id => 300000, $transport->id => 25000],
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseCount('employee_pay_components', 3);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $base->id, 'amount' => 300000,
        ]);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $transport->id, 'amount' => 25000,
        ]);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $cnps->id, 'amount' => null,
        ]);
    }

    public function test_assigning_a_fixed_component_without_an_amount_fails_validation(): void
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
            ['payroll_component_ids' => [$component->id]]
        );

        $response->assertSessionHasErrors(['amounts']);
        $this->assertDatabaseCount('employee_pay_components', 0);
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
            ['payroll_component_ids' => [$component->id], 'amounts' => [$component->id => 150000]]
        )->assertForbidden();
    }

    public function test_hr_can_bulk_assign_a_component_to_several_employees_with_individual_amounts(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $first = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $second = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Kadidia', 'last_name' => 'Ouattara',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('payroll.components.bulk-assign', $component),
            [
                'employee_ids' => [$first->id, $second->id],
                'amounts' => [$first->id => 25000, $second->id => 30000],
            ]
        );

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $first->id, 'payroll_component_id' => $component->id, 'amount' => 25000,
        ]);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $second->id, 'payroll_component_id' => $component->id, 'amount' => 30000,
        ]);
    }

    public function test_bulk_assigning_a_fixed_component_requires_an_amount_for_each_selected_employee(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $first = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $second = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Kadidia', 'last_name' => 'Ouattara',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('payroll.components.bulk-assign', $component),
            ['employee_ids' => [$first->id, $second->id], 'amounts' => [$first->id => 25000]]
        );

        $response->assertSessionHasErrors(['amounts']);
        $this->assertDatabaseCount('employee_pay_components', 0);
    }

    public function test_bulk_assigning_an_already_assigned_employee_does_not_error(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $component->id, 'amount' => 10000]);

        $response = $this->actingAs($this->admin)->post(
            route('payroll.components.bulk-assign', $component),
            ['employee_ids' => [$employee->id], 'amounts' => [$employee->id => 30000]]
        );

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertDatabaseCount('employee_pay_components', 1);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $component->id, 'amount' => 30000,
        ]);
    }

    public function test_a_user_without_payroll_permission_cannot_bulk_assign_components(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $component = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $this->actingAs($manager)->post(
            route('payroll.components.bulk-assign', $component),
            ['employee_ids' => [$employee->id], 'amounts' => [$employee->id => 25000]]
        )->assertForbidden();
    }

    public function test_base_salary_component_is_pre_filled_from_the_employees_latest_contract_on_their_own_page(): void
    {
        PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        // Still "Brouillon" (draft), as a freshly entered contract commonly is —
        // the pre-fill must not require it to already be flipped to "Actif".
        $employee->contracts()->create([
            'contract_type' => 'cdi', 'start_date' => now()->subYear(),
            'status' => 'draft', 'base_salary' => 350000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('organisation.employees.show', $employee));

        $response->assertOk();
        $response->assertSee('350 000', false);
    }

    public function test_base_salary_component_is_pre_filled_per_employee_on_the_bulk_assign_screen(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $employee->contracts()->create([
            'contract_type' => 'cdi', 'start_date' => now()->subYear(),
            'status' => 'draft', 'base_salary' => 420000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('payroll.components.index'));

        $response->assertOk();
        $response->assertSee('420 000', false);
    }

    public function test_hr_can_reorder_payroll_components_via_drag_and_drop(): void
    {
        $first = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $second = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 2,
        ]);

        $response = $this->actingAs($this->admin)->post(route('payroll.components.reorder'), [
            'order' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }

    public function test_a_user_without_payroll_permission_cannot_reorder_components(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $component = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);

        $this->actingAs($manager)
            ->post(route('payroll.components.reorder'), ['order' => [$component->id]])
            ->assertForbidden();
    }

    public function test_checking_a_component_in_the_defaults_checklist_immediately_assigns_existing_active_employees(): void
    {
        $component = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS', 'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS, 'rate' => 6.3, 'order' => 1,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $terminated = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Sorti', 'last_name' => 'Employé',
            'hire_date' => now()->subYears(3), 'status' => Employee::STATUS_TERMINATED,
        ]);

        $response = $this->actingAs($this->admin)->post(route('payroll.components.defaults.update'), [
            'component_ids' => [$component->id],
        ]);

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertTrue($component->fresh()->assign_to_all_employees);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $component->id,
        ]);
        $this->assertDatabaseMissing('employee_pay_components', [
            'employee_id' => $terminated->id, 'payroll_component_id' => $component->id,
        ]);
    }

    public function test_saving_the_defaults_checklist_does_not_touch_an_employees_own_amount(): void
    {
        $component = PayrollComponent::create([
            'name' => 'Prime de transport', 'code' => 'TRANSPORT',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $alreadyAssigned = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $alreadyAssigned->payComponents()->create(['payroll_component_id' => $component->id, 'amount' => 25000]);
        $notYetAssigned = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Kadidia', 'last_name' => 'Ouattara',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('payroll.components.defaults.update'), [
            'component_ids' => [$component->id],
        ]);

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $alreadyAssigned->id, 'payroll_component_id' => $component->id, 'amount' => 25000,
        ]);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $notYetAssigned->id, 'payroll_component_id' => $component->id, 'amount' => null,
        ]);
    }

    public function test_unchecking_a_component_in_the_defaults_checklist_clears_the_flag_without_removing_assignments(): void
    {
        $component = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS', 'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS, 'rate' => 6.3, 'order' => 1,
            'assign_to_all_employees' => true,
        ]);
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear(), 'status' => Employee::STATUS_ACTIVE,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $component->id]);

        $response = $this->actingAs($this->admin)->post(route('payroll.components.defaults.update'), [
            'component_ids' => [],
        ]);

        $response->assertRedirect(route('payroll.components.index'));
        $this->assertFalse($component->fresh()->assign_to_all_employees);
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $component->id,
        ]);
    }

    public function test_a_user_without_payroll_permission_cannot_update_the_defaults_checklist(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $component = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS', 'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS, 'rate' => 6.3, 'order' => 1,
        ]);

        $this->actingAs($manager)
            ->post(route('payroll.components.defaults.update'), ['component_ids' => [$component->id]])
            ->assertForbidden();
    }

    public function test_a_newly_created_employee_automatically_receives_default_payroll_components(): void
    {
        $component = PayrollComponent::create([
            'name' => 'CNPS', 'code' => 'CNPS', 'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS, 'rate' => 6.3,
            'order' => 1, 'assign_to_all_employees' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya', 'last_name' => 'Koné',
            'hire_date' => now()->subYear()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();
        $employee = Employee::where('first_name', 'Aya')->firstOrFail();
        $this->assertDatabaseHas('employee_pay_components', [
            'employee_id' => $employee->id, 'payroll_component_id' => $component->id,
        ]);
    }
}
