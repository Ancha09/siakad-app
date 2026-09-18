<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Khs;
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
        'submitted_at' => '2020-01-02 12:34:00',
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
        'kuesioner' => $kuesioner,
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
        ->assertDontSee($kuesioner->submitted_at->format('d M Y'))
        ->assertDontSee('Identitas Mahasiswa Rahasia')
        ->assertDontSee('EVAL-MHS-001');
});

test('evaluation overview defaults to one latest academic semester', function () {
    $data = buatDataEvaluasiAdmin();
    $mahasiswa = Mahasiswa::create([
        'nim' => 'EVAL-MHS-LATEST',
        'nama' => 'Responden Semester Terbaru',
        'prodi_id' => $data['prodi']->id,
        'kelas_id' => $data['kelas']->id,
    ]);
    $jadwal = Jadwal::where('mata_kuliah_id', $data['mataKuliah']->id)->firstOrFail();
    $krs = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Genap',
    ]);
    $answers = collect(array_keys(Kuesioner::PERTANYAAN))
        ->mapWithKeys(fn (string $column) => [$column => 2])
        ->all();
    $krs->kuesioner()->create([
        ...$answers,
        'dosen_id' => $data['dosenDinilai']->id,
        'mata_kuliah_id' => $data['mataKuliah']->id,
        'kelas_id' => $data['kelas']->id,
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Genap',
        'komentar' => 'Komentar khusus semester terbaru.',
        'submitted_at' => now(),
    ]);

    $latest = $this->actingAs($data['admin'])->get(route('admin.kuesioner'));
    $latest->assertOk()
        ->assertSee('2027/2028')
        ->assertSee('Genap')
        ->assertDontSee('Penyampaian materi sangat terstruktur.');
    $latestRow = $latest->viewData('evaluasiDosen')->getCollection()
        ->first(fn ($item) => $item->dosen->is($data['dosenDinilai']));
    expect($latestRow->jumlah_responden)->toBe(1)
        ->and($latestRow->rata_rata)->toBe(2.0)
        ->and($latest->viewData('activeFilters')['tahun_akademik'])->toBe('2027/2028')
        ->and($latest->viewData('activeFilters')['semester_akademik'])->toBe('Genap');

    $older = $this->get(route('admin.kuesioner', [
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]));
    $olderRow = $older->viewData('evaluasiDosen')->getCollection()
        ->first(fn ($item) => $item->dosen->is($data['dosenDinilai']));
    expect($olderRow->jumlah_responden)->toBe(1)
        ->and($olderRow->rata_rata)->toBe(4.0);
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

test('manual grade lecturer override maps Bahasa Inggris evaluation to Tessa instead of Affan', function () {
    $data = buatDataEvaluasiAdmin();
    $informatika = Prodi::create([
        'kode_prodi' => 'IF-EVAL',
        'nama_prodi' => 'Informatika',
        'jenjang' => 'S1',
    ]);
    $userTessa = User::factory()->create(['role' => 'dosen']);
    $userAffan = User::factory()->create(['role' => 'dosen']);
    $tessa = Dosen::create([
        'nidn' => 'TESSA-001', 'nama' => 'Tessa', 'prodi_id' => $informatika->id, 'user_id' => $userTessa->id,
    ]);
    $affan = Dosen::create([
        'nidn' => 'AFFAN-001', 'nama' => 'Affan', 'prodi_id' => $data['prodi']->id, 'user_id' => $userAffan->id,
    ]);
    $english = MataKuliah::create([
        'kode_mk' => 'ENG101', 'nama_mk' => 'Bahasa Inggris', 'sks' => 2, 'semester' => 1, 'prodi_id' => $data['prodi']->id,
    ]);
    $oldSchedule = Jadwal::create([
        'mata_kuliah_id' => $english->id,
        'dosen_id' => $affan->id,
        'kelas_id' => $data['kelas']->id,
        'hari' => 'Selasa',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
    ]);
    $krs = Krs::create([
        'mahasiswa_id' => $data['mahasiswa']->id,
        'jadwal_id' => $oldSchedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
    ]);
    Khs::create([
        'krs_id' => $krs->id,
        'nilai_angka' => 88,
        'nilai_huruf' => 'A',
        'bobot' => 4,
        'sks' => 2,
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
        'is_manual' => true,
        'dosen_id' => $tessa->id,
        'dosen_override' => true,
    ]);
    $answers = collect(array_keys(Kuesioner::PERTANYAAN))->mapWithKeys(fn ($column) => [$column => 5])->all();
    $krs->kuesioner()->create([
        ...$answers,
        'dosen_id' => null, // Simulasi evaluasi lama sebelum dosen_id disimpan langsung.
        'komentar' => 'Evaluasi Bahasa Inggris milik Tessa.',
        'submitted_at' => now(),
    ]);

    $overview = $this->actingAs($data['admin'])->get(route('admin.kuesioner', [
        'mata_kuliah_id' => $english->id,
        'tahun_akademik' => '2024/2025',
        'status_evaluasi' => 'sudah',
    ]));
    $overview->assertOk()->assertSee('Bahasa Inggris');
    expect(substr_count($overview->getContent(), '>Tessa<'))->toBe(1)
        ->and(substr_count($overview->getContent(), '>Affan<'))->toBe(0);

    $this->get(route('admin.kuesioner.dosen', ['dosen' => $tessa, 'mata_kuliah_id' => $english->id]))
        ->assertOk()
        ->assertSee('Evaluasi Bahasa Inggris milik Tessa.');
    $this->get(route('admin.kuesioner.dosen', ['dosen' => $affan, 'mata_kuliah_id' => $english->id]))
        ->assertOk()
        ->assertSee('Belum ada evaluasi untuk dosen ini.')
        ->assertDontSee('Evaluasi Bahasa Inggris milik Tessa.');

    $this->actingAs($userTessa)->get(route('dosen.evaluasi'))
        ->assertOk()
        ->assertSee('Evaluasi Bahasa Inggris milik Tessa.');
    $this->actingAs($userAffan)->get(route('dosen.evaluasi'))
        ->assertOk()
        ->assertSee('Belum ada evaluasi pada filter ini.')
        ->assertDontSee('Evaluasi Bahasa Inggris milik Tessa.');
});

test('questionnaire submission snapshots lecturer from manual grade override', function () {
    $data = buatDataEvaluasiAdmin();
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $data['mahasiswa']->update(['user_id' => $studentUser->id]);
    $tessa = Dosen::create(['nidn' => 'TESSA-NEW', 'nama' => 'Tessa Baru', 'prodi_id' => $data['prodi']->id]);
    $affan = Dosen::create(['nidn' => 'AFFAN-OLD', 'nama' => 'Affan Lama', 'prodi_id' => $data['prodi']->id]);
    $english = MataKuliah::create([
        'kode_mk' => 'ENG102', 'nama_mk' => 'Bahasa Inggris Lanjutan', 'sks' => 2, 'semester' => 2, 'prodi_id' => $data['prodi']->id,
    ]);
    $schedule = Jadwal::create([
        'mata_kuliah_id' => $english->id, 'dosen_id' => $affan->id, 'kelas_id' => $data['kelas']->id,
        'hari' => 'Rabu', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap',
    ]);
    $krs = Krs::create([
        'mahasiswa_id' => $data['mahasiswa']->id, 'jadwal_id' => $schedule->id, 'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap',
    ]);
    Khs::create([
        'krs_id' => $krs->id, 'nilai_angka' => 90, 'nilai_huruf' => 'A', 'bobot' => 4, 'sks' => 2,
        'tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap', 'is_manual' => true,
        'dosen_id' => $tessa->id, 'dosen_override' => true,
    ]);
    $answers = collect(array_keys(Kuesioner::PERTANYAAN))->mapWithKeys(fn ($column) => [$column => 4])->all();
    $answers['komentar'] = 'Penyampaian materi sudah jelas.';

    $this->actingAs($studentUser)
        ->post(route('mahasiswa.kuesioner.store', $krs), $answers)
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('kuesioners', [
        'krs_id' => $krs->id,
        'dosen_id' => $tessa->id,
        'mata_kuliah_id' => $english->id,
        'kelas_id' => $data['kelas']->id,
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
    ]);
    $this->assertDatabaseMissing('kuesioners', ['krs_id' => $krs->id, 'dosen_id' => $affan->id]);

    $khs = Khs::where('krs_id', $krs->id)->firstOrFail();
    $this->actingAs($data['admin'])
        ->put(route('admin.nilai-manual.update', $khs), [
            'mahasiswa_id' => $data['mahasiswa']->id,
            'mata_kuliah_id' => $english->id,
            'dosen_id' => $affan->id,
            'jadwal_id' => $schedule->id,
            'prodi_id' => $data['prodi']->id,
            'kelas_id' => $data['kelas']->id,
            'tahun_akademik' => '2025/2026',
            'semester_akademik' => 'Genap',
            'nilai_angka' => 90,
            'nilai_huruf' => 'A',
            'sks' => 2,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('kuesioners', [
        'krs_id' => $krs->id,
        'dosen_id' => $affan->id,
        'mata_kuliah_id' => $english->id,
        'kelas_id' => $data['kelas']->id,
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
    ]);
});

test('new respondents immediately change lecturer response count and average', function () {
    $data = buatDataEvaluasiAdmin();
    $first = $this->actingAs($data['admin'])->get(route('admin.kuesioner'));
    $first->assertOk();
    $firstRow = $first->viewData('evaluasiDosen')->getCollection()
        ->first(fn ($item) => $item->dosen->is($data['dosenDinilai']));
    expect($firstRow->jumlah_responden)->toBe(1)
        ->and($firstRow->rata_rata)->toBe(4.0);

    $mahasiswa = Mahasiswa::create([
        'nim' => 'EVAL-MHS-002',
        'nama' => 'Responden Kedua',
        'prodi_id' => $data['prodi']->id,
        'kelas_id' => $data['kelas']->id,
    ]);
    $jadwal = Jadwal::where('mata_kuliah_id', $data['mataKuliah']->id)->firstOrFail();
    $krs = Krs::create([
        'mahasiswa_id' => $mahasiswa->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $answers = collect(array_keys(Kuesioner::PERTANYAAN))
        ->mapWithKeys(fn (string $column) => [$column => 2])
        ->all();
    $krs->kuesioner()->create([
        ...$answers,
        'dosen_id' => $data['dosenDinilai']->id,
        'mata_kuliah_id' => $data['mataKuliah']->id,
        'kelas_id' => $data['kelas']->id,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'submitted_at' => now(),
    ]);

    $updated = $this->actingAs($data['admin'])->get(route('admin.kuesioner'));
    $updated->assertOk();
    $updatedRow = $updated->viewData('evaluasiDosen')->getCollection()
        ->first(fn ($item) => $item->dosen->is($data['dosenDinilai']));
    expect($updatedRow->jumlah_responden)->toBe(2)
        ->and($updatedRow->rata_rata)->toBe(3.0);
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
