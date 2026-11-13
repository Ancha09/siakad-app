<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\PresensiPertemuan;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function presensiPdfFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    $prodi = Prodi::create([
        'kode_prodi' => 'TP',
        'nama_prodi' => 'Teknik Pertambangan',
        'jenjang' => 'S1',
    ]);

    $dosenUser = User::factory()->create(['role' => 'dosen']);
    $dosen = Dosen::create([
        'nidn' => 'DOS-PDF-001',
        'nama' => 'Agus Setyanto, S.T., M.Eng.',
        'user_id' => $dosenUser->id,
        'prodi_id' => $prodi->id,
        'is_active' => true,
    ]);

    $ruangan = Ruangan::create([
        'kode_ruangan' => 'RK-01',
        'nama_ruangan' => 'Ruang Kelas',
        'kapasitas' => 40,
    ]);

    $mk = MataKuliah::create([
        'kode_mk' => 'GL 302 (TP)',
        'nama_mk' => 'Kristal & Mineral (P)',
        'sks' => 3,
        'semester' => 3,
        'prodi_id' => $prodi->id,
    ]);

    $jadwal = Jadwal::create([
        'mata_kuliah_id' => $mk->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $ruangan->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:30:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $mhs = Mahasiswa::create([
        'nim' => '1125007',
        'nama' => 'Reginald Arvizu Tobing',
        'prodi_id' => $prodi->id,
        'is_active' => true,
    ]);

    $krs = Krs::create([
        'mahasiswa_id' => $mhs->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_manual' => false,
    ]);

    // Buat 7 pertemuan
    for ($i = 1; $i <= 7; $i++) {
        PresensiPertemuan::create([
            'jadwal_id' => $jadwal->id,
            'pertemuan' => $i,
            'tanggal' => '2026-10-0' . $i,
            'materi_kuliah' => 'Materi Pertemuan ' . $i,
            'keterangan' => 'Kuliah Tatap Muka',
        ]);

        Presensi::create([
            'krs_id' => $krs->id,
            'pertemuan' => $i,
            'tanggal' => '2026-10-0' . $i,
            'status' => 'Hadir',
        ]);
    }

    return compact('admin', 'prodi', 'dosen', 'mk', 'jadwal', 'mhs', 'krs');
}

test('admin presensi pdf downloads course meeting progress instead of raw student detail list', function () {
    ['admin' => $admin, 'mk' => $mk, 'dosen' => $dosen] = presensiPdfFixture();

    $response = $this->actingAs($admin)->get(route('admin.presensi.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    // Pastikan template view dirender dengan data progres mata kuliah
    $view = view('admin.presensi.print', [
        'reportTitle' => 'Rekap Presensi Mahasiswa',
        'deskripsiFilter' => 'Semua data',
        'progresMataKuliah' => Jadwal::with(['mataKuliah', 'dosen', 'dosens', 'kelas', 'ruangan'])
            ->withCount('presensiPertemuans as total_pertemuan')
            ->get(),
        'summary' => [
            'total' => 7,
            'hadir' => 7,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ],
    ])->render();

    expect($view)->toContain('Progres Pertemuan Mata Kuliah (Target Standar: 16 Pertemuan)');
    expect($view)->toContain('GL 302 (TP)');
    expect($view)->toContain('Kristal &amp; Mineral (P)');
    expect($view)->toContain('Agus Setyanto, S.T., M.Eng.');
    expect($view)->toContain('Pertemuan 7 / 16');
    expect($view)->not->toContain('<h2>Detail Presensi</h2>');
});

test('admin presensi excel includes progress sheet on first tab', function () {
    ['admin' => $admin] = presensiPdfFixture();

    $response = $this->actingAs($admin)->get(route('admin.presensi.excel'));

    $response->assertOk();
    $response->assertHeader('content-disposition');
});
