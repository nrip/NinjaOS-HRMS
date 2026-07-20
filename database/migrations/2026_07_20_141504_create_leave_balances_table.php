<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * leave_balances tracks each employee's leave balance per type per year.
     *
     * Balance equation: closing_balance = opening_balance + accrued - availed - pending
     *
     * Columns:
     *   opening_balance  — balance carried forward from previous year (capped by carry_forward_limit)
     *   accrued          — days accrued so far in this year (monthly accrual jobs)
     *   availed          — days consumed by approved leave applications
     *   pending          — days reserved by pending_approval applications (tentative deduction)
     *   closing_balance  — opening + accrued - availed - pending (recomputed on every mutation)
     *   expiry_date      — for Comp Off and other expiring leave types; null = no expiry
     */
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('cascade');
            $table->foreignId('location_id')
                ->constrained('locations')
                ->onDelete('cascade');
            $table->string('leave_type', 5)
                ->comment('EL, CL, SL, ML, PL, BL, CO, UL — matches config/statutory.php keys');
            $table->smallInteger('year')
                ->comment('Calendar year, e.g. 2026');
            $table->decimal('opening_balance', 6, 2)->default(0.00);
            $table->decimal('accrued', 6, 2)->default(0.00);
            $table->decimal('availed', 6, 2)->default(0.00);
            $table->decimal('pending', 6, 2)->default(0.00)
                ->comment('Tentative deduction for pending_approval applications');
            $table->decimal('closing_balance', 6, 2)->default(0.00)
                ->comment('opening + accrued - availed - pending; recomputed on every mutation');
            $table->date('expiry_date')->nullable()
                ->comment('Expiry date for Comp Off and other time-limited leave types');
            $table->timestamps();
            $table->softDeletes();

            // ── Composite unique index: one row per employee per leave type per year ──
            $table->unique(['employee_id', 'leave_type', 'year'], 'leave_balances_emp_type_year_unique');

            // ── Performance indexes for projection queries (avoids N+1) ─────────────
            $table->index(['employee_id', 'leave_type', 'year'], 'leave_balances_emp_type_year_idx');
            $table->index(['location_id', 'year'], 'leave_balances_location_year_idx');
            $table->index(['expiry_date'], 'leave_balances_expiry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
