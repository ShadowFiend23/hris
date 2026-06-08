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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo_login')->nullable()->after('swap_enabled');
            $table->string('logo_nav')->nullable()->after('logo_login');
            $table->string('favicon')->nullable()->after('logo_nav');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['logo_login', 'logo_nav', 'favicon']);
        });
    }
};
