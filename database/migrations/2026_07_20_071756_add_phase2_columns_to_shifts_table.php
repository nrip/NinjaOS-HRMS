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
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_night_shift')->default(false)->after('end_time')
                ->comment('True when shift crosses midnight (e.g. 22:00-06:00)');
            $table->unsignedSmallInteger('grace_period_minutes')->default(0)->after('is_night_shift')
                ->comment('Minutes after shift start before employee is marked late');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['is_night_shift', 'grace_period_minutes']);
        });
    }
};
