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
        if (Schema::hasTable('rps_penilaian_skema') && ! Schema::hasColumn('rps_penilaian_skema', 'komponen_standar')) {
            Schema::table('rps_penilaian_skema', function (Blueprint $table) {
                $table->json('komponen_standar')->nullable()->after('matrix_alokasi');
            });
        }

        if (Schema::hasTable('khs') && ! Schema::hasColumn('khs', 'detail_nilai')) {
            Schema::table('khs', function (Blueprint $table) {
                $table->json('detail_nilai')->nullable()->after('bobot');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('rps_penilaian_skema') && Schema::hasColumn('rps_penilaian_skema', 'komponen_standar')) {
            Schema::table('rps_penilaian_skema', function (Blueprint $table) {
                $table->dropColumn('komponen_standar');
            });
        }

        if (Schema::hasTable('khs') && Schema::hasColumn('khs', 'detail_nilai')) {
            Schema::table('khs', function (Blueprint $table) {
                $table->dropColumn('detail_nilai');
            });
        }
    }
};

