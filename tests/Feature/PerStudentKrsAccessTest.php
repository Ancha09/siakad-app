<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

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

    return compact('admin', 'studentUser', 'otherStudentUser', 'prodi', 'kelas', 'student', 'otherStudent', 'schedule', 'period');
}

test('admin manages one student KRS access and payment without affecting another student', function () {
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
        ->assertSee('Belum Dibuka');

    $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
        'status_akses' => 'dibuka',
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => 'dibuka',
        'admin_id' => $data['admin']->id,
    ]);
    $this->assertDatabaseMissing('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['otherStudent']->id,
    ]);

    $this->patch(route('admin.periode-krs.students.payment', [$data['period'], $data['student']]), [
        'status_bayar' => 'lunas',
        'catatan' => 'Pembayaran diperiksa manual.',
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('pembayaran_krs', [
        'mahasiswa_id' => $data['student']->id,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'status_bayar' => 'lunas',
        'catatan' => 'Pembayaran diperiksa manual.',
    ]);

    $this->get(route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'status_akses' => 'dibuka',
        'status_bayar' => 'lunas',
    ]))->assertOk()->assertSee('Mahasiswa Akses A')->assertDontSee('Mahasiswa Akses B');
});

test('student can submit KRS only when global period and personal access are both open', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Akses KRS Anda belum dibuka. Silakan hubungi admin.')
        ->assertDontSee('Algoritma Akses KRS');

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka. Silakan hubungi admin.');
    $this->assertDatabaseCount('krs', 0);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 'dibuka']
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
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka. Silakan hubungi admin.');
    $this->assertDatabaseMissing('krs', ['mahasiswa_id' => $data['otherStudent']->id]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 'ditutup']
    )->assertSessionHasNoErrors();
    $this->assertDatabaseCount('periode_krs_mahasiswas', 1);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka. Silakan hubungi admin.');
    $this->assertDatabaseCount('krs', 1);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 'dibuka']
    )->assertSessionHasNoErrors();

    $data['period']->update(['status' => 'Ditutup']);
    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Pengisian KRS sedang ditutup atau periode KRS telah berakhir.');
});

test('student and lecturer cannot manage per student KRS access', function () {
    $data = makePerStudentKrsAccessData();

    foreach ([$data['studentUser'], User::factory()->create(['role' => 'dosen'])] as $user) {
        $this->actingAs($user)
            ->get(route('admin.periode-krs.students', $data['period']))
            ->assertForbidden();
        $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
            'status_akses' => 'dibuka',
        ])->assertForbidden();
    }
});
