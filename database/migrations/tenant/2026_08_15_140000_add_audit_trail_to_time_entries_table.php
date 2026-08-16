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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('clock_in_ip')->nullable()->after('clock_in');
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_ip');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');

            $table->string('clock_out_ip')->nullable()->after('clock_out');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_ip');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_ip', 'clock_in_latitude', 'clock_in_longitude',
                'clock_out_ip', 'clock_out_latitude', 'clock_out_longitude',
            ]);
        });
    }
};
