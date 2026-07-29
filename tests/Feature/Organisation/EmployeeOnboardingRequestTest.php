<?php

namespace Tests\Feature\Organisation;

use App\Models\CompanySetting;
use App\Models\EmployeeOnboardingRequest;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOnboardingRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $rhAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->rhAdmin = User::factory()->create();
        $this->rhAdmin->assignRole('rh-admin');
    }

    public function test_anyone_can_submit_an_onboarding_request_without_authentication(): void
    {
        $response = $this->post(route('onboarding.store'), [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
            'personal_phone' => '0102030405',
            'city' => 'Abidjan',
        ]);

        $response->assertRedirect(route('onboarding.thanks'));
        $this->assertDatabaseHas('employee_onboarding_requests', [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
            'status' => EmployeeOnboardingRequest::STATUS_PENDING,
        ]);
    }

    public function test_the_form_requires_first_name_last_name_and_email(): void
    {
        $response = $this->post(route('onboarding.store'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'personal_email']);
    }

    public function test_a_user_without_employees_manage_permission_cannot_view_onboarding_requests(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employe');

        $this->actingAs($employee)
            ->get(route('organisation.employees.onboarding-requests.index'))
            ->assertForbidden();
    }

    public function test_rh_can_approve_a_pending_request_and_it_creates_an_employee(): void
    {
        $onboardingRequest = EmployeeOnboardingRequest::create([
            'first_name' => 'Fatou',
            'last_name' => 'Bamba',
            'personal_email' => 'fatou.bamba@example.com',
        ]);

        $response = $this->actingAs($this->rhAdmin)->post(
            route('organisation.employees.onboarding-requests.approve', $onboardingRequest)
        );

        $onboardingRequest->refresh();

        $response->assertRedirect(route('organisation.employees.show', $onboardingRequest->employee));
        $this->assertSame(EmployeeOnboardingRequest::STATUS_APPROVED, $onboardingRequest->status);
        $this->assertSame($this->rhAdmin->id, $onboardingRequest->reviewed_by_id);
        $this->assertNotNull($onboardingRequest->employee_id);

        $this->assertDatabaseHas('employees', [
            'id' => $onboardingRequest->employee_id,
            'first_name' => 'Fatou',
            'last_name' => 'Bamba',
            'personal_email' => 'fatou.bamba@example.com',
            'status' => 'active',
        ]);
    }

    public function test_rh_can_reject_a_pending_request_with_a_reason(): void
    {
        $onboardingRequest = EmployeeOnboardingRequest::create([
            'first_name' => 'Issa',
            'last_name' => 'Traoré',
            'personal_email' => 'issa.traore@example.com',
        ]);

        $response = $this->actingAs($this->rhAdmin)->post(
            route('organisation.employees.onboarding-requests.reject', $onboardingRequest),
            ['rejection_reason' => 'Informations incomplètes, à ressaisir.']
        );

        $onboardingRequest->refresh();

        $response->assertRedirect(route('organisation.employees.onboarding-requests.index'));
        $this->assertSame(EmployeeOnboardingRequest::STATUS_REJECTED, $onboardingRequest->status);
        $this->assertSame('Informations incomplètes, à ressaisir.', $onboardingRequest->rejection_reason);
        $this->assertDatabaseMissing('employees', ['personal_email' => 'issa.traore@example.com']);
    }

    public function test_an_already_reviewed_request_cannot_be_approved_again(): void
    {
        $onboardingRequest = EmployeeOnboardingRequest::create([
            'first_name' => 'Kadi',
            'last_name' => 'Ouattara',
            'personal_email' => 'kadi.ouattara@example.com',
        ]);
        $onboardingRequest->forceFill(['status' => EmployeeOnboardingRequest::STATUS_APPROVED])->save();

        $this->actingAs($this->rhAdmin)
            ->post(route('organisation.employees.onboarding-requests.approve', $onboardingRequest))
            ->assertStatus(400);
    }

    public function test_the_link_shows_as_closed_and_rejects_submissions_when_disabled(): void
    {
        CompanySetting::current()->update(['onboarding_enabled' => false]);

        $this->get(route('onboarding.create'))
            ->assertOk()
            ->assertSee('disponible pour le moment')
            ->assertDontSee('Envoyer mes informations');

        $this->post(route('onboarding.store'), [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
        ])->assertNotFound();

        $this->assertDatabaseMissing('employee_onboarding_requests', ['personal_email' => 'aya.kone@example.com']);
    }

    public function test_the_link_is_closed_before_its_scheduled_start_date(): void
    {
        CompanySetting::current()->update([
            'onboarding_enabled' => true,
            'onboarding_starts_at' => Carbon::now()->addDay(),
        ]);

        $this->post(route('onboarding.store'), [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
        ])->assertNotFound();
    }

    public function test_the_link_is_closed_after_its_end_date(): void
    {
        CompanySetting::current()->update([
            'onboarding_enabled' => true,
            'onboarding_ends_at' => Carbon::now()->subDay(),
        ]);

        $this->post(route('onboarding.store'), [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
        ])->assertNotFound();
    }

    public function test_the_link_accepts_submissions_within_its_activation_window(): void
    {
        CompanySetting::current()->update([
            'onboarding_enabled' => true,
            'onboarding_starts_at' => Carbon::now()->subDay(),
            'onboarding_ends_at' => Carbon::now()->addDay(),
        ]);

        $this->post(route('onboarding.store'), [
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'personal_email' => 'aya.kone@example.com',
        ])->assertRedirect(route('onboarding.thanks'));
    }

    public function test_rh_can_toggle_the_link_off_from_the_review_queue(): void
    {
        $this->assertTrue(CompanySetting::current()->onboarding_enabled);

        $response = $this->actingAs($this->rhAdmin)->put(
            route('organisation.employees.onboarding-settings.update'),
            ['onboarding_enabled' => '0']
        );

        $response->assertRedirect(route('organisation.employees.onboarding-requests.index'));
        $this->assertFalse(CompanySetting::current()->fresh()->onboarding_enabled);
    }

    public function test_a_user_without_employees_manage_permission_cannot_change_onboarding_settings(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employe');

        $this->actingAs($employee)
            ->put(route('organisation.employees.onboarding-settings.update'), ['onboarding_enabled' => '1'])
            ->assertForbidden();
    }
}
