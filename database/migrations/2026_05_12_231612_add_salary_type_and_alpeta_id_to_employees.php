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
        Schema::table('employees', function (Blueprint $table): void {
            $table->enum('salary_type', ['monthly', 'daily'])->default('monthly')->after('salary');
            $table->string('alpeta_employee_id')->nullable()->after('salary_type');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(['salary_type', 'alpeta_employee_id']);
        });
    }
};
