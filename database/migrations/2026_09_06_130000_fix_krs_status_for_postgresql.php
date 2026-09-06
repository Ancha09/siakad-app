<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Laravel maps enum() to a CHECK constraint on PostgreSQL.
        // The earlier MySQL MODIFY ENUM migration intentionally skips this driver.
        DB::statement('ALTER TABLE krs DROP CONSTRAINT IF EXISTS krs_status_check');
        DB::statement("ALTER TABLE krs ADD CONSTRAINT krs_status_check CHECK (status IN ('Menunggu', 'Diambil', 'Disetujui', 'Ditolak'))");
        DB::statement("ALTER TABLE krs ALTER COLUMN status SET DEFAULT 'Menunggu'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // PostgreSQL rolls this transaction back if Menunggu rows still exist.
        // Do not silently rewrite student submissions on rollback.
        DB::statement('ALTER TABLE krs DROP CONSTRAINT IF EXISTS krs_status_check');
        DB::statement("ALTER TABLE krs ADD CONSTRAINT krs_status_check CHECK (status IN ('Diambil', 'Disetujui', 'Ditolak'))");
        DB::statement("ALTER TABLE krs ALTER COLUMN status SET DEFAULT 'Diambil'");
    }
};
