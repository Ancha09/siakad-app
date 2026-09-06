<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosens', function (Blueprint $table) {

            $table->id();

            $table->string('nidn')->unique();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();

            $table->string('jabatan')->nullable();
            $table->string('golongan')->nullable();

            $table->foreignId('prodi_id')
                  ->nullable()
                  ->constrained('prodis')
                  ->nullOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosens');
    }
};