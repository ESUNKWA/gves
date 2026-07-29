<?php

namespace Tests\Feature\Documents;

use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\GeneratedDocument;
use App\Models\GeneratedDocumentApproval;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    private function makeEmployee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aya',
            'last_name' => 'Koné',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
        ], $attributes));
    }

    private function makeChainedTemplate(array $steps): DocumentTemplate
    {
        return DocumentTemplate::create([
            'name' => 'Avance sur salaire',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Demande de {{employe.nom_complet}}.',
            'is_active' => true,
            'approval_steps' => $steps,
        ]);
    }

    public function test_hr_can_configure_ordered_approval_steps_on_a_template(): void
    {
        $response = $this->actingAs($this->admin)->post(route('documents.templates.store'), [
            '_modal' => 'template-create',
            'name' => 'Avance sur salaire',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => '1',
            'approval_steps' => [
                DocumentTemplate::STEP_MANAGER,
                DocumentTemplate::STEP_HR,
                DocumentTemplate::STEP_PAYROLL,
            ],
        ]);

        $response->assertRedirect(route('documents.templates.index'));

        $template = DocumentTemplate::where('name', 'Avance sur salaire')->firstOrFail();
        $this->assertSame([
            DocumentTemplate::STEP_MANAGER,
            DocumentTemplate::STEP_HR,
            DocumentTemplate::STEP_PAYROLL,
        ], $template->approval_steps);
    }

    public function test_generating_a_document_from_a_chained_template_creates_ordered_pending_approvals(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_MANAGER, DocumentTemplate::STEP_HR]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance - Aya']
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertCount(2, $generatedDocument->approvals);
        $this->assertSame(DocumentTemplate::STEP_MANAGER, $generatedDocument->approvals[0]->step_type);
        $this->assertSame(DocumentTemplate::STEP_HR, $generatedDocument->approvals[1]->step_type);
        $this->assertTrue($generatedDocument->approvals->every(
            fn (GeneratedDocumentApproval $a) => $a->status === GeneratedDocumentApproval::STATUS_PENDING
        ));
    }

    public function test_generating_a_document_from_an_unchained_template_creates_no_approval_rows(): void
    {
        $employee = $this->makeEmployee();
        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Attestation']
        );

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertCount(0, $generatedDocument->approvals);
    }

    public function test_steps_must_be_completed_in_order_and_only_the_right_person_can_act(): void
    {
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');
        $manager = $this->makeEmployee(['first_name' => 'Chef', 'user_id' => $managerUser->id]);

        $employee = $this->makeEmployee(['manager_id' => $manager->id]);
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_MANAGER, DocumentTemplate::STEP_HR]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance']
        );
        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $hrApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_HR)->firstOrFail();

        // HR tries to act before the manager step is resolved: forbidden because it's not their turn.
        $response = $this->actingAs($this->admin)->post(route('approvals.approve', $hrApproval), []);
        $response->assertStatus(400);

        // A random employee has no role in this chain at all: forbidden.
        $strangerUser = User::factory()->create();
        $strangerUser->assignRole('employe');
        $this->makeEmployee(['first_name' => 'Etranger', 'user_id' => $strangerUser->id]);
        $managerApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_MANAGER)->firstOrFail();
        $this->actingAs($strangerUser)->post(route('approvals.approve', $managerApproval), [])->assertForbidden();

        // The manager approves their step.
        $this->actingAs($managerUser)->post(route('approvals.approve', $managerApproval), [])
            ->assertRedirect(route('approvals.index'));

        $generatedDocument->refresh();
        $this->assertSame(GeneratedDocumentApproval::STATUS_APPROVED, $managerApproval->fresh()->status);
        $this->assertSame(GeneratedDocument::STATUS_PENDING, $generatedDocument->status);

        // Now HR can act, since it's their turn.
        $this->actingAs($this->admin)->post(route('approvals.approve', $hrApproval), [
            'signature_data' => self::SAMPLE_SIGNATURE,
        ])->assertRedirect(route('approvals.index'));

        $generatedDocument->refresh();
        $this->assertSame(GeneratedDocument::STATUS_SIGNED, $generatedDocument->status);
        $this->assertNotNull($generatedDocument->document_hash);
        $this->assertNotNull($generatedDocument->employee_document_id);

        $employeeDocument = EmployeeDocument::find($generatedDocument->employee_document_id);
        $this->assertNotNull($employeeDocument);
        Storage::disk('local')->assertExists($employeeDocument->file_path);
    }

    public function test_a_rejection_at_any_step_cancels_the_whole_document(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_HR, DocumentTemplate::STEP_DIRECTION]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance']
        );
        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        // The leading "hr" step auto-approves for the HR user who just generated
        // the document (see test_hr_only_leading_step_does_not_auto_approve_the_next_step_in_a_longer_chain),
        // so "direction" is the step actually still awaiting a decision here.
        $directionApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_DIRECTION)->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('approvals.reject', $directionApproval), [
            'note' => 'Motif insuffisant',
        ]);
        $response->assertRedirect(route('approvals.index'));

        $generatedDocument->refresh();
        $this->assertSame(GeneratedDocument::STATUS_CANCELLED, $generatedDocument->status);
        $this->assertSame(GeneratedDocumentApproval::STATUS_REJECTED, $directionApproval->fresh()->status);

        $hrApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_HR)->firstOrFail();
        $this->assertSame(GeneratedDocumentApproval::STATUS_APPROVED, $hrApproval->status);
    }

    public function test_legacy_single_signature_flow_is_blocked_once_a_document_has_a_configured_chain(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_REQUESTER, DocumentTemplate::STEP_HR]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance']
        );
        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();

        $this->actingAs($employeeUser)
            ->get(route('portal.document-requests.show', $generatedDocument))
            ->assertStatus(400);

        $this->actingAs($employeeUser)
            ->post(route('portal.document-requests.sign', $generatedDocument), [
                'signature_data' => self::SAMPLE_SIGNATURE,
                'consent' => '1',
            ])
            ->assertStatus(400);
    }

    public function test_a_document_with_a_configured_chain_does_not_appear_in_the_legacy_pending_signatures_list(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_REQUESTER]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance']
        );

        $response = $this->actingAs($employeeUser)->get(route('portal.documents.index'));

        $response->assertOk();
        $response->assertViewHas('pendingSignatures', fn ($pendingSignatures) => $pendingSignatures->isEmpty());
    }

    public function test_approving_an_hr_only_chain_request_finalizes_the_document_in_a_single_click(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_HR]);
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $template->id,
            'reason' => 'Avance pour frais médicaux',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('documents.document-requests.approve', $documentRequest));

        $response->assertRedirect(route('documents.document-requests.index'));

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(GeneratedDocument::STATUS_SIGNED, $generatedDocument->status);
        $this->assertNotNull($generatedDocument->employee_document_id);
        $this->assertNotNull($generatedDocument->document_hash);

        $hrApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_HR)->firstOrFail();
        $this->assertSame(GeneratedDocumentApproval::STATUS_APPROVED, $hrApproval->status);
        $this->assertSame($this->admin->id, $hrApproval->decided_by);

        $employeeDocument = EmployeeDocument::find($generatedDocument->employee_document_id);
        $this->assertNotNull($employeeDocument);
        Storage::disk('local')->assertExists($employeeDocument->file_path);
    }

    public function test_hr_direct_generation_with_an_hr_only_chain_also_finalizes_in_one_click(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_HR]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Attestation']
        );

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(GeneratedDocument::STATUS_SIGNED, $generatedDocument->status);
    }

    public function test_hr_only_leading_step_does_not_auto_approve_the_next_step_in_a_longer_chain(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_HR, DocumentTemplate::STEP_DIRECTION]);
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $template->id,
            'reason' => 'Avance',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post(route('documents.document-requests.approve', $documentRequest));

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(GeneratedDocument::STATUS_PENDING, $generatedDocument->status);

        $hrApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_HR)->firstOrFail();
        $this->assertSame(GeneratedDocumentApproval::STATUS_APPROVED, $hrApproval->status);

        $directionApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_DIRECTION)->firstOrFail();
        $this->assertSame(GeneratedDocumentApproval::STATUS_PENDING, $directionApproval->status);
        $this->assertSame($directionApproval->id, $generatedDocument->currentApproval()?->id);
    }

    public function test_a_leading_manager_step_is_not_auto_approved_by_the_hr_user_approving_the_request(): void
    {
        $employee = $this->makeEmployee();
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_MANAGER, DocumentTemplate::STEP_HR]);
        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $template->id,
            'reason' => 'Avance',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post(route('documents.document-requests.approve', $documentRequest));

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $managerApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_MANAGER)->firstOrFail();
        $this->assertSame(GeneratedDocumentApproval::STATUS_PENDING, $managerApproval->status);
        $this->assertSame($managerApproval->id, $generatedDocument->currentApproval()?->id);
    }

    public function test_the_requester_can_act_on_a_requester_step_via_the_unified_approval_inbox(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);
        $template = $this->makeChainedTemplate([DocumentTemplate::STEP_REQUESTER, DocumentTemplate::STEP_HR]);

        $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Avance']
        );
        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $requesterApproval = $generatedDocument->approvals()->where('step_type', DocumentTemplate::STEP_REQUESTER)->firstOrFail();

        $this->actingAs($employeeUser)->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('Avance');

        $this->actingAs($employeeUser)
            ->post(route('approvals.approve', $requesterApproval), [
                'signature_data' => self::SAMPLE_SIGNATURE,
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(GeneratedDocumentApproval::STATUS_APPROVED, $requesterApproval->fresh()->status);
    }
}
