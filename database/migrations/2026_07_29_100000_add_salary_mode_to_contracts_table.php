<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('salary_mode')->default('gross')->after('base_salary');
            $table->decimal('net_salary_target', 12, 2)->nullable()->after('salary_mode');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['salary_mode', 'net_salary_target']);
        });
    }
};
