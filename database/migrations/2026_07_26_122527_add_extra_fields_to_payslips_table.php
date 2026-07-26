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
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('id');
            $table->decimal('employer_charges_amount', 14, 2)->default(0)->after('net_amount');
            $table->string('payment_method')->nullable()->after('validated_at');
            $table->date('payment_date')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['reference', 'employer_charges_amount', 'payment_method', 'payment_date']);
        });
    }
};
