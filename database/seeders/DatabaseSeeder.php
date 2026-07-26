<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(LeaveTypesSeeder::class);
        $this->call(DocumentTemplatesSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(PayrollComponentsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin RH',
            'email' => 'admin@sirh.test',
        ]);

        $admin->assignRole('super-admin');
    }
}
