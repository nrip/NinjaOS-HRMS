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
            if (! Schema::hasColumn('locations', 'code')) {
                $table->string('code', 20)
                    ->nullable()
                    ->unique()
                    ->after('name')
                    ->comment('Short location code, e.g. BLR-01, MUM-HQ — used for display and biometric device mapping');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
