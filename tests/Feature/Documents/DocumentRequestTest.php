<?php

namespace Tests\Feature\Documents;

use App\Models\DocumentRequest;
use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\GeneratedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private DocumentTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->template = DocumentTemplate::create([
            'name' => 'Attestation de travail',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
    }

    private function makeEmployeeWithUser(array $attributes = []): Employee
    {
        $user = User::factory()->create();
        $user->assignRole('employe');

        return Employee::create(array_merge([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'user_id' => $user->id,
        ], $attributes));
    }

    public function test_employee_can_request_a_document(): void
    {
        $employee = $this->makeEmployeeWithUser();

        $response = $this->actingAs($employee->user)->post(route('portal.document-requests.store'), [
            'document_template_id' => $this->template->id,
            'reason' => 'Dossier bancaire',
        ]);

        $response->assertRedirect(route('portal.documents.index'));
        $this->assertDatabaseHas('document_requests', [
            'employee_id' => $employee->id,
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
            'reason' => 'Dossier bancaire',
        ]);
    }

    public function test_employee_can_cancel_their_own_pending_request(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employee->user)->delete(
            route('portal.document-requests.destroy', $documentRequest)
        );

        $response->assertRedirect(route('portal.documents.index'));
        $this->assertDatabaseMissing('document_requests', ['id' => $documentRequest->id]);
    }

    public function test_employee_cannot_cancel_someone_elses_request(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $otherEmployee = $this->makeEmployeeWithUser(['first_name' => 'Autre']);
        $documentRequest = $otherEmployee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employee->user)->delete(
            route('portal.document-requests.destroy', $documentRequest)
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('document_requests', ['id' => $documentRequest->id]);
    }

    public function test_hr_can_approve_a_request_which_generates_a_document_for_signature(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('documents.document-requests.approve', $documentRequest)
        );

        $response->assertRedirect();
        $documentRequest->refresh();
        $this->assertSame(DocumentRequest::STATUS_FULFILLED, $documentRequest->status);
        $this->assertNotNull($documentRequest->generated_document_id);

        $generatedDocument = GeneratedDocument::find($documentRequest->generated_document_id);
        $this->assertSame($employee->id, $generatedDocument->employee_id);
        $this->assertSame(GeneratedDocument::STATUS_PENDING, $generatedDocument->status);
        $this->assertStringContainsString($employee->full_name, $generatedDocument->content);
    }

    public function test_hr_can_reject_a_request_with_a_reason(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('documents.document-requests.reject', $documentRequest),
            ['decision_note' => 'Gabarit non applicable à votre contrat.']
        );

        $response->assertRedirect();
        $this->assertSame(DocumentRequest::STATUS_REJECTED, $documentRequest->fresh()->status);
    }

    public function test_rejecting_a_request_requires_a_reason(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('documents.document-requests.reject', $documentRequest),
            []
        );

        $response->assertSessionHasErrors(['decision_note']);
        $this->assertSame(DocumentRequest::STATUS_PENDING, $documentRequest->fresh()->status);
    }

    public function test_a_user_without_documents_permission_cannot_approve_requests(): void
    {
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');

        $employee = $this->makeEmployeeWithUser();
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $this->template->id,
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $this->actingAs($managerUser)->post(route('documents.document-requests.approve', $documentRequest))
            ->assertForbidden();
    }
}
