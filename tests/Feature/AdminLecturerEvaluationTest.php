<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;

function buatDataEvaluasiAdmin(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Evaluasi']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TI-EVAL',
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);
    $dosenDinilai = Dosen::create([
        'nidn' => 'EVAL-001',
        'nama' => 'Dosen Sudah Dinilai',
        'prodi_id' => $prodi->id,
    ]);
    $dosenBelum = Dosen::create([
        'nidn' => 'EVAL-002',
        'nama' => 'Dosen Tanpa Evaluasi',
        'prodi_id' => $prodi->id,
    ]);
    $kelas = Kelas::create([
        'nama_kelas' => 'TI Reguler A',
        'prodi_id' => $prodi->id,
        'angkatan' => '2026',
        'semester' => 1,
    ]);
    $mataKuliah = MataKuliah::create([
        'kode_mk' => 'EVAL101',
        'nama_mk' => 'Mata Kuliah Evaluasi',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $jadwal = Jadwal::create([
        'mata_kuliah_id' => $mataKuliah->id,
        'dosen_id' => $dosenDinilai->id,
        'kelas_id' => $kelas->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $mahasiswa = Mahasiswa::create([
        'nim' => 'EVAL-MHS-001',
        'nama' => 'Identitas Mahasiswa Rahasia',
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
    ]);
    $krs = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $jawaban = collect(array_keys(Kuesioner::PERTANYAAN))
        ->mapWithKeys(fn (string $kolom) => [$kolom => 4])
        ->all();
    $kuesioner = $krs->kuesioner()->create([
        ...$jawaban,
        'komentar' => 'Penyampaian materi sangat terstruktur.',
        'submitted_at' => now(),
    ]);

    return compact('admin', 'prodi', 'dosenDinilai', 'dosenBelum', 'mataKuliah', 'kelas', 'mahasiswa', 'kuesioner');
}

test('admin evaluation dashboard lists evaluated and unevaluated lecturers', function () {
    ['admin' => $admin] = buatDataEvaluasiAdmin();

    $this->actingAs($admin)
        ->get(route('admin.kuesioner'))
        ->assertOk()
        ->assertSee('Dosen Sudah Dinilai')
        ->assertSee('Dosen Tanpa Evaluasi')
        ->assertSee('Total Dosen')
        ->assertSee('Sudah Dinilai')
        ->assertSee('Belum Dinilai')
        ->assertSee('Total Responden')
        ->assertDontSee('Identitas Mahasiswa Rahasia')
        ->assertDontSee('EVAL-MHS-001');
});

test('admin can filter lecturer evaluation status and academic context', function () {
    ['admin' => $admin, 'mataKuliah' => $mataKuliah] = buatDataEvaluasiAdmin();

    $sudah = $this->actingAs($admin)
        ->get(route('admin.kuesioner', [
            'status_evaluasi' => 'sudah',
            'mata_kuliah_id' => $mataKuliah->id,
            'semester_akademik' => 'Ganjil',
            'tahun_akademik' => '2026/2027',
        ]));
    $sudah->assertOk()->assertSee('1 dosen');
    expect(substr_count($sudah->getContent(), 'Dosen Sudah Dinilai'))->toBe(2)
        ->and(substr_count($sudah->getContent(), 'Dosen Tanpa Evaluasi'))->toBe(1);

    $belum = $this->actingAs($admin)
        ->get(route('admin.kuesioner', [
            'status_evaluasi' => 'belum',
            'tahun_akademik' => '2026/2027',
        ]));
    $belum->assertOk()->assertSee('1 dosen');
    expect(substr_count($belum->getContent(), 'Dosen Tanpa Evaluasi'))->toBe(2)
        ->and(substr_count($belum->getContent(), 'Dosen Sudah Dinilai'))->toBe(1);
});

test('evaluation detail shows aggregates and anonymous comments without student identity', function () {
    [
        'admin' => $admin,
        'dosenDinilai' => $dosen,
    ] = buatDataEvaluasiAdmin();

    $this->actingAs($admin)
        ->get(route('admin.kuesioner.dosen', [
            'dosen' => $dosen,
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]))
        ->assertOk()
        ->assertSee('Rata-rata per Pertanyaan')
        ->assertSee('4.00')
        ->assertSee('Penyampaian materi sangat terstruktur.')
        ->assertSee('Mata Kuliah Evaluasi')
        ->assertSee('TI Reguler A')
        ->assertDontSee('Identitas Mahasiswa Rahasia')
        ->assertDontSee('EVAL-MHS-001');
});

test('unevaluated lecturer detail has a clear empty state', function () {
    ['admin' => $admin, 'dosenBelum' => $dosen] = buatDataEvaluasiAdmin();

    $this->actingAs($admin)
        ->get(route('admin.kuesioner.dosen', $dosen))
        ->assertOk()
        ->assertSee('Belum ada evaluasi untuk dosen ini.');
});

test('manual academic records use their direct lecturer and course in evaluation recap', function () {
    [
        'admin' => $admin,
        'dosenBelum' => $dosen,
        'mataKuliah' => $mataKuliah,
        'prodi' => $prodi,
        'kelas' => $kelas,
        'mahasiswa' => $mahasiswa,
    ] = buatDataEvaluasiAdmin();

    $krsManual = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'jadwal_id' => null,
        'mata_kuliah_id' => $mataKuliah->id,
        'dosen_id' => $dosen->id,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'is_manual' => true,
        'manual_identity' => 'evaluation-manual-test',
    ]);
    $jawaban = collect(array_keys(Kuesioner::PERTANYAAN))
        ->mapWithKeys(fn (string $kolom) => [$kolom => 5])
        ->all();
    $krsManual->kuesioner()->create([
        ...$jawaban,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.kuesioner', [
        'mata_kuliah_id' => $mataKuliah->id,
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'status_evaluasi' => 'sudah',
    ]));

    $response->assertOk()->assertSee('5.00');
    expect(substr_count($response->getContent(), 'Dosen Tanpa Evaluasi'))->toBe(2);
});

test('evaluation pages reject non admin users', function () {
    ['dosenDinilai' => $dosen] = buatDataEvaluasiAdmin();
    $mahasiswaUser = User::factory()->create(['role' => 'mahasiswa']);

    $this->actingAs($mahasiswaUser)->get(route('admin.kuesioner'))->assertForbidden();
    $this->actingAs($mahasiswaUser)->get(route('admin.kuesioner.dosen', $dosen))->assertForbidden();
});

test('evaluation pagination keeps filters and starts page two at row eleven', function () {
    ['admin' => $admin, 'prodi' => $prodi] = buatDataEvaluasiAdmin();

    foreach (range(3, 12) as $number) {
        Dosen::create([
            'nidn' => sprintf('EVAL-%03d', $number),
            'nama' => sprintf('Dosen Evaluasi %02d', $number),
            'prodi_id' => $prodi->id,
        ]);
    }

    $firstPage = $this->actingAs($admin)->get(route('admin.kuesioner', ['status_evaluasi' => 'belum']));
    $firstPage->assertOk()->assertSee('status_evaluasi=belum', false)->assertSee('page=2', false);

    $this->actingAs($admin)
        ->get(route('admin.kuesioner', ['status_evaluasi' => 'belum', 'page' => 2]))
        ->assertOk()
        ->assertSee('Menampilkan 11&ndash;11', false)
        ->assertSee('>11<', false);
});

test('external return url is not used by lecturer evaluation detail', function () {
    ['admin' => $admin, 'dosenDinilai' => $dosen] = buatDataEvaluasiAdmin();

    $this->actingAs($admin)
        ->get(route('admin.kuesioner.dosen', ['dosen' => $dosen, 'return_url' => 'https://evil.example/admin/kuesioner']))
        ->assertOk()
        ->assertSee('href="'.route('admin.kuesioner').'"', false)
        ->assertDontSee('evil.example');
});
