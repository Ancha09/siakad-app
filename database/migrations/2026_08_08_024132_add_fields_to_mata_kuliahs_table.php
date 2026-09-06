<?php

use Illuminate\Database\Migrations\Migration;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kolom mata kuliah sudah dibuat lengkap pada migration create_mata_kuliahs_table.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada perubahan skema yang perlu dibatalkan.
    }
};
