<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable `period` discriminator used by withholding-tax rows so the
     * BIR revised withholding tables can be stored per payroll period type
     * (semi_monthly, monthly, weekly). Null for non-tax bracket types.
     */
    public function up(): void
    {
        Schema::table('contribution_brackets', function (Blueprint $table): void {
            $table->string('period', 20)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_brackets', function (Blueprint $table): void {
            $table->dropColumn('period');
        });
    }
};
