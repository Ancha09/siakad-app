<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('presensi_pertemuans', 'materi_kuliah')) {
            Schema::table('presensi_pertemuans', function (Blueprint $table) {
                $table->text('materi_kuliah')->nullable();
            });
        }

        if (! Schema::hasColumn('presensi_pertemuans', 'keterangan')) {
            Schema::table('presensi_pertemuans', function (Blueprint $table) {
                $table->text('keterangan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('presensi_pertemuans', 'materi_kuliah')) {
            Schema::table('presensi_pertemuans', function (Blueprint $table) {
                $table->dropColumn('materi_kuliah');
            });
        }

        if (Schema::hasColumn('presensi_pertemuans', 'keterangan')) {
            Schema::table('presensi_pertemuans', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
