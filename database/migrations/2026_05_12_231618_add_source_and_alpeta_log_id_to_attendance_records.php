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
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->enum('source', ['manual', 'biometric'])->default('manual')->after('notes');
            $table->string('alpeta_log_id')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn(['source', 'alpeta_log_id']);
        });
    }
};
