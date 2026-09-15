<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::table('mahasiswas', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('user_id');
        });

        Schema::table('dosens', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('user_id');
        });

        Schema::table('krs', function (Blueprint $table) {
            $table->foreignId('jadwal_id')->nullable()->change();
            $table->foreignId('mata_kuliah_id')->nullable()->after('jadwal_id')
                ->constrained('mata_kuliahs')->nullOnDelete();
            $table->foreignId('dosen_id')->nullable()->after('mata_kuliah_id')
                ->constrained('dosens')->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->after('dosen_id')
                ->constrained('prodis')->nullOnDelete();
            $table->foreignId('kelas_id')->nullable()->after('prodi_id')
                ->constrained('kelas')->nullOnDelete();
            $table->year('angkatan')->nullable()->after('kelas_id');
            $table->unsignedTinyInteger('semester')->nullable()->after('angkatan');
            $table->boolean('is_manual')->default(false)->after('semester_akademik');
            $table->string('manual_identity', 64)->nullable()->unique()->after('is_manual');
        });

        Schema::table('khs', function (Blueprint $table) {
            $table->unsignedTinyInteger('sks')->nullable()->after('bobot');
            $table->boolean('is_manual')->default(false)->after('semester_akademik');
        });

        Schema::table('presensis', function (Blueprint $table) {
            $table->unsignedTinyInteger('pertemuan')->nullable()->change();
            $table->boolean('is_manual')->default(false)->after('materi');
            $table->string('manual_identity', 64)->nullable()->unique()->after('is_manual');
        });
    }

    public function down(): void
    {
        Schema::table('presensis', function (Blueprint $table) {
            $table->dropUnique(['manual_identity']);
            $table->dropColumn(['is_manual', 'manual_identity']);
        });

        Schema::table('khs', function (Blueprint $table) {
            $table->dropColumn(['sks', 'is_manual']);
        });

        Schema::table('krs', function (Blueprint $table) {
            $table->dropUnique(['manual_identity']);
            $table->dropConstrainedForeignId('mata_kuliah_id');
            $table->dropConstrainedForeignId('dosen_id');
            $table->dropConstrainedForeignId('prodi_id');
            $table->dropConstrainedForeignId('kelas_id');
            $table->dropColumn(['angkatan', 'semester', 'is_manual', 'manual_identity']);
        });

        Schema::table('dosens', fn (Blueprint $table) => $table->dropColumn('is_active'));
        Schema::table('mahasiswas', fn (Blueprint $table) => $table->dropColumn('is_active'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_active'));
    }
};
