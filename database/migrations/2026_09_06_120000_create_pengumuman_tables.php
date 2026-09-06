<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penulis_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('penerima', 20);
            $table->string('judul', 180);
            $table->text('isi');
            $table->string('tautan', 2048)->nullable();
            $table->boolean('penting')->default(false);
            $table->string('status', 20)->default('draft');
            $table->dateTime('terbit_pada')->nullable();
            $table->dateTime('berakhir_pada')->nullable();
            $table->timestamps();
            $table->index(['penerima', 'status', 'terbit_pada']);
        });

        Schema::create('pengumuman_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumuman_id')->constrained('pengumumans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('read_at');
            $table->unique(['pengumuman_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman_reads');
        Schema::dropIfExists('pengumumans');
    }
};
