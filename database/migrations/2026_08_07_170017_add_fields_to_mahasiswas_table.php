<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    public function up(): void
    {
        // Kolom mahasiswa sudah dibuat lengkap pada migration create_mahasiswas_table.
    }

    public function down(): void
    {
        // Tidak ada perubahan skema yang perlu dibatalkan.
    }
};
