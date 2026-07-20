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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('employee_id')->unique();
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('aadhaar')->nullable()->unique();
            $table->string('pan')->nullable()->unique();
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->enum('status', ['onboarding', 'probation', 'confirmed', 'transferred', 'on_leave', 'suspended', 'exit'])->default('onboarding');
            $table->date('date_of_joining');
            $table->date('date_of_exit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
