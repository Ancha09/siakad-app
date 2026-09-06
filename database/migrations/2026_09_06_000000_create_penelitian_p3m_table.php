<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penelitian_p3m', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')
                ->constrained('dosens')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('judul');
            $table->enum('jenis', ['Penelitian', 'Pengabdian']);
            $table->year('tahun');
            $table->string('sumber_dana')->nullable();
            $table->enum('status', ['Draft', 'Berjalan', 'Selesai', 'Terbit'])->default('Draft');
            $table->text('ringkasan')->nullable();
            $table->string('link_artikel', 2048)->nullable();
            $table->string('hasil_path')->nullable();
            $table->string('artikel_path')->nullable();
            $table->timestamps();

            $table->index(['dosen_id', 'tahun']);
            $table->index(['dosen_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penelitian_p3m');
    }
};
