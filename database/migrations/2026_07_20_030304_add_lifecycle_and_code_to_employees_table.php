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
        Schema::table('employees', function (Blueprint $table) {
            // Employee Code (configurable format EMP-STATE-SEQ)
            $table->string('employee_code')->nullable()->after('employee_id')->unique();
            
            // Lifecycle fields
            $table->foreignId('reporting_manager_id')->nullable()->after('designation_id')->constrained('employees')->nullOnDelete();
            $table->date('probation_end_date')->nullable()->after('date_of_joining');
            $table->date('confirmation_date')->nullable()->after('probation_end_date');
            
            // Note: SoftDeletes is already in the original migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reporting_manager_id']);
            $table->dropColumn([
                'employee_code',
                'reporting_manager_id',
                'probation_end_date',
                'confirmation_date'
            ]);
        });
    }
};
