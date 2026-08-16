<?php

namespace Tests\Feature\Layout;

use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SidebarLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_sidebar_shows_the_generic_icon_when_no_logo_is_uploaded(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('company/', false);
    }

    public function test_sidebar_shows_the_company_logo_when_one_is_uploaded(): void
    {
        Storage::fake('public');

        $logoPath = Storage::disk('public')->putFile('company', UploadedFile::fake()->image('logo.png'));
        CompanySetting::current()->update(['logo_path' => $logoPath]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($logoPath, false);
    }

    public function test_sidebar_shows_the_company_name_instead_of_the_app_name(): void
    {
        CompanySetting::current()->update(['name' => 'Acme Corp']);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Acme Corp');
    }
}
