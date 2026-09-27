<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('krs', function (Blueprint $table) {
            $table->boolean('admin_revision_open')->default(false);
        });

        Schema::create('krs_approval_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mahasiswa_id')->nullable()->constrained('mahasiswas')->nullOnDelete();
            $table->foreignId('periode_krs_id')->nullable()->constrained('periode_krs')->nullOnDelete();
            $table->string('aksi', 30);
            $table->text('alasan');
            $table->json('krs_sebelum');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['periode_krs_id', 'created_at']);
        });

        Schema::table('pengumumans', function (Blueprint $table) {
            $table->string('target_type', 20)->default('all');
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_periode_krs_id')->nullable()->constrained('periode_krs')->nullOnDelete();
            $table->foreignId('target_prodi_id')->nullable()->constrained('prodis')->nullOnDelete();
            $table->unsignedSmallInteger('target_angkatan')->nullable();
            $table->index(['target_type', 'target_user_id']);
        });
    }

    public function down(): void
    {
        // Riwayat reset dan target penerima dipertahankan agar rollback kode
        // tidak menghapus audit atau membuat pengumuman pribadi menjadi umum.
    }
};
