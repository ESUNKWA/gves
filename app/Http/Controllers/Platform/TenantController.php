<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

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
     * also runs the tenant migrations synchronously), attach its domain,
     * then seed roles/permissions and a first super-admin inside that
     * tenant's own database so it's immediately usable for testing.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash|unique:tenants,slug',
            'domain' => 'required|string|max:255|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => ['required', 'string', Password::min(8)],
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        $tenant->domains()->create(['domain' => $data['domain']]);

        $tenant->run(function () use ($data) {
            Artisan::call('db:seed', [
                '--class' => RolesAndPermissionsSeeder::class,
                '--force' => true,
            ]);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'email_verified_at' => now(),
            ]);

            $admin->assignRole('super-admin');
        });

        return redirect()->route('platform.tenants.index')->with('status', 'Tenant créé.');
    }
}
