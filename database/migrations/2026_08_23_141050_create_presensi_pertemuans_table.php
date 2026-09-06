<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_pertemuans', function (Blueprint $table) {

            $table->id();

            // Jadwal yang sedang diabsen
            $table->foreignId('jadwal_id')
                ->constrained('jadwals')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Pertemuan 1 - 16
            $table->unsignedTinyInteger('pertemuan');

            // Tanggal perkuliahan
            $table->date('tanggal');

            // Foto dokumentasi
            $table->string('foto')->nullable();

            // File materi
            $table->string('materi')->nullable();

            $table->timestamps();

            // Satu jadwal hanya boleh memiliki
            // satu sesi untuk setiap pertemuan
            $table->unique(
                ['jadwal_id', 'pertemuan'],
                'jadwal_pertemuan_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_pertemuans');
    }
};