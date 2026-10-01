<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\Prodi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttendanceSampleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Mempersiapkan data contoh absensi untuk dashboard...');

        // Pastikan ada beberapa prodi
        $prodis = Prodi::all();
        if ($prodis->count() < 2) {
            Prodi::firstOrCreate(
                ['kode_prodi' => 'IF-01'],
                [
                    'nama_prodi' => 'Teknik Informatika',
                    'jenjang' => 'S1',
                    'ketua_program_studi_nama' => 'Dr. Ir. Budi Santoso, M.T.',
                    'ketua_program_studi_nip' => '198001012005011001',
                ]
            );
            Prodi::firstOrCreate(
                ['kode_prodi' => 'SI-01'],
                [
                    'nama_prodi' => 'Sistem Informasi',
                    'jenjang' => 'S1',
                    'ketua_program_studi_nama' => 'Dr. Ratna Sari, M.Kom.',
                    'ketua_program_studi_nip' => '198502022008022002',
                ]
            );
            $prodis = Prodi::all();
        }

        // Pastikan ada mahasiswa untuk sampel
        $sampleStudents = [
            ['nim' => '20221001', 'nama' => 'Ahmad Fauzan', 'prodi_id' => $prodis[0]->id ?? 1],
            ['nim' => '20221002', 'nama' => 'Bima Pratama', 'prodi_id' => $prodis[0]->id ?? 1],
            ['nim' => '20221003', 'nama' => 'Citra Lestari', 'prodi_id' => $prodis[1]->id ?? 1],
            ['nim' => '20221004', 'nama' => 'Dwi Handayani', 'prodi_id' => $prodis[1]->id ?? 1],
            ['nim' => '20221005', 'nama' => 'Eko Prasetyo', 'prodi_id' => $prodis[2]->id ?? ($prodis[0]->id ?? 1)],
            ['nim' => '20221006', 'nama' => 'Fajar Nugroho', 'prodi_id' => $prodis[2]->id ?? ($prodis[0]->id ?? 1)],
        ];

        $kelas = Kelas::first();

        foreach ($sampleStudents as $sData) {
            $m = Mahasiswa::firstOrCreate(
                ['nim' => $sData['nim']],
                [
                    'nama' => $sData['nama'],
                    'prodi_id' => $sData['prodi_id'],
                    'kelas_id' => $kelas?->id,
                    'semester' => 3,
                    'angkatan' => '2022',
                    'is_active' => true,
                ]
            );

            // Buat KRS jika belum ada
            Krs::firstOrCreate(
                [
                    'mahasiswa_id' => $m->id,
                    'semester' => 3,
                    'tahun_akademik' => '2026/2027',
                ],
                [
                    'status' => 'Disetujui',
                    'semester_akademik' => 'Ganjil',
                    'prodi_id' => $m->prodi_id,
                    'kelas_id' => $kelas?->id,
                    'is_manual' => false,
                ]
            );
        }

        $allKrs = Krs::where('status', 'Disetujui')->get();
        if ($allKrs->isEmpty()) {
            $allKrs = Krs::all();
        }

        $today = Carbon::today();
        $createdCount = 0;

        // Ambil hari-hari kerja dalam 30 hari terakhir termasuk hari ini
        $workDays = [];
        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            if (! $date->isWeekend()) {
                $workDays[] = $date;
            }
        }

        // Ambil maksimal 16 hari untuk pertemuan 1..16, memastikan hari ini termasuk jika hari kerja
        $sliceDays = array_slice($workDays, -16);

        foreach ($allKrs as $krsItem) {
            $existingPertemuans = Presensi::where('krs_id', $krsItem->id)->pluck('pertemuan')->toArray();

            $meetingNum = 1;
            foreach ($sliceDays as $date) {
                while (in_array($meetingNum, $existingPertemuans, true) && $meetingNum <= 16) {
                    $meetingNum++;
                }

                if ($meetingNum > 16) {
                    break;
                }

                // Cek jika sudah ada tanggal ini
                $existsDate = Presensi::where('krs_id', $krsItem->id)
                    ->whereDate('tanggal', $date->toDateString())
                    ->exists();

                if ($existsDate) {
                    continue;
                }

                $rand = rand(1, 100);
                if ($rand <= 82) {
                    $status = 'Hadir';
                    $keterangan = ($rand % 10 === 0) ? 'Hadir tepat waktu' : null;
                } elseif ($rand <= 90) {
                    $status = 'Izin';
                    $keterangan = 'Ada keperluan keluarga / dinas';
                } elseif ($rand <= 96) {
                    $status = 'Sakit';
                    $keterangan = 'Surat dokter terlampir';
                } else {
                    $status = 'Alpha';
                    $keterangan = 'Tanpa pemberitahuan';
                }

                Presensi::create([
                    'krs_id' => $krsItem->id,
                    'tanggal' => $date->toDateString(),
                    'pertemuan' => $meetingNum,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'is_manual' => false,
                    'manual_identity' => Str::random(32),
                ]);

                $existingPertemuans[] = $meetingNum;
                $meetingNum++;
                $createdCount++;
            }
        }

        $this->command?->info("Berhasil membuat {$createdCount} catatan absensi sampel!");
    }
}
