<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kuesioners', function (Blueprint $table) {
            $table->foreignId('dosen_id')
                ->nullable()
                ->after('krs_id')
                ->constrained('dosens')
                ->nullOnDelete();
            $table->foreignId('mata_kuliah_id')
                ->nullable()
                ->after('dosen_id')
                ->constrained('mata_kuliahs')
                ->nullOnDelete();
            $table->foreignId('kelas_id')
                ->nullable()
                ->after('mata_kuliah_id')
                ->constrained('kelas')
                ->nullOnDelete();
            $table->string('tahun_akademik', 20)->nullable()->after('kelas_id');
            $table->string('semester_akademik', 10)->nullable()->after('tahun_akademik');
        });
    }

    public function down(): void
    {
        Schema::table('kuesioners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropConstrainedForeignId('mata_kuliah_id');
            $table->dropConstrainedForeignId('dosen_id');
            $table->dropColumn(['tahun_akademik', 'semester_akademik']);
        });
    }
};
