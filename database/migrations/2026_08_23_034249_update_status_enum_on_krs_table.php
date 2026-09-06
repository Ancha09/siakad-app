<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE krs
            MODIFY status ENUM(
                'Menunggu',
                'Diambil',
                'Disetujui',
                'Ditolak'
            )
            NOT NULL
            DEFAULT 'Menunggu'
        ");
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("
            ALTER TABLE krs
            MODIFY status ENUM(
                'Diambil',
                'Disetujui',
                'Ditolak'
            )
            NOT NULL
            DEFAULT 'Diambil'
        ");
    }
};
