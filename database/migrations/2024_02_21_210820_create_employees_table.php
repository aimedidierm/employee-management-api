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
            $table->string("code", 7)->unique();
            $table->string("name");
            $table->string("national_id", 16)->unique();
            $table->string("phone", 13)->unique()->nullable();
            $table->string("email")->unique();
            $table->text("password")->nullable();
            $table->date("dob");
            $table->string("reset_code")->nullable();
            $table->dateTime("reset_code_expires_in")->nullable();
            $table->string("status");
            $table->string("position");
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
