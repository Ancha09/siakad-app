<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_mahasiswas', function (Blueprint $table) {
            $table->foreignId('mahasiswa_id')->nullable()->after('tagihan_mahasiswa_id')->constrained('mahasiswas')->restrictOnDelete();
            $table->string('metode_pembayaran', 30)->nullable()->after('source')->index();
            $table->string('nomor_referensi', 100)->nullable()->after('provider_payment_id')->index();
            $table->text('bukti_path')->nullable()->after('nomor_referensi');
            $table->text('catatan')->nullable()->after('bukti_path');
            $table->foreignId('created_by')->nullable()->after('catatan')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Ledger pembayaran tidak dikurangi kolomnya saat rollback aplikasi.
    }
};
