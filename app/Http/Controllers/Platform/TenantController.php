<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\TenantAdminWelcomeMail;
use App\Models\CompanySetting;
use App\Models\Country;
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
     * with an unusable random password, set the tenant's company name,
     * then email that admin a password-set link (Password::broker(), the
     * same mechanism as EmployeeAccountController — never a plaintext
     * password: besides being bad practice, Gmail/Outlook reliably flag
     * "here is your email + password" emails as spam/phishing regardless
     * of SPF/DKIM/DMARC, confirmed the hard way on gves.ekwatech.com).
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

        $scheme = $request->secure() ? 'https' : 'http';
        $resetUrl = null;

        $tenant->run(function () use ($data, $scheme, &$resetUrl) {
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

            CompanySetting::current()->update(['name' => $data['name']]);

            // URL::forceRootUrl: this request is on the central domain, but
            // 'password.reset' is a tenant-only route (routes/tenant.php) —
            // without this, url()/route() would build the link against the
            // central domain, which PreventAccessFromCentralDomains blocks.
            URL::forceRootUrl("{$scheme}://{$data['domain']}");
            $token = Password::broker()->createToken($admin);
            $resetUrl = URL::route('password.reset', ['token' => $token, 'email' => $admin->email]);
            URL::forceRootUrl(null);
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
}
