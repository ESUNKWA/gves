<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\TenantAdminWelcomeMail;
use App\Models\CompanySetting;
use App\Models\Country;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DocumentTemplatesSeeder;
use Database\Seeders\LeaveTypesSeeder;
use Database\Seeders\OrganisationStarterSeeder;
use Database\Seeders\PayrollComponentsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('domains')->latest()->get();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Provision a new tenant: create its database (via the TenantCreated
     * event pipeline — see App\Providers\TenancyServiceProvider — which
     * also runs the tenant migrations synchronously, and names the
     * physical database tenant_{slug} via the custom generator registered
     * there), attach its domain, seed roles/permissions, starter leave
     * types, starter document templates, a single starter country (Côte
     * d'Ivoire — more are added by the client under Administration > Pays
     * as needed), starter payroll components (PayrollComponentsSeeder
     * — includes the "Salaire de base" component with is_base_salary
     * set, required by Payslip::generateFor() and the net-salary solver,
     * see CLAUDE.md), a starter organisation chart (3 departments, 3
     * positions — OrganisationStarterSeeder), create a first super-admin
     * with an unusable random password plus a matching Employee record
     * (linked via user_id, in the Direction Générale department as
     * Directeur Général — without one, EnsureUserHasEmployeeProfile locks
     * them out of "Mon espace"), set the tenant's company name, then email
     * that admin a password-set link (Password::broker(), the same
     * mechanism as EmployeeAccountController — never a plaintext password:
     * besides being bad practice, Gmail/Outlook reliably flag "here is your
     * email + password" emails as spam/phishing regardless of SPF/DKIM/
     * DMARC, confirmed the hard way on gves.ekwatech.com).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            // max:50: physical DB name is "tenant_{slug}" (see
            // TenancyServiceProvider::useSlugForDatabaseName); Postgres
            // identifiers are capped at 63 bytes.
            'slug' => 'required|string|max:50|alpha_dash|unique:tenants,slug',
            'domain' => 'required|string|max:255|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        $tenant->domains()->create(['domain' => $data['domain']]);

        $origin = $this->tenantOrigin($request, $data['domain']);
        $resetUrl = null;

        $tenant->run(function () use ($data, $origin, &$resetUrl) {
            Artisan::call('db:seed', [
                '--class' => RolesAndPermissionsSeeder::class,
                '--force' => true,
            ]);

            Artisan::call('db:seed', [
                '--class' => LeaveTypesSeeder::class,
                '--force' => true,
            ]);

            Artisan::call('db:seed', [
                '--class' => DocumentTemplatesSeeder::class,
                '--force' => true,
            ]);

            Country::firstOrCreate(['name' => "Côte d'Ivoire"]);

            Artisan::call('db:seed', [
                '--class' => PayrollComponentsSeeder::class,
                '--force' => true,
            ]);

            Artisan::call('db:seed', [
                '--class' => OrganisationStarterSeeder::class,
                '--force' => true,
            ]);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                // Random & never revealed: the admin sets their own password via
                // the emailed reset link, they never receive this one.
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);

            $admin->assignRole('super-admin');

            // Also give this admin an Employee record, not just a User: without
            // one, EnsureUserHasEmployeeProfile blocks them from "Mon espace"
            // (congés, ma-paie, profil...) entirely. Linked to the "Direction
            // Générale" department/"Directeur Général" position that
            // OrganisationStarterSeeder just seeded above.
            [$firstName, $lastName] = array_pad(explode(' ', $data['admin_name'], 2), 2, '');

            $adminEmployee = Employee::create([
                'user_id' => $admin->id,
                'employee_number' => Employee::nextEmployeeNumber(),
                'first_name' => $firstName,
                'last_name' => $lastName ?: $firstName,
                'personal_email' => $data['admin_email'],
                'department_id' => Department::where('code', 'DG')->value('id'),
                'position_id' => Position::where('code', 'DG-01')->value('id'),
                'hire_date' => now(),
                'status' => Employee::STATUS_ACTIVE,
            ]);

            PayrollComponent::assignDefaultsTo($adminEmployee);

            CompanySetting::current()->update(['name' => $data['name']]);

            $resetUrl = $this->buildResetUrl($admin, $origin);
        });

        $status = 'Tenant créé.';

        try {
            Mail::to($data['admin_email'])->send(new TenantAdminWelcomeMail(
                $data['name'],
                $resetUrl,
                $data['admin_email'],
            ));
        } catch (Throwable $e) {
            report($e);
            $status = "Tenant créé, mais l'email de bienvenue n'a pas pu être envoyé (voir les logs).";
        }

        return redirect()->route('platform.tenants.index')->with('status', $status);
    }

    /**
     * Re-send the same password-set welcome email as at provisioning time —
     * for when the admin never got the first one, lost it, or the link
     * expired (Password::broker() tokens expire after 60 minutes by
     * default). Generates a fresh token every time; doesn't invalidate any
     * still-outstanding one from a previous send.
     */
    public function resendWelcomeEmail(Request $request, Tenant $tenant): RedirectResponse
    {
        $domain = $tenant->domains->first()?->domain;

        if (! $domain) {
            return redirect()->route('platform.tenants.index')
                ->with('error', 'Ce tenant n\'a aucun domaine configuré.');
        }

        $origin = $this->tenantOrigin($request, $domain);
        $flash = null;

        $tenant->run(function () use ($tenant, $origin, &$flash) {
            $admin = User::role('super-admin')->oldest()->first();

            if (! $admin) {
                $flash = ['error', 'Aucun compte administrateur trouvé pour ce tenant.'];

                return;
            }

            $resetUrl = $this->buildResetUrl($admin, $origin);

            try {
                Mail::to($admin->email)->send(new TenantAdminWelcomeMail($tenant->name, $resetUrl, $admin->email));
                $flash = ['status', "Email renvoyé à {$admin->email}."];
            } catch (Throwable $e) {
                report($e);
                $flash = ['error', "L'email n'a pas pu être envoyé (voir les logs)."];
            }
        });

        [$type, $message] = $flash ?? ['error', 'Une erreur est survenue.'];

        return redirect()->route('platform.tenants.index')->with($type, $message);
    }

    /**
     * URL::forceRootUrl: the triggering request is always on the central
     * domain, but 'password.reset' is a tenant-only route (routes/tenant.php)
     * — without this, url()/route() would build the link against the central
     * domain, which PreventAccessFromCentralDomains blocks.
     */
    private function buildResetUrl(User $admin, string $origin): string
    {
        URL::forceRootUrl($origin);
        $token = Password::broker()->createToken($admin);
        $resetUrl = URL::route('password.reset', ['token' => $token, 'email' => $admin->email]);
        URL::forceRootUrl(null);

        return $resetUrl;
    }

    /**
     * scheme://domain, plus the port the operator is currently using to
     * reach the central platform screen when it isn't the scheme's default
     * (80/443) — e.g. local dev on :8000. Without this, a reset link built
     * from the tenant's domain alone silently drops the port and 404s (or
     * hits whatever else is listening on 80/443, if anything).
     */
    private function tenantOrigin(Request $request, string $domain): string
    {
        $scheme = $request->secure() ? 'https' : 'http';
        $port = $request->getPort();
        $isDefaultPort = ($scheme === 'https' && (int) $port === 443) || ($scheme === 'http' && (int) $port === 80);

        return $isDefaultPort ? "{$scheme}://{$domain}" : "{$scheme}://{$domain}:{$port}";
    }
}
