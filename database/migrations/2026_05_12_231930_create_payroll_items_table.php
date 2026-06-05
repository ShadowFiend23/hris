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
        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->noActionOnDelete();
            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('total_hours', 6, 2)->default(0);
            $table->decimal('days_worked', 5, 2)->default(0);
            $table->decimal('days_absent', 5, 2)->default(0);
            $table->unsignedInteger('minutes_late')->default(0);
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['payroll_period_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
