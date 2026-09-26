<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function attendanceFixture(): array
{
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $lecturer = Dosen::create([
        'nidn' => 'ATT-DOS-001',
        'nama' => 'Dosen Presensi',
        'user_id' => $lecturerUser->id,
        'is_active' => true,
    ]);

    $student = Mahasiswa::create([
        'nim' => 'ATT-MHS-001',
        'nama' => 'Mahasiswa Presensi',
        'is_active' => true,
    ]);

    $schedule = Jadwal::create([
        'dosen_id' => $lecturer->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $krs = Krs::create([
        'mahasiswa_id' => $student->id,
        'jadwal_id' => $schedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_manual' => false,
    ]);

    return compact('lecturerUser', 'schedule', 'krs');
}

test('new lecturer attendance requires material description notes and photo', function () {
    ['lecturerUser' => $user, 'schedule' => $schedule, 'krs' => $krs] = attendanceFixture();

    $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $schedule->id,
        'krs_id' => [$krs->id],
        'status' => ['Hadir'],
        'pertemuan' => 1,
        'tanggal' => '2026-09-21',
    ])->assertSessionHasErrors(['materi_kuliah', 'keterangan', 'foto']);
});

test('lecturer attendance can be created without an uploaded material file', function () {
    Storage::fake('public');
    ['lecturerUser' => $user, 'schedule' => $schedule, 'krs' => $krs] = attendanceFixture();

    $response = $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $schedule->id,
        'krs_id' => [$krs->id],
        'status' => ['Hadir'],
        'pertemuan' => 1,
        'tanggal' => '2026-09-21',
        'materi_kuliah' => 'Pengenalan keselamatan kerja tambang',
        'keterangan' => 'Perkuliahan dilaksanakan secara tatap muka.',
        'foto' => UploadedFile::fake()->image('foto-absen.jpg'),
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('presensi_pertemuans', [
        'jadwal_id' => $schedule->id,
        'pertemuan' => 1,
        'materi_kuliah' => 'Pengenalan keselamatan kerja tambang',
        'keterangan' => 'Perkuliahan dilaksanakan secara tatap muka.',
        'materi' => null,
    ]);
    $this->assertDatabaseHas('presensis', [
        'krs_id' => $krs->id,
        'pertemuan' => 1,
        'status' => 'Hadir',
    ]);
});

test('editing attendance status does not require another photo upload', function () {
    Storage::fake('public');
    ['lecturerUser' => $user, 'schedule' => $schedule, 'krs' => $krs] = attendanceFixture();

    $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $schedule->id,
        'krs_id' => [$krs->id],
        'status' => ['Hadir'],
        'pertemuan' => 1,
        'tanggal' => '2026-09-21',
        'materi_kuliah' => 'Pengenalan keselamatan kerja tambang',
        'keterangan' => 'Perkuliahan dilaksanakan secara tatap muka.',
        'foto' => UploadedFile::fake()->image('foto-absen.jpg'),
    ])->assertSessionHasNoErrors();

    $this->post(route('dosen.presensi.store'), [
        'jadwal_id' => $schedule->id,
        'krs_id' => [$krs->id],
        'status' => ['Izin'],
        'pertemuan' => 1,
        'tanggal' => '2026-09-21',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('presensis', [
        'krs_id' => $krs->id,
        'pertemuan' => 1,
        'status' => 'Izin',
    ]);
});
