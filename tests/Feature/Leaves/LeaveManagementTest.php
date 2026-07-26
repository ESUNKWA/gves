<?php

namespace Tests\Feature\Leaves;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\LeaveTypesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LeaveTypesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_business_days_calculation_excludes_weekends(): void
    {
        // Monday 2026-07-13 to Friday 2026-07-17: 5 business days.
        $this->assertSame(5.0, LeaveRequest::calculateDaysCount('2026-07-13', '2026-07-17'));

        // Monday to the following Monday (spans one weekend): 6 business days.
        $this->assertSame(6.0, LeaveRequest::calculateDaysCount('2026-07-13', '2026-07-20'));

        // A single Saturday: 0 business days.
        $this->assertSame(0.0, LeaveRequest::calculateDaysCount('2026-07-18', '2026-07-18'));
    }

    public function test_leave_balance_accrues_proportionally_to_months_elapsed(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 15));

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya',
            'last_name' => 'N\'Guessan',
            'hire_date' => Carbon::create(2026, 1, 1),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();
        $balance = LeaveBalance::forEmployee($employee, $leaveType, 2026);

        // Hired Jan 1, "today" is mid-July: 7 months elapsed (Jan..Jul) x 2.2 = 15.4.
        $this->assertSame(15.4, $balance->accruedDays());

        Carbon::setTestNow();
    }

    public function test_hr_can_create_a_leave_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('leaves.types.store'), [
            '_modal' => 'leave-type-create',
            'name' => 'Congé exceptionnel',
            'code' => 'CE',
            'is_paid' => '1',
            'requires_approval' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('leaves.types.index'));
        $this->assertDatabaseHas('leave_types', ['code' => 'CE', 'name' => 'Congé exceptionnel']);
    }

    public function test_hr_can_create_a_leave_request_for_an_employee(): void
    {
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Koffi',
            'last_name' => 'Assouan',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.leave-requests.store', $employee),
            [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-07',
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => LeaveRequest::STATUS_PENDING,
            'days_count' => 5,
        ]);
    }

    public function test_overlapping_leave_requests_are_rejected(): void
    {
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Adjoua',
            'last_name' => 'Kouassi',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();

        $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-07',
            'days_count' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.leave-requests.store', $employee),
            [
                'leave_type_id' => $leaveType->id,
                'start_date' => '2026-08-05',
                'end_date' => '2026-08-10',
            ]
        );

        $response->assertSessionHasErrors(['start_date']);
        $this->assertSame(1, $employee->leaveRequests()->count());
    }

    public function test_direct_manager_can_approve_a_subordinates_request(): void
    {
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');

        $managerEmployee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Manager',
            'last_name' => 'Direct',
            'hire_date' => now()->subYears(2),
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => $managerUser->id,
        ]);

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Subordonné',
            'last_name' => 'Test',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'manager_id' => $managerEmployee->id,
        ]);

        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();
        $leaveRequest = $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-07',
            'days_count' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($managerUser)->post(route('leaves.requests.approve', $leaveRequest));

        $response->assertRedirect();
        $this->assertSame(LeaveRequest::STATUS_APPROVED, $leaveRequest->fresh()->status);
    }

    public function test_a_manager_cannot_approve_a_request_from_someone_who_is_not_their_report(): void
    {
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');

        Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Manager',
            'last_name' => 'SansEquipe',
            'hire_date' => now()->subYears(2),
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => $managerUser->id,
        ]);

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Autre',
            'last_name' => 'Employé',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();
        $leaveRequest = $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-07',
            'days_count' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($managerUser)->post(route('leaves.requests.approve', $leaveRequest));

        $response->assertForbidden();
        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->fresh()->status);
    }

    public function test_rejecting_a_request_requires_a_reason(): void
    {
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Rejet',
            'last_name' => 'Test',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();
        $leaveRequest = $employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-03',
            'end_date' => '2026-08-07',
            'days_count' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(route('leaves.requests.reject', $leaveRequest), []);

        $response->assertSessionHasErrors(['decision_note']);
        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->fresh()->status);
    }
}
