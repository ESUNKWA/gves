<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\LeaveTypesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LeaveTypesSeeder::class);
    }

    private function makeEmployeeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('employe');

        Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Fatou',
            'last_name' => 'Bamba',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => $user->id,
        ]);

        return $user->fresh();
    }

    public function test_a_plain_employee_sees_their_personal_overview_instead_of_the_empty_fallback(): void
    {
        $user = $this->makeEmployeeUser();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('myOverview');
        $response->assertViewHas('stats', null);
        $response->assertSee('Mon espace');
        $response->assertSee('Mon pointage');
        $response->assertSee('Mes congés');
        $response->assertDontSee('Bienvenue sur votre espace RH.');
    }

    public function test_the_overview_reflects_leave_balances_and_pending_requests(): void
    {
        $user = $this->makeEmployeeUser();
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();

        $user->employee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addDays(2),
            'days_count' => 3,
            'status' => \App\Models\LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('myOverview', function (array $overview) {
            return $overview['pendingLeaveRequests'] === 1 && $overview['leaveBalances']->isNotEmpty();
        });
        $response->assertSee('1 demande en attente');
    }

    public function test_a_user_without_a_linked_employee_or_permissions_sees_the_fallback_message(): void
    {
        $user = User::factory()->create();
        $user->assignRole('employe');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('myOverview', null);
        $response->assertSee('Bienvenue sur votre espace RH.');
    }

    public function test_a_super_admin_still_sees_organisation_wide_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return array_key_exists('activeEmployees', $stats);
        });
    }
}
