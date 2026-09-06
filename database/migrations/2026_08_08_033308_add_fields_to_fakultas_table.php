<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fakultas', function (Blueprint $table) {
            $table->string('kode_fakultas')->unique()->after('id');
            $table->string('nama_fakultas')->after('kode_fakultas');
        });
    }

    public function down(): void
    {
        Schema::table('fakultas', function (Blueprint $table) {
            $table->dropColumn([
                'kode_fakultas',
                'nama_fakultas',
            ]);
        });
    }
};