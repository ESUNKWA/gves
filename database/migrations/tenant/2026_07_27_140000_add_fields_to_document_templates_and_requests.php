<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            // Custom fields the template declares (e.g. "Montant", "Motif" for a
            // salary advance request), filled in by the employee at request time.
            // Shape: [{key, label, type, required}, ...].
            $table->json('fields')->nullable()->after('content');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            // What the employee actually typed for the template's custom fields,
            // keyed by field key. Shape: {key: value, ...}.
            $table->json('field_values')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('fields');
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn('field_values');
        });
    }
};
