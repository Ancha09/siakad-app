<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {

            $table->foreignId('mata_kuliah_id')
                  ->nullable()
                  ->constrained('mata_kuliahs')
                  ->nullOnDelete();

            $table->foreignId('dosen_id')
                  ->nullable()
                  ->constrained('dosens')
                  ->nullOnDelete();

            $table->foreignId('ruangan_id')
                  ->nullable()
                  ->constrained('ruangans')
                  ->nullOnDelete();

            $table->enum('hari', [
                'Senin',
                'Selasa',
                'Rabu',
                'Kamis',
                'Jumat',
                'Sabtu'
            ]);

            $table->time('jam_mulai');
            $table->time('jam_selesai');

            $table->string('kelas')->nullable();

            $table->string('tahun_akademik')->nullable();

            $table->enum('semester_akademik', [
                'Ganjil',
                'Genap'
            ])->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {

            $table->dropForeign(['mata_kuliah_id']);
            $table->dropForeign(['dosen_id']);
            $table->dropForeign(['ruangan_id']);

            $table->dropColumn([
                'mata_kuliah_id',
                'dosen_id',
                'ruangan_id',
                'hari',
                'jam_mulai',
                'jam_selesai',
                'kelas',
                'tahun_akademik',
                'semester_akademik',
            ]);

        });
    }
};