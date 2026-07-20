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
        Schema::table('attendance', function (Blueprint $table) {
            // Shift linkage
            $table->foreignId('shift_id')->nullable()->after('employee_id')
                ->constrained('shifts')->nullOnDelete()
                ->comment('Assigned shift for this attendance record');

            // OT tracking
            $table->decimal('ot_hours', 5, 2)->nullable()->after('hours_worked')
                ->comment('Overtime hours calculated from config/statutory.php OT rules');

            // Geo-fencing
            $table->unsignedInteger('geo_distance_meters')->nullable()->after('longitude')
                ->comment('Calculated Haversine distance from office at time of punch');

            // Biometric / punch source metadata
            $table->enum('punch_source', ['biometric', 'mobile_gps', 'manual', 'ip_whitelist'])
                ->default('manual')->after('mode')
                ->comment('Origin of the punch record');
            $table->string('device_id', 64)->nullable()->after('punch_source')
                ->comment('Biometric device ID (e.g. ZK-MOCK-01) or null for mobile/manual');

            // Regularization workflow
            $table->enum('regularization_status', ['none', 'pending', 'approved', 'rejected'])
                ->default('none')->after('status')
                ->comment('Status of employee regularization request for this record');
            $table->text('regularization_reason')->nullable()->after('regularization_status');
            $table->foreignId('regularized_by')->nullable()->after('regularization_reason')
                ->constrained('users')->nullOnDelete()
                ->comment('User (Location HR) who approved/rejected the regularization');
            $table->timestamp('regularized_at')->nullable()->after('regularized_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['regularized_by']);
            $table->dropColumn([
                'shift_id', 'ot_hours', 'geo_distance_meters',
                'punch_source', 'device_id',
                'regularization_status', 'regularization_reason',
                'regularized_by', 'regularized_at',
            ]);
        });
    }
};
