<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pastikan tabel cpls memiliki kolom prodi_id
        if (Schema::hasTable('cpls')) {
            if (! Schema::hasColumn('cpls', 'prodi_id')) {
                Schema::table('cpls', function (Blueprint $table) {
                    $table->foreignId('prodi_id')->nullable()->after('id')->constrained('prodis')->nullOnDelete();
                });

                // Sinkronisasi prodi_id dari program_studi_id jika ada
                if (Schema::hasColumn('cpls', 'program_studi_id')) {
                    DB::table('cpls')
                        ->whereNull('prodi_id')
                        ->whereNotNull('program_studi_id')
                        ->update(['prodi_id' => DB::raw('program_studi_id')]);
                }
            }
        } else {
            Schema::create('cpls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('prodi_id')->nullable()->constrained('prodis')->nullOnDelete();
                $table->foreignId('program_studi_id')->nullable()->constrained('prodis')->nullOnDelete();
                $table->string('kode_cpl', 50);
                $table->text('nama_cpl')->nullable();
                $table->text('deskripsi')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Tabel CPMK (Capaian Pembelajaran Mata Kuliah)
        if (! Schema::hasTable('cpmks')) {
            Schema::create('cpmks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
                $table->string('kode_cpmk', 50);
                $table->text('deskripsi')->nullable();
                $table->timestamps();

                $table->index('mata_kuliah_id');
            });
        }

        // 3. Tabel Sub-CPMK
        if (! Schema::hasTable('sub_cpmks')) {
            Schema::create('sub_cpmks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cpmk_id')->constrained('cpmks')->cascadeOnDelete();
                $table->foreignId('cpl_id')->nullable()->constrained('cpls')->nullOnDelete();
                $table->string('kode_sub_cpmk', 50);
                $table->text('deskripsi')->nullable();
                $table->decimal('bobot_default', 5, 2)->nullable();
                $table->timestamps();

                $table->index('cpmk_id');
                $table->index('cpl_id');
            });
        }

        // 4. Tabel Mata Kuliah RPS
        if (! Schema::hasTable('mata_kuliah_rps')) {
            Schema::create('mata_kuliah_rps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mata_kuliah_id')->constrained('mata_kuliahs')->cascadeOnDelete();
                $table->string('tahun_akademik', 20)->nullable();
                $table->string('file_rps')->nullable();
                $table->decimal('target_passing_grade', 5, 2)->default(60.00);
                $table->json('porsi_cpl')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('mata_kuliah_id');
            });
        }

        // 5. Tabel Skema Penilaian RPS per Jadwal
        if (! Schema::hasTable('rps_penilaian_skema')) {
            Schema::create('rps_penilaian_skema', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jadwal_id')->constrained('jadwals')->cascadeOnDelete();
                $table->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete();
                $table->boolean('is_finalized')->default(false);
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();

                $table->unique('jadwal_id');
                $table->index('dosen_id');
            });
        }

        // 6. Tabel Komponen Instrumen Penilaian RPS
        if (! Schema::hasTable('rps_penilaian_komponen')) {
            Schema::create('rps_penilaian_komponen', function (Blueprint $table) {
                $table->id();
                $table->foreignId('skema_id')->constrained('rps_penilaian_skema')->cascadeOnDelete();
                $table->string('nama_instrumen', 100);
                $table->foreignId('sub_cpmk_id')->nullable()->constrained('sub_cpmks')->nullOnDelete();
                $table->decimal('bobot', 5, 2)->default(0.00);
                $table->integer('urutan')->default(0);
                $table->timestamps();

                $table->index('skema_id');
                $table->index('sub_cpmk_id');
            });
        }

        // 7. Tabel Nilai Komponen Mahasiswa
        if (! Schema::hasTable('mahasiswa_nilai_komponen')) {
            Schema::create('mahasiswa_nilai_komponen', function (Blueprint $table) {
                $table->id();
                $table->foreignId('krs_id')->constrained('krs')->cascadeOnDelete();
                $table->foreignId('komponen_id')->constrained('rps_penilaian_komponen')->cascadeOnDelete();
                $table->decimal('nilai_angka', 5, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['krs_id', 'komponen_id']);
                $table->index('komponen_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_nilai_komponen');
        Schema::dropIfExists('rps_penilaian_komponen');
        Schema::dropIfExists('rps_penilaian_skema');
        Schema::dropIfExists('mata_kuliah_rps');
        Schema::dropIfExists('sub_cpmks');
        Schema::dropIfExists('cpmks');

        if (Schema::hasTable('cpls') && Schema::hasColumn('cpls', 'prodi_id')) {
            Schema::table('cpls', function (Blueprint $table) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            });
        }
    }
};

