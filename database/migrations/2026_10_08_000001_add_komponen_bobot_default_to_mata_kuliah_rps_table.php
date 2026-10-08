<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mata_kuliah_rps')) {
            Schema::table('mata_kuliah_rps', function (Blueprint $table) {
                if (! Schema::hasColumn('mata_kuliah_rps', 'komponen_bobot_default')) {
                    $table->json('komponen_bobot_default')->nullable()->after('porsi_cpl');
                }
                if (! Schema::hasColumn('mata_kuliah_rps', 'file_rps_path')) {
                    $table->string('file_rps_path')->nullable()->after('file_rps');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mata_kuliah_rps')) {
            Schema::table('mata_kuliah_rps', function (Blueprint $table) {
                if (Schema::hasColumn('mata_kuliah_rps', 'komponen_bobot_default')) {
                    $table->dropColumn('komponen_bobot_default');
                }
                if (Schema::hasColumn('mata_kuliah_rps', 'file_rps_path')) {
                    $table->dropColumn('file_rps_path');
                }
            });
        }
    }
};
