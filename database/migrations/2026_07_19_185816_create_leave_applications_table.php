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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('leave_id')->unique();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('leave_type');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('number_of_days');
            $table->text('reason')->nullable();
            $table->string('contact_during_leave')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};
