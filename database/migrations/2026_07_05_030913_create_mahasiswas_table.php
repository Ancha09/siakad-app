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
        Schema::create('mahasiswas', function (Blueprint $table) {

            $table->id();

            $table->string('nim')->unique();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();

            $table->year('angkatan')->nullable();
            $table->unsignedTinyInteger('semester')->nullable();

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswas');
    }
};