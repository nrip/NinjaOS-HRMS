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
        Schema::table('leave_applications', function (Blueprint $table) {
            // Half-day leave support
            if (! Schema::hasColumn('leave_applications', 'is_half_day')) {
                $table->boolean('is_half_day')->default(false)->after('number_of_days')
                    ->comment('True when the application covers only half a working day');
            }
            if (! Schema::hasColumn('leave_applications', 'half_day_session')) {
                $table->enum('half_day_session', ['first_half', 'second_half'])->nullable()->after('is_half_day')
                    ->comment('Which half of the day is taken; null for full-day leaves');
            }

            // Rejection tracking
            if (! Schema::hasColumn('leave_applications', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')
                    ->constrained('users')->onDelete('set null');
            }
            if (! Schema::hasColumn('leave_applications', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            // Cancellation tracking
            if (! Schema::hasColumn('leave_applications', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
            }

            // Composite indexes for projection queries and approval dashboard
            $table->index(
                ['employee_id', 'status', 'from_date', 'to_date'],
                'leave_apps_emp_status_dates_idx'
            );
            $table->index(
                ['location_id', 'status', 'from_date'],
                'leave_apps_location_status_from_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            // Drop indexes first
            try { $table->dropIndex('leave_apps_emp_status_dates_idx'); } catch (\Throwable) {}
            try { $table->dropIndex('leave_apps_location_status_from_idx'); } catch (\Throwable) {}

            $cols = ['is_half_day', 'half_day_session', 'rejected_by', 'rejected_at', 'cancelled_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('leave_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
