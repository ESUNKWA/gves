<?php

namespace Tests\Feature\Attendance;

use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeAndAttendanceTest extends TestCase
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

    private function makeEmployeeWithUser(array $attributes = []): Employee
    {
        $user = User::factory()->create();
        $user->assignRole('employe');

        return Employee::create(array_merge([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => $user->id,
        ], $attributes));
    }

    public function test_employee_can_clock_in(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'));

        $response->assertRedirect(route('portal.time-clock.index'));
        $entry = $employee->timeEntries()->first();
        $this->assertSame(today()->toDateString(), $entry->date->toDateString());
        $this->assertSame(TimeEntry::SOURCE_SELF, $entry->source);
        $this->assertNotNull($entry->clock_in);
    }

    public function test_clocking_in_records_the_request_ip_as_an_audit_trail(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'));

        $entry = $employee->timeEntries()->first();
        $this->assertNotNull($entry->clock_in_ip);
    }

    public function test_clocking_in_with_coordinates_records_them_but_never_blocks_without(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'), [
            'latitude' => '5.359951',
            'longitude' => '-4.008256',
        ]);

        $response->assertRedirect(route('portal.time-clock.index'));
        $entry = $employee->timeEntries()->first();
        $this->assertSame('5.3599510', (string) $entry->clock_in_latitude);
        $this->assertSame('-4.0082560', (string) $entry->clock_in_longitude);
    }

    public function test_employee_cannot_clock_in_twice_the_same_day(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'));

        $response = $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'));

        $response->assertStatus(400);
        $this->assertSame(1, $employee->timeEntries()->count());
    }

    public function test_employee_can_clock_out_after_clocking_in(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $this->actingAs($employee->user)->post(route('portal.time-clock.clock-in'));

        $response = $this->actingAs($employee->user)->post(route('portal.time-clock.clock-out'));

        $response->assertRedirect(route('portal.time-clock.index'));
        $this->assertNotNull($employee->timeEntries()->first()->clock_out);
    }

    public function test_employee_cannot_clock_out_before_clocking_in(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($employee->user)->post(route('portal.time-clock.clock-out'));

        $response->assertStatus(400);
    }

    public function test_late_arrival_is_calculated_against_the_employees_schedule(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 7, 13, 9, 30)); // a Monday, 09:30

        $employee = $this->makeEmployeeWithUser();
        $schedule = WorkSchedule::create([
            'employee_id' => $employee->id,
            'monday_start' => '08:00',
            'monday_end' => '17:00',
        ]);

        $entry = $employee->timeEntries()->create([
            'date' => '2026-07-13',
            'clock_in' => '2026-07-13 09:30:00',
            'source' => TimeEntry::SOURCE_SELF,
        ]);

        $this->assertSame(90, $entry->lateMinutes($schedule));

        Carbon::setTestNow();
    }

    public function test_hr_can_set_an_employees_work_schedule(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($this->admin)->put(
            route('organisation.employees.work-schedule.update', $employee),
            [
                'monday_start' => '08:00',
                'monday_end' => '17:00',
                'tuesday_start' => '08:00',
                'tuesday_end' => '17:00',
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('work_schedules', [
            'employee_id' => $employee->id,
            'monday_start' => '08:00',
            'friday_start' => null,
        ]);
    }

    public function test_an_employee_without_their_own_schedule_uses_the_company_default(): void
    {
        WorkSchedule::default()->update(['monday_start' => '08:00', 'monday_end' => '17:00']);

        $employee = $this->makeEmployeeWithUser();

        $this->assertNull($employee->workSchedule);
        $this->assertTrue($employee->effectiveWorkSchedule()->isDefault());
        $this->assertSame('08:00', $employee->effectiveWorkSchedule()->monday_start);
    }

    public function test_an_employees_own_schedule_overrides_the_company_default(): void
    {
        WorkSchedule::default()->update(['monday_start' => '08:00', 'monday_end' => '17:00']);

        $employee = $this->makeEmployeeWithUser();
        WorkSchedule::create([
            'employee_id' => $employee->id,
            'monday_start' => '10:00',
            'monday_end' => '19:00',
        ]);

        $this->assertFalse($employee->effectiveWorkSchedule()->isDefault());
        $this->assertSame('10:00', $employee->effectiveWorkSchedule()->monday_start);
    }

    public function test_hr_can_set_the_default_work_schedule(): void
    {
        $response = $this->actingAs($this->admin)->put(route('attendance.work-schedule.update'), [
            'monday_start' => '08:00',
            'monday_end' => '17:00',
        ]);

        $response->assertRedirect(route('attendance.requests.index'));
        $this->assertDatabaseHas('work_schedules', [
            'employee_id' => null,
            'monday_start' => '08:00',
        ]);
    }

    public function test_a_user_without_attendance_permission_cannot_set_the_default_work_schedule(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');

        $this->actingAs($employeeUser)->put(route('attendance.work-schedule.update'), [
            'monday_start' => '08:00',
            'monday_end' => '17:00',
        ])->assertForbidden();
    }

    public function test_hr_can_manually_add_a_time_entry_for_an_employee(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.time-entries.store', $employee),
            [
                'date' => '2026-07-13',
                'clock_in' => '08:05',
                'clock_out' => '17:10',
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $entry = $employee->timeEntries()->first();
        $this->assertSame('2026-07-13', $entry->date->toDateString());
        $this->assertSame(TimeEntry::SOURCE_MANUAL, $entry->source);
        $this->assertSame('08:05', $entry->clock_in->format('H:i'));
        $this->assertSame('17:10', $entry->clock_out->format('H:i'));
    }

    public function test_hr_can_delete_a_time_entry(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $entry = $employee->timeEntries()->create([
            'date' => '2026-07-13',
            'clock_in' => '2026-07-13 08:00:00',
            'source' => TimeEntry::SOURCE_SELF,
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('organisation.employees.time-entries.destroy', [$employee, $entry])
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseMissing('time_entries', ['id' => $entry->id]);
    }

    public function test_a_manager_viewing_attendance_suivi_only_sees_their_own_team(): void
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

        $report = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Subordonné',
            'last_name' => 'Test',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'manager_id' => $managerEmployee->id,
        ]);

        $outsider = $this->makeEmployeeWithUser(['first_name' => 'Autre']);

        $response = $this->actingAs($managerUser)->get(route('attendance.requests.index'));

        $response->assertOk();
        $response->assertSee($report->full_name);
        $response->assertDontSee($outsider->full_name);
    }

    public function test_a_user_without_reports_or_permission_cannot_view_attendance_suivi(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');

        $this->actingAs($employeeUser)->get(route('attendance.requests.index'))->assertForbidden();
    }
}
