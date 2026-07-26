<?php

namespace Tests\Feature\Payroll;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipDetailsTest extends TestCase
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

    public function test_employer_charges_do_not_affect_net_but_add_to_employer_cost(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employerCharge = PayrollComponent::create([
            'name' => 'CNPS patronale', 'code' => 'CNPS_PAT',
            'type' => PayrollComponent::TYPE_EMPLOYER_CHARGE,
            'calculation_method' => PayrollComponent::METHOD_PERCENTAGE_OF_GROSS,
            'rate' => 15, 'order' => 20,
        ]);

        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $employee->payComponents()->create(['payroll_component_id' => $employerCharge->id, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->assertSame(100000.0, (float) $payslip->net_amount);
        $this->assertSame(15000.0, (float) $payslip->employer_charges_amount);
        $this->assertSame(115000.0, $payslip->employerCost());
    }

    public function test_a_reference_number_is_generated_on_first_creation(): void
    {
        $employee = $this->makeEmployee();
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $this->assertNotNull($payslip->reference);
        $this->assertStringContainsString('202607', $payslip->reference);
    }

    public function test_attendance_summary_counts_worked_days_late_arrivals_and_absences(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31));

        $employee = $this->makeEmployee(['hire_date' => Carbon::create(2026, 7, 1)]);
        WorkSchedule::create([
            'employee_id' => $employee->id,
            'monday_start' => '08:00', 'monday_end' => '17:00',
            'tuesday_start' => '08:00', 'tuesday_end' => '17:00',
        ]);

        // Monday 2026-07-06: on time.
        $employee->timeEntries()->create([
            'date' => '2026-07-06', 'clock_in' => '2026-07-06 08:00:00', 'clock_out' => '2026-07-06 17:00:00',
        ]);
        // Tuesday 2026-07-07: late, no clock-out.
        $employee->timeEntries()->create([
            'date' => '2026-07-07', 'clock_in' => '2026-07-07 09:00:00',
        ]);
        // Monday 2026-07-13: a declared holiday — should not count as absence.
        Holiday::create(['date' => '2026-07-13', 'name' => 'Jour férié test']);
        // Remaining Mon/Tue in July with no entry (07-14, 07-20, 07-21, 07-27,
        // 07-28 — 4 Mondays + 4 Tuesdays fall in July 2026 in total) -> absences.

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $summary = $payslip->attendanceSummary();

        $this->assertSame(2, $summary['worked_days']);
        $this->assertSame(1, $summary['late_count']);
        // 8 expected Mon/Tue workdays in July, minus 1 holiday, minus 2 accounted for (worked + late-present).
        $this->assertSame(5, $summary['absences']);
        $this->assertSame(1, $summary['holidays']);

        Carbon::setTestNow();
    }

    public function test_leave_summary_reflects_accrual_and_usage_for_the_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 31));

        $employee = $this->makeEmployee(['hire_date' => Carbon::create(2026, 1, 1)]);
        $leaveType = LeaveType::create([
            'name' => 'Congé payé', 'code' => 'CP', 'accrual_days_per_month' => 2.2,
            'is_paid' => true, 'requires_approval' => true, 'is_active' => true,
        ]);
        $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id, 'start_date' => '2026-07-06', 'end_date' => '2026-07-08',
            'days_count' => 3, 'status' => 'approved',
        ]);

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));

        $summary = $payslip->leaveSummary();

        $this->assertSame('Congé payé', $summary['leave_type']);
        $this->assertSame(2.2, $summary['accrued_this_month']);
        $this->assertSame(3.0, $summary['taken_this_month']);

        Carbon::setTestNow();
    }

    public function test_verification_page_shows_authentic_for_a_validated_payslip(): void
    {
        $employee = $this->makeEmployee();
        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE',
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);
        $payslip = Payslip::generateFor($employee, Carbon::create(2026, 7, 1));
        $payslip->update(['status' => Payslip::STATUS_VALIDATED, 'validated_at' => now()]);

        $response = $this->get(route('verification.payslip', $payslip->reference));

        $response->assertOk();
        $response->assertSee('Document authentique');
        $response->assertSee($employee->full_name);
        $response->assertDontSee('100');
    }

    public function test_verification_page_shows_not_found_for_an_unknown_reference(): void
    {
        $response = $this->get(route('verification.payslip', 'BUL-000000-00000'));

        $response->assertOk();
        $response->assertSee('Document non reconnu');
    }

    public function test_hr_can_manage_holidays(): void
    {
        $response = $this->actingAs($this->admin)->post(route('administration.holidays.store'), [
            'date' => '2026-08-07',
            'name' => "Fête de l'indépendance",
        ]);

        $response->assertRedirect(route('administration.holidays.index'));
        $this->assertTrue(Holiday::isHoliday(Carbon::create(2026, 8, 7)));
    }

    public function test_a_user_without_administration_permission_cannot_manage_holidays(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->post(route('administration.holidays.store'), [
            'date' => '2026-08-07', 'name' => 'Test',
        ])->assertForbidden();
    }
}
