<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Create this instance's first super-admin, idempotently — safe to run on
     * every deploy/restart (see docker/entrypoint.sh), not just the very
     * first one. Credentials come from config('app.admin_*') (ADMIN_EMAIL /
     * ADMIN_PASSWORD in .env) so each client deployment gets its own admin
     * account instead of every instance sharing the same demo login.
     */
    public function run(): void
    {
        $email = config('app.admin_email');

        $admin = User::where('email', $email)->first();

        if ($admin) {
            if (! $admin->hasRole('super-admin')) {
                $admin->assignRole('super-admin');
            }

            return;
        }

        $configuredPassword = config('app.admin_password');
        $password = $configuredPassword ?: Str::password(20);

        $admin = User::create([
            'name' => config('app.admin_name'),
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('super-admin');

        if (! $configuredPassword) {
            $this->command?->warn("ADMIN_PASSWORD absent — mot de passe généré pour {$email} : {$password}");
            $this->command?->warn('Connectez-vous immédiatement et changez ce mot de passe depuis le profil.');
        } elseif ($configuredPassword === 'password') {
            $this->command?->warn("ADMIN_PASSWORD est encore la valeur de démo (« password ») pour {$email} — à changer avant toute mise en production.");
        }
    }
}
