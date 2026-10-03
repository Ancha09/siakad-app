<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jadwal_dosen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwals')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('dosens')->onDelete('cascade');
            $table->string('peran', 30)->default('pendamping'); // 'utama', 'pendamping'
            $table->timestamps();

            $table->unique(['jadwal_id', 'dosen_id']);
        });

        // Sinkronisasi data lama secara otomatis (backward compatibility):
        // Seluruh jadwal yang sudah memiliki dosen_id dimasukkan ke tabel jadwal_dosen sebagai dosen 'utama'
        $existingJadwals = DB::table('jadwals')
            ->whereNotNull('dosen_id')
            ->get(['id', 'dosen_id']);

        $now = now();
        $records = [];

        foreach ($existingJadwals as $jadwal) {
            $records[] = [
                'jadwal_id' => $jadwal->id,
                'dosen_id' => $jadwal->dosen_id,
                'peran' => 'utama',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            foreach (array_chunk($records, 100) as $chunk) {
                DB::table('jadwal_dosen')->insertOrIgnore($chunk);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_dosen');
    }
};
