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
        Schema::table('loans', function (Blueprint $table): void {
            $table->foreignId('loan_type_id')->nullable()->after('type')->constrained('loan_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropForeign(['loan_type_id']);
            $table->dropColumn('loan_type_id');
        });
    }
};
