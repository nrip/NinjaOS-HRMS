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
        Schema::create('statutory_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->onDelete('cascade');
            $table->string('statutory_type');
            $table->decimal('employee_contribution', 12, 2)->default(0);
            $table->decimal('employer_contribution', 12, 2)->default(0);
            $table->decimal('total_contribution', 12, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_records');
    }
};
