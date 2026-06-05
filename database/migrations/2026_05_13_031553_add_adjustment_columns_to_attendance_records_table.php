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
            $table->foreignId('adjusted_by')->nullable()->after('approved_at')->constrained('users')->noActionOnDelete();
            $table->dateTime('adjusted_at')->nullable()->after('adjusted_by');
            $table->text('adjustment_reason')->nullable()->after('adjusted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropForeign(['adjusted_by']);
            $table->dropColumn(['adjusted_by', 'adjusted_at', 'adjustment_reason']);
        });
    }
};
