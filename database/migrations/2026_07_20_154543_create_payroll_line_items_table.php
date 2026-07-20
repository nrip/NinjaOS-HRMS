<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->string('type', 10);   // 'earning' | 'deduction'
            $table->string('code', 20);   // e.g., 'BASIC', 'HRA', 'PF', 'ESI', 'PT', 'TDS'
            $table->string('label', 100);
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_statutory')->default(false);
            $table->timestamps();
            $table->index(['payroll_record_id', 'type'], 'idx_line_items_record_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_line_items');
    }
};
