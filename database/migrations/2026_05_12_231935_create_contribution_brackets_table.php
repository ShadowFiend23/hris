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
        Schema::create('contribution_brackets', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['sss', 'philhealth', 'pagibig', 'tax']);
            $table->date('effective_date');
            $table->decimal('min_salary', 12, 2);
            $table->decimal('max_salary', 12, 2)->nullable()->comment('Null means no upper cap');
            $table->decimal('employee_rate', 6, 4)->nullable()->comment('Percentage as decimal e.g. 0.045');
            $table->decimal('employer_rate', 6, 4)->nullable();
            $table->decimal('employee_amount', 12, 2)->nullable()->comment('Fixed amount override');
            $table->decimal('employer_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_brackets');
    }
};
