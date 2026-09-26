<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use App\Services\MahasiswaNilaiService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function makePerStudentKrsAccessData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $prodi = Prodi::create([
        'kode_prodi' => 'IF-AKSES',
        'nama_prodi' => 'Informatika Akses KRS',
        'jenjang' => 'S1',
    ]);
    $dosen = Dosen::create([
        'nidn' => 'DOSEN-AKSES-01',
        'nama' => 'Dosen Akses KRS',
        'prodi_id' => $prodi->id,
        'user_id' => $lecturerUser->id,
    ]);
    $kelas = Kelas::create([
        'nama_kelas' => 'IF 2026 A',
        'prodi_id' => $prodi->id,
        'angkatan' => 2026,
        'semester' => 1,
        'dosen_wali_id' => $dosen->id,
    ]);
    $student = Mahasiswa::create([
        'nim' => '260001',
        'nama' => 'Mahasiswa Akses A',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $studentUser->id,
    ]);
    $otherStudent = Mahasiswa::create([
        'nim' => '260002',
        'nama' => 'Mahasiswa Akses B',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $otherStudentUser->id,
    ]);
    $course = MataKuliah::create([
        'kode_mk' => 'IF101-A',
        'nama_mk' => 'Algoritma Akses KRS',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'AKSES-1',
        'nama_ruangan' => 'Ruang Akses',
        'kapasitas' => 30,
    ]);
    $schedule = Jadwal::create([
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $room->id,
        'kelas_id' => $kelas->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $period = PeriodeKrs::create([
        'tahun_akademik' => '2026/2027',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
    ]);

    return compact(
        'admin',
        'studentUser',
        'otherStudentUser',
        'lecturerUser',
        'prodi',
        'dosen',
        'kelas',
        'student',
        'otherStudent',
        'course',
        'room',
        'schedule',
        'period'
    );
}

function makeAcademicParitySchedule(
    array $data,
    int $semester,
    string $academicSemester,
    ?int $programId = null
): Jadwal {
    $course = MataKuliah::create([
        'kode_mk' => 'PAR-'.$academicSemester.'-'.$semester,
        'nama_mk' => 'Mata Kuliah Semester '.$semester,
        'sks' => 2,
        'semester' => $semester,
        'prodi_id' => func_num_args() >= 4 ? $programId : $data['prodi']->id,
    ]);

    return Jadwal::create([
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $data['dosen']->id,
        'ruangan_id' => $data['room']->id,
        // Nilai kelas legacy dibuat berbeda/null untuk membuktikan filter tidak memakainya.
        'kelas_id' => $semester % 2 === 0 ? $data['kelas']->id : null,
        'hari' => 'Kamis',
        'jam_mulai' => sprintf('%02d:00:00', 7 + $semester),
        'jam_selesai' => sprintf('%02d:00:00', 8 + $semester),
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => $academicSemester,
    ]);
}

function seedKrsLimitHistory(array $data, float $ipk, array $currentSks): void
{
    $historicalCourse = MataKuliah::create([
        'kode_mk' => 'HIST-IPK-KRS',
        'nama_mk' => 'Nilai Historis untuk Batas KRS',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $data['prodi']->id,
    ]);
    $historicalKrs = Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'mata_kuliah_id' => $historicalCourse->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'is_manual' => true,
    ]);
    Khs::create([
        'krs_id' => $historicalKrs->id,
        'nilai_angka' => 80,
        'nilai_huruf' => 'B',
        'bobot' => $ipk,
        'sks' => 2,
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'is_manual' => true,
    ]);
    DB::table('kuesioners')->insert([
        'krs_id' => $historicalKrs->id,
        'penguasaan_materi' => 4,
        'kejelasan_penyampaian' => 4,
        'kesesuaian_rps' => 4,
        'ketepatan_waktu' => 4,
        'kesempatan_bertanya' => 4,
        'objektivitas_penilaian' => 4,
        'penggunaan_media' => 4,
        'motivasi_belajar' => 4,
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach ($currentSks as $index => $sks) {
        $course = MataKuliah::create([
            'kode_mk' => 'KRS-LIMIT-'.$index,
            'nama_mk' => 'Mata Kuliah Batas '.$index,
            'sks' => $sks,
            'semester' => 1,
            'prodi_id' => $data['prodi']->id,
        ]);
        $schedule = Jadwal::create([
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $data['dosen']->id,
            'ruangan_id' => $data['room']->id,
            'kelas_id' => $data['kelas']->id,
            'hari' => 'Selasa',
            'jam_mulai' => sprintf('%02d:00:00', 7 + $index),
            'jam_selesai' => sprintf('%02d:00:00', 8 + $index),
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]);
        Krs::create([
            'mahasiswa_id' => $data['student']->id,
            'jadwal_id' => $schedule->id,
            'status' => 'Disetujui',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_manual' => false,
        ]);
    }
}

test('students with IPK below 3.5 can reach 22 SKS when the period permits it', function (float $ipk) {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    seedKrsLimitHistory($data, $ipk, [4, 4, 4, 4, 3]);

    $this->assertSame($ipk, app(MahasiswaNilaiService::class)
        ->ringkasanMahasiswa($data['student']->id)['ipk_aktual']);

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertViewHas('batasSks', 22)
        ->assertViewHas('sisaSks', 3)
        ->assertSee(number_format($ipk, 2));

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
    $this->assertDatabaseHas('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'status' => 'Menunggu',
    ]);
    $this->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertViewHas('totalSks', 22)
        ->assertViewHas('batasSks', 22);
    $this->assertSame($ipk, app(MahasiswaNilaiService::class)
        ->ringkasanMahasiswa($data['student']->id)['ipk_aktual']);
})->with([3.14, 2.80]);

test('student KRS rejects 23 SKS even when period limit is higher', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    seedKrsLimitHistory($data, 3.14, [4, 4, 4, 4, 4]);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Mata kuliah tidak dapat diambil karena total SKS melebihi batas maksimal Anda, yaitu 22 SKS.');
    $this->assertDatabaseMissing('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
    ]);
});

test('student KRS respects a lower period limit of 20 SKS', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all', 'maksimal_sks' => 20]);
    seedKrsLimitHistory($data, 3.14, [4, 4, 4, 4, 2]);

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertViewHas('batasSks', 20)
        ->assertViewHas('sisaSks', 2);

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Mata kuliah tidak dapat diambil karena total SKS melebihi batas maksimal Anda, yaitu 20 SKS.');
    $this->assertDatabaseMissing('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
    ]);
});

test('admin opens and closes one student KRS access without affecting another student', function () {
    $data = makePerStudentKrsAccessData();
    $returnUrl = route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'search' => '260001',
        'status_akses' => 'belum_dibuka',
        'page' => 1,
    ]);

    $this->actingAs($data['admin'])
        ->get($returnUrl)
        ->assertOk()
        ->assertSee('Mahasiswa Akses A')
        ->assertDontSee('Mahasiswa Akses B')
        ->assertSee('Ditutup');

    $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
        'status_akses' => 1,
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'dibuka_oleh' => $data['admin']->id,
    ]);
    $this->assertDatabaseMissing('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['otherStudent']->id,
    ]);

    $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
        'status_akses' => 0,
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => false,
    ]);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 1);

    $this->get(route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'status_akses' => 'ditutup',
    ]))->assertOk()->assertSee('Mahasiswa Akses A')->assertSee('Mahasiswa Akses B');
});

test('student can submit KRS only when global period and personal access are both open', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Akses KRS Anda belum dibuka oleh admin.')
        ->assertDontSee('Algoritma Akses KRS');

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseCount('krs', 0);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Algoritma Akses KRS');
    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $this->actingAs($data['otherStudentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseMissing('krs', ['mahasiswa_id' => $data['otherStudent']->id]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 0]
    )->assertSessionHasNoErrors();
    $this->assertDatabaseCount('periode_krs_mahasiswas', 1);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseCount('krs', 1);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $data['period']->update(['status' => 'Ditutup']);
    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Pengisian KRS sedang ditutup.');
});

test('ended period rejects KRS even when personal access is open', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update([
        'tanggal_mulai' => now()->subDays(2),
        'tanggal_selesai' => now()->subDay(),
    ]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Periode KRS sudah berakhir.');
    $this->assertDatabaseCount('krs', 0);
});

test('closed mode rejects every student even when an old pivot is open', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'closed']);
    DB::table('periode_krs_mahasiswas')->insert([
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseCount('krs', 0);
});

test('all mode allows every active student without pivot rows', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);

    foreach ([$data['studentUser'], $data['otherStudentUser']] as $user) {
        $this->actingAs($user)
            ->get(route('mahasiswa.krs'))
            ->assertOk()
            ->assertSee('Algoritma Akses KRS');
    }
    $this->assertDatabaseCount('periode_krs_mahasiswas', 0);
});

test('all except mode rejects exclusions and allows students without an exception', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all_except']);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 0]
    )->assertSessionHasNoErrors();

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->actingAs($data['otherStudentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Algoritma Akses KRS');
});

test('future period reports that KRS has not started', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update([
        'access_mode' => 'all',
        'tanggal_mulai' => now()->addDay(),
        'tanggal_selesai' => now()->addDays(2),
    ]);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Periode KRS belum dimulai.');
});

test('an active period takes priority over a newer future period', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    PeriodeKrs::create([
        'tahun_akademik' => '2027/2028',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->addMonth(),
        'tanggal_selesai' => now()->addMonths(2),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
        'access_mode' => 'all',
    ]);

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Algoritma Akses KRS')
        ->assertDontSee('Periode KRS belum dimulai.');
});

test('period form stores selected access and renders searchable student controls', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['admin'])
        ->get(route('admin.periode-krs.create'))
        ->assertOk()
        ->assertSee('Pengaturan Akses Mahasiswa')
        ->assertSee('Cari nama/NIM')
        ->assertSee('Pilih Semua Hasil Filter');

    $this->post(route('admin.periode-krs.store'), [
        'tahun_akademik' => '2027/2028',
        'semester' => 'Genap',
        'tanggal_mulai' => now()->addMonth()->format('Y-m-d H:i:s'),
        'tanggal_selesai' => now()->addMonths(2)->format('Y-m-d H:i:s'),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
        'access_mode' => 'selected',
        'mahasiswa_ids' => [$data['student']->id],
    ])->assertSessionHasNoErrors();

    $period = PeriodeKrs::where('tahun_akademik', '2027/2028')->firstOrFail();
    expect($period->access_mode)->toBe('selected');
    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $period->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'dibuka_oleh' => $data['admin']->id,
    ]);
    $this->assertDatabaseMissing('periode_krs_mahasiswas', [
        'periode_krs_id' => $period->id,
        'mahasiswa_id' => $data['otherStudent']->id,
    ]);
});

test('admin can open and close KRS access in bulk without duplicate pivot rows', function () {
    $data = makePerStudentKrsAccessData();
    $studentIds = [$data['student']->id, $data['otherStudent']->id];
    $url = route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'angkatan' => 2026,
        'page' => 1,
    ]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access-bulk', $data['period']),
        [
            'mahasiswa_ids' => $studentIds,
            'status_akses' => 0,
            'return_url' => $url,
        ]
    )->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 0);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access-bulk', $data['period']),
        [
            'mahasiswa_ids' => $studentIds,
            'status_akses' => 1,
            'return_url' => $url,
        ]
    )->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 2);
    foreach ($studentIds as $studentId) {
        $this->assertDatabaseHas('periode_krs_mahasiswas', [
            'periode_krs_id' => $data['period']->id,
            'mahasiswa_id' => $studentId,
            'status_akses' => true,
        ]);
    }

    $this->patch(route('admin.periode-krs.students.access-bulk', $data['period']), [
        'mahasiswa_ids' => $studentIds,
        'status_akses' => 0,
        'return_url' => $url,
    ])->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 2);
    foreach ($studentIds as $studentId) {
        $this->assertDatabaseHas('periode_krs_mahasiswas', [
            'periode_krs_id' => $data['period']->id,
            'mahasiswa_id' => $studentId,
            'status_akses' => false,
        ]);
    }
});

test('KRS pages never query a payment table', function () {
    $data = makePerStudentKrsAccessData();
    expect(Schema::hasTable('pembayaran_krs'))->toBeFalse();

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = strtolower($query->sql);
    });

    $this->actingAs($data['admin'])
        ->get(route('admin.periode-krs.students', $data['period']))
        ->assertOk();
    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk();

    expect(collect($queries)->contains(
        fn (string $sql) => str_contains($sql, 'pembayaran_krs')
    ))->toBeFalse();
});

test('student sees and can take a cross program course assigned to their curriculum without class matching', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);

    $otherProgram = Prodi::create([
        'kode_prodi' => 'MKU-UMUM',
        'nama_prodi' => 'Program Pengelola MKU',
        'jenjang' => 'S1',
    ]);
    $generalCourse = MataKuliah::create([
        'kode_mk' => 'MKU101',
        'nama_mk' => 'Bahasa Inggris Lintas Prodi',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $otherProgram->id,
    ]);
    $curriculum = Kurikulum::create([
        'prodi_id' => $data['prodi']->id,
        'nama_kurikulum' => 'Kurikulum Informatika 2026',
        'tahun_mulai' => 2026,
        'status' => 'Aktif',
    ]);
    $curriculum->mataKuliahs()->attach($generalCourse->id, [
        'semester' => 1,
        'jenis' => 'Wajib',
    ]);
    $oppositeParityCourse = MataKuliah::create([
        'kode_mk' => 'MKU102',
        'nama_mk' => 'MKU Genap pada Periode Ganjil',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $otherProgram->id,
    ]);
    $curriculum->mataKuliahs()->attach($oppositeParityCourse->id, [
        'semester' => 2,
        'jenis' => 'Wajib',
    ]);
    $generalSchedule = Jadwal::create([
        'mata_kuliah_id' => $generalCourse->id,
        'dosen_id' => null,
        'ruangan_id' => null,
        'kelas_id' => null,
        'hari' => 'Selasa',
        'jam_mulai' => '10:00:00',
        'jam_selesai' => '12:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $oppositeParitySchedule = Jadwal::create([
        'mata_kuliah_id' => $oppositeParityCourse->id,
        'dosen_id' => null,
        'ruangan_id' => null,
        'kelas_id' => $data['kelas']->id,
        'hari' => 'Rabu',
        'jam_mulai' => '10:00:00',
        'jam_selesai' => '12:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    $response->assertOk()->assertSee('Bahasa Inggris Lintas Prodi');
    expect($response->viewData('jadwals')->pluck('id'))
        ->toContain($generalSchedule->id)
        ->not->toContain($oppositeParitySchedule->id);

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $generalSchedule->id])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $generalSchedule->id,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
});

test('odd KRS period shows every available odd course semester regardless of current student semester', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all', 'semester' => 'Ganjil']);
    $data['student']->update(['semester' => 5, 'kelas_id' => null]);

    $schedules = collect();
    foreach (range(2, 8) as $semester) {
        $schedules->put(
            $semester,
            makeAcademicParitySchedule(
                $data,
                $semester,
                'Ganjil',
                in_array($semester, [6, 7], true) ? null : $data['prodi']->id
            )
        );
    }

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    $availableIds = $response->viewData('jadwals')->pluck('id');
    $semesterGroups = $response->viewData('jadwalsBySemester');

    expect($availableIds)
        ->toContain($data['schedule']->id)
        ->toContain($schedules->get(3)->id)
        ->toContain($schedules->get(5)->id)
        ->toContain($schedules->get(7)->id)
        ->not->toContain($schedules->get(2)->id)
        ->not->toContain($schedules->get(4)->id)
        ->not->toContain($schedules->get(6)->id)
        ->not->toContain($schedules->get(8)->id);
    expect($semesterGroups->keys()->values()->all())->toBe([1, 3, 5, 7]);
    $response->assertSeeInOrder(['Semester 1', 'Semester 3', 'Semester 5', 'Semester 7']);
});

test('even KRS period shows every available even course semester and excludes odd semesters', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all', 'semester' => 'Genap']);

    $schedules = collect();
    foreach (range(1, 8) as $semester) {
        $schedules->put($semester, makeAcademicParitySchedule($data, $semester, 'Genap'));
    }

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    $availableIds = $response->viewData('jadwals')->pluck('id');
    $semesterGroups = $response->viewData('jadwalsBySemester');

    foreach ([2, 4, 6, 8] as $semester) {
        expect($availableIds)->toContain($schedules->get($semester)->id);
    }
    foreach ([1, 3, 5, 7] as $semester) {
        expect($availableIds)->not->toContain($schedules->get($semester)->id);
    }
    expect($semesterGroups->keys()->values()->all())->toBe([2, 4, 6, 8]);
    $response->assertSeeInOrder(['Semester 2', 'Semester 4', 'Semester 6', 'Semester 8']);
});

test('academic and course semester formats are normalized safely', function () {
    $service = app(\App\Services\AvailableKrsScheduleService::class);

    expect($service->normalizeAcademicSemester('Semester Ganjil'))->toBe('Ganjil')
        ->and($service->normalizeAcademicSemester('ganjil'))->toBe('Ganjil')
        ->and($service->normalizeAcademicSemester('1'))->toBe('Ganjil')
        ->and($service->normalizeAcademicSemester('odd'))->toBe('Ganjil')
        ->and($service->normalizeAcademicSemester('Semester Gasal'))->toBe('Ganjil')
        ->and($service->normalizeAcademicSemester('Semester Genap'))->toBe('Genap')
        ->and($service->normalizeAcademicSemester('genap'))->toBe('Genap')
        ->and($service->normalizeAcademicSemester('2'))->toBe('Genap')
        ->and($service->normalizeAcademicSemester('even'))->toBe('Genap')
        ->and($service->requiredParity('Semester_Ganjil'))->toBe('odd')
        ->and($service->requiredParity('even'))->toBe('even')
        ->and($service->normalizeCourseSemester('Semester 7'))->toBe(7)
        ->and($service->normalizeCourseSemester('semester_1'))->toBe(1)
        ->and($service->extractCourseSemesterNumber('Semester 7'))->toBe(7)
        ->and($service->isCourseAllowedForPeriod(7, 'Ganjil'))->toBeTrue()
        ->and($service->isCourseAllowedForPeriod(6, 'Ganjil'))->toBeFalse()
        ->and($service->normalizeCourseSemester(' 8 '))->toBe(8)
        ->and($service->normalizeCourseSemester(null))->toBeNull()
        ->and($service->normalizeCourseSemester('tidak diketahui'))->toBeNull();
});

test('all valid scheduled courses survive staged KRS filters without hardcoded course names', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all', 'semester' => 'Ganjil']);

    $curriculum = Kurikulum::create([
        'prodi_id' => $data['prodi']->id,
        'nama_kurikulum' => 'Kurikulum Audit KRS',
        'tahun_mulai' => 2026,
        'status' => 'Aktif',
    ]);
    $statistics = MataKuliah::create([
        'kode_mk' => 'AUD301',
        'nama_mk' => 'Statistika Terjadwal',
        'sks' => 3,
        'semester' => 3,
        'prodi_id' => $data['prodi']->id,
    ]);
    // Pivot lama yang tidak sinkron tidak boleh mengalahkan semester master
    // untuk mata kuliah milik prodi mahasiswa sendiri.
    $curriculum->mataKuliahs()->attach($statistics->id, ['semester' => 2, 'jenis' => 'Wajib']);
    $statisticsSchedule = Jadwal::create([
        'mata_kuliah_id' => $statistics->id,
        'dosen_id' => null,
        'ruangan_id' => null,
        'kelas_id' => $data['kelas']->id,
        'hari' => 'Kamis',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026-2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $statisticsSchedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Ganjil',
    ]);

    $commonCourse = MataKuliah::create([
        'kode_mk' => 'AUD501',
        'nama_mk' => 'Mata Kuliah Umum Terjadwal',
        'sks' => 2,
        'semester' => 5,
        'prodi_id' => null,
    ]);
    Jadwal::create([
        'mata_kuliah_id' => $commonCourse->id,
        'dosen_id' => null,
        'ruangan_id' => null,
        'kelas_id' => null,
        'hari' => 'Jumat',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026 / 2027',
        'semester_akademik' => 'Semester Ganjil',
    ]);

    $makeSchedule = function (string $code, int $semester, int|null $prodiId, string $year, string $academicSemester) use ($data): Jadwal {
        $course = MataKuliah::create([
            'kode_mk' => $code,
            'nama_mk' => 'Audit '.$code,
            'sks' => 2,
            'semester' => $semester,
            'prodi_id' => $prodiId,
        ]);

        return Jadwal::create([
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $data['dosen']->id,
            'ruangan_id' => $data['room']->id,
            'kelas_id' => null,
            'hari' => 'Sabtu',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tahun_akademik' => $year,
            'semester_akademik' => $academicSemester,
        ]);
    };

    $otherProgram = Prodi::create(['kode_prodi' => 'AUD-OTHER', 'nama_prodi' => 'Prodi Audit Lain', 'jenjang' => 'S1']);
    $makeSchedule('AUD-EVEN', 2, $data['prodi']->id, '2026/2027', 'Ganjil');
    $makeSchedule('AUD-OTHER', 3, $otherProgram->id, '2026/2027', 'Ganjil');
    $makeSchedule('AUD-YEAR', 3, $data['prodi']->id, '2025/2026', 'Ganjil');
    $makeSchedule('AUD-TERM', 3, $data['prodi']->id, '2026/2027', 'Genap');
    $takenSchedule = $makeSchedule('AUD-TAKEN', 7, $data['prodi']->id, '2026/2027', 'Ganjil');
    $alternateTakenSchedule = Jadwal::create([
        'mata_kuliah_id' => $takenSchedule->mata_kuliah_id,
        'dosen_id' => null,
        'ruangan_id' => null,
        'kelas_id' => null,
        'hari' => 'Senin',
        'jam_mulai' => '13:00:00',
        'jam_selesai' => '15:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $takenSchedule->id,
        'status' => 'Menunggu',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    $availableIds = $response->viewData('jadwals')->pluck('id');

    $response->assertOk()
        ->assertSee('Statistika Terjadwal')
        ->assertSee('Mata Kuliah Umum Terjadwal');
    expect($availableIds)
        ->toContain($statisticsSchedule->id)
        ->not->toContain($takenSchedule->id)
        ->not->toContain($alternateTakenSchedule->id);

    $service = app(\App\Services\AvailableKrsScheduleService::class);
    $service->forStudent(
        $data['student'],
        $data['period']->fresh(),
        [$takenSchedule->id],
        [$takenSchedule->mata_kuliah_id]
    );
    $audit = $service->lastAudit();

    expect($audit['candidate_count_before_filter'])->toBeGreaterThan($audit['candidate_count_after_academic_year'])
        ->and($audit['candidate_count_after_academic_year'])->toBeGreaterThan($audit['candidate_count_after_academic_semester'])
        ->and($audit['candidate_count_after_academic_semester'])->toBeGreaterThan($audit['candidate_count_after_parity'])
        ->and($audit['candidate_count_after_parity'])->toBeGreaterThan($audit['candidate_count_after_study_program'])
        ->and($audit['candidate_count_after_study_program'])->toBeGreaterThan($audit['candidate_count_after_taken_exclusion'])
        ->and(collect($audit['included_examples'])->pluck('kode_mk'))->toContain('AUD301');
});

test('student sees a classless matching schedule with normalized academic year', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    $data['schedule']->update([
        'kelas_id' => null,
        'dosen_id' => null,
        'ruangan_id' => null,
        'tahun_akademik' => ' 2026-2027 ',
    ]);

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));

    $response->assertOk()->assertSee('Algoritma Akses KRS');
    expect($response->viewData('jadwals')->pluck('id'))->toContain($data['schedule']->id);
});

test('student without a class sees a matching program and semester schedule', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    $data['student']->update(['kelas_id' => null]);

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));

    $response->assertOk()->assertSee('Algoritma Akses KRS');
    expect($response->viewData('jadwals')->pluck('id'))->toContain($data['schedule']->id);
});

test('student does not see schedules from another program or opposite academic semester', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    $otherProgram = Prodi::create([
        'kode_prodi' => 'PRODI-LAIN',
        'nama_prodi' => 'Program Studi Lain',
        'jenjang' => 'S1',
    ]);
    $otherProgramCourse = MataKuliah::create([
        'kode_mk' => 'LAIN101',
        'nama_mk' => 'Mata Kuliah Prodi Lain',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $otherProgram->id,
    ]);
    $otherSemesterCourse = MataKuliah::create([
        'kode_mk' => 'IF202-A',
        'nama_mk' => 'Mata Kuliah Semester Genap',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $data['prodi']->id,
    ]);

    $otherProgramSchedule = Jadwal::create([
        'mata_kuliah_id' => $otherProgramCourse->id,
        'dosen_id' => $data['dosen']->id,
        'ruangan_id' => $data['room']->id,
        'kelas_id' => $data['kelas']->id,
        'hari' => 'Selasa',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $otherSemesterSchedule = Jadwal::create([
        'mata_kuliah_id' => $otherSemesterCourse->id,
        'dosen_id' => $data['dosen']->id,
        'ruangan_id' => $data['room']->id,
        'kelas_id' => null,
        'hari' => 'Rabu',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    $scheduleIds = $response->viewData('jadwals')->pluck('id');

    expect($scheduleIds)
        ->toContain($data['schedule']->id)
        ->not->toContain($otherProgramSchedule->id)
        ->not->toContain($otherSemesterSchedule->id);
});

test('only schedules already taken in the active period are excluded', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);

    Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Ganjil',
    ]);

    $oldPeriodResponse = $this->actingAs($data['studentUser'])->get(route('mahasiswa.krs'));
    expect($oldPeriodResponse->viewData('jadwals')->pluck('id'))->toContain($data['schedule']->id);

    Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'status' => 'Menunggu',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $activePeriodResponse = $this->get(route('mahasiswa.krs'));
    expect($activePeriodResponse->viewData('jadwals')->pluck('id'))->not->toContain($data['schedule']->id);
});

test('empty KRS schedule list shows an actionable message', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update(['access_mode' => 'all']);
    $data['schedule']->update(['tahun_akademik' => '2025/2026']);

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Belum ada mata kuliah tersedia untuk semester akademik periode ini. Hubungi admin akademik.');
});

test('admin schedule input stores the same academic period format used by KRS', function () {
    $data = makePerStudentKrsAccessData();
    $schedule = $data['schedule']->load(['mataKuliah', 'dosen', 'ruangan']);

    $this->actingAs($data['admin'])->post(route('admin.jadwal.store'), [
        'mata_kuliah_id' => $schedule->mata_kuliah_id,
        'dosen_id' => $schedule->dosen_id,
        'ruangan_id' => $schedule->ruangan_id,
        'hari' => 'Rabu',
        'jam_mulai' => '13:00',
        'jam_selesai' => '15:00',
        'tahun_akademik' => '2027 - 2028',
        'semester_akademik' => 'Semester 1',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('jadwals', [
        'mata_kuliah_id' => $schedule->mata_kuliah_id,
        'kelas_id' => null,
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Ganjil',
    ]);
});

test('schedule and student forms no longer require a class', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['admin'])
        ->get(route('admin.jadwal.create'))
        ->assertOk()
        ->assertDontSee('name="kelas_id"', false);

    $this->get(route('admin.mahasiswa.create'))
        ->assertOk()
        ->assertSee('Dosen Wali')
        ->assertSee('name="dosen_wali_id"', false)
        ->assertDontSee('name="kelas_id"', false);

    $this->get(route('admin.periode-krs.students', $data['period']))
        ->assertOk()
        ->assertDontSee('name="kelas_id"', false)
        ->assertDontSee('Prodi / Kelas');
});

test('admin stores a direct student advisor without assigning a class', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['admin'])
        ->post(route('admin.mahasiswa.store'), [
            'nim' => '260099',
            'nama' => 'Mahasiswa Tanpa Kelas',
            'angkatan' => 2026,
            'semester' => 1,
            'prodi_id' => $data['prodi']->id,
            'dosen_wali_id' => $data['dosen']->id,
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mahasiswas', [
        'nim' => '260099',
        'kelas_id' => null,
        'dosen_wali_id' => $data['dosen']->id,
    ]);

    $otherAdvisorUser = User::factory()->create(['role' => 'dosen']);
    $otherAdvisor = Dosen::create([
        'nidn' => 'DOSEN-WALI-EDIT',
        'nama' => 'Dosen Wali Pengganti',
        'prodi_id' => $data['prodi']->id,
        'user_id' => $otherAdvisorUser->id,
    ]);
    $student = Mahasiswa::where('nim', '260099')->firstOrFail();

    $this->get(route('admin.mahasiswa.edit', $student))
        ->assertOk()
        ->assertSee('name="dosen_wali_id"', false)
        ->assertDontSee('name="kelas_id"', false);
    $this->put(route('admin.mahasiswa.update', $student), [
        'nim' => $student->nim,
        'nama' => $student->nama,
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $data['prodi']->id,
        'dosen_wali_id' => $otherAdvisor->id,
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->dosen_wali_id)->toBe($otherAdvisor->id);
});

test('only the direct student advisor can review and approve KRS', function () {
    $data = makePerStudentKrsAccessData();
    $courseLecturerUser = User::factory()->create(['role' => 'dosen']);
    $courseLecturer = Dosen::create([
        'nidn' => 'DOSEN-PENGAMPU-02',
        'nama' => 'Dosen Pengampu Bukan Wali',
        'prodi_id' => $data['prodi']->id,
        'user_id' => $courseLecturerUser->id,
    ]);
    $data['schedule']->update(['dosen_id' => $courseLecturer->id]);
    $submission = Krs::create([
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'status' => 'Menunggu',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $this->actingAs($courseLecturerUser)
        ->get(route('dosen.krs'))
        ->assertOk()
        ->assertDontSee('Mahasiswa Akses A');
    $this->put(route('dosen.krs.setujui', $submission))->assertNotFound();

    $this->actingAs($data['lecturerUser'])
        ->get(route('dosen.krs'))
        ->assertOk()
        ->assertSee('Mahasiswa Akses A');
    $this->put(route('dosen.krs.setujui', $submission))
        ->assertRedirect(route('dosen.krs'));

    expect($submission->fresh()->status)->toBe('Disetujui');
});

test('legacy string access rows are normalized without losing their owner', function () {
    $data = makePerStudentKrsAccessData();

    Schema::drop('periode_krs_mahasiswas');
    Schema::create('periode_krs_mahasiswas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('periode_krs_id')->constrained('periode_krs')->cascadeOnDelete();
        $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
        $table->string('status_akses', 20)->default('ditutup');
        $table->timestamp('tanggal_dibuka')->nullable();
        $table->timestamp('tanggal_ditutup')->nullable();
        $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
        $table->text('catatan')->nullable();
        $table->timestamps();
        $table->unique(['periode_krs_id', 'mahasiswa_id'], 'periode_krs_mahasiswa_unique');
        $table->index(['periode_krs_id', 'status_akses'], 'periode_krs_akses_filter_index');
    });

    DB::table('periode_krs_mahasiswas')->insert([
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => 'dibuka',
        'admin_id' => $data['admin']->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_09_17_000000_normalize_periode_krs_mahasiswa_access.php');
    $migration->up();

    expect(Schema::hasColumn('periode_krs_mahasiswas', 'dibuka_oleh'))->toBeTrue();
    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'dibuka_oleh' => $data['admin']->id,
    ]);
});

test('student and lecturer cannot manage per student KRS access', function () {
    $data = makePerStudentKrsAccessData();

    foreach ([$data['studentUser'], User::factory()->create(['role' => 'dosen'])] as $user) {
        $this->actingAs($user)
            ->get(route('admin.periode-krs.students', $data['period']))
            ->assertForbidden();
        $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
            'status_akses' => 1,
        ])->assertForbidden();
    }
});
