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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->noActionOnDelete();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->date('date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->integer('break_duration')->default(0)->comment('Break duration in minutes');
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->string('status')->default('present')->comment('present, late, absent, half_day');
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
