<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_krs', function (Blueprint $table) {

            $table->id();

            $table->string('tahun_akademik');

            $table->enum('semester', [
                'Ganjil',
                'Genap'
            ]);

            $table->dateTime('tanggal_mulai');

            $table->dateTime('tanggal_selesai');

            $table->unsignedInteger('minimal_sks')
                ->default(0);

            $table->unsignedInteger('maksimal_sks')
                ->default(24);

            $table->enum('status', [
                'Dibuka',
                'Ditutup'
            ])->default('Ditutup');

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_krs');
    }
};