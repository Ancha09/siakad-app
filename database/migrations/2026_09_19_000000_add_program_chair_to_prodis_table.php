<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('prodis', 'ketua_program_studi_nama')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->string('ketua_program_studi_nama', 150)->nullable();
            });
        }

        if (! Schema::hasColumn('prodis', 'ketua_program_studi_nip')) {
            Schema::table('prodis', function (Blueprint $table) {
                $table->string('ketua_program_studi_nip', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak menghapus kolom agar data Ketua Program Studi tidak hilang saat rollback.
    }
};
