<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('krs_id')
                ->constrained('krs')
                ->cascadeOnDelete();

            $table->date('tanggal');

            $table->unsignedTinyInteger('pertemuan');

            $table->enum('status', [
                'Hadir',
                'Izin',
                'Sakit',
                'Alpha',
            ])->default('Hadir');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->unique(['krs_id', 'pertemuan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensis');
    }
};