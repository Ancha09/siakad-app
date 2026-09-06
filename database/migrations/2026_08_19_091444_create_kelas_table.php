<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();

            $table->string('nama_kelas');

            $table->foreignId('prodi_id')
                ->constrained('prodis')
                ->cascadeOnDelete();

            $table->string('angkatan', 4);

            $table->unsignedTinyInteger('semester')->nullable();

            $table->foreignId('dosen_wali_id')
                ->nullable()
                ->constrained('dosens')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};