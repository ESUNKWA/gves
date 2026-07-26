<?php

namespace Tests\Feature\Documents;

use App\Models\DocumentTemplate;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\GeneratedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentSignatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A minimal 1x1 transparent PNG, used to satisfy the signature_data
     * validation rule and to exercise real PDF/image embedding.
     */
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

    public function test_hr_can_create_a_document_template(): void
    {
        $response = $this->actingAs($this->admin)->post(route('documents.templates.store'), [
            '_modal' => 'template-create',
            'name' => 'Attestation simple',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('documents.templates.index'));
        $this->assertDatabaseHas('document_templates', ['name' => 'Attestation simple']);
    }

    public function test_a_template_already_used_cannot_be_deleted(): void
    {
        $employee = $this->makeEmployee();
        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('documents.templates.destroy', $template));

        $response->assertRedirect(route('documents.templates.index'));
        $this->assertDatabaseHas('document_templates', ['id' => $template->id]);
    }

    public function test_hr_can_generate_a_document_for_an_employee_with_merge_fields_substituted(): void
    {
        $employee = $this->makeEmployee(['first_name' => 'Kadidia', 'last_name' => 'Ouattara']);
        $template = DocumentTemplate::create([
            'name' => 'Attestation de travail',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Nous attestons que {{employe.nom_complet}} travaille chez nous.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            [
                'document_template_id' => $template->id,
                'title' => 'Attestation - Kadidia',
            ]
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $generatedDocument = GeneratedDocument::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(GeneratedDocument::STATUS_PENDING, $generatedDocument->status);
        $this->assertStringContainsString('Kadidia Ouattara', $generatedDocument->content);
        $this->assertStringNotContainsString('{{employe.nom_complet}}', $generatedDocument->content);
    }

    public function test_a_user_without_documents_permission_cannot_generate_a_document(): void
    {
        $managerUser = User::factory()->create();
        $managerUser->assignRole('manager');

        $employee = $this->makeEmployee();
        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($managerUser)->post(
            route('organisation.employees.document-requests.store', $employee),
            ['document_template_id' => $template->id, 'title' => 'Attestation'],
        );

        $response->assertForbidden();
    }

    public function test_hr_can_cancel_a_pending_generated_document(): void
    {
        $employee = $this->makeEmployee();
        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('organisation.employees.document-requests.destroy', [$employee, $generatedDocument])
        );

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseMissing('generated_documents', ['id' => $generatedDocument->id]);
    }

    public function test_employee_can_view_their_own_pending_document(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);

        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('portal.document-requests.show', $generatedDocument));

        $response->assertOk();
    }

    public function test_employee_cannot_view_someone_elses_pending_document(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $this->makeEmployee(['user_id' => $employeeUser->id]);

        $otherEmployee = $this->makeEmployee(['first_name' => 'Autre']);
        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $generatedDocument = $otherEmployee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $otherEmployee),
            'status' => GeneratedDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employeeUser)->get(route('portal.document-requests.show', $generatedDocument));

        $response->assertNotFound();
    }

    public function test_employee_can_sign_their_own_pending_document_and_it_is_archived(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);

        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_PENDING,
        ]);

        $response = $this->actingAs($employeeUser)->post(
            route('portal.document-requests.sign', $generatedDocument),
            [
                'signature_data' => self::SAMPLE_SIGNATURE,
                'consent' => '1',
            ]
        );

        $response->assertRedirect(route('portal.documents.index'));

        $generatedDocument->refresh();
        $this->assertSame(GeneratedDocument::STATUS_SIGNED, $generatedDocument->status);
        $this->assertNotNull($generatedDocument->signed_at);
        $this->assertNotNull($generatedDocument->employee_document_id);
        $this->assertNotNull($generatedDocument->document_hash);

        $employeeDocument = EmployeeDocument::find($generatedDocument->employee_document_id);
        $this->assertNotNull($employeeDocument);
        $this->assertSame($employee->id, $employeeDocument->employee_id);
        $this->assertSame(EmployeeDocument::CATEGORY_ATTESTATION, $employeeDocument->category);
        Storage::disk('local')->assertExists($employeeDocument->file_path);
    }

    public function test_employee_cannot_sign_an_already_signed_document(): void
    {
        $employeeUser = User::factory()->create();
        $employeeUser->assignRole('employe');
        $employee = $this->makeEmployee(['user_id' => $employeeUser->id]);

        $template = DocumentTemplate::create([
            'name' => 'Attestation',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => 'Bonjour {{employe.nom_complet}}.',
            'is_active' => true,
        ]);
        $generatedDocument = $employee->generatedDocuments()->create([
            'document_template_id' => $template->id,
            'title' => 'Attestation',
            'content' => GeneratedDocument::renderContent($template->content, $employee),
            'status' => GeneratedDocument::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($employeeUser)->post(
            route('portal.document-requests.sign', $generatedDocument),
            [
                'signature_data' => self::SAMPLE_SIGNATURE,
                'consent' => '1',
            ]
        );

        $response->assertStatus(400);
    }
}
