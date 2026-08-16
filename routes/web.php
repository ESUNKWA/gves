<?php

use App\Http\Controllers\Platform\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Routes served on a central domain (config('tenancy.central_domains')),
| never on a tenant subdomain — the actual SIRH app lives entirely in
| routes/tenant.php now. This file is deliberately minimal: the landing
| page and the platform-admin tenant provisioning screen.
|
*/

// resources/views/welcome.blade.php calls CompanySetting::current()
// (tenant-scoped, doesn't exist centrally) — not reusable here.
//
// '/' is also registered (as a redirect to /login) in routes/tenant.php.
// Without an explicit ->domain() here, both routes would collide on the
// same lookup key (RouteCollection keys by method+domain+uri, and "no
// domain" is itself a shared key) — whichever file registered '/' last
// would silently shadow the other for every hostname, central included.
// Scoping this one to the configured central domains keeps both reachable.
foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->get('/', fn () => redirect('/platform/tenants'));
}

Route::prefix('platform')
    ->name('platform.')
    ->middleware('platform.token')
    ->group(function () {
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::post('tenants/{tenant}/renvoyer-email', [TenantController::class, 'resendWelcomeEmail'])->name('tenants.resend-welcome');
    });
