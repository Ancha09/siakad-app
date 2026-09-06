<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuesioners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krs_id')->unique()->constrained('krs')->cascadeOnDelete();
            $table->unsignedTinyInteger('penguasaan_materi');
            $table->unsignedTinyInteger('kejelasan_penyampaian');
            $table->unsignedTinyInteger('kesesuaian_rps');
            $table->unsignedTinyInteger('ketepatan_waktu');
            $table->unsignedTinyInteger('kesempatan_bertanya');
            $table->unsignedTinyInteger('objektivitas_penilaian');
            $table->unsignedTinyInteger('penggunaan_media');
            $table->unsignedTinyInteger('motivasi_belajar');
            $table->text('komentar')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuesioners');
    }
};
