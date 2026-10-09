<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rps_penilaian_skema')) {
            Schema::table('rps_penilaian_skema', function (Blueprint $table) {
                if (! Schema::hasColumn('rps_penilaian_skema', 'matrix_alokasi')) {
                    $table->json('matrix_alokasi')->nullable()->after('finalized_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rps_penilaian_skema')) {
            Schema::table('rps_penilaian_skema', function (Blueprint $table) {
                if (Schema::hasColumn('rps_penilaian_skema', 'matrix_alokasi')) {
                    $table->dropColumn('matrix_alokasi');
                }
            });
        }
    }
};
