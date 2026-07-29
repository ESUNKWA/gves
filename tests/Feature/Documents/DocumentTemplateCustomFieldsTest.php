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

class DocumentTemplateCustomFieldsTest extends TestCase
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

    public function test_hr_can_create_a_template_with_custom_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('documents.templates.store'), [
            'name' => "Demande d'avance sur salaire",
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Montant demandé : {{demande.montant}}. Motif : {{demande.motif}}.',
            'is_active' => '1',
            'fields' => [
                ['label' => 'Montant', 'type' => 'number', 'required' => '1'],
                ['label' => 'Motif', 'type' => 'textarea', 'required' => ''],
            ],
        ]);

        $response->assertRedirect(route('documents.templates.index'));

        $template = DocumentTemplate::firstWhere('name', "Demande d'avance sur salaire");
        $this->assertNotNull($template);
        $this->assertSame([
            ['key' => 'montant', 'label' => 'Montant', 'type' => 'number', 'required' => true],
            ['key' => 'motif', 'label' => 'Motif', 'type' => 'textarea', 'required' => false],
        ], $template->fields);
    }

    public function test_blank_field_rows_are_dropped_and_duplicate_labels_get_unique_keys(): void
    {
        $this->actingAs($this->admin)->post(route('documents.templates.store'), [
            'name' => 'Gabarit test',
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Contenu',
            'is_active' => '1',
            'fields' => [
                ['label' => 'Montant', 'type' => 'number', 'required' => '1'],
                ['label' => '', 'type' => 'text', 'required' => ''],
                ['label' => 'Montant', 'type' => 'text', 'required' => ''],
            ],
        ]);

        $template = DocumentTemplate::firstWhere('name', 'Gabarit test');
        $this->assertCount(2, $template->fields);
        $this->assertSame('montant', $template->fields[0]['key']);
        $this->assertSame('montant_2', $template->fields[1]['key']);
    }

    public function test_employee_must_fill_required_custom_fields_when_requesting_a_document(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $template = DocumentTemplate::create([
            'name' => "Demande d'avance sur salaire",
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Montant : {{demande.montant}}',
            'fields' => [
                ['key' => 'montant', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['key' => 'motif', 'label' => 'Motif', 'type' => 'textarea', 'required' => false],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee->user)->post(route('portal.document-requests.store'), [
            'document_template_id' => $template->id,
        ]);

        $response->assertSessionHasErrors(['field_values.montant']);
        $this->assertDatabaseMissing('document_requests', ['employee_id' => $employee->id]);
    }

    public function test_employee_can_submit_custom_field_values_with_their_request(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $template = DocumentTemplate::create([
            'name' => "Demande d'avance sur salaire",
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Montant : {{demande.montant}}. Motif : {{demande.motif}}.',
            'fields' => [
                ['key' => 'montant', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['key' => 'motif', 'label' => 'Motif', 'type' => 'textarea', 'required' => false],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($employee->user)->post(route('portal.document-requests.store'), [
            'document_template_id' => $template->id,
            'field_values' => ['montant' => '150000', 'motif' => 'Frais médicaux'],
        ]);

        $response->assertRedirect(route('portal.documents.index'));
        $documentRequest = DocumentRequest::firstWhere('employee_id', $employee->id);
        $this->assertSame(['montant' => '150000', 'motif' => 'Frais médicaux'], $documentRequest->field_values);
    }

    public function test_approving_a_request_substitutes_custom_field_values_into_the_generated_document(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $template = DocumentTemplate::create([
            'name' => "Demande d'avance sur salaire",
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Montant demandé : {{demande.montant}} FCFA. Motif : {{demande.motif}}.',
            'fields' => [
                ['key' => 'montant', 'label' => 'Montant', 'type' => 'number', 'required' => true],
                ['key' => 'motif', 'label' => 'Motif', 'type' => 'textarea', 'required' => false],
            ],
            'is_active' => true,
        ]);

        $documentRequest = $employee->documentRequests()->create([
            'document_template_id' => $template->id,
            'field_values' => ['montant' => '150000', 'motif' => 'Frais médicaux'],
            'status' => DocumentRequest::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)->post(route('documents.document-requests.approve', $documentRequest));

        $generatedDocument = GeneratedDocument::find($documentRequest->fresh()->generated_document_id);
        $this->assertStringContainsString('Montant demandé : 150000 FCFA', $generatedDocument->content);
        $this->assertStringContainsString('Motif : Frais médicaux', $generatedDocument->content);
    }

    public function test_hr_direct_generation_also_substitutes_custom_field_values(): void
    {
        $employee = $this->makeEmployeeWithUser();
        $template = DocumentTemplate::create([
            'name' => "Demande d'avance sur salaire",
            'category' => DocumentTemplate::CATEGORY_AUTRE,
            'content' => 'Montant : {{demande.montant}}.',
            'fields' => [
                ['key' => 'montant', 'label' => 'Montant', 'type' => 'number', 'required' => true],
            ],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('organisation.employees.document-requests.store', $employee),
            [
                'document_template_id' => $template->id,
                'title' => 'Avance sur salaire',
                'field_values' => ['montant' => '75000'],
            ]
        );

        $response->assertRedirect();
        $generatedDocument = $employee->generatedDocuments()->latest()->first();
        $this->assertStringContainsString('Montant : 75000', $generatedDocument->content);
    }
}
