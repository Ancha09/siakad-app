<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('krs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('mahasiswa_id')
                  ->constrained('mahasiswas')
                  ->cascadeOnDelete();

            $table->foreignId('jadwal_id')
                  ->constrained('jadwals')
                  ->cascadeOnDelete();

            $table->enum('status', [
                'Diambil',
                'Disetujui',
                'Ditolak'
            ])->default('Diambil');

            $table->string('tahun_akademik');

            $table->enum('semester_akademik', [
                'Ganjil',
                'Genap'
            ]);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krs');
    }
};