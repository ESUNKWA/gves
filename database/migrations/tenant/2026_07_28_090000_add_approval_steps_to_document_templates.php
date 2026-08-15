<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            // Ordered list of step types (e.g. ["requester","manager","hr"]).
            // Null/empty means "no chain configured" — the document keeps the
            // original single-signature-by-the-requester behaviour untouched.
            $table->json('approval_steps')->nullable()->after('fields');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('approval_steps');
        });
    }
};
