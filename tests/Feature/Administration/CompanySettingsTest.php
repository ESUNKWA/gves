<?php

namespace Tests\Feature\Administration;

use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CountrySeeder::class);
    }

    public function test_super_admin_can_view_and_update_company_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('administration.company.edit'))->assertOk();

        $response = $this->actingAs($admin)->put(route('administration.company.update'), [
            'name' => 'Ekwatech SIRH',
            'legal_name' => 'Ekwatech SARL',
            'primary_color' => '#0f766e',
            'currency' => 'XOF',
            'address' => 'Cocody',
            'city' => 'Abidjan',
            'country' => "Côte d'Ivoire",
            'phone' => '+225 01 02 03 04 05',
            'email' => 'contact@ekwatech.example',
            'registration_number' => 'CI-ABJ-2026-B-1234',
            'tax_id' => '1234567A',
        ]);

        $response->assertRedirect(route('administration.company.edit'));

        $settings = CompanySetting::current();
        $this->assertSame('Ekwatech SIRH', $settings->name);
        $this->assertSame('Ekwatech SARL', $settings->legal_name);
        $this->assertSame('CI-ABJ-2026-B-1234', $settings->registration_number);
        $this->assertSame('#0f766e', $settings->primary_color);
    }

    public function test_uploading_a_logo_replaces_the_previous_one(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->put(route('administration.company.update'), [
            'name' => 'Ekwatech SIRH',
            'primary_color' => '#4f46e5',
            'currency' => 'XOF',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $settings = CompanySetting::current();
        $firstLogoPath = $settings->logo_path;
        Storage::disk('public')->assertExists($firstLogoPath);

        $this->actingAs($admin)->put(route('administration.company.update'), [
            'name' => 'Ekwatech SIRH',
            'primary_color' => '#4f46e5',
            'currency' => 'XOF',
            'logo' => UploadedFile::fake()->image('logo-v2.png'),
        ]);

        $settings->refresh();
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_a_user_without_the_administration_permission_cannot_access_company_settings(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->get(route('administration.company.edit'))->assertForbidden();
        $this->actingAs($manager)->put(route('administration.company.update'), ['name' => 'x', 'currency' => 'XOF'])->assertForbidden();
    }

    public function test_rh_admin_cannot_access_company_settings(): void
    {
        $rhAdmin = User::factory()->create();
        $rhAdmin->assignRole('rh-admin');

        $this->actingAs($rhAdmin)->get(route('administration.company.edit'))->assertForbidden();
    }
}
