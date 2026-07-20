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
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('payroll_id')->unique();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->year('payroll_year');
            $table->integer('payroll_month');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->decimal('pf_deduction', 12, 2)->default(0);
            $table->decimal('esi_deduction', 12, 2)->default(0);
            $table->decimal('pt_deduction', 12, 2)->default(0);
            $table->decimal('tds_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->enum('status', ['draft', 'locked', 'processed', 'approved', 'finalized', 'paid'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['location_id', 'employee_id', 'payroll_year', 'payroll_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
