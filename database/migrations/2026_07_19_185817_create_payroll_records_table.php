<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('payroll_id')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('employee_code', 20)->index();
            $table->foreignId('location_id')->constrained('locations');
            $table->string('state_code', 5);
            $table->unsignedTinyInteger('payroll_month');
            $table->unsignedSmallInteger('payroll_year');
            $table->string('tax_regime', 5)->default('new');
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('special_allowance', 12, 2)->default(0);
            $table->decimal('ot_earnings', 12, 2)->default(0);
            $table->decimal('encashment_payout', 12, 2)->default(0);
            $table->decimal('lwp_days', 5, 2)->default(0);
            $table->decimal('lwp_deduction', 12, 2)->default(0);
            $table->decimal('effective_gross', 12, 2)->default(0);
            $table->decimal('employee_pf', 12, 2)->default(0);
            $table->decimal('employer_pf', 12, 2)->default(0);
            $table->decimal('employee_esi', 12, 2)->default(0);
            $table->decimal('employer_esi', 12, 2)->default(0);
            $table->decimal('professional_tax', 12, 2)->default(0);
            $table->decimal('monthly_tds', 12, 2)->default(0);
            $table->decimal('notice_pay_recovery', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('prev_net_pay', 12, 2)->nullable();
            $table->decimal('variance_percent', 8, 2)->nullable();
            $table->boolean('variance_flag')->default(false);
            $table->boolean('variance_acknowledged')->default(false);
            $table->foreignId('variance_acknowledged_by')->nullable()->constrained('users');
            $table->timestamp('variance_acknowledged_at')->nullable();
            $table->json('payslip_snapshot')->nullable();
            $table->decimal('legacy_net_pay', 12, 2)->nullable();
            $table->decimal('reconciliation_variance', 12, 2)->nullable();
            $table->boolean('reconciliation_cleared')->default(false);
            $table->string('status', 20)->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users');
            $table->timestamp('finalized_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['employee_id', 'payroll_month', 'payroll_year'], 'uq_payroll_employee_period');
            $table->index(['payroll_month', 'payroll_year', 'status'], 'idx_payroll_period_status');
            $table->index(['location_id', 'payroll_month', 'payroll_year'], 'idx_payroll_location_period');
            $table->index(['state_code', 'payroll_month', 'payroll_year'], 'idx_payroll_state_period');
            $table->index('variance_flag', 'idx_payroll_variance_flag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
