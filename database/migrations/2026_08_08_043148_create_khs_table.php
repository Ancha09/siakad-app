<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('khs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('krs_id')
                  ->constrained('krs')
                  ->cascadeOnDelete();

            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->string('nilai_huruf')->nullable();
            $table->decimal('bobot', 3, 2)->nullable();

            $table->string('tahun_akademik');
            $table->enum('semester_akademik', ['Ganjil', 'Genap']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khs');
    }
};