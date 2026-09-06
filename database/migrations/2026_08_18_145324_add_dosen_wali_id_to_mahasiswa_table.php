<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->foreignId('dosen_wali_id')
                ->nullable()
                ->after('prodi_id')
                ->constrained('dosens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->dropForeign(['dosen_wali_id']);
            $table->dropColumn('dosen_wali_id');
        });
    }
};