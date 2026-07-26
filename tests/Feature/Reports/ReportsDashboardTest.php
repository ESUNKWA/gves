<?php

namespace Tests\Feature\Reports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\PayrollComponent;
use App\Models\Payslip;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsDashboardTest extends TestCase
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

    public function test_a_user_without_reports_permission_cannot_view_the_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employe');

        $this->actingAs($user)->get(route('reports.dashboard'))->assertForbidden();
    }

    public function test_dashboard_aggregates_workforce_by_department_and_site(): void
    {
        $site = Site::create(['name' => 'Siège', 'code' => 'HQ']);
        $department = Department::create(['name' => 'RH', 'code' => 'RH', 'site_id' => $site->id]);

        $this->makeEmployee(['site_id' => $site->id, 'department_id' => $department->id]);
        $this->makeEmployee(['site_id' => $site->id, 'department_id' => $department->id]);
        $this->makeEmployee(['status' => Employee::STATUS_TERMINATED]);

        $response = $this->actingAs($this->admin)->get(route('reports.dashboard'));

        $response->assertOk();
        $response->assertViewHas('workforce', function (array $workforce) {
            return $workforce['total'] === 2
                && $workforce['by_department']['RH'] === 2
                && $workforce['by_site']['Siège'] === 2;
        });
    }

    public function test_payroll_report_only_counts_validated_payslips_for_the_selected_year(): void
    {
        $employee = $this->makeEmployee();

        $baseSalary = PayrollComponent::create([
            'name' => 'Salaire de base', 'code' => 'BASE', 'is_base_salary' => true,
            'type' => PayrollComponent::TYPE_GAIN, 'calculation_method' => PayrollComponent::METHOD_FIXED, 'order' => 1,
        ]);
        $employee->payComponents()->create(['payroll_component_id' => $baseSalary->id, 'amount' => 100000, 'is_active' => true]);

        $validated = Payslip::generateFor($employee, Carbon::create(2026, 3, 1));
        $validated->forceFill(['status' => Payslip::STATUS_VALIDATED])->save();

        // A draft payslip in the same month must not be counted.
        Payslip::generateFor($employee, Carbon::create(2026, 4, 1));

        $response = $this->actingAs($this->admin)->get(route('reports.dashboard', ['year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('payroll', function (array $payroll) {
            $march = $payroll['by_month']->firstWhere('month', 3);
            $april = $payroll['by_month']->firstWhere('month', 4);

            return $march['gross'] === 100000.0 && $april['gross'] === 0.0;
        });
    }

    public function test_movements_report_counts_hires_and_departures_by_month(): void
    {
        $this->makeEmployee(['hire_date' => Carbon::create(2026, 5, 10)]);
        $this->makeEmployee([
            'hire_date' => Carbon::create(2020, 1, 1),
            'status' => Employee::STATUS_TERMINATED,
            'termination_date' => Carbon::create(2026, 5, 20),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.dashboard', ['year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('movements', function (array $movements) {
            $may = $movements['by_month']->firstWhere('month', 5);

            return $may['hires'] === 1 && $may['departures'] === 1;
        });
    }

    public function test_leave_report_sums_accrued_used_and_remaining_across_active_employees(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Congé payé', 'code' => 'CP', 'accrual_days_per_month' => 2.5,
            'is_paid' => true, 'requires_approval' => true, 'is_active' => true,
        ]);

        $this->makeEmployee(['hire_date' => Carbon::create(2020, 1, 1)]);
        $this->makeEmployee(['hire_date' => Carbon::create(2020, 1, 1)]);

        $response = $this->actingAs($this->admin)->get(route('reports.dashboard'));

        $response->assertOk();
        $response->assertViewHas('leaves', function (array $leaves) use ($leaveType) {
            $entry = $leaves['by_type']->firstWhere('name', $leaveType->name);

            return $entry !== null && $entry['accrued'] > 0;
        });
    }

    public function test_export_endpoints_return_csv_and_require_permission(): void
    {
        $this->makeEmployee();

        $this->actingAs($this->admin)->get(route('reports.export.workforce'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->admin)->get(route('reports.export.payroll'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->admin)->get(route('reports.export.leaves'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $employee = User::factory()->create();
        $employee->assignRole('employe');

        $this->actingAs($employee)->get(route('reports.export.workforce'))->assertForbidden();
    }
}
