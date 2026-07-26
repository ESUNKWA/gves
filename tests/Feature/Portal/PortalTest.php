<?php

namespace Tests\Feature\Portal;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Seeders\LeaveTypesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
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

    public function test_a_user_without_a_linked_employee_cannot_access_the_portal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('portal.profile.edit'));

        $response->assertForbidden();
    }

    public function test_an_employee_can_view_and_update_their_own_profile(): void
    {
        $user = $this->makeEmployeeUser();

        $this->actingAs($user)->get(route('portal.profile.edit'))->assertOk();

        $response = $this->actingAs($user)->put(route('portal.profile.update'), [
            'personal_email' => 'fatou@example.com',
            'personal_phone' => '+225 07 00 00 00',
        ]);

        $response->assertRedirect(route('portal.profile.edit'));
        $this->assertSame('fatou@example.com', $user->employee->fresh()->personal_email);
    }

    public function test_an_employee_can_submit_and_cancel_their_own_leave_request(): void
    {
        $user = $this->makeEmployeeUser();
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();

        $this->actingAs($user)->get(route('portal.leaves.index'))->assertOk();

        $this->actingAs($user)->post(route('portal.leaves.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-11',
        ])->assertRedirect(route('portal.leaves.index'));

        $leaveRequest = $user->employee->leaveRequests()->firstOrFail();
        $this->assertSame(LeaveRequest::STATUS_PENDING, $leaveRequest->status);

        $this->actingAs($user)
            ->delete(route('portal.leaves.destroy', $leaveRequest))
            ->assertRedirect(route('portal.leaves.index'));

        $this->assertDatabaseMissing('leave_requests', ['id' => $leaveRequest->id]);
    }

    public function test_an_employee_cannot_cancel_someone_elses_leave_request(): void
    {
        $user = $this->makeEmployeeUser();

        $otherEmployee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Autre',
            'last_name' => 'Personne',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $leaveType = LeaveType::where('code', 'CP')->firstOrFail();
        $leaveRequest = $otherEmployee->leaveRequests()->create([
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-11',
            'days_count' => 5,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->delete(route('portal.leaves.destroy', $leaveRequest));

        $response->assertForbidden();
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id]);
    }

    public function test_an_employee_can_only_download_their_own_documents(): void
    {
        $user = $this->makeEmployeeUser();

        $otherEmployee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Autre',
            'last_name' => 'Personne',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ]);
        $document = $otherEmployee->documents()->create([
            'category' => 'cv',
            'title' => 'CV',
            'file_path' => 'employee-documents/999/cv.pdf',
            'uploaded_at' => now(),
        ]);

        $this->actingAs($user)->get(route('portal.documents.index'))->assertOk();

        $response = $this->actingAs($user)->get(route('portal.documents.download', $document));

        $response->assertNotFound();
    }
}
