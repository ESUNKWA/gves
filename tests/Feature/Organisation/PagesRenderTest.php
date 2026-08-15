<?php

namespace Tests\Feature\Organisation;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\DocumentTemplatesSeeder;
use Database\Seeders\LeaveTypesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_authenticated_page_renders_without_error(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LeaveTypesSeeder::class);
        $this->seed(DocumentTemplatesSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $site = Site::create(['name' => 'Siège', 'code' => 'HQ']);
        $department = Department::create(['name' => 'RH', 'code' => 'RH', 'site_id' => $site->id]);
        $position = Position::create(['title' => 'Chargé RH', 'department_id' => $department->id]);

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Kadidia',
            'last_name' => 'Ouattara',
            'site_id' => $site->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'start_date' => now(),
            'status' => Contract::STATUS_ACTIVE,
        ]);

        $employee->update(['user_id' => $admin->id]);

        $this->actingAs($admin);

        $routes = [
            'dashboard',
            'profile',
            'organisation.sites.index',
            'organisation.departments.index',
            'organisation.positions.index',
            'organisation.employees.index',
            'organisation.employees.create',
            'leaves.types.index',
            'leaves.requests.index',
            'portal.profile.edit',
            'portal.leaves.index',
            'portal.documents.index',
            'documents.templates.index',
            'documents.requests.index',
            'administration.countries.index',
            'administration.users.index',
            'attendance.requests.index',
            'portal.time-clock.index',
            'documents.document-requests.index',
            'payroll.components.index',
            'payroll.payslips.index',
            'portal.payslips.index',
            'administration.holidays.index',
            'reports.dashboard',
            'organisation.employees.onboarding-requests.index',
        ];

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('organisation.employees.show', $employee))->assertOk();
        $this->get(route('organisation.employees.edit', $employee))->assertOk();
    }

    public function test_guest_pages_render_without_error(): void
    {
        $this->get('/')->assertRedirect(route('login', absolute: false));

        $routes = ['login', 'login.personnel', 'register', 'password.request', 'onboarding.create', 'onboarding.thanks'];

        foreach ($routes as $route) {
            $this->get(route($route))->assertOk();
        }
    }
}
