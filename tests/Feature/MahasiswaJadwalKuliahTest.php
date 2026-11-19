<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function setupHadinScheduleScenario(): array
{
    $hadinUser = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Hadin']);
    $otherUser = User::factory()->create(['role' => 'mahasiswa', 'name' => 'Mahasiswa Lain']);
    $dosenUser = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Pengampu']);

    $prodi = Prodi::create([
        'kode_prodi' => 'IF-HADIN',
        'nama_prodi' => 'Teknik Informatika Hadin',
        'jenjang' => 'S1',
    ]);

    $dosen = Dosen::create([
        'nidn' => 'HDN-DOSEN-01',
        'nama' => 'Dosen Pengampu Hadin',
        'prodi_id' => $prodi->id,
        'user_id' => $dosenUser->id,
    ]);

    $kelasA = Kelas::create(['nama_kelas' => 'A', 'prodi_id' => $prodi->id, 'angkatan' => 2021]);
    $kelasB = Kelas::create(['nama_kelas' => 'B', 'prodi_id' => $prodi->id, 'angkatan' => 2021]);

    $hadin = Mahasiswa::create([
        'nim' => '20210001',
        'nama' => 'Hadin',
        'angkatan' => 2021,
        'semester' => 5,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelasA->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $hadinUser->id,
    ]);

    $otherStudent = Mahasiswa::create([
        'nim' => '20210002',
        'nama' => 'Mahasiswa Lain',
        'angkatan' => 2021,
        'semester' => 5,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelasB->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $otherUser->id,
    ]);

    $ruangan = Ruangan::create([
        'kode_ruangan' => 'R-HDN',
        'nama_ruangan' => 'Ruang Teori Hadin',
        'kapasitas' => 40,
    ]);

    // Periode KRS Aktif: 2026/2027 Ganjil
    $periodeAktif = PeriodeKrs::create([
        'tahun_akademik' => '2026/2027',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDays(5),
        'tanggal_selesai' => now()->addDays(20),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
        'access_mode' => 'all',
    ]);

    // 1. Buat 29 mata kuliah historis di 4 semester sebelumnya (Disetujui)
    $pastSemesters = [
        ['year' => '2024/2025', 'sem' => 'Ganjil', 'count' => 7],
        ['year' => '2024/2025', 'sem' => 'Genap',  'count' => 7],
        ['year' => '2025/2026', 'sem' => 'Ganjil', 'count' => 7],
        ['year' => '2025/2026', 'sem' => 'Genap',  'count' => 8],
    ];

    $historicalCourses = [];
    foreach ($pastSemesters as $ps) {
        for ($i = 1; $i <= $ps['count']; $i++) {
            $code = 'PAST-'.$ps['year'].'-'.$ps['sem'].'-'.$i;
            $name = "Matkul Historis {$ps['year']} {$ps['sem']} {$i}";
            $mk = MataKuliah::create([
                'kode_mk' => $code,
                'nama_mk' => $name,
                'sks' => 2,
                'semester' => 1,
                'prodi_id' => $prodi->id,
            ]);

            $j = Jadwal::create([
                'mata_kuliah_id' => $mk->id,
                'dosen_id' => $dosen->id,
                'ruangan_id' => $ruangan->id,
                'kelas_id' => $kelasA->id,
                'hari' => 'Senin',
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:40:00',
                'tahun_akademik' => $ps['year'],
                'semester_akademik' => $ps['sem'],
            ]);

            Krs::create([
                'mahasiswa_id' => $hadin->id,
                'jadwal_id' => $j->id,
                'status' => 'Disetujui',
                'tahun_akademik' => $ps['year'],
                'semester_akademik' => $ps['sem'],
            ]);

            $historicalCourses[] = $name;
        }
    }

    // 2. Buat 8 mata kuliah di semester aktif 2026/2027 Ganjil dengan total tepat 20 SKS
    // SKS: 3 + 3 + 3 + 3 + 2 + 2 + 2 + 2 = 20 SKS
    $activeSksList = [3, 3, 3, 3, 2, 2, 2, 2];
    $activeCourses = [];
    $activeSchedules = [];

    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Senin', 'Selasa', 'Rabu'];
    $hours = [
        ['08:00:00', '10:30:00'],
        ['10:30:00', '13:00:00'],
        ['08:00:00', '10:30:00'],
        ['13:00:00', '15:30:00'],
        ['08:00:00', '09:40:00'],
        ['13:00:00', '14:40:00'],
        ['14:00:00', '15:40:00'],
        ['15:30:00', '17:10:00'],
    ];

    foreach ($activeSksList as $idx => $sks) {
        $num = $idx + 1;
        $code = "AKTIF-10{$num}";
        $name = "Mata Kuliah Aktif {$num}";

        $mk = MataKuliah::create([
            'kode_mk' => $code,
            'nama_mk' => $name,
            'sks' => $sks,
            'semester' => 5,
            'prodi_id' => $prodi->id,
        ]);

        $j = Jadwal::create([
            'mata_kuliah_id' => $mk->id,
            'dosen_id' => $dosen->id,
            'ruangan_id' => $ruangan->id,
            'kelas_id' => $kelasA->id,
            'hari' => $days[$idx],
            'jam_mulai' => $hours[$idx][0],
            'jam_selesai' => $hours[$idx][1],
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]);

        Krs::create([
            'mahasiswa_id' => $hadin->id,
            'jadwal_id' => $j->id,
            'status' => 'Disetujui',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]);

        $activeCourses[] = $name;
        $activeSchedules[] = $j;
    }

    // 3. Buat kelas paralel (Kelas B) untuk salah satu mata kuliah aktif yang TIDAK diambil Hadin
    $parallelSchedule = Jadwal::create([
        'mata_kuliah_id' => $activeSchedules[0]->mata_kuliah_id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $ruangan->id,
        'kelas_id' => $kelasB->id,
        'hari' => 'Sabtu',
        'jam_mulai' => '13:00:00',
        'jam_selesai' => '15:30:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    // 4. Buat 1 mata kuliah berstatus Draft di semester aktif untuk Hadin
    $draftCourse = MataKuliah::create([
        'kode_mk' => 'DRAFT-99',
        'nama_mk' => 'Mata Kuliah Draft Hadin',
        'sks' => 3,
        'semester' => 5,
        'prodi_id' => $prodi->id,
    ]);
    $draftSchedule = Jadwal::create([
        'mata_kuliah_id' => $draftCourse->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $ruangan->id,
        'kelas_id' => $kelasA->id,
        'hari' => 'Jumat',
        'jam_mulai' => '13:00:00',
        'jam_selesai' => '15:30:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $hadin->id,
        'jadwal_id' => $draftSchedule->id,
        'status' => 'Draft',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    // 5. Buat KRS mahasiswa lain di semester aktif
    $otherCourse = MataKuliah::create([
        'kode_mk' => 'OTHER-77',
        'nama_mk' => 'Mata Kuliah Mahasiswa Lain',
        'sks' => 3,
        'semester' => 5,
        'prodi_id' => $prodi->id,
    ]);
    $otherSchedule = Jadwal::create([
        'mata_kuliah_id' => $otherCourse->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $ruangan->id,
        'kelas_id' => $kelasB->id,
        'hari' => 'Sabtu',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:30:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $otherStudent->id,
        'jadwal_id' => $otherSchedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    return compact(
        'hadinUser',
        'hadin',
        'otherStudent',
        'periodeAktif',
        'historicalCourses',
        'activeCourses',
        'activeSchedules',
        'parallelSchedule',
        'draftCourse',
        'otherCourse'
    );
}

test('hadin has 37 total approved courses in database, but jadwal kuliah page only displays active 20 SKS', function () {
    $data = setupHadinScheduleScenario();

    // Verifikasi kondisi awal database Hadin:
    // Total KRS yang disetujui milik Hadin adalah 29 (historis) + 8 (aktif) = tepat 37 mata kuliah!
    $totalApprovedHadin = Krs::where('mahasiswa_id', $data['hadin']->id)
        ->where('status', 'Disetujui')
        ->count();
    expect($totalApprovedHadin)->toBe(37);

    // Kunjungi halaman Jadwal Kuliah mahasiswa
    $response = $this->actingAs($data['hadinUser'])->get(route('mahasiswa.jadwal'));

    $response->assertOk();

    // 1. krsItems yang dikirim ke view harus HANYA 8 mata kuliah (bukan 37!)
    $krsItems = $response->viewData('krsItems');
    expect($krsItems)->toHaveCount(8);

    // 2. Total SKS pada semester aktif harus tepat 20 SKS
    $totalSks = $response->viewData('totalSks');
    expect($totalSks)->toBe(20);

    // 3. Seluruh 8 mata kuliah semester aktif harus terlihat di response
    foreach ($data['activeCourses'] as $activeCourseName) {
        $response->assertSee($activeCourseName);
    }

    // 4. Mata kuliah dari semester-semester sebelumnya (29 mata kuliah) TIDAK boleh muncul
    foreach ($data['historicalCourses'] as $pastCourseName) {
        $response->assertDontSee($pastCourseName);
    }

    // 5. Mata kuliah dengan status Draft TIDAK boleh muncul
    $response->assertDontSee($data['draftCourse']->nama_mk);

    // 6. Mata kuliah milik mahasiswa lain TIDAK boleh muncul
    $response->assertDontSee($data['otherCourse']->nama_mk);

    // 7. Jadwal kelas paralel (Sabtu) yang TIDAK diambil Hadin TIDAK boleh muncul
    $response->assertDontSee('Sabtu');
});

test('student can switch to a past semester to view historical schedule via period filter', function () {
    $data = setupHadinScheduleScenario();

    // Hadin memilih untuk melihat jadwal semester lama 2025/2026 Genap
    $response = $this->actingAs($data['hadinUser'])->get(route('mahasiswa.jadwal', [
        'periode' => '2025/2026|Genap',
    ]));

    $response->assertOk();

    $krsItems = $response->viewData('krsItems');
    expect($krsItems)->toHaveCount(8);

    // Menampilkan mata kuliah 2025/2026 Genap
    $response->assertSee('Matkul Historis 2025/2026 Genap 1');
    $response->assertSee('Matkul Historis 2025/2026 Genap 8');

    // Tidak menampilkan mata kuliah dari semester lain
    $response->assertDontSee('Matkul Historis 2024/2025 Ganjil 1');
    $response->assertDontSee('Mata Kuliah Aktif 1');
});

test('jadwal kuliah still shows active semester even when krs enrollment period is closed', function () {
    $data = setupHadinScheduleScenario();

    // Simulasikan masa pengisian KRS telah berakhir (ditutup), tetapi perkuliahan semester aktif sedang berjalan
    $data['periodeAktif']->update([
        'status' => 'Ditutup',
        'tanggal_mulai' => now()->subMonths(2),
        'tanggal_selesai' => now()->subMonth(),
    ]);

    $response = $this->actingAs($data['hadinUser'])->get(route('mahasiswa.jadwal'));

    $response->assertOk();

    $krsItems = $response->viewData('krsItems');
    // Tetap menampilkan jadwal semester aktif (8 mata kuliah / 20 SKS), bukan 37 matkul
    expect($krsItems)->toHaveCount(8);
    expect($response->viewData('totalSks'))->toBe(20);

    foreach ($data['activeCourses'] as $activeCourseName) {
        $response->assertSee($activeCourseName);
    }
});
