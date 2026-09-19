<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function makeCourseDisplayData(): array
{
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $otherLecturerUser = User::factory()->create(['role' => 'dosen']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::create([
        'kode_prodi' => 'IF-DISPLAY',
        'nama_prodi' => 'Informatika Display',
        'jenjang' => 'S1',
    ]);
    $lecturer = Dosen::create([
        'nidn' => 'DISPLAY-01',
        'nama' => 'Dosen Pengampu Display',
        'prodi_id' => $prodi->id,
        'user_id' => $lecturerUser->id,
    ]);
    $otherLecturer = Dosen::create([
        'nidn' => 'DISPLAY-02',
        'nama' => 'Dosen Pengampu Lain',
        'prodi_id' => $prodi->id,
        'user_id' => $otherLecturerUser->id,
    ]);
    $student = Mahasiswa::create([
        'nim' => 'DISPLAY-MHS-01',
        'nama' => 'Mahasiswa Display',
        'angkatan' => 2026,
        'semester' => 2,
        'prodi_id' => $prodi->id,
        'dosen_wali_id' => $lecturer->id,
        'user_id' => $studentUser->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'DISPLAY-R1',
        'nama_ruangan' => 'Ruang Display',
        'kapasitas' => 30,
    ]);
    $course = MataKuliah::create([
        'kode_mk' => 'DSP101',
        'nama_mk' => 'Mata Kuliah Display',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $otherCourse = MataKuliah::create([
        'kode_mk' => 'DSP999',
        'nama_mk' => 'Mata Kuliah Dosen Lain',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $manualCourse = MataKuliah::create([
        'kode_mk' => 'DSP202',
        'nama_mk' => 'Mata Kuliah Tanpa Jadwal',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $prodi->id,
    ]);
    $schedule = Jadwal::create([
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $lecturer->id,
        'ruangan_id' => $room->id,
        'hari' => 'Selasa',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Jadwal::create([
        'mata_kuliah_id' => $otherCourse->id,
        'dosen_id' => $otherLecturer->id,
        'ruangan_id' => $room->id,
        'hari' => 'Rabu',
        'jam_mulai' => '10:00:00',
        'jam_selesai' => '12:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $student->id,
        'jadwal_id' => $schedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    Krs::create([
        'mahasiswa_id' => $student->id,
        'jadwal_id' => null,
        'mata_kuliah_id' => $manualCourse->id,
        'dosen_id' => null,
        'prodi_id' => $prodi->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Genap',
        'is_manual' => true,
        'manual_identity' => 'course-display-manual',
    ]);

    return compact('lecturerUser', 'studentUser', 'lecturer', 'student', 'schedule');
}

test('lecturer course and schedule pages show complete assigned schedule data only', function () {
    $data = makeCourseDisplayData();

    foreach (['dosen.matakuliah', 'dosen.jadwal'] as $routeName) {
        $response = $this->actingAs($data['lecturerUser'])->get(route($routeName));

        $response->assertOk()
            ->assertSee('DSP101')
            ->assertSee('Mata Kuliah Display')
            ->assertSee('Dosen Pengampu Display')
            ->assertSee('3')
            ->assertSee('Informatika Display')
            ->assertSee('Semester 1')
            ->assertSee('2026/2027')
            ->assertSee('Selasa')
            ->assertSee('08:00:00 - 10:00:00')
            ->assertSee('Ruang Display')
            ->assertDontSee('DSP999')
            ->assertDontSee('Mata Kuliah Dosen Lain');

        $schedule = $response->viewData('jadwals')->first();
        expect($schedule->relationLoaded('mataKuliah'))->toBeTrue()
            ->and($schedule->mataKuliah->relationLoaded('prodi'))->toBeTrue()
            ->and($schedule->relationLoaded('dosen'))->toBeTrue()
            ->and($schedule->relationLoaded('ruangan'))->toBeTrue()
            ->and((int) $schedule->jumlah_mahasiswa)->toBe(1);
    }
});

test('student schedule shows KRS course lecturer credits and nullable schedule fallbacks', function () {
    $data = makeCourseDisplayData();

    $response = $this->actingAs($data['studentUser'])->get(route('mahasiswa.jadwal'));

    $response->assertOk()
        ->assertSee('DSP101')
        ->assertSee('Mata Kuliah Display')
        ->assertSee('Dosen Pengampu Display')
        ->assertSee('Ruang Display')
        ->assertSee('Selasa')
        ->assertSee('DSP202')
        ->assertSee('Mata Kuliah Tanpa Jadwal')
        ->assertDontSee('DSP999');

    $items = $response->viewData('krsItems');
    $scheduledItem = $items->firstWhere('jadwal_id', $data['schedule']->id);
    expect($items)->toHaveCount(2)
        ->and($scheduledItem)->not->toBeNull()
        ->and($scheduledItem->relationLoaded('jadwal'))->toBeTrue()
        ->and($scheduledItem->jadwal->relationLoaded('mataKuliah'))->toBeTrue()
        ->and($scheduledItem->jadwal->relationLoaded('dosen'))->toBeTrue()
        ->and($scheduledItem->jadwal->relationLoaded('ruangan'))->toBeTrue();
});
