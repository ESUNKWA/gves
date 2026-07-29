<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            // Default true so the public /rejoindre link keeps working as-is
            // for installs that predate this setting.
            $table->boolean('onboarding_enabled')->default(true);
            $table->timestamp('onboarding_starts_at')->nullable();
            $table->timestamp('onboarding_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['onboarding_enabled', 'onboarding_starts_at', 'onboarding_ends_at']);
        });
    }
};
