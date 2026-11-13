<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function makeDosenPendampingFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);

    $programA = Prodi::create([
        'kode_prodi' => 'TI',
        'nama_prodi' => 'Teknik Informatika',
        'jenjang' => 'S1',
    ]);
    $programB = Prodi::create([
        'kode_prodi' => 'SI',
        'nama_prodi' => 'Sistem Informasi',
        'jenjang' => 'S1',
    ]);

    $dosenUser1 = User::factory()->create(['role' => 'dosen']);
    $dosenUser2 = User::factory()->create(['role' => 'dosen']);
    $dosenUser3 = User::factory()->create(['role' => 'dosen']);

    $dosenA = Dosen::create([
        'nidn' => 'DOSEN-A',
        'nama' => 'Dosen Utama A',
        'prodi_id' => $programA->id,
        'user_id' => $dosenUser1->id,
    ]);

    $dosenB = Dosen::create([
        'nidn' => 'DOSEN-B',
        'nama' => 'Dosen Utama B',
        'prodi_id' => $programB->id,
        'user_id' => $dosenUser2->id,
    ]);

    $dosenC = Dosen::create([
        'nidn' => 'DOSEN-C',
        'nama' => 'Dosen Pendamping C',
        'prodi_id' => $programA->id,
        'user_id' => $dosenUser3->id,
    ]);

    $room1 = Ruangan::create([
        'kode_ruangan' => 'R-101',
        'nama_ruangan' => 'Ruang 101',
        'kapasitas' => 40,
    ]);

    $room2 = Ruangan::create([
        'kode_ruangan' => 'R-102',
        'nama_ruangan' => 'Ruang 102',
        'kapasitas' => 40,
    ]);

    $courseA = MataKuliah::create([
        'kode_mk' => 'TI-101',
        'nama_mk' => 'Algoritma & Pemrograman',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $programA->id,
    ]);

    $courseB = MataKuliah::create([
        'kode_mk' => 'SI-101',
        'nama_mk' => 'Pengantar Sistem Informasi',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $programB->id,
    ]);

    return compact('admin', 'programA', 'programB', 'dosenA', 'dosenB', 'dosenC', 'room1', 'room2', 'courseA', 'courseB');
}

test('dosen pendamping boleh bentrok dengan dosen pendamping lain pada hari dan jam yang sama', function () {
    $data = makeDosenPendampingFixture();

    // Jadwal 1: Dosen Utama = Dosen A, Dosen Pendamping = Dosen C, Ruangan = R-101
    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenA']->id,
            'dosen_pendamping_ids' => [$data['dosenC']->id],
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasNoErrors();

    // Jadwal 2: Dosen Utama = Dosen B, Dosen Pendamping = Dosen C (SAMA), Ruangan = R-102 (BEDA), Jam sama
    // Harusnya BERHASIL disimpan (tidak diblokir bentrok)
    $this->post(route('admin.jadwal.store'), [
        'mata_kuliah_id' => $data['courseB']->id,
        'dosen_id' => $data['dosenB']->id,
        'dosen_pendamping_ids' => [$data['dosenC']->id],
        'ruangan_id' => $data['room2']->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 0,
    ])
    ->assertSessionHasNoErrors();

    expect(Jadwal::count())->toBe(2);

    $jadwal2 = Jadwal::with('dosens')->latest('id')->first();
    expect($jadwal2->dosens->pluck('id')->all())->toContain($data['dosenB']->id, $data['dosenC']->id);
});

test('dosen pendamping boleh bentrok jika dosen tersebut merupakan dosen utama di jadwal lain', function () {
    $data = makeDosenPendampingFixture();

    // Jadwal 1: Dosen C adalah Dosen Utama di Ruangan 101
    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenC']->id,
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasNoErrors();

    // Jadwal 2: Dosen Utama = Dosen B, tapi Dosen C ditambahkan sebagai Dosen Pendamping
    // Harusnya BERHASIL disimpan (Dosen C sebagai pendamping tidak diblokir)
    $this->post(route('admin.jadwal.store'), [
        'mata_kuliah_id' => $data['courseB']->id,
        'dosen_id' => $data['dosenB']->id,
        'dosen_pendamping_ids' => [$data['dosenC']->id],
        'ruangan_id' => $data['room2']->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 0,
    ])
    ->assertSessionHasNoErrors();

    expect(Jadwal::count())->toBe(2);
});

test('rule aman: dosen utama tetap TIDAK BOLEH bentrok dengan dosen utama lain pada hari dan jam yang sama', function () {
    $data = makeDosenPendampingFixture();

    // Jadwal 1: Dosen Utama = Dosen A di Ruangan 1
    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenA']->id,
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasNoErrors();

    // Jadwal 2: Dosen Utama = Dosen A (SAMA), Ruangan 2 (BEDA), Jam sama
    // Harusnya DITOLAK / ERROR bentrok dosen
    $this->post(route('admin.jadwal.store'), [
        'mata_kuliah_id' => $data['courseB']->id,
        'dosen_id' => $data['dosenA']->id,
        'ruangan_id' => $data['room2']->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 0,
    ])
    ->assertSessionHasErrors('jam_mulai');

    expect(Jadwal::count())->toBe(1);
});

test('rule aman: ruangan tetap TIDAK BOLEH bentrok pada hari dan jam yang sama', function () {
    $data = makeDosenPendampingFixture();

    // Jadwal 1: Dosen A di Ruang 101
    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenA']->id,
            'dosen_pendamping_ids' => [$data['dosenC']->id],
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasNoErrors();

    // Jadwal 2: Dosen B (BEDA) tapi Ruangan 101 (SAMA), Jam sama
    // Harusnya DITOLAK / ERROR bentrok ruangan
    $this->post(route('admin.jadwal.store'), [
        'mata_kuliah_id' => $data['courseB']->id,
        'dosen_id' => $data['dosenB']->id,
        'ruangan_id' => $data['room1']->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '10:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 0,
    ])
    ->assertSessionHasErrors('jam_mulai');

    expect(Jadwal::count())->toBe(1);
});

test('rule aman: dosen pendamping tidak boleh sama dengan dosen utama dalam jadwal yang sama', function () {
    $data = makeDosenPendampingFixture();

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenA']->id,
            'dosen_pendamping_ids' => [$data['dosenA']->id],
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasErrors('dosen_pendamping_ids.0');

    expect(Jadwal::count())->toBe(0);
});

test('admin dapat memperbarui jadwal dengan dosen pendamping tanpa memicu bentrok palsu', function () {
    $data = makeDosenPendampingFixture();

    $this->actingAs($data['admin'])
        ->post(route('admin.jadwal.store'), [
            'mata_kuliah_id' => $data['courseA']->id,
            'dosen_id' => $data['dosenA']->id,
            'dosen_pendamping_ids' => [$data['dosenC']->id],
            'ruangan_id' => $data['room1']->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '10:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
            'is_lintas_prodi' => 0,
        ])
        ->assertSessionHasNoErrors();

    $jadwal = Jadwal::first();

    $this->put(route('admin.jadwal.update', $jadwal->id), [
        'mata_kuliah_id' => $data['courseA']->id,
        'dosen_id' => $data['dosenA']->id,
        'dosen_pendamping_ids' => [$data['dosenC']->id],
        'ruangan_id' => $data['room1']->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '11:00', // diubah durasinya
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_lintas_prodi' => 0,
    ])
    ->assertSessionHasNoErrors();

    $jadwal->refresh();
    expect($jadwal->jam_selesai)->toBe('11:00:00');
});
