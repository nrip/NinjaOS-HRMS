<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            // UUID primary key per DPDP/orchestrator mandate
            $table->uuid('id')->primary();
            // Business-facing UUID (same as PK for candidates)
            $table->uuid('candidate_id')->unique();
            $table->foreignId('requisition_id')->constrained('job_requisitions')->onDelete('cascade');

            // Personal details
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 191);
            $table->string('phone', 20)->nullable();

            // Pipeline stage
            $table->enum('current_stage', [
                'applied',
                'screened',
                'interview_1',
                'interview_2',
                'offer',
                'hired',
                'rejected',
            ])->default('applied');

            // Source of application
            $table->string('source', 50)->nullable(); // linkedin, naukri, referral, direct, etc.

            // Resume parsed data (stored as JSON — no raw PII in logs)
            $table->json('parsed_skills')->nullable();
            $table->float('parsed_experience')->nullable();

            // Rejection
            $table->text('rejection_reason')->nullable();

            // Offer details
            $table->decimal('offered_ctc', 12, 2)->nullable();
            $table->date('date_of_joining')->nullable();

            // Convert-to-Employee handoff
            $table->foreignId('converted_to_employee_id')
                  ->nullable()
                  ->constrained('employees')
                  ->onDelete('set null');
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Performance indexes
            $table->index(['requisition_id', 'current_stage']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
