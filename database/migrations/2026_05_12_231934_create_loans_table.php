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
        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->noActionOnDelete();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->enum('type', ['sss_loan', 'pagibig_loan', 'company_loan']);
            $table->decimal('principal', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->decimal('monthly_amortization', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'defaulted'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'status']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
