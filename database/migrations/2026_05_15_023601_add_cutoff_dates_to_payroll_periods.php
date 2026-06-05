<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->date('cutoff_start_date')->nullable()->after('end_date');
            $table->date('cutoff_end_date')->nullable()->after('cutoff_start_date');
        });

        // Back-fill existing periods: cutoff = pay period dates
        DB::table('payroll_periods')->update([
            'cutoff_start_date' => DB::raw('start_date'),
            'cutoff_end_date' => DB::raw('end_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('payroll_periods', function (Blueprint $table): void {
            $table->dropColumn(['cutoff_start_date', 'cutoff_end_date']);
        });
    }
};
