<?php

namespace Tests\Feature\Administration;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
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

    public function test_super_admin_can_view_the_users_list(): void
    {
        $this->actingAs($this->admin)->get(route('administration.users.index'))->assertOk();
    }

    public function test_super_admin_can_change_another_users_role(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');

        $response = $this->actingAs($this->admin)->put(
            route('administration.users.update', $employeeUser),
            ['role' => 'manager']
        );

        $response->assertRedirect(route('administration.users.index'));
        $this->assertTrue($employeeUser->fresh()->hasRole('manager'));
        $this->assertFalse($employeeUser->fresh()->hasRole('employe'));
    }

    public function test_a_user_cannot_change_their_own_role(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('administration.users.update', $this->admin),
            ['role' => 'employe']
        );

        $response->assertForbidden();
        $this->assertTrue($this->admin->fresh()->hasRole('super-admin'));
    }

    public function test_a_user_without_the_administration_permission_cannot_manage_roles(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->get(route('administration.users.index'))->assertForbidden();

        $other = User::factory()->create();
        $other->assignRole('employe');
        $this->actingAs($manager)->put(route('administration.users.update', $other), ['role' => 'manager'])
            ->assertForbidden();
    }

    public function test_an_invalid_role_is_rejected(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');

        $response = $this->actingAs($this->admin)->put(
            route('administration.users.update', $employeeUser),
            ['role' => 'not-a-real-role']
        );

        $response->assertSessionHasErrors(['role']);
        $this->assertTrue($employeeUser->fresh()->hasRole('employe'));
    }
}
