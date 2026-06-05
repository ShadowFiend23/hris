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
        Schema::create('employee_allowances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->string('type')->default('other'); // rice, transport, clothing, meal, other
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->boolean('is_taxable')->default(false);
            $table->string('frequency')->default('monthly'); // monthly, per_cutoff
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');
    }
};
