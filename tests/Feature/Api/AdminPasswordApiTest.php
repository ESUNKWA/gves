<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.admin_email' => 'admin@sirh.test', 'app.admin_reset_token' => 'test-secret-token']);
    }

    public function test_a_valid_token_resets_the_admin_password(): void
    {
        $admin = User::factory()->create(['email' => 'admin@sirh.test']);

        $response = $this->putJson('/api/admin/password', ['password' => 'brand-new-password'], [
            'X-Admin-Reset-Token' => 'test-secret-token',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('brand-new-password', $admin->fresh()->password));
    }

    public function test_a_missing_token_is_rejected(): void
    {
        $admin = User::factory()->create(['email' => 'admin@sirh.test', 'password' => Hash::make('original')]);

        $response = $this->putJson('/api/admin/password', ['password' => 'brand-new-password']);

        $response->assertStatus(401);
        $this->assertTrue(Hash::check('original', $admin->fresh()->password));
    }

    public function test_a_wrong_token_is_rejected(): void
    {
        $admin = User::factory()->create(['email' => 'admin@sirh.test', 'password' => Hash::make('original')]);

        $response = $this->putJson('/api/admin/password', ['password' => 'brand-new-password'], [
            'X-Admin-Reset-Token' => 'not-the-right-token',
        ]);

        $response->assertStatus(401);
        $this->assertTrue(Hash::check('original', $admin->fresh()->password));
    }

    public function test_the_endpoint_is_disabled_when_no_reset_token_is_configured(): void
    {
        config(['app.admin_reset_token' => null]);
        User::factory()->create(['email' => 'admin@sirh.test']);

        $response = $this->putJson('/api/admin/password', ['password' => 'brand-new-password'], [
            'X-Admin-Reset-Token' => 'anything',
        ]);

        $response->assertStatus(503);
    }

    public function test_the_new_password_must_meet_minimum_requirements(): void
    {
        User::factory()->create(['email' => 'admin@sirh.test']);

        $response = $this->putJson('/api/admin/password', ['password' => 'short'], [
            'X-Admin-Reset-Token' => 'test-secret-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_it_returns_404_when_no_admin_account_exists_yet(): void
    {
        $response = $this->putJson('/api/admin/password', ['password' => 'brand-new-password'], [
            'X-Admin-Reset-Token' => 'test-secret-token',
        ]);

        $response->assertStatus(404);
    }
}
