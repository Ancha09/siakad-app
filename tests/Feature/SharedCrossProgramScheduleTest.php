<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function makeSharedScheduleFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $programA = Prodi::create([
        'kode_prodi' => 'LPA',
        'nama_prodi' => 'Lintas Prodi A',
        'jenjang' => 'S1',
    ]);
    $programB = Prodi::create([
        'kode_prodi' => 'LPB',
        'nama_prodi' => 'Lintas Prodi B',
        'jenjang' => 'S1',
    ]);
    $lecturer = Dosen::create([
        'nidn' => 'LINTAS-001',
        'nama' => 'Dosen Lintas Prodi',
        'prodi_id' => $programA->id,
        'user_id' => $lecturerUser->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'LP-01',
        'nama_ruangan' => 'Ruang Lintas Prodi',
        'kapasitas' => 60,
    ]);
    $courseA = MataKuliah::create([
        'kode_mk' => 'TG-101',
        'nama_mk' => 'Matematika Lintas A',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $programA->id,
    ]);
    $courseB = MataKuliah::create([
        'kode_mk' => 'TP-101',
        'nama_mk' => 'Matematika Lintas B',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $programB->id,
    ]);

    return compact('admin', 'programA', 'programB', 'lecturer', 'room', 'courseA', 'courseB');
}

function sharedSchedulePayload(array $data, int $courseId, array $overrides = []): array
{
    return array_merge([
        'mata_kuliah_id' => $courseId,
        'dosen_id' => $data['lecturer']->id,
        'ruangan_id' => $data['room']->id,
        'hari' => 'Selasa',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 1,
        'group_key' => 'GAB-LINTAS-01',
    ], $overrides);
}

test('admin can mark a course as common for all study programs without a new table', function () {
    $data = makeSharedScheduleFixture();

    $this->actingAs($data['admin'])
        ->post(route('admin.matakuliah.store'), [
            'kode_mk' => 'MKU-ADMIN',
            'nama_mk' => 'Mata Kuliah Umum Admin',
            'sks' => 2,
            'semester' => 1,
            'prodi_id' => '',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('mata_kuliahs', [
        'kode_mk' => 'MKU-ADMIN',
        'prodi_id' => null,
    ]);
});

test('admin can store separate course codes in one shared cross program schedule', function () {
    $data = makeSharedScheduleFixture();

    $this->actingAs($data['admin'])
        ->get(route('admin.jadwal.create'))
        ->assertOk()
        ->assertSee('Ketik kode atau nama mata kuliah')
        ->assertSee('name="is_lintas_prodi"', false)
        ->assertSee('name="group_key"', false);

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseA']->id))
        ->assertSessionHasNoErrors();

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseB']->id))
        ->assertSessionHasNoErrors();

    $rows = Jadwal::query()->where('group_key', 'GAB-LINTAS-01')->orderBy('mata_kuliah_id')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('mata_kuliah_id')->all())->toContain($data['courseA']->id, $data['courseB']->id)
        ->and($rows->every(fn (Jadwal $schedule) => $schedule->is_lintas_prodi))->toBeTrue();

    $this->get(route('admin.jadwal.edit', $rows->first()))
        ->assertOk()
        ->assertSee('GAB-LINTAS-01')
        ->assertSee('TG-101 - Matematika Lintas A (3 SKS)');

    $this->get(route('admin.jadwal'))
        ->assertOk()
        ->assertSee('Lintas Prodi')
        ->assertSee('Kelas Gabungan')
        ->assertSee('GAB-LINTAS-01');
});

test('blank shared group keys are generated consistently for identical sessions', function () {
    $data = makeSharedScheduleFixture();

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseA']->id, ['group_key' => '']))
        ->assertSessionHasNoErrors();

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseB']->id, ['group_key' => '']))
        ->assertSessionHasNoErrors();

    $groups = Jadwal::query()->orderBy('id')->pluck('group_key');

    expect($groups)->toHaveCount(2)
        ->and($groups->first())->toStartWith('GAB-')
        ->and($groups->unique())->toHaveCount(1);
});

test('ordinary conflicts remain blocked and a shared group cannot describe different sessions', function () {
    $data = makeSharedScheduleFixture();

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseA']->id))
        ->assertSessionHasNoErrors();

    $regularCourse = MataKuliah::create([
        'kode_mk' => 'REG-101',
        'nama_mk' => 'Jadwal Reguler Bentrok',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
    ]);

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $regularCourse->id, [
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]))->assertSessionHasErrors('jam_mulai');

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseB']->id, [
        'jam_mulai' => '13:00',
        'jam_selesai' => '15:00',
    ]))->assertSessionHasErrors('group_key');

    expect(Jadwal::count())->toBe(1);
});

test('regular schedules may overlap across programs but not inside the same program', function () {
    $data = makeSharedScheduleFixture();
    $regularA = sharedSchedulePayload($data, $data['courseA']->id, [
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]);
    $regularB = sharedSchedulePayload($data, $data['courseB']->id, [
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]);

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), $regularA)
        ->assertSessionHasNoErrors();
    $this->post(route('admin.jadwal.store'), $regularB)
        ->assertSessionHasNoErrors();

    $sameProgramCourse = MataKuliah::create([
        'kode_mk' => 'TG-102',
        'nama_mk' => 'Bentrok Dalam Prodi A',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
    ]);

    $this->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $sameProgramCourse->id, [
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]))->assertSessionHasErrors('jam_mulai');

    expect(Jadwal::count())->toBe(2);
});

test('students only see the shared schedule course code belonging to their program', function () {
    $data = makeSharedScheduleFixture();

    foreach ([$data['courseA'], $data['courseB']] as $course) {
        Jadwal::create(sharedSchedulePayload($data, $course->id));
    }

    $generalCourse = MataKuliah::create([
        'kode_mk' => 'MKU-101',
        'nama_mk' => 'Mata Kuliah Umum',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => null,
    ]);
    Jadwal::create(sharedSchedulePayload($data, $generalCourse->id, [
        'hari' => 'Kamis',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]));

    PeriodeKrs::create([
        'tahun_akademik' => '2026/2027',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
        'access_mode' => 'all',
    ]);

    $studentUserA = User::factory()->create(['role' => 'mahasiswa']);
    $studentUserB = User::factory()->create(['role' => 'mahasiswa']);
    Mahasiswa::create([
        'nim' => 'LPA-001',
        'nama' => 'Mahasiswa Lintas A',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
        'user_id' => $studentUserA->id,
    ]);
    Mahasiswa::create([
        'nim' => 'LPB-001',
        'nama' => 'Mahasiswa Lintas B',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $data['programB']->id,
        'user_id' => $studentUserB->id,
    ]);

    $responseA = $this->actingAs($studentUserA)->get(route('mahasiswa.krs'));
    $responseB = $this->actingAs($studentUserB)->get(route('mahasiswa.krs'));
    $coursesA = $responseA->assertOk()->viewData('jadwals')->pluck('mataKuliah.kode_mk');
    $coursesB = $responseB->assertOk()->viewData('jadwals')->pluck('mataKuliah.kode_mk');

    expect($coursesA)->toContain('TG-101', 'MKU-101')->not->toContain('TP-101')
        ->and($coursesB)->toContain('TP-101', 'MKU-101')->not->toContain('TG-101');
    $responseA->assertSee('Lintas Prodi')->assertSee('MKU')->assertSee('Lintas Prodi A');
    $responseB->assertSee('Lintas Prodi')->assertSee('MKU')->assertSee('Lintas Prodi B');
});

test('student KRS rejects overlapping courses and accepts a non conflicting course', function () {
    $data = makeSharedScheduleFixture();
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $student = Mahasiswa::create([
        'nim' => 'BENTROK-001',
        'nama' => 'Mahasiswa Cek Bentrok',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
        'user_id' => $studentUser->id,
    ]);
    $overlapCourse = MataKuliah::create([
        'kode_mk' => 'TG-OVERLAP',
        'nama_mk' => 'Mata Kuliah Bertabrakan',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
    ]);
    $safeCourse = MataKuliah::create([
        'kode_mk' => 'TG-SAFE',
        'nama_mk' => 'Mata Kuliah Tidak Bentrok',
        'sks' => 2,
        'semester' => 1,
        'prodi_id' => $data['programA']->id,
    ]);
    $firstSchedule = Jadwal::create(sharedSchedulePayload($data, $data['courseA']->id, [
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]));
    $overlapSchedule = Jadwal::create(sharedSchedulePayload($data, $overlapCourse->id, [
        'jam_mulai' => '09:30',
        'jam_selesai' => '11:00',
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]));
    $safeSchedule = Jadwal::create(sharedSchedulePayload($data, $safeCourse->id, [
        'jam_mulai' => '11:00',
        'jam_selesai' => '13:00',
        'is_lintas_prodi' => 0,
        'group_key' => null,
    ]));
    PeriodeKrs::create([
        'tahun_akademik' => '2026/2027',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
        'access_mode' => 'all',
    ]);

    $this->actingAs($studentUser)
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $firstSchedule->id])
        ->assertSessionHas('success');
    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $overlapSchedule->id])
        ->assertSessionHas(
            'error',
            'Terdapat jadwal mata kuliah yang bentrok: Matematika Lintas A dan Mata Kuliah Bertabrakan.'
        );
    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $safeSchedule->id])
        ->assertSessionHas('success');

    expect(Krs::where('mahasiswa_id', $student->id)->count())->toBe(2);
    $this->assertDatabaseMissing('krs', [
        'mahasiswa_id' => $student->id,
        'jadwal_id' => $overlapSchedule->id,
    ]);
});

test('adding a shared schedule does not change historical KRS and KHS course values', function () {
    $data = makeSharedScheduleFixture();
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $student = Mahasiswa::create([
        'nim' => 'HIST-001',
        'nama' => 'Mahasiswa Historis',
        'angkatan' => 2022,
        'semester' => 8,
        'prodi_id' => $data['programA']->id,
        'user_id' => $studentUser->id,
    ]);
    $historicalSchedule = Jadwal::create(sharedSchedulePayload($data, $data['courseA']->id, [
        'hari' => 'Senin',
        'jam_mulai' => '10:00',
        'jam_selesai' => '12:00',
        'tahun_akademik' => '2022/2023',
        'group_key' => null,
        'is_lintas_prodi' => 0,
    ]));
    $krs = Krs::create([
        'mahasiswa_id' => $student->id,
        'jadwal_id' => $historicalSchedule->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2022/2023',
        'semester_akademik' => 'Ganjil',
    ]);
    $khs = Khs::create([
        'krs_id' => $krs->id,
        'nilai_angka' => 88,
        'nilai_huruf' => 'A',
        'bobot' => 4,
        'sks' => 3,
        'tahun_akademik' => '2022/2023',
        'semester_akademik' => 'Ganjil',
    ]);

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), sharedSchedulePayload($data, $data['courseB']->id))
        ->assertSessionHasNoErrors();

    expect($krs->fresh()->jadwal_id)->toBe($historicalSchedule->id)
        ->and($krs->fresh()->mata_kuliah_efektif->id)->toBe($data['courseA']->id)
        ->and((float) $khs->fresh()->nilai_angka)->toBe(88.0)
        ->and($khs->fresh()->nilai_huruf)->toBe('A')
        ->and((float) $khs->fresh()->bobot)->toBe(4.0);
});
