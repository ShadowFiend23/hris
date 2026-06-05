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
        Schema::table('employee_allowances', function (Blueprint $table): void {
            $table->foreignId('allowance_type_id')->nullable()->after('company_id')
                ->constrained('allowance_types')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_allowances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('allowance_type_id');
        });
    }
};
