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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('contract_type');
            $table->string('job_title')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('trial_end_date')->nullable();
            $table->decimal('base_salary', 12, 2)->nullable();
            $table->string('currency')->default('XOF');
            $table->unsignedSmallInteger('working_hours_per_week')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('signed_at')->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
