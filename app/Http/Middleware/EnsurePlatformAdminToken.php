<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdminToken
{
    /**
     * Gates the central /platform/tenants screen with a shared secret
     * (config('app.platform_admin_token')) instead of a real login — there
     * is no central user model yet, `users` is entirely tenant-scoped. The
     * token only needs to be supplied once per browser session: after a
     * successful ?token= visit, access is remembered in the (central)
     * session for the rest of the browsing session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('app.platform_admin_token');

        abort_if(! $token, 503, "PLATFORM_ADMIN_TOKEN n'est pas configuré côté serveur.");

        if ($request->session()->get('platform_admin_authed') === true) {
            return $next($request);
        }

        $provided = (string) $request->query('token');

        abort_unless($provided !== '' && hash_equals($token, $provided), 403, 'Jeton invalide. Ajoutez ?token=... à l’URL.');

        $request->session()->put('platform_admin_authed', true);

        return $next($request);
    }
}
