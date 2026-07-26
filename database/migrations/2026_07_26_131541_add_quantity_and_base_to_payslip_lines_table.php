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
        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->decimal('quantity', 8, 2)->nullable()->after('label');
            $table->decimal('base_amount', 14, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'base_amount']);
        });
    }
};
