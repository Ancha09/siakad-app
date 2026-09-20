<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cpls')) {
            Schema::create('cpls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_studi_id')->nullable()->constrained('prodis')->nullOnDelete();
                $table->string('kode_cpl', 30);
                $table->text('nama_cpl');
                $table->text('deskripsi')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['program_studi_id', 'kode_cpl']);
            });
        }

        if (! Schema::hasTable('cpl_mata_kuliah')) {
            Schema::create('cpl_mata_kuliah', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cpl_id')->constrained('cpls')->cascadeOnDelete();
                $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliahs')->nullOnDelete();
                $table->string('kode_sumber', 100);
                $table->string('nama_sumber');
                $table->unsignedTinyInteger('semester')->nullable();
                $table->unsignedTinyInteger('sks')->nullable();
                $table->timestamps();

                $table->unique(['cpl_id', 'mata_kuliah_id']);
                $table->unique(['cpl_id', 'kode_sumber']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cpl_mata_kuliah');
        Schema::dropIfExists('cpls');
    }
};
