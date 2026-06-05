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
        Schema::table('work_policies', function (Blueprint $table): void {
            $table->decimal('night_differential_rate', 3, 2)->default(0.10)->after('holiday_overtime_rate');
            $table->decimal('regular_holiday_rate', 3, 2)->default(2.00)->after('night_differential_rate');
            $table->decimal('special_holiday_rate', 3, 2)->default(1.30)->after('regular_holiday_rate');
            $table->decimal('rest_day_rate', 3, 2)->default(1.30)->after('special_holiday_rate');
        });
    }

    public function down(): void
    {
        Schema::table('work_policies', function (Blueprint $table): void {
            $table->dropColumn(['night_differential_rate', 'regular_holiday_rate', 'special_holiday_rate', 'rest_day_rate']);
        });
    }
};
