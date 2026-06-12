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
        Schema::create('payroll_earnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained('payroll_items')->cascadeOnDelete();
            // Free-form: the engine emits granular types (e.g. overtime_weekday, allowance_rice,
            // rest_day, regular_holiday). Kept as a string so no CHECK constraint blocks them.
            $table->string('type');
            $table->decimal('hours', 6, 2)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['payroll_item_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_earnings');
    }
};
