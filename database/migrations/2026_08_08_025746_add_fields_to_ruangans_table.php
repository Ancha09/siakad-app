<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruangans', function (Blueprint $table) {
            $table->string('kode_ruangan')->unique()->after('id');
            $table->string('nama_ruangan')->after('kode_ruangan');
            $table->string('gedung')->nullable()->after('nama_ruangan');
            $table->unsignedInteger('kapasitas')->nullable()->after('gedung');
        });
    }

    public function down(): void
    {
        Schema::table('ruangans', function (Blueprint $table) {
            $table->dropColumn([
                'kode_ruangan',
                'nama_ruangan',
                'gedung',
                'kapasitas',
            ]);
        });
    }
};