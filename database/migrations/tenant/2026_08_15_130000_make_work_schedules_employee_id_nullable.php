<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->foreignId('employee_id')->nullable()->change();
        });

        // Re-added after the nullable change: still guarantees at most one
        // schedule per real employee. A unique index treats every NULL as
        // distinct (allows more than one), so this alone doesn't stop
        // duplicate "default" rows — WorkSchedule::default()'s firstOrCreate
        // is what keeps that one, by convention rather than constraint.
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->foreignId('employee_id')->nullable(false)->change();
        });

        Schema::table('work_schedules', function (Blueprint $table) {
            $table->unique('employee_id');
        });
    }
};
