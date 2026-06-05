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
        Schema::table('payroll_settings', function (Blueprint $table): void {
            $table->unsignedInteger('cutoff_offset_days')->default(15)->after('work_days_per_month');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table): void {
            $table->dropColumn('cutoff_offset_days');
        });
    }
};
