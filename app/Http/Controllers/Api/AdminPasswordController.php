<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminPasswordController extends Controller
{
    /**
     * Reset this instance's super-admin password from an external caller that
     * doesn't hold a browser session (deploy tooling, ops script) — e.g. to
     * replace the random password AdminUserSeeder generates on first deploy
     * when ADMIN_PASSWORD isn't set in .env. Authenticated by a shared secret
     * (ADMIN_RESET_TOKEN), never by the current password, since the whole
     * point is recovering access when that password is unknown.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $token = config('app.admin_reset_token');

        abort_if(! $token, 503, "ADMIN_RESET_TOKEN n'est pas configuré côté serveur.");

        $provided = (string) $request->header('X-Admin-Reset-Token');
        abort_unless($provided !== '' && hash_equals($token, $provided), 401, 'Jeton invalide.');

        $data = $request->validate([
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $admin = User::where('email', config('app.admin_email'))->first();

        abort_if(! $admin, 404, 'Aucun compte admin trouvé pour '.config('app.admin_email').'.');

        $admin->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Mot de passe admin mis à jour.']);
    }
}
