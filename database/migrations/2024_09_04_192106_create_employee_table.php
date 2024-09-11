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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employeeID');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('middleName')->nullable();
            $table->string('suffix')->nullable();
            $table->text('addressNo');
            $table->text('addressStreet');
            $table->text('addressProvince');
            $table->text('addressCity');
            $table->text('addressBarangay');
            $table->string('phoneNumber');
            $table->string('emailAddress');
            $table->integer('department');
            $table->integer('position');
            $table->date('birthDate');
            $table->string('photo');
            $table->integer('employmentType');
            $table->string('status');
            $table->date('dateStart');
            $table->date('dateEnd')->nullable();
            $table->string('gender');
            $table->string('maritalStatus');
            $table->integer('supervisor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
