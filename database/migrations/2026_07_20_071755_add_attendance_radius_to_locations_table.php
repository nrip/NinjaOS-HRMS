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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('state_code', 2)
                ->nullable()
                ->after('state')
                ->comment('ISO 3166-2 state code, e.g. MH, KA, DL — used for statutory OT/PT lookups');

            $table->unsignedInteger('attendance_radius_meters')
                ->nullable()
                ->after('gis_lng')
                ->comment('Per-location geo-fence radius in metres; falls back to config(nexusos.geofencing.default_radius_meters) when null');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['state_code', 'attendance_radius_meters']);
        });
    }
};
