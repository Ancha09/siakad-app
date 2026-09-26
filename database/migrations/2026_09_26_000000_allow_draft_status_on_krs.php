<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE krs MODIFY status ENUM('Menunggu', 'Diambil', 'Disetujui', 'Ditolak', 'Draft') NOT NULL DEFAULT 'Menunggu'");
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE krs DROP CONSTRAINT IF EXISTS krs_status_check');
            DB::statement("ALTER TABLE krs ADD CONSTRAINT krs_status_check CHECK (status IN ('Menunggu', 'Diambil', 'Disetujui', 'Ditolak', 'Draft'))");
        }
    }

    public function down(): void
    {
        // Jangan persempit enum/check constraint selama masih mungkin ada KRS Draft.
        // Rollback kode tidak boleh membuat data draft lama tidak valid.
    }
};
