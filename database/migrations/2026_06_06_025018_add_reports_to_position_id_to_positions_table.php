<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->unsignedBigInteger('reports_to_position_id')->nullable()->after('position_name');
            $table->foreign('reports_to_position_id')->references('id')->on('positions')->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropForeign(['reports_to_position_id']);
            $table->dropColumn('reports_to_position_id');
        });
    }
};
