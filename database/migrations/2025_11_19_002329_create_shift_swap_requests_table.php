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
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('employees')->noActionOnDelete();
            $table->foreignId('target_employee_id')->constrained('employees')->noActionOnDelete();
            $table->foreignId('requester_schedule_id')->constrained('employee_schedules')->noActionOnDelete();
            $table->foreignId('target_schedule_id')->constrained('employee_schedules')->noActionOnDelete();
            $table->text('reason')->nullable();
            $table->string('status')->default('pending')->comment('pending, approved, rejected');
            $table->foreignId('approved_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['requester_id', 'status']);
            $table->index(['target_employee_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
    }
};
