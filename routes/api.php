<?php

use App\Http\Controllers\Api\AdminPasswordController;
use Illuminate\Support\Facades\Route;

// Token-authenticated (ADMIN_RESET_TOKEN), not session-authenticated — meant
// for deploy tooling/ops scripts that don't hold a browser session, e.g. to
// replace the random password AdminUserSeeder generates on first deploy.
Route::middleware('throttle:5,1')->put('admin/password', AdminPasswordController::class);
