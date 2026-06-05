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
        Schema::create('payroll_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('period_type', ['weekly', 'semi_monthly', 'monthly'])->default('semi_monthly');
            $table->tinyInteger('pay_day_1')->default(15)->comment('Day of month for first pay date');
            $table->tinyInteger('pay_day_2')->nullable()->comment('Day of month for second pay date (semi-monthly only)');
            $table->tinyInteger('work_days_per_month')->default(26);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
