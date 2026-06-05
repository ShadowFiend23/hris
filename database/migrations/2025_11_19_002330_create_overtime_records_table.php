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
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->noActionOnDelete();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->date('date');
            $table->decimal('hours', 5, 2);
            $table->string('overtime_type')->default('weekday')->comment('weekday, weekend, holiday');
            $table->text('reason')->nullable();
            $table->decimal('pay_rate_multiplier', 3, 2)->default(1.50);
            $table->string('status')->default('pending')->comment('pending, approved, rejected, paid');
            $table->foreignId('approved_by')->nullable()->constrained('users')->noActionOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'date']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
