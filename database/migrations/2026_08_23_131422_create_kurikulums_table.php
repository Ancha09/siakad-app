<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulums', function (Blueprint $table) {

            $table->id();

            // Program Studi pemilik kurikulum
            $table->foreignId('prodi_id')
                ->constrained('prodis')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Nama kurikulum
            $table->string('nama_kurikulum');

            // Tahun mulai berlaku
            $table->year('tahun_mulai');

            // Tahun selesai / berlaku sampai
            $table->year('tahun_selesai')->nullable();

            // Aktif / Tidak Aktif
            $table->enum('status', [
                'Aktif',
                'Tidak Aktif'
            ])->default('Aktif');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulums');
    }
};