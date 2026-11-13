<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\PresensiPertemuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function presensiEditFixture(): array
{
    $user = User::factory()->create(['role' => 'dosen']);
    $dosen = Dosen::create([
        'nidn' => 'EDIT-DOS-' . uniqid(),
        'nama' => 'Dosen Pengampu Edit',
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    $mhs1 = Mahasiswa::create([
        'nim' => 'MHS-ED-01',
        'nama' => 'Mahasiswa Edit Satu',
        'is_active' => true,
    ]);

    $mhs2 = Mahasiswa::create([
        'nim' => 'MHS-ED-02',
        'nama' => 'Mahasiswa Edit Dua',
        'is_active' => true,
    ]);

    $jadwal = Jadwal::create([
        'dosen_id' => $dosen->id,
        'hari' => 'Selasa',
        'jam_mulai' => '10:00:00',
        'jam_selesai' => '12:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $krs1 = Krs::create([
        'mahasiswa_id' => $mhs1->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_manual' => false,
    ]);

    $krs2 = Krs::create([
        'mahasiswa_id' => $mhs2->id,
        'jadwal_id' => $jadwal->id,
        'status' => 'Disetujui',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
        'is_manual' => false,
    ]);

    return compact('user', 'dosen', 'jadwal', 'krs1', 'krs2');
}

test('dosen can view editable form when meeting already exists', function () {
    ['user' => $user, 'jadwal' => $jadwal, 'krs1' => $krs1] = presensiEditFixture();

    PresensiPertemuan::create([
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 1,
        'tanggal' => '2026-10-01',
        'materi_kuliah' => 'Materi Pertemuan Awal',
        'keterangan' => 'Keterangan Pertemuan Awal',
        'foto' => 'presensi/foto/awal.jpg',
    ]);

    $response = $this->actingAs($user)->get(route('dosen.presensi.show', [
        'jadwal' => $jadwal->id,
        'pertemuan' => 1,
    ]));

    $response->assertOk();
    $response->assertSee('Edit Pokok Bahasan &amp; Dokumentasi Pertemuan 1', false);
    $response->assertSee('Materi Pertemuan Awal');
    $response->assertSee('Keterangan Pertemuan Awal');
    $response->assertSee('Simpan Perubahan Pertemuan 1');
});

test('dosen can update discussion topic notes and meeting date for existing saved meeting', function () {
    Storage::fake('public');
    ['user' => $user, 'jadwal' => $jadwal, 'krs1' => $krs1, 'krs2' => $krs2] = presensiEditFixture();

    $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 2,
        'tanggal' => '2026-10-02',
        'materi_kuliah' => 'Materi Sebelum Diedit',
        'keterangan' => 'Keterangan Sebelum Diedit',
        'foto' => UploadedFile::fake()->image('foto1.jpg'),
        'krs_id' => [$krs1->id, $krs2->id],
        'status' => ['Hadir', 'Hadir'],
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('presensi_pertemuans', [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 2,
        'materi_kuliah' => 'Materi Sebelum Diedit',
        'keterangan' => 'Keterangan Sebelum Diedit',
        'tanggal' => '2026-10-02',
    ]);

    // Sekarang Dosen mengedit pokok bahasan (materi kuliah), keterangan, dan tanggal
    $response = $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 2,
        'tanggal' => '2026-10-05',
        'materi_kuliah' => 'Materi Baru Telah Diedit - Karakteristik Batuan Tambang',
        'keterangan' => 'Keterangan Baru Telah Diedit - Sesi Praktikum Laboratorium',
        'krs_id' => [$krs1->id, $krs2->id],
        'status' => ['Hadir', 'Izin'],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dosen.presensi.show', ['jadwal' => $jadwal->id, 'pertemuan' => 2]));

    $this->assertDatabaseHas('presensi_pertemuans', [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 2,
        'tanggal' => '2026-10-05',
        'materi_kuliah' => 'Materi Baru Telah Diedit - Karakteristik Batuan Tambang',
        'keterangan' => 'Keterangan Baru Telah Diedit - Sesi Praktikum Laboratorium',
    ]);

    $this->assertDatabaseHas('presensis', [
        'krs_id' => $krs1->id,
        'pertemuan' => 2,
        'status' => 'Hadir',
    ]);

    $this->assertDatabaseHas('presensis', [
        'krs_id' => $krs2->id,
        'pertemuan' => 2,
        'status' => 'Izin',
    ]);

    $presensi1 = Presensi::where('krs_id', $krs1->id)->where('pertemuan', 2)->first();
    expect($presensi1->tanggal->format('Y-m-d'))->toBe('2026-10-05');
});

test('dosen can replace documentation photo and material file on existing meeting', function () {
    Storage::fake('public');
    ['user' => $user, 'jadwal' => $jadwal, 'krs1' => $krs1] = presensiEditFixture();

    $fotoAwal = UploadedFile::fake()->image('foto_awal.jpg');
    $materiAwal = UploadedFile::fake()->create('slide_awal.pdf', 500, 'application/pdf');

    $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 3,
        'tanggal' => '2026-10-08',
        'materi_kuliah' => 'Geologi Struktur',
        'keterangan' => 'Kuliah Tatap Muka',
        'foto' => $fotoAwal,
        'materi' => $materiAwal,
        'krs_id' => [$krs1->id],
        'status' => ['Hadir'],
    ])->assertSessionHasNoErrors();

    $pertemuan = PresensiPertemuan::where('jadwal_id', $jadwal->id)->where('pertemuan', 3)->first();
    $pathFotoAwal = $pertemuan->foto;
    $pathMateriAwal = $pertemuan->materi;

    Storage::disk('public')->assertExists($pathFotoAwal);
    Storage::disk('public')->assertExists($pathMateriAwal);

    // Ganti foto dan ganti file materi
    $fotoBaru = UploadedFile::fake()->image('foto_revisi.png');
    $materiBaru = UploadedFile::fake()->create('slide_revisi.pdf', 700, 'application/pdf');

    $response = $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 3,
        'tanggal' => '2026-10-08',
        'materi_kuliah' => 'Geologi Struktur (Revisi Bahasan)',
        'keterangan' => 'Kuliah Tatap Muka & Diskusi Kelompok',
        'foto' => $fotoBaru,
        'materi' => $materiBaru,
        'krs_id' => [$krs1->id],
        'status' => ['Hadir'],
    ]);

    $response->assertSessionHasNoErrors();

    $pertemuan->refresh();

    // Foto lama dan file materi lama terhapus dari storage
    Storage::disk('public')->assertMissing($pathFotoAwal);
    Storage::disk('public')->assertMissing($pathMateriAwal);

    // File baru tersimpan dan path diperbarui di database
    Storage::disk('public')->assertExists($pertemuan->foto);
    Storage::disk('public')->assertExists($pertemuan->materi);
    expect($pertemuan->materi_kuliah)->toBe('Geologi Struktur (Revisi Bahasan)');
    expect($pertemuan->keterangan)->toBe('Kuliah Tatap Muka & Diskusi Kelompok');
});

test('existing photo and material remain intact if dosen only updates topic or status without reuploading files', function () {
    Storage::fake('public');
    ['user' => $user, 'jadwal' => $jadwal, 'krs1' => $krs1] = presensiEditFixture();

    $fotoAwal = UploadedFile::fake()->image('dokumentasi_asli.jpg');

    $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 4,
        'tanggal' => '2026-10-15',
        'materi_kuliah' => 'Topik 4 Awal',
        'keterangan' => 'Catatan 4 Awal',
        'foto' => $fotoAwal,
        'krs_id' => [$krs1->id],
        'status' => ['Hadir'],
    ])->assertSessionHasNoErrors();

    $pertemuan = PresensiPertemuan::where('jadwal_id', $jadwal->id)->where('pertemuan', 4)->first();
    $pathFotoAwal = $pertemuan->foto;

    // Update hanya topik dan status presensi tanpa menyertakan foto
    $response = $this->actingAs($user)->post(route('dosen.presensi.store'), [
        'jadwal_id' => $jadwal->id,
        'pertemuan' => 4,
        'tanggal' => '2026-10-15',
        'materi_kuliah' => 'Topik 4 Telah Diperbaiki Bahasannya',
        'keterangan' => 'Catatan 4 Telah Ditambahkan',
        'krs_id' => [$krs1->id],
        'status' => ['Sakit'],
    ]);

    $response->assertSessionHasNoErrors();

    $pertemuan->refresh();
    expect($pertemuan->foto)->toBe($pathFotoAwal);
    Storage::disk('public')->assertExists($pathFotoAwal);
    expect($pertemuan->materi_kuliah)->toBe('Topik 4 Telah Diperbaiki Bahasannya');
    expect($pertemuan->keterangan)->toBe('Catatan 4 Telah Ditambahkan');

    $this->assertDatabaseHas('presensis', [
        'krs_id' => $krs1->id,
        'pertemuan' => 4,
        'status' => 'Sakit',
    ]);
});
