<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('periode_krs_mahasiswas')) {
            Schema::create('periode_krs_mahasiswas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('periode_krs_id')->constrained('periode_krs')->cascadeOnDelete();
                $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
                $table->boolean('status_akses')->default(false);
                $table->timestamp('tanggal_dibuka')->nullable();
                $table->timestamp('tanggal_ditutup')->nullable();
                $table->foreignId('dibuka_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->text('catatan')->nullable();
                $table->timestamps();
                $table->unique(['periode_krs_id', 'mahasiswa_id'], 'periode_krs_mahasiswa_unique');
                $table->index(['periode_krs_id', 'status_akses'], 'periode_krs_akses_filter_index');
            });

            return;
        }

        if (! Schema::hasColumn('periode_krs_mahasiswas', 'dibuka_oleh')) {
            Schema::table('periode_krs_mahasiswas', function (Blueprint $table) {
                $table->foreignId('dibuka_oleh')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });

            if (Schema::hasColumn('periode_krs_mahasiswas', 'admin_id')) {
                DB::table('periode_krs_mahasiswas')
                    ->whereNotNull('admin_id')
                    ->update(['dibuka_oleh' => DB::raw('admin_id')]);
            }
        }

        $statusColumnType = Schema::getColumnType('periode_krs_mahasiswas', 'status_akses');

        if (in_array($statusColumnType, ['char', 'varchar', 'string', 'text'], true)) {
            DB::table('periode_krs_mahasiswas')
                ->where('status_akses', 'dibuka')
                ->update(['status_akses' => '1']);
            DB::table('periode_krs_mahasiswas')
                ->where('status_akses', 'ditutup')
                ->update(['status_akses' => '0']);

            Schema::table('periode_krs_mahasiswas', function (Blueprint $table) {
                $table->boolean('status_akses')->default(false)->change();
            });
        }
    }

    public function down(): void
    {
        // Sengaja tidak membalikkan perubahan agar data akses tetap aman.
    }
};
