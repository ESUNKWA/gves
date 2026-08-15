<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_document_id')->constrained()->cascadeOnDelete();
            $table->string('step_type');
            $table->unsignedInteger('step_order');
            $table->string('status')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->longText('signature_data')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['generated_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_document_approvals');
    }
};
