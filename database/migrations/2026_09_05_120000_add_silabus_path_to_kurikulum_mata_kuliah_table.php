<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurikulum_mata_kuliah', function (Blueprint $table) {
            $table->string('silabus_path')->nullable()->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('kurikulum_mata_kuliah', function (Blueprint $table) {
            $table->dropColumn('silabus_path');
        });
    }
};
