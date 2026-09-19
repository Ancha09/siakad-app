<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jadwals', 'is_lintas_prodi')) {
            Schema::table('jadwals', function (Blueprint $table) {
                // Default false keeps every historical schedule behaving as a regular schedule.
                $table->boolean('is_lintas_prodi')->default(false);
            });
        }

        if (! Schema::hasColumn('jadwals', 'group_key')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->string('group_key', 100)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus kolom agar konfigurasi grup jadwal production tidak hilang.
    }
};
