<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverses 2026_08_15_140000_add_audit_trail_to_time_entries_table and
     * 2026_08_16_090000_add_location_label_to_time_entries_table: the
     * clock-in/out audit trail (IP + geolocation) was dropped as a feature —
     * neither the browser-GPS approach nor the IP-geolocation fallback that
     * replaced it were worth keeping.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_ip', 'clock_in_latitude', 'clock_in_longitude', 'clock_in_location',
                'clock_out_ip', 'clock_out_latitude', 'clock_out_longitude', 'clock_out_location',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->string('clock_in_ip')->nullable()->after('clock_in');
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_ip');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->string('clock_in_location')->nullable()->after('clock_in_longitude');

            $table->string('clock_out_ip')->nullable()->after('clock_out');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_ip');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->string('clock_out_location')->nullable()->after('clock_out_longitude');
        });
    }
};
