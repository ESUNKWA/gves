<?php

namespace Tests\Feature\Organisation;

use App\Models\Employee;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CountrySeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_create_a_site(): void
    {
        $response = $this->actingAs($this->admin)->post(route('organisation.sites.store'), [
            '_modal' => 'site-create',
            'name' => 'Siège Abidjan',
            'code' => 'ABJ',
            'city' => 'Abidjan',
            'country' => "Côte d'Ivoire",
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('organisation.sites.index'));
        $this->assertDatabaseHas('sites', ['code' => 'ABJ', 'name' => 'Siège Abidjan']);
    }

    public function test_admin_can_create_a_department_linked_to_a_site(): void
    {
        $site = Site::create(['name' => 'Siège', 'code' => 'HQ']);

        $response = $this->actingAs($this->admin)->post(route('organisation.departments.store'), [
            '_modal' => 'department-create',
            'name' => 'Ressources Humaines',
            'code' => 'RH',
            'site_id' => $site->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('organisation.departments.index'));
        $this->assertDatabaseHas('departments', ['code' => 'RH', 'site_id' => $site->id]);
    }

    public function test_admin_can_create_an_employee_and_reach_the_show_page(): void
    {
        $response = $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'hire_date' => now()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $employee = Employee::firstOrFail();
        $response->assertRedirect(route('organisation.employees.show', $employee));

        $this->assertSame('Aminata', $employee->first_name);
        $this->assertSame(Employee::STATUS_ACTIVE, $employee->status);
    }

    public function test_admin_can_set_birth_place_and_nationality_which_are_optional(): void
    {
        $response = $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'hire_date' => now()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
            'birth_place' => 'Bouaké',
            'nationality' => "Côte d'Ivoire",
        ]);

        $employee = Employee::firstOrFail();
        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertSame('Bouaké', $employee->birth_place);
        $this->assertSame("Côte d'Ivoire", $employee->nationality);
    }

    public function test_birth_place_and_nationality_are_not_required_to_create_an_employee(): void
    {
        $response = $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'hire_date' => now()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response->assertSessionDoesntHaveErrors(['birth_place', 'nationality']);
        $employee = Employee::firstOrFail();
        $this->assertNull($employee->birth_place);
        $this->assertNull($employee->nationality);
    }

    public function test_anonymizing_an_employee_also_erases_birth_place_and_nationality(): void
    {
        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aminata',
            'last_name' => 'Koné',
            'hire_date' => now()->subYear(),
            'status' => Employee::STATUS_ACTIVE,
            'birth_place' => 'Bouaké',
            'nationality' => "Côte d'Ivoire",
        ]);

        $employee->anonymize();

        $this->assertNull($employee->fresh()->birth_place);
        $this->assertNull($employee->fresh()->nationality);
    }

    public function test_employee_number_is_auto_generated_and_unique(): void
    {
        $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Yao',
            'last_name' => 'Kouassi',
            'hire_date' => now()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $first = Employee::firstOrFail();
        $this->assertNotEmpty($first->employee_number);

        $this->actingAs($this->admin)->post(route('organisation.employees.store'), [
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Fatou',
            'last_name' => 'Diarra',
            'hire_date' => now()->toDateString(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $second = Employee::where('id', '!=', $first->id)->firstOrFail();
        $this->assertNotSame($first->employee_number, $second->employee_number);
    }

    public function test_a_document_can_be_uploaded_to_an_employee(): void
    {
        Storage::fake('local');

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Awa',
            'last_name' => 'Traoré',
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.documents.store', $employee), [
            'title' => "Carte d'identité",
            'category' => 'piece_identite',
            'file' => UploadedFile::fake()->create('cni.pdf', 100),
        ]);

        $response->assertRedirect(route('organisation.employees.show', $employee));
        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'title' => "Carte d'identité",
        ]);
    }

    public function test_anonymizing_an_employee_erases_personal_data_and_deletes_documents(): void
    {
        Storage::fake('local');

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Moussa',
            'last_name' => 'Sangaré',
            'personal_email' => 'moussa@example.com',
            'national_id' => 'CI-12345',
            'hire_date' => now(),
            'status' => Employee::STATUS_TERMINATED,
        ]);

        $employee->documents()->create([
            'category' => 'cv',
            'title' => 'CV',
            'file_path' => 'employee-documents/1/cv.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('organisation.employees.anonymize', $employee));

        $response->assertRedirect(route('organisation.employees.show', $employee));

        $employee->refresh();

        $this->assertTrue($employee->is_anonymized);
        $this->assertNull($employee->personal_email);
        $this->assertNull($employee->national_id);
        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_a_user_without_permission_cannot_view_employees(): void
    {
        $employee = User::factory()->create();

        $response = $this->actingAs($employee)->get(route('organisation.employees.index'));

        $response->assertForbidden();
    }

    public function test_a_view_only_manager_cannot_delete_an_employee(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Ibrahim',
            'last_name' => 'Cissé',
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($manager)->delete(route('organisation.employees.destroy', $employee));

        $response->assertForbidden();
        $this->assertNotNull($employee->fresh());
    }

    public function test_a_view_only_manager_cannot_create_a_site(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)->post(route('organisation.sites.store'), [
            'name' => 'Siège non autorisé',
            'code' => 'NOPE',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('sites', ['code' => 'NOPE']);
    }

    public function test_a_view_only_manager_cannot_add_a_contract(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Salif',
            'last_name' => 'Keita',
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($manager)->post(route('organisation.employees.contracts.store', $employee), [
            'contract_type' => 'cdi',
            'start_date' => now()->toDateString(),
            'currency' => 'XOF',
            'status' => 'draft',
        ]);

        $response->assertForbidden();
        $this->assertSame(0, $employee->contracts()->count());
    }

    public function test_a_view_only_manager_cannot_upload_a_document(): void
    {
        Storage::fake('local');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $employee = Employee::create([
            'employee_number' => Employee::nextEmployeeNumber(),
            'first_name' => 'Aicha',
            'last_name' => 'Diallo',
            'hire_date' => now(),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($manager)->post(route('organisation.employees.documents.store', $employee), [
            'title' => 'CV',
            'category' => 'cv',
            'file' => UploadedFile::fake()->create('cv.pdf', 100),
        ]);

        $response->assertForbidden();
        $this->assertSame(0, $employee->documents()->count());
    }

    public function test_updating_a_site_with_invalid_data_returns_validation_errors(): void
    {
        $siteA = Site::create(['name' => 'Site A', 'code' => 'AAA']);
        Site::create(['name' => 'Site B', 'code' => 'BBB']);

        $response = $this->actingAs($this->admin)->put(route('organisation.sites.update', $siteA), [
            '_modal' => 'site-edit-'.$siteA->id,
            'name' => 'Site A',
            'code' => 'BBB',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertSame('AAA', $siteA->fresh()->code);
    }
}
