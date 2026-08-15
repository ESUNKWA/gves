<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App-wide default brand color changed to #f990a5. Only rows still on
     * the old default (#4f46e5, i.e. nobody picked a custom color for that
     * tenant) are backfilled — a tenant that deliberately chose a color is
     * left untouched.
     */
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#f990a5')->after('logo_path')->change();
        });

        DB::table('company_settings')->where('primary_color', '#4f46e5')->update(['primary_color' => '#f990a5']);
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#4f46e5')->after('logo_path')->change();
        });

        DB::table('company_settings')->where('primary_color', '#f990a5')->update(['primary_color' => '#4f46e5']);
    }
};
