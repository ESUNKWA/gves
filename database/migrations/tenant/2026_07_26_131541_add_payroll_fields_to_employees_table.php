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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('social_security_number')->nullable()->after('bank_account_number');
            $table->string('category')->nullable()->after('social_security_number');
            $table->string('qualification')->nullable()->after('category');
            $table->decimal('tax_shares', 4, 2)->nullable()->after('qualification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['social_security_number', 'category', 'qualification', 'tax_shares']);
        });
    }
};
