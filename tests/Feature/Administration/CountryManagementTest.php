<?php

namespace Tests\Feature\Administration;

use App\Models\Country;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryManagementTest extends TestCase
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

    public function test_admin_can_create_a_country(): void
    {
        $response = $this->actingAs($this->admin)->post(route('administration.countries.store'), [
            '_modal' => 'country-create',
            'name' => 'Rwanda',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('administration.countries.index'));
        $this->assertDatabaseHas('countries', ['name' => 'Rwanda', 'is_active' => true]);
    }

    public function test_a_user_without_the_administration_permission_cannot_manage_countries(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($manager)->get(route('administration.countries.index'))->assertForbidden();
        $this->actingAs($manager)->post(route('administration.countries.store'), ['name' => 'Rwanda'])->assertForbidden();
    }

    public function test_country_options_includes_a_deactivated_country_only_if_it_is_the_current_value(): void
    {
        Country::create(['name' => 'Ghana', 'is_active' => true]);
        Country::create(['name' => 'Togo', 'is_active' => false]);

        $this->assertTrue(Country::options()->contains('Ghana'));
        $this->assertFalse(Country::options()->contains('Togo'));
        $this->assertTrue(Country::options('Togo')->contains('Togo'));
    }

    public function test_site_form_rejects_a_country_not_in_the_configured_list(): void
    {
        Country::create(['name' => 'Ghana', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('organisation.sites.store'), [
            'name' => 'Site test',
            'code' => 'ST1',
            'country' => 'Narnia',
        ]);

        $response->assertSessionHasErrors(['country']);
    }

    public function test_site_form_accepts_its_own_current_country_even_after_it_is_deactivated(): void
    {
        $country = Country::create(['name' => 'Ghana', 'is_active' => true]);
        $site = Site::create(['name' => 'Accra Office', 'code' => 'ACC', 'country' => 'Ghana']);
        $country->update(['is_active' => false]);

        $response = $this->actingAs($this->admin)->put(route('organisation.sites.update', $site), [
            'name' => 'Accra Office',
            'code' => 'ACC',
            'country' => 'Ghana',
        ]);

        $response->assertRedirect(route('organisation.sites.index'));
        $this->assertDatabaseHas('sites', ['id' => $site->id, 'country' => 'Ghana']);
    }
}
