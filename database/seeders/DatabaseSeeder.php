<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Every seeder called here is
     * idempotent (firstOrCreate/updateOrCreate-based), so this is safe to
     * run on every deploy — see docker/entrypoint.sh — not just the first.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(OrganisationStarterSeeder::class);
        $this->call(LeaveTypesSeeder::class);
        $this->call(DocumentTemplatesSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(PayrollComponentsSeeder::class);
    }
}
