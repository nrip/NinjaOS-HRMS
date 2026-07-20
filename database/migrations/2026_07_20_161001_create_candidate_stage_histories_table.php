<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_stage_histories', function (Blueprint $table) {
            $table->id();
            // FK to candidates.id (UUID)
            $table->uuid('candidate_id');
            $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('cascade');

            $table->string('from_stage', 30)->nullable();
            $table->string('to_stage', 30);

            // Who moved the candidate
            $table->foreignId('moved_by')->constrained('users')->onDelete('cascade');

            // Rejection reason — required when to_stage = 'rejected'
            $table->text('rejection_reason')->nullable();

            // Free-form notes (interview feedback, etc.)
            $table->text('notes')->nullable();

            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            // Performance index
            $table->index(['candidate_id', 'to_stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_stage_histories');
    }
};
