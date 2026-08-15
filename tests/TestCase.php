<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\DatabaseManager as TenancyDatabaseManager;

abstract class TestCase extends BaseTestCase
{
    protected ?Tenant $tenant = null;

    /**
     * Every test runs inside its own freshly-provisioned, freshly-migrated
     * tenant database (business tables now live under
     * database/migrations/tenant, not the default/central migrations path
     * RefreshDatabase targets). Creating the tenant here — after
     * parent::setUp() has run RefreshDatabase against the central
     * connection — means tenant creation lands inside RefreshDatabase's
     * open transaction, so the central `tenants` row is rolled back for
     * free; the tenant's physical database still needs explicit cleanup,
     * done in tearDown().
     *
     * All app routes (routes/tenant.php) now sit behind
     * InitializeTenancyByDomain + PreventAccessFromCentralDomains, so an
     * HTTP test request must arrive on a real, registered tenant domain —
     * the default test host ('localhost', from app.url) is itself a central
     * domain and would 404. Each test therefore gets its own throwaway
     * domain, and URL::forceRootUrl() makes route()/url() (used by most
     * tests via $this->get(route(...))) generate URLs under that domain.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-'.Str::random(12),
        ]);

        // Lowercase: Domain (ConvertsDomainsToLowercase) stores hostnames
        // lowercased, and Str::random()'s mixed case would otherwise mismatch
        // against the incoming request's Host header during route resolution.
        $domain = 'test-'.Str::random(12).'.example';
        $domain = strtolower($domain);
        $this->tenant->domains()->create(['domain' => $domain]);
        $this->baseUrl = "http://{$domain}";
        URL::forceRootUrl($this->baseUrl);

        tenancy()->initialize($this->tenant);
    }

    /**
     * Cleanup is deliberately event-free. tenancy()->end() and Tenant::delete()
     * only revert the DB connection / remove the tenant's physical database
     * because TenancyServiceProvider wires that behavior to tenancy events —
     * any test that calls Event::fake() (a completely normal thing to do)
     * silently turns both into no-ops, leaving database.default stuck on
     * 'tenant' and the tenant's sqlite file undeleted, which wedges every
     * test that runs afterward in the same process. Calling the underlying
     * DatabaseManager/TenantDatabaseManager methods directly sidesteps that.
     *
     * Also guards with a null check: if setUp() fails before assigning
     * $this->tenant, we must still fall through to parent::tearDown() so
     * RefreshDatabase's transaction rollback runs — skipping it leaves the
     * shared in-memory sqlite PDO mid-transaction, wedging every subsequent
     * test with a cryptic "There is already an active transaction" error.
     */
    protected function tearDown(): void
    {
        if ($this->tenant) {
            app(TenancyDatabaseManager::class)->reconnectToCentral();

            // FilesystemTenancyBootstrapper is active but its own revert()
            // never runs here (event-free cleanup, see above) — any file a
            // test wrote via Storage::disk('local'|'public') landed under
            // storage/{suffix_base}{tenantKey} (see FilesystemTenancyBootstrapper::bootstrap()),
            // which must be removed by hand or it leaks on every test run.
            // base_path(), unlike storage_path(), isn't affected by the
            // bootstrapper's useStoragePath() override, so it reliably
            // points at the real root regardless of current tenancy state.
            File::deleteDirectory(base_path(
                'storage/'.config('tenancy.filesystem.suffix_base').$this->tenant->getTenantKey()
            ));

            $this->tenant->database()->manager()->deleteDatabase($this->tenant);
            $this->tenant->delete();
        }

        parent::tearDown();
    }
}
