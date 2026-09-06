<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->boolean('skripsi_aktif')->default(false);
        });
        Schema::create('periode_skripsis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->dateTime('mulai');
            $table->dateTime('berakhir');
            // Atomic write serializes submissions, decisions and deadline edits, also on SQLite.
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['mulai', 'berakhir']);
        });
        Schema::create('pengajuan_skripsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_skripsi_id')->constrained()->restrictOnDelete();
            $table->foreignId('mahasiswa_id')->constrained()->restrictOnDelete();
            $table->foreignId('dosen_id')->constrained()->restrictOnDelete();
            $table->string('judul', 1000);
            $table->enum('status', ['Menunggu', 'Diterima', 'Ditolak', 'Dialihkan'])->default('Menunggu');
            $table->text('alasan_keputusan')->nullable();
            $table->dateTime('diputuskan_pada')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->enum('jenis_pembuat', ['mahasiswa', 'admin']);
            $table->foreignId('pengajuan_asal_id')->nullable()->constrained('pengajuan_skripsis')->restrictOnDelete();
            // NULL permits multiple historical rejections; the generated value cannot be forged.
            $table->unsignedBigInteger('mahasiswa_aktif')->nullable()->storedAs("CASE WHEN status IN ('Menunggu', 'Diterima') THEN mahasiswa_id ELSE NULL END");
            $table->timestamps();
            $table->unique(['periode_skripsi_id', 'mahasiswa_aktif'], 'skripsi_satu_aktif_per_periode');
            $table->index(['periode_skripsi_id', 'mahasiswa_id', 'id'], 'skripsi_riwayat_mahasiswa');
            $table->index(['periode_skripsi_id', 'dosen_id', 'status'], 'skripsi_beban_dosen');
        });
        Schema::create('riwayat_skripsis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_skripsi_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('pengajuan_skripsi_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('pelaku_id')->constrained('users')->restrictOnDelete();
            $table->string('pelaku_nama');
            $table->string('pelaku_role');
            $table->string('tindakan');
            $table->json('perubahan');
            $table->timestamps();
            $table->index(['periode_skripsi_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_skripsis');
        Schema::dropIfExists('pengajuan_skripsis');
        Schema::dropIfExists('periode_skripsis');
        Schema::table('dosens', fn (Blueprint $table) => $table->dropColumn('skripsi_aktif'));
    }
};
