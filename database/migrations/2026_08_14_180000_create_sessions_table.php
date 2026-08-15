<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central-only twin of the tenant `sessions` table (see
     * database/migrations/tenant/0001_01_01_000000_create_users_table.php):
     * the 'web' middleware group always starts a session, even on
     * unauthenticated central routes, so the central connection needs its
     * own sessions table too — sessions themselves stay tenant-scoped since
     * a client's logged-in users should stay isolated from another's.
     */
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
