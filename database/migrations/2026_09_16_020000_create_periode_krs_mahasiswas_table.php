<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_krs_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_krs_id')->constrained('periode_krs')->cascadeOnDelete();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->boolean('status_akses')->default(false);
            $table->timestamp('tanggal_dibuka')->nullable();
            $table->timestamp('tanggal_ditutup')->nullable();
            $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(
                ['periode_krs_id', 'mahasiswa_id'],
                'periode_krs_mahasiswa_unique'
            );
            $table->index(
                ['periode_krs_id', 'status_akses'],
                'periode_krs_akses_filter_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_krs_mahasiswas');
    }
};
