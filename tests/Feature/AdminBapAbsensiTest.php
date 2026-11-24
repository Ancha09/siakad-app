<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\PresensiPertemuan;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function createBapFixture(int $studentCount = 65, string $kodeMk = 'GL/301', string $namaMk = 'Geologi Struktur'): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    $prodi = Prodi::create([
        'kode_prodi' => 'TG',
        'nama_prodi' => 'Teknik Geologi',
        'jenjang' => 'S1',
        'ketua_program_studi_nama' => 'Dr. Geologi, M.T.',
        'ketua_program_studi_nip' => '198001012005011001',
    ]);

    $dosenUser = User::factory()->create(['role' => 'dosen']);
    $dosen = Dosen::create([
        'nidn' => 'DOS-GEO-' . uniqid(),
        'nama' => 'Dr. Dosen Geologi, M.T.',
        'user_id' => $dosenUser->id,
        'prodi_id' => $prodi->id,
        'is_active' => true,
    ]);

    $kelasA = Kelas::create([
        'nama_kelas' => 'A',
        'prodi_id' => $prodi->id,
        'angkatan' => '2026',
        'semester' => 3,
    ]);
    $kelasB = Kelas::create([
        'nama_kelas' => 'B',
        'prodi_id' => $prodi->id,
        'angkatan' => '2026',
        'semester' => 3,
    ]);

    $ruangan = Ruangan::create([
        'kode_ruangan' => 'LAB-GEO',
        'nama_ruangan' => 'Laboratorium Geologi Struktur',
        'kapasitas' => 80,
    ]);

    $mk = MataKuliah::create([
        'kode_mk' => $kodeMk,
        'nama_mk' => $namaMk,
        'sks' => 3,
        'semester' => 3,
        'prodi_id' => $prodi->id,
    ]);

    $jadwal = Jadwal::create([
        'mata_kuliah_id' => $mk->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $ruangan->id,
        'kelas_id' => $kelasA->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:30:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    // 16 Pertemuan
    for ($p = 1; $p <= 16; $p++) {
        PresensiPertemuan::create([
            'jadwal_id' => $jadwal->id,
            'pertemuan' => $p,
            'tanggal' => sprintf('2026-09-%02d', min(30, $p)),
            'materi_kuliah' => 'Materi Kuliah Pertemuan ' . $p,
            'keterangan' => 'Kuliah Selesai',
            'foto' => 'presensi/foto/pertemuan_' . $p . '.jpg',
        ]);
    }

    // Mahasiswa & KRS Kelas Besar
    $krsList = [];
    for ($i = 1; $i <= $studentCount; $i++) {
        $mhs = Mahasiswa::create([
            'nim' => sprintf('202610%03d', $i),
            'nama' => 'Mahasiswa Geologi ' . $i,
            'prodi_id' => $prodi->id,
            'kelas_id' => $kelasA->id,
            'is_active' => true,
        ]);

        $krs = Krs::create([
            'mahasiswa_id' => $mhs->id,
            'jadwal_id' => $jadwal->id,
            'status' => 'Disetujui',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'kelas_id' => $kelasA->id,
            'is_manual' => false,
        ]);

        $krsList[] = $krs;
    }

    // Input Presensi untuk semua mahasiswa x 16 pertemuan
    $presensiRecords = [];
    $statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Izin', 'Sakit', 'Alpha'];
    foreach ($krsList as $krs) {
        for ($p = 1; $p <= 16; $p++) {
            $status = $statuses[($krs->id + $p) % count($statuses)];
            $presensiRecords[] = [
                'krs_id' => $krs->id,
                'pertemuan' => $p,
                'tanggal' => sprintf('2026-09-%02d', min(30, $p)),
                'status' => $status,
                'keterangan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }
    // Bulk insert untuk kecepatan setup test
    foreach (array_chunk($presensiRecords, 500) as $chunk) {
        Presensi::insert($chunk);
    }

    return compact('admin', 'dosenUser', 'dosen', 'mk', 'jadwal', 'kelasA', 'kelasB', 'prodi');
}

test('admin can download BAP absensi for large class with bounded query count and valid PDF', function () {
    // 65 mahasiswa x 16 pertemuan = 1,040 presensi
    $fixture = createBapFixture(65, 'GL/301', 'Geologi Struktur');

    DB::enableQueryLog();

    $response = $this->actingAs($fixture['admin'])
        ->get(route('admin.presensi.bap-pdf', $fixture['jadwal']->id));

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Pastikan respon 200 dan content-type valid PDF
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    // Pastikan query efisien dan tidak terjadi N+1 (sebelumnya ada 64+ query hanya untuk count)
    // Sekarang query log total harus <= 20 query meskipun ada 65 mahasiswa dan 16 pertemuan
    expect(count($queries))->toBeLessThanOrEqual(20);

    // Header Content-Disposition harus attachment dan berisi nama file PDF yang valid
    $disposition = $response->headers->get('content-disposition');
    expect($disposition)->toContain('attachment');
    expect($disposition)->toContain('BAP_GL-301_geologi-struktur');
});

test('attendance data from other class or semester is not mixed into BAP', function () {
    $fixture = createBapFixture(10, 'TNK-201', 'Mekanika Teknik');
    $jadwalUtama = $fixture['jadwal'];

    // Buat Jadwal Lain di Kelas B / Semester Lain untuk mata kuliah yang sama
    $jadwalLain = Jadwal::create([
        'mata_kuliah_id' => $fixture['mk']->id,
        'dosen_id' => $fixture['dosen']->id,
        'kelas_id' => $fixture['kelasB']->id,
        'hari' => 'Rabu',
        'jam_mulai' => '13:00:00',
        'jam_selesai' => '15:30:00',
        'tahun_akademik' => '2025/2026', // Semester berbeda
        'semester_akademik' => 'Genap',
    ]);

    $mhsLain = Mahasiswa::create([
        'nim' => '99999999',
        'nama' => 'Mahasiswa Kelas Lain',
        'prodi_id' => $fixture['prodi']->id,
        'kelas_id' => $fixture['kelasB']->id,
        'is_active' => true,
    ]);

    $krsLain = Krs::create([
        'mahasiswa_id' => $mhsLain->id,
        'jadwal_id' => $jadwalLain->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'kelas_id' => $fixture['kelasB']->id,
        'is_manual' => false,
    ]);

    Presensi::create([
        'krs_id' => $krsLain->id,
        'pertemuan' => 1,
        'tanggal' => '2026-03-01',
        'status' => 'Hadir',
    ]);

    $response = $this->actingAs($fixture['admin'])
        ->get(route('admin.presensi.bap-pdf', $jadwalUtama->id));

    $response->assertOk();

    // Cek service data langsung untuk memastikan hitungan peserta murni 10 mahasiswa
    $service = app(\App\Services\BapPresensiService::class);
    $bapData = $service->prepareBapData($jadwalUtama);

    expect($bapData['totalPeserta'])->toBe(10);
    expect($bapData['krs']->pluck('mahasiswa_id'))->not->toContain($mhsLain->id);
});

test('download succeeds when image files are missing or course has special characters and CRLF', function () {
    // Kode MK mengandung slash, backslash, dan kontrol karakter CRLF
    $fixture = createBapFixture(5, "GL/301\\MT\r\n", 'Mekanika & Geologi: Teknik (A/B)');

    // Pastikan foto-foto pertemuan mengarah ke path yang tidak ada di storage
    // dan tidak ada kop/logo khusus
    $response = $this->actingAs($fixture['admin'])
        ->get(route('admin.presensi.bap-pdf', $fixture['jadwal']->id));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    // Header filename harus tersanitasi dari /, \, \r, \n
    $disposition = $response->headers->get('content-disposition');
    expect($disposition)->not->toContain("\r");
    expect($disposition)->not->toContain("\n");
    expect($disposition)->not->toMatch('/filename="[^"]*[\/\\\\]/');
    expect($disposition)->toContain('BAP_GL-301-MT_mekanika-geologi-teknik-ab');
});

test('unauthorized user without admin or assigned lecturer role is denied with 403', function () {
    $fixture = createBapFixture(2, 'GL-101', 'Geologi Dasar');

    $studentUser = User::factory()->create(['role' => 'mahasiswa']);

    // Mahasiswa mengakses endpoint admin BAP -> Ditolak 403
    $responseAdmin = $this->actingAs($studentUser)
        ->get(route('admin.presensi.bap-pdf', $fixture['jadwal']->id));
    $responseAdmin->assertForbidden();

    // Mahasiswa mengakses endpoint dosen BAP -> Ditolak 403
    $responseDosen = $this->actingAs($studentUser)
        ->get(route('dosen.presensi.bap-pdf', $fixture['jadwal']->id));
    $responseDosen->assertForbidden();

    // Dosen lain yang BUKAN pengampu jadwal ini mengakses endpoint dosen BAP -> Ditolak 403
    $otherDosenUser = User::factory()->create(['role' => 'dosen']);
    Dosen::create([
        'nidn' => 'DOS-OTHER-' . uniqid(),
        'nama' => 'Dosen Lain',
        'user_id' => $otherDosenUser->id,
        'is_active' => true,
    ]);

    $responseOtherDosen = $this->actingAs($otherDosenUser)
        ->get(route('dosen.presensi.bap-pdf', $fixture['jadwal']->id));
    $responseOtherDosen->assertForbidden();
});
