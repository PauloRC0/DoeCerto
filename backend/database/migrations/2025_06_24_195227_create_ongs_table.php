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
        Schema::create('ongs', function (Blueprint $table) {
            $table->id();
            $table->string('ong_name');
            $table->string('ong_email')->unique();
            $table->string('ong_password');
            $table->string('ong_cnpj');
            $table->string('ong_cep')->nullable();
            $table->decimal('ong_latitude', 10, 8)->nullable();
            $table->decimal('ong_longitude', 10, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ongs');
    }
};
