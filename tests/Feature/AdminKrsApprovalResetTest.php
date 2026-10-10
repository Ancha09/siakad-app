<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\KrsApprovalReset;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Pengumuman;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\User;

function makeKrsResetFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $advisorUser = User::factory()->create(['role' => 'dosen']);
    $students = collect(range(1, 3))->map(fn () => User::factory()->create(['role' => 'mahasiswa']));
    $prodi = Prodi::create(['kode_prodi' => 'RESET-TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $advisor = Dosen::create(['nidn' => 'RESET-WALI', 'nama' => 'Dosen Wali Reset', 'prodi_id' => $prodi->id, 'user_id' => $advisorUser->id]);
    $period = PeriodeKrs::create([
        'tahun_akademik' => '2026/2027', 'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDay(), 'tanggal_selesai' => now()->addWeek(),
        'status' => 'Dibuka', 'access_mode' => 'all', 'maksimal_sks' => 22,
    ]);
    $course = MataKuliah::create(['kode_mk' => 'RESET101', 'nama_mk' => 'Mata Kuliah Reset', 'sks' => 3, 'semester' => 1, 'prodi_id' => $prodi->id]);
    $schedule = Jadwal::create([
        'mata_kuliah_id' => $course->id, 'dosen_id' => $advisor->id,
        'hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
        'tahun_akademik' => $period->tahun_akademik, 'semester_akademik' => $period->semester,
    ]);

    $mahasiswas = $students->values()->map(function ($user, $index) use ($prodi, $advisor) {
        return Mahasiswa::create([
            'nim' => 'RESET2600'.($index + 1), 'nama' => 'Mahasiswa Reset '.($index + 1),
            'user_id' => $user->id, 'prodi_id' => $prodi->id,
            'dosen_wali_id' => $advisor->id, 'angkatan' => 2026,
            'semester' => 1, 'is_active' => true,
        ]);
    });
    $records = $mahasiswas->map(fn ($student) => Krs::create([
        'mahasiswa_id' => $student->id, 'jadwal_id' => $schedule->id,
        'tahun_akademik' => $period->tahun_akademik,
        'semester_akademik' => $period->semester,
        'status' => 'Disetujui', 'is_manual' => false,
    ]));

    return compact('admin', 'advisorUser', 'students', 'mahasiswas', 'period', 'records');
}

test('admin reset requires a reason and preserves KRS while notifying only the student', function () {
    $data = makeKrsResetFixture();
    $period = $data['period'];
    $student = $data['mahasiswas'][0];
    $returnUrl = route('admin.krs-mahasiswa.index', ['periode_krs_id' => $period->id, 'status' => 'Disetujui', 'page' => 2]);

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $period), [
        'mode' => 'single', 'mahasiswa_id' => $student->id, 'alasan' => '',
    ])->assertSessionHasErrors('alasan');
    expect($data['records'][0]->fresh()->status)->toBe('Disetujui');

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $period), [
        'mode' => 'single', 'mahasiswa_id' => $student->id,
        'alasan' => 'Pilihan mata kuliah perlu diperbaiki', 'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    expect($data['records'][0]->fresh()->status)->toBe('Menunggu')
        ->and($data['records'][0]->fresh()->admin_revision_open)->toBeTrue()
        ->and(Krs::count())->toBe(3)
        ->and(KrsApprovalReset::count())->toBe(1)
        ->and(KrsApprovalReset::first()->alasan)->toBe('Pilihan mata kuliah perlu diperbaiki')
        ->and(Pengumuman::terlihat($data['students'][0])->count())->toBe(1)
        ->and(Pengumuman::terlihat($data['students'][1])->count())->toBe(0)
        ->and($data['records'][1]->fresh()->status)->toBe('Disetujui');

    $this->actingAs($data['students'][0])->post(route('mahasiswa.krs.pdf'), [
        'tahun_akademik' => $period->tahun_akademik,
        'semester_akademik' => $period->semester,
    ])->assertRedirect(route('mahasiswa.krs'));

    $this->actingAs($data['students'][0])->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertViewHas('draftDapatDiubah', true)
        ->assertSee('Persetujuan KRS dikembalikan oleh admin');

    $this->actingAs($data['advisorUser'])->get(route('dosen.krs'))
        ->assertOk()->assertDontSee($student->nim);
    $this->actingAs($data['advisorUser'])->put(route('dosen.krs.setujui', $data['records'][0]->id))
        ->assertNotFound();
    expect($data['records'][0]->fresh()->status)->toBe('Menunggu');

    $extraCourse = MataKuliah::create([
        'kode_mk' => 'RESET102', 'nama_mk' => 'Mata Kuliah Koreksi',
        'sks' => 2, 'semester' => 1, 'prodi_id' => $student->prodi_id,
    ]);
    $extraSchedule = Jadwal::create([
        'mata_kuliah_id' => $extraCourse->id,
        'dosen_id' => $student->dosen_wali_id,
        'hari' => 'Selasa', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
        'tahun_akademik' => $period->tahun_akademik,
        'semester_akademik' => $period->semester,
    ]);
    $this->actingAs($data['students'][0])->post(route('mahasiswa.krs.store'), [
        'jadwal_id' => $extraSchedule->id,
    ])->assertRedirect(route('mahasiswa.krs'));
    $extraRecord = Krs::where('mahasiswa_id', $student->id)->where('jadwal_id', $extraSchedule->id)->firstOrFail();
    expect($extraRecord->status)->toBe('Draft');
    $this->actingAs($data['students'][0])->delete(route('mahasiswa.krs.destroy', $extraRecord->id))
        ->assertRedirect(route('mahasiswa.krs'));
    expect(Krs::whereKey($extraRecord->id)->exists())->toBeFalse();

    $this->actingAs($data['students'][0])->post(route('mahasiswa.krs.ajukan'))
        ->assertRedirect(route('mahasiswa.krs'));
    expect($data['records'][0]->fresh()->admin_revision_open)->toBeFalse();

    $this->actingAs($data['advisorUser'])->get(route('dosen.krs'))
        ->assertOk()->assertSee($student->nama);
});

test('admin can reset selected students and all remaining students in one period', function () {
    $data = makeKrsResetFixture();
    $period = $data['period'];

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $period), [
        'mode' => 'selected',
        'mahasiswa_ids' => [$data['mahasiswas'][0]->id, $data['mahasiswas'][1]->id],
        'alasan' => 'Sosialisasi KRS belum lengkap',
    ])->assertRedirect(route('admin.krs-mahasiswa.index'));

    expect(KrsApprovalReset::count())->toBe(2)
        ->and($data['records'][0]->fresh()->status)->toBe('Menunggu')
        ->and($data['records'][1]->fresh()->status)->toBe('Menunggu')
        ->and($data['records'][2]->fresh()->status)->toBe('Disetujui');

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $period), [
        'mode' => 'all', 'alasan' => 'Perbaikan seluruh KRS periode ini',
    ])->assertRedirect(route('admin.krs-mahasiswa.index'));

    expect(KrsApprovalReset::count())->toBe(3)
        ->and($data['records'][2]->fresh()->status)->toBe('Menunggu')
        ->and(Krs::count())->toBe(3)
        ->and(Pengumuman::count())->toBe(3);
});

test('student cannot use admin reset endpoint or see another student correction', function () {
    $data = makeKrsResetFixture();
    $this->actingAs($data['students'][1])->post(route('admin.periode-krs.reset-persetujuan', $data['period']), [
        'mode' => 'all', 'alasan' => 'Tidak berwenang',
    ])->assertForbidden();

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $data['period']), [
        'mode' => 'single', 'mahasiswa_id' => $data['mahasiswas'][0]->id,
        'alasan' => 'Koreksi terbatas pada mahasiswa pertama',
    ])->assertRedirect();

    $this->actingAs($data['students'][1])->get(route('mahasiswa.pemberitahuan'))
        ->assertOk()->assertDontSee('Koreksi terbatas pada mahasiswa pertama');
});

test('reset skips graded KRS so published grades remain available', function () {
    $data = makeKrsResetFixture();
    Khs::create([
        'krs_id' => $data['records'][0]->id,
        'nilai_angka' => 90,
        'nilai_huruf' => 'A',
        'bobot' => 4,
        'tahun_akademik' => $data['period']->tahun_akademik,
        'semester_akademik' => $data['period']->semester,
    ]);

    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $data['period']), [
        'mode' => 'all', 'alasan' => 'Koreksi seluruh periode',
    ])->assertRedirect(route('admin.krs-mahasiswa.index'));

    expect($data['records'][0]->fresh()->status)->toBe('Disetujui')
        ->and($data['records'][1]->fresh()->status)->toBe('Menunggu')
        ->and($data['records'][2]->fresh()->status)->toBe('Menunggu')
        ->and(Khs::count())->toBe(1)
        ->and(Pengumuman::terlihat($data['students'][0])->count())->toBe(0);
});

test('admin announcement targets period program cohort or one student without leaking to others', function () {
    $data = makeKrsResetFixture();
    $outsideUser = User::factory()->create(['role' => 'mahasiswa']);
    $outsideProdi = Prodi::create(['kode_prodi' => 'RESET-OUT', 'nama_prodi' => 'Prodi Lain', 'jenjang' => 'S1']);
    Mahasiswa::create([
        'nim' => 'RESET-OUT-1', 'nama' => 'Mahasiswa Di Luar Target',
        'user_id' => $outsideUser->id, 'prodi_id' => $outsideProdi->id,
        'angkatan' => 2025, 'semester' => 1,
    ]);

    foreach ([
        ['target_type' => 'period', 'target_periode_krs_id' => $data['period']->id],
        ['target_type' => 'prodi', 'target_prodi_id' => $data['mahasiswas'][0]->prodi_id],
        ['target_type' => 'angkatan', 'target_angkatan' => 2026],
        ['target_type' => 'student', 'target_nim' => $data['mahasiswas'][0]->nim],
    ] as $index => $target) {
        $this->actingAs($data['admin'])->post(route('admin.pengumuman.store'), $target + [
            'penerima' => 'mahasiswa', 'judul' => 'Target KRS '.$index,
            'isi' => 'Informasi terbatas', 'status' => 'terbit',
        ])->assertRedirect();
    }

    expect(Pengumuman::terlihat($data['students'][0])->count())->toBe(4)
        ->and(Pengumuman::terlihat($data['students'][1])->count())->toBe(3)
        ->and(Pengumuman::terlihat($outsideUser)->count())->toBe(0);
});

test('student can cancel a course during admin revision even if attendance records exist', function () {
    $data = makeKrsResetFixture();
    $period = $data['period'];
    $student = $data['mahasiswas'][0];
    $record = $data['records'][0];

    // Reset persetujuan KRS oleh admin
    $this->actingAs($data['admin'])->post(route('admin.periode-krs.reset-persetujuan', $period), [
        'mode' => 'single', 'mahasiswa_id' => $student->id,
        'alasan' => 'Perbaikan KRS karena ingin ganti mata kuliah',
    ])->assertRedirect();

    expect($record->fresh()->status)->toBe('Menunggu')
        ->and($record->fresh()->admin_revision_open)->toBeTrue();

    // Simulasikan perkuliahan sudah berjalan dan ada catatan absensi (presensi)
    \App\Models\Presensi::create([
        'krs_id' => $record->id,
        'tanggal' => now()->toDateString(),
        'pertemuan' => 1,
        'status' => 'Hadir',
        'keterangan' => 'Hadir pertemuan 1',
    ]);
    expect($record->presensis()->exists())->toBeTrue();

    // Mahasiswa membatalkan mata kuliah tersebut
    $this->actingAs($data['students'][0])->delete(route('mahasiswa.krs.destroy', $record->id))
        ->assertRedirect(route('mahasiswa.krs'))
        ->assertSessionHas('success', 'Mata kuliah berhasil dibatalkan dari KRS.');

    expect(Krs::whereKey($record->id)->exists())->toBeFalse()
        ->and(\App\Models\Presensi::where('krs_id', $record->id)->exists())->toBeFalse();
});

