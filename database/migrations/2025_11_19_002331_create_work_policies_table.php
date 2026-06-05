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
        Schema::create('work_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->noActionOnDelete();
            $table->integer('standard_hours_per_day')->default(8);
            $table->integer('standard_hours_per_week')->default(40);
            $table->integer('late_threshold_minutes')->default(15);
            $table->integer('grace_period_minutes')->default(5);
            $table->decimal('weekday_overtime_rate', 3, 2)->default(1.25);
            $table->decimal('weekend_overtime_rate', 3, 2)->default(1.50);
            $table->decimal('holiday_overtime_rate', 3, 2)->default(2.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_policies');
    }
};
