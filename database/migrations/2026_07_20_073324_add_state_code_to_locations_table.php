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
            if (! Schema::hasColumn('locations', 'state_code')) {
                $table->string('state_code', 2)
                    ->nullable()
                    ->after('state')
                    ->comment('ISO 3166-2 state code, e.g. MH, KA, DL — used for statutory OT/PT lookups');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'state_code')) {
                $table->dropColumn('state_code');
            }
        });
    }
};
