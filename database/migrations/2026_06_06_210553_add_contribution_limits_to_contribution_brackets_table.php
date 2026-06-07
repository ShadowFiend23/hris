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
        Schema::table('contribution_brackets', function (Blueprint $table) {
            $table->decimal('min_contribution', 12, 2)->nullable()->after('employer_amount');
            $table->decimal('max_contribution', 12, 2)->nullable()->after('min_contribution');
            $table->string('notes', 255)->nullable()->after('max_contribution');
        });
    }

    public function down(): void
    {
        Schema::table('contribution_brackets', function (Blueprint $table) {
            $table->dropColumn(['min_contribution', 'max_contribution', 'notes']);
        });
    }
};
