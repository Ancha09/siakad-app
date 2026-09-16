<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_krs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->string('tahun_akademik', 20);
            $table->string('semester_akademik', 10);
            $table->string('status_bayar', 20)->default('belum_bayar');
            $table->date('tanggal_bayar')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['mahasiswa_id', 'tahun_akademik', 'semester_akademik'],
                'pembayaran_krs_periode_unique'
            );
            $table->index(
                ['status_bayar', 'tahun_akademik', 'semester_akademik'],
                'pembayaran_krs_filter_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_krs');
    }
};
