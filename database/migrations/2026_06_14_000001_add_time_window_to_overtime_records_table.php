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
        Schema::table('overtime_records', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable()->after('date')->comment('Overtime time-in (date + time)');
            $table->dateTime('end_at')->nullable()->after('start_at')->comment('Overtime time-out (date + time)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtime_records', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at']);
        });
    }
};
