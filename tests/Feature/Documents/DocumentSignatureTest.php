<?php

namespace Tests\Feature\Documents;

use App\Models\CompanySetting;
use App\Models\Contract;
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

    public function test_template_content_keeps_rich_text_formatting_but_strips_disallowed_tags_and_attributes(): void
    {
        $response = $this->actingAs($this->admin)->post(route('documents.templates.store'), [
            '_modal' => 'template-create',
            'name' => 'Attestation formatée',
            'category' => DocumentTemplate::CATEGORY_ATTESTATION,
            'content' => '<p onclick="alert(1)"><strong>Important</strong> : <u>lu et approuvé</u></p>'
                .'<script>alert(1)</script><img src="x" onerror="alert(1)">',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('documents.templates.index'));

        $template = DocumentTemplate::where('name', 'Attestation formatée')->firstOrFail();
        $this->assertStringContainsString('<strong>Important</strong>', $template->content);
        $this->assertStringContainsString('<u>lu et approuvé</u>', $template->content);
        $this->assertStringContainsString('<p>', $template->content);
        $this->assertStringNotContainsString('onclick', $template->content);
        $this->assertStringNotContainsString('<script', $template->content);
        $this->assertStringNotContainsString('<img', $template->content);
        $this->assertStringNotContainsString('onerror', $template->content);
    }

    public function test_generated_document_keeps_template_formatting_but_escapes_substituted_employee_data(): void
    {
        $employee = $this->makeEmployee(['first_name' => '<script>alert(1)</script>', 'last_name' => 'Koné']);

        $rendered = GeneratedDocument::renderContent(
            '<p><strong>Attestation</strong> pour {{employe.prenom}} {{employe.nom}}.</p>',
            $employee
        );

        $this->assertStringContainsString('<strong>Attestation</strong>', $rendered);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
    }

    public function test_generated_document_substitutes_contract_variables_from_the_latest_contract(): void
    {
        $employee = $this->makeEmployee();
        // Still "Brouillon" (draft), as a freshly entered contract commonly
        // is when HR generates the contract document right after typing it in.
        $employee->contracts()->create([
            'contract_type' => Contract::TYPE_CDI,
            'job_title' => 'Développeur Backend',
            'start_date' => '2026-01-15',
            'base_salary' => 350000,
            'currency' => 'XOF',
            'working_hours_per_week' => 40,
            'status' => Contract::STATUS_DRAFT,
        ]);

        $rendered = GeneratedDocument::renderContent(
            'Contrat {{contrat.type}} pour le poste {{contrat.poste}}, débutant le {{contrat.date_debut}}, '
                .'salaire {{contrat.salaire_base}} {{contrat.devise}}, {{contrat.heures_semaine}}h/semaine.',
            $employee
        );

        $this->assertStringContainsString('Contrat CDI', $rendered);
        $this->assertStringContainsString('Développeur Backend', $rendered);
        $this->assertStringContainsString('15/01/2026', $rendered);
        $this->assertStringContainsString('350 000 XOF', $rendered);
        $this->assertStringContainsString('40h/semaine', $rendered);
    }

    public function test_generated_document_substitutes_all_employee_variables(): void
    {
        $manager = $this->makeEmployee(['first_name' => 'Kader', 'last_name' => 'Traoré']);
        $employee = $this->makeEmployee([
            'gender' => 'female',
            'birth_date' => '1994-03-12',
            'birth_place' => 'Bouaké',
            'nationality' => "Côte d'Ivoire",
            'national_id' => 'CI-99887766',
            'marital_status' => 'Célibataire',
            'address' => 'Rue des Jardins',
            'city' => 'Abidjan',
            'country' => "Côte d'Ivoire",
            'bank_account_number' => 'CI93CI0080000123456789',
            'social_security_number' => 'CNPS-11223',
            'category' => 'Cadre Classe 4.3',
            'qualification' => 'Master RH',
            'tax_shares' => 2.5,
            'manager_id' => $manager->id,
            'termination_date' => '2026-12-31',
            'status' => Employee::STATUS_TERMINATED,
        ]);

        $rendered = GeneratedDocument::renderContent(
            'Genre : {{employe.genre}}. Né(e) le {{employe.date_naissance}} à {{employe.lieu_naissance}}. '
                .'Nationalité : {{employe.nationalite}}. Pièce : {{employe.piece_identite}}. '
                .'Situation : {{employe.situation_familiale}}. Adresse : {{employe.adresse}}, {{employe.ville}}, {{employe.pays}}. '
                .'Compte : {{employe.compte_bancaire}}. Sécu : {{employe.numero_secu}}. '
                .'Catégorie : {{employe.categorie}}. Qualification : {{employe.qualification}}. Parts : {{employe.parts_fiscales}}. '
                .'Manager : {{employe.manager}}. Sortie le {{employe.date_sortie}}. Statut : {{employe.statut}}.',
            $employee
        );

        $this->assertStringContainsString('Genre : Féminin', $rendered);
        $this->assertStringContainsString('Né(e) le 12/03/1994 à Bouaké', $rendered);
        $this->assertStringContainsString('Nationalité : Côte d&#039;Ivoire', $rendered);
        $this->assertStringContainsString('Pièce : CI-99887766', $rendered);
        $this->assertStringContainsString('Situation : Célibataire', $rendered);
        $this->assertStringContainsString('Adresse : Rue des Jardins, Abidjan, Côte d&#039;Ivoire', $rendered);
        $this->assertStringContainsString('Compte : CI93CI0080000123456789', $rendered);
        $this->assertStringContainsString('Sécu : CNPS-11223', $rendered);
        $this->assertStringContainsString('Catégorie : Cadre Classe 4.3', $rendered);
        $this->assertStringContainsString('Qualification : Master RH', $rendered);
        $this->assertStringContainsString('Parts : 2.5', $rendered);
        $this->assertStringContainsString('Manager : Kader Traoré', $rendered);
        $this->assertStringContainsString('Sortie le 31/12/2026', $rendered);
        $this->assertStringContainsString('Statut : Sorti', $rendered);
    }

    public function test_contract_variables_render_as_empty_strings_when_the_employee_has_no_contract(): void
    {
        $employee = $this->makeEmployee();

        $rendered = GeneratedDocument::renderContent('Poste : {{contrat.poste}}.', $employee);

        $this->assertSame('Poste : .', $rendered);
    }

    public function test_generated_document_substitutes_company_variables(): void
    {
        $employee = $this->makeEmployee();
        CompanySetting::current()->update([
            'name' => 'GVES', 'legal_name' => 'GVES SARL', 'phone' => '+225 27 00 00 00',
            'email' => 'contact@gves.test', 'registration_number' => 'CI-ABJ-2024-B-1234',
            'tax_id' => 'FISC-5678', 'social_security_number' => 'CNPS-9012',
            'collective_agreement' => 'Convention interprofessionnelle',
        ]);

        $rendered = GeneratedDocument::renderContent(
            '{{entreprise.nom}} ({{entreprise.raison_sociale}}) - {{entreprise.telephone}} - {{entreprise.email}} '
                .'- RCCM {{entreprise.rccm}} - Fiscal {{entreprise.numero_fiscal}} - CNPS {{entreprise.cnps}} - {{entreprise.convention_collective}}',
            $employee
        );

        $this->assertStringContainsString('GVES (GVES SARL)', $rendered);
        $this->assertStringContainsString('+225 27 00 00 00', $rendered);
        $this->assertStringContainsString('contact@gves.test', $rendered);
        $this->assertStringContainsString('RCCM CI-ABJ-2024-B-1234', $rendered);
        $this->assertStringContainsString('Fiscal FISC-5678', $rendered);
        $this->assertStringContainsString('CNPS CNPS-9012', $rendered);
        $this->assertStringContainsString('Convention interprofessionnelle', $rendered);
    }

    public function test_template_editor_variables_are_grouped_by_source(): void
    {
        $variables = DocumentTemplate::availableVariables();

        $this->assertEqualsCanonicalizing(['Employé', 'Contrat', 'Entreprise', 'Autre'], array_keys($variables));
        $this->assertArrayHasKey('{{contrat.salaire_base}}', $variables['Contrat']);
        $this->assertArrayHasKey('{{entreprise.rccm}}', $variables['Entreprise']);
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
