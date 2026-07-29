<?php

namespace Tests\Feature\Administration;

use App\Models\Department;
use App\Models\PayrollComponent;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\OrganisationStarterSeeder;
use Database\Seeders\PayrollComponentsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_a_super_admin_from_config(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['app.admin_name' => 'Direction', 'app.admin_email' => 'boss@client.test', 'app.admin_password' => 'secret-1234']);

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'boss@client.test')->firstOrFail();
        $this->assertSame('Direction', $admin->name);
        $this->assertTrue($admin->hasRole('super-admin'));
    }

    public function test_admin_user_seeder_is_idempotent_and_does_not_duplicate_the_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['app.admin_email' => 'boss@client.test', 'app.admin_password' => 'secret-1234']);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'boss@client.test')->count());
    }

    public function test_admin_user_seeder_generates_a_random_password_when_none_is_configured(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['app.admin_email' => 'boss@client.test', 'app.admin_password' => null]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'boss@client.test')->firstOrFail();
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('password', $admin->password));
    }

    public function test_admin_user_seeder_reattaches_the_super_admin_role_if_missing(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['app.admin_email' => 'boss@client.test', 'app.admin_password' => 'secret-1234']);

        $admin = User::factory()->create(['email' => 'boss@client.test']);
        $this->assertFalse($admin->hasRole('super-admin'));

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }

    public function test_organisation_starter_seeder_creates_departments_and_positions(): void
    {
        $this->seed(OrganisationStarterSeeder::class);

        $this->assertTrue(Department::where('code', 'RH')->exists());
        $this->assertTrue(Position::where('code', 'RH-01')->exists());

        $position = Position::where('code', 'RH-01')->firstOrFail();
        $department = Department::where('code', 'RH')->firstOrFail();
        $this->assertSame($department->id, $position->department_id);
    }

    public function test_organisation_starter_seeder_is_idempotent(): void
    {
        $this->seed(OrganisationStarterSeeder::class);
        $this->seed(OrganisationStarterSeeder::class);

        $this->assertSame(1, Department::where('code', 'RH')->count());
        $this->assertSame(1, Position::where('code', 'RH-01')->count());
    }

    public function test_payroll_components_seeder_flags_the_base_salary_component(): void
    {
        $this->seed(PayrollComponentsSeeder::class);

        $baseSalary = PayrollComponent::where('code', 'SALAIRE_BASE')->firstOrFail();
        $this->assertTrue($baseSalary->is_base_salary);
    }
}
