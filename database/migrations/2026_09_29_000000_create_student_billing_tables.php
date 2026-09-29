<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->unique()->constrained('mahasiswas')->restrictOnDelete();
            $table->boolean('enabled')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tagihan_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->restrictOnDelete();
            $table->string('kode_tagihan', 50)->unique();
            $table->string('creation_key', 64)->unique();
            $table->string('fingerprint', 64)->index();
            $table->string('jenis_tagihan', 100);
            $table->text('deskripsi');
            $table->unsignedBigInteger('nominal_pokok');
            $table->unsignedBigInteger('biaya_layanan')->default(0);
            $table->unsignedBigInteger('total_tagihan');
            $table->unsignedBigInteger('total_dibayar')->default(0);
            $table->unsignedBigInteger('sisa_tagihan');
            $table->string('status', 30)->default('belum_dibayar');
            $table->date('jatuh_tempo')->nullable();
            $table->boolean('boleh_cicil')->default(false);
            $table->boolean('is_test')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['mahasiswa_id', 'status']);
        });

        Schema::create('pembayaran_mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_mahasiswa_id')->constrained('tagihan_mahasiswas')->restrictOnDelete();
            $table->string('external_id', 80)->unique();
            $table->string('invoice_id', 100)->nullable()->unique();
            $table->string('source', 20)->default('midtrans');
            $table->string('status', 25)->default('creating');
            $table->unsignedBigInteger('nominal_pokok');
            $table->unsignedBigInteger('biaya_layanan')->default(0);
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('IDR');
            $table->text('checkout_url')->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->string('provider_payment_id', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_test')->default(true);
            $table->timestamps();
            $table->index(['tagihan_mahasiswa_id', 'status']);
        });

        Schema::create('payment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tagihan_mahasiswa_id')->nullable()->constrained('tagihan_mahasiswas')->restrictOnDelete();
            $table->foreignId('pembayaran_mahasiswa_id')->nullable()->constrained('pembayaran_mahasiswas')->restrictOnDelete();
            $table->string('action', 60);
            $table->text('reason')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 64)->unique();
            $table->string('invoice_id', 100)->nullable();
            $table->string('external_id', 80)->nullable();
            $table->string('status', 25)->nullable();
            $table->string('outcome', 60);
            $table->json('safe_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        // Catatan tagihan, pembayaran dan audit tidak dihapus oleh rollback aplikasi.
    }
};
