<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PayrollCalculationTest extends TestCase
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

    public function test_fixed_percentage_of_component_and_percentage_of_gross_are_calculated_correctly(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base',
            'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'order' => 1,
        ]);

        $housing = PayrollComponent::create([
            'name' => 'Indemnité logement',
            'code' => 'LOGEMENT',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_COMPONENT,
            'base_component_id' => $baseSalary->id,
            'rate' => 20,
            'order' => 2,
        ]);

        $socialSecurity = PayrollComponent::create([
            'name' => 'CNPS',
            'code' => 'CNPS',
            'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 10,
            'order' => 10,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 200000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $housing->id, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $socialSecurity->id, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        // Gross = 200000 (base) + 40000 (20% of base) = 240000.
        $this->assertSame(240000.0, (float) $payslip->gross_amount);
        // CNPS = 10% of gross (240000) = 24000.
        $this->assertSame(24000.0, (float) $payslip->deductions_amount);
        $this->assertSame(216000.0, (float) $payslip->net_amount);

        $this->assertSame(3, $payslip->lines()->count());
    }

    public function test_a_ceiling_caps_a_percentage_based_component(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base',
            'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'order' => 1,
        ]);

        $cappedDeduction = PayrollComponent::create([
            'name' => 'Retenue plafonnée',
            'code' => 'CAP',
            'type' => PayrollComponent::TYPE_DEDUCTION,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 50,
            'ceiling_amount' => 30000,
            'order' => 10,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 500000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $cappedDeduction->id, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        // 50% of 500000 = 250000, but capped at 30000.
        $this->assertSame(30000.0, (float) $payslip->deductions_amount);
    }

    public function test_recalculating_preserves_manual_lines_but_replaces_component_lines(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base',
            'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN,
            'calculation_method' => PayrollComponent::METHOD_FIXED,
            'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
        $payslip->lines()->create(['label' => 'Prime exceptionnelle', 'type' => 'gain', 'amount' => 5000]);

        // Bump the base salary and regenerate.
        $employee->payComponents()->first()->update(['amount' => 150000]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->assertSame(2, $payslip->lines()->count());
        $this->assertDatabaseHas('payslip_lines', ['payslip_id' => $payslip->id, 'label' => 'Prime exceptionnelle', 'amount' => 5000]);
        $this->assertDatabaseHas('payslip_lines', ['payslip_id' => $payslip->id, 'payroll_component_id' => $baseSalary->id, 'amount' => 150000]);
    }

    public function test_a_validated_payslip_cannot_be_recalculated(): void
    {
        $employee = $this->makeEmployee();
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
        $payslip->update(['status' => Payslip::STATUS_VALIDATED]);

        $this->expectException(HttpException::class);
        Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
    }

    public function test_bulk_run_generates_payslips_only_for_active_employees_with_pay_components(): void
    {
        $withComponents = $this->makeEmployee(['first_name' => 'Avec']);
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $withComponents->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $withoutComponents = $this->makeEmployee(['first_name' => 'Sans']);

        $response = $this->actingAs($this->admin)->post(route('payroll.payslips.run'), ['period' => '2026-07']);

        $response->assertRedirect();
        $this->assertDatabaseHas('payslips', ['employee_id' => $withComponents->id]);
        $this->assertDatabaseMissing('payslips', ['employee_id' => $withoutComponents->id]);
    }

    public function test_bulk_run_skips_already_validated_payslips_instead_of_failing(): void
    {
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);

        $validatedEmployee = $this->makeEmployee(['first_name' => 'Validé']);
        $validatedEmployee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $validatedPayslip = Payslip::generateFor($validatedEmployee, Carbon::create(2026, 7, 1));
        $validatedPayslip->update(['status' => Payslip::STATUS_VALIDATED, 'gross_amount' => 100000]);

        $draftEmployee = $this->makeEmployee(['first_name' => 'Brouillon']);
        $draftEmployee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 200000, 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('payroll.payslips.run'), ['period' => '2026-07']);

        $response->assertRedirect(route('payroll.payslips.index', ['period' => '2026-07']));
        $this->assertDatabaseHas('payslips', ['employee_id' => $draftEmployee->id, 'status' => Payslip::STATUS_DRAFT]);
        // The already-validated payslip must be left untouched, not recalculated.
        $this->assertSame(100000.0, (float) $validatedPayslip->fresh()->gross_amount);
    }

    public function test_validating_a_payslip_generates_a_pdf_and_locks_it(): void
    {
        $employee = $this->makeEmployee();
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $response = $this->actingAs($this->admin)->post(route('payroll.payslips.validate', $payslip), [
            'payment_method' => 'Virement bancaire',
            'payment_date' => '2026-07-31',
        ]);

        $response->assertRedirect();
        $payslip->refresh();
        $this->assertSame(Payslip::STATUS_VALIDATED, $payslip->status);
        $this->assertNotNull($payslip->pdf_path);
        $this->assertNotNull($payslip->reference);
        $this->assertSame('Virement bancaire', $payslip->payment_method);
        Storage::disk('local')->assertExists($payslip->pdf_path);
    }

    public function test_employee_only_sees_validated_payslips_in_self_service(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $draft = Payslip::generateFor($employee, Carbon::create(2026, 6, 1));
        $validated = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
        $validated->update(['status' => Payslip::STATUS_VALIDATED]);

        $response = $this->actingAs($employeeUser)->get(route('portal.payslips.index'));

        $response->assertOk();
        $response->assertSee(ucfirst($validated->periodLabel()));
        $response->assertDontSee(ucfirst($draft->periodLabel()));
    }

    public function test_employee_can_view_their_own_payslip_inline_and_download_it_as_an_attachment(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->actingAs($this->admin)->post(route('payroll.payslips.validate', $payslip), [
            'payment_method' => 'Virement bancaire',
            'payment_date' => '2026-07-31',
        ]);

        $viewResponse = $this->actingAs($employeeUser)->get(route('portal.payslips.view', $payslip));
        $viewResponse->assertOk();
        $this->assertStringContainsString('inline', $viewResponse->headers->get('Content-Disposition'));

        $downloadResponse = $this->actingAs($employeeUser)->get(route('portal.payslips.download', $payslip));
        $downloadResponse->assertOk();
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_employee_cannot_download_someone_elses_payslip(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $this->makeEmployee(['user_id' => $employeeUser->id]);

        $otherEmployee = $this->makeEmployee(['first_name' => 'Autre']);
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $otherEmployee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($otherEmployee, Carbon::create(2026, 7, 1));
        $payslip->update(['status' => Payslip::STATUS_VALIDATED, 'pdf_path' => 'payslips/fake.pdf']);

        $this->actingAs($employeeUser)->get(route('portal.payslips.view', $payslip))->assertNotFound();
        $this->actingAs($employeeUser)->get(route('portal.payslips.download', $payslip))->assertNotFound();
    }

    public function test_a_user_without_payroll_permission_cannot_run_payroll(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->post(route('payroll.payslips.run'), ['period' => '2026-07'])
            ->assertForbidden();
    }
}
