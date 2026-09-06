<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulum_mata_kuliah', function (Blueprint $table) {

            $table->id();

            // Relasi ke kurikulum
            $table->foreignId('kurikulum_id')
                ->constrained('kurikulums')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Relasi ke mata kuliah
            $table->foreignId('mata_kuliah_id')
                ->constrained('mata_kuliahs')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Semester dalam kurikulum
            $table->unsignedTinyInteger('semester');

            // Jenis mata kuliah
            $table->enum('jenis', [
                'Wajib',
                'Pilihan'
            ])->default('Wajib');

            $table->timestamps();

            // Constraint unik dengan nama pendek
            $table->unique(
                [
                    'kurikulum_id',
                    'mata_kuliah_id',
                    'semester'
                ],
                'kmk_unique'
            );

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurikulum_mata_kuliah');
    }
};