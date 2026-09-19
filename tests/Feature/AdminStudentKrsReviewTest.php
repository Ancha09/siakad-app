<?php

use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use App\Services\KrsCardService;

function makeStudentKrsReviewData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $fakultas = Fakultas::create([
        'kode_fakultas' => 'FT-KRS',
        'nama_fakultas' => 'Fakultas Teknik KRS',
    ]);
    $prodi = Prodi::create([
        'kode_prodi' => 'TG-KRS',
        'nama_prodi' => 'Teknik Geologi',
        'jenjang' => 'S1',
        'fakultas_id' => $fakultas->id,
        'ketua_program_studi_nama' => 'Ketua Program Studi Teknik Geologi',
        'ketua_program_studi_nip' => '198001012010011001',
    ]);
    $dosen = Dosen::create([
        'nidn' => 'KRS-DOSEN-01',
        'nama' => 'Dosen Wali Dinamis',
        'prodi_id' => $prodi->id,
        'user_id' => $lecturerUser->id,
    ]);
    $kelas = Kelas::create([
        'nama_kelas' => 'Geologi 2025 A',
        'prodi_id' => $prodi->id,
        'angkatan' => '2025',
        'semester' => 2,
        'dosen_wali_id' => $dosen->id,
    ]);
    $student = Mahasiswa::create([
        'nim' => '1025207',
        'nama' => 'Windiye Maharani',
        'angkatan' => 2025,
        'semester' => 2,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $studentUser->id,
    ]);
    $otherStudent = Mahasiswa::create([
        'nim' => '1025208',
        'nama' => 'Mahasiswa KRS Lain',
        'angkatan' => 2025,
        'semester' => 2,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $otherStudentUser->id,
    ]);
    $course = MataKuliah::create([
        'kode_mk' => 'GEO102',
        'nama_mk' => 'Geologi Dinamik',
        'sks' => 3,
        'semester' => 2,
        'prodi_id' => $prodi->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'R-KRS',
        'nama_ruangan' => 'Ruang KRS',
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
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
    ]);

    foreach ([$student, $otherStudent] as $owner) {
        Krs::create([
            'mahasiswa_id' => $owner->id,
            'jadwal_id' => $schedule->id,
            'status' => 'Disetujui',
            'tahun_akademik' => '2025/2026',
            'semester_akademik' => 'Genap',
        ]);
    }

    return compact(
        'admin',
        'studentUser',
        'otherStudentUser',
        'lecturerUser',
        'fakultas',
        'prodi',
        'kelas',
        'student',
        'otherStudent',
        'course'
    );
}

test('admin can filter and open KRS grouped per student and academic period', function () {
    $data = makeStudentKrsReviewData();

    $response = $this->actingAs($data['admin'])->get(route('admin.krs-mahasiswa.index', [
        'search' => '1025207',
        'angkatan' => 2025,
        'prodi_id' => $data['prodi']->id,
        'kelas_id' => $data['kelas']->id,
        'semester' => 2,
        'semester_akademik' => 'Genap',
        'tahun_akademik' => '2025/2026',
    ]));

    $response->assertOk()
        ->assertSee('Windiye Maharani')
        ->assertDontSee('Mahasiswa KRS Lain')
        ->assertSee('3 SKS');
    expect($response->viewData('summaries')->total())->toBe(1)
        ->and($response->viewData('summaries')->url(2))->toContain('search=1025207');

    $this->get(route('admin.krs-mahasiswa.show', [
        'mahasiswa' => $data['student'],
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
    ]))
        ->assertOk()
        ->assertSee('Geologi Dinamik')
        ->assertSee('Dosen Wali Dinamis')
        ->assertSee('Ruang KRS')
        ->assertDontSee('name="nama_ketua_program_studi"', false)
        ->assertDontSee('name="nip_ketua_program_studi"', false);
});

test('admin and each student download a dynamic KRS PDF while role access stays isolated', function () {
    $data = makeStudentKrsReviewData();
    $period = ['tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap'];

    $this->actingAs($data['admin'])
        ->post(route('admin.krs-mahasiswa.pdf', $data['student']), $period)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KRS-1025207-2025-2026-Genap.pdf');

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.pdf'), $period)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KRS-1025207-2025-2026-Genap.pdf');

    $this->actingAs($data['otherStudentUser'])
        ->post(route('mahasiswa.krs.pdf'), $period + ['mahasiswa_id' => $data['student']->id])
        ->assertOk()
        ->assertDownload('KRS-1025208-2025-2026-Genap.pdf');

    $this->actingAs($data['lecturerUser'])
        ->post(route('dosen.krs.pdf', $data['student']), $period)
        ->assertOk()
        ->assertDownload('KRS-1025207-2025-2026-Genap.pdf');

    $otherLecturerUser = User::factory()->create(['role' => 'dosen']);
    Dosen::create([
        'nidn' => 'KRS-DOSEN-02',
        'nama' => 'Dosen Wali Lain',
        'prodi_id' => $data['prodi']->id,
        'user_id' => $otherLecturerUser->id,
    ]);
    $this->actingAs($otherLecturerUser)
        ->post(route('dosen.krs.pdf', $data['student']), $period)
        ->assertNotFound();

    $this->actingAs($data['studentUser'])
        ->get(route('admin.krs-mahasiswa.show', ['mahasiswa' => $data['otherStudent']] + $period))
        ->assertForbidden();
    $this->actingAs($data['lecturerUser'])
        ->get(route('admin.krs-mahasiswa.index'))
        ->assertForbidden();
});

test('student KRS page shows a download button per available period and an empty state without KRS', function () {
    $data = makeStudentKrsReviewData();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Download KRS PDF - 2025/2026 Genap')
        ->assertSee('action="'.route('mahasiswa.krs.pdf').'"', false)
        ->assertDontSee('Belum ada KRS yang dapat diunduh.');

    $emptyUser = User::factory()->create(['role' => 'mahasiswa']);
    Mahasiswa::create([
        'nim' => '1025209',
        'nama' => 'Mahasiswa Tanpa KRS',
        'angkatan' => 2025,
        'semester' => 2,
        'prodi_id' => $data['prodi']->id,
        'kelas_id' => $data['kelas']->id,
        'dosen_wali_id' => $data['student']->dosen_wali_id,
        'user_id' => $emptyUser->id,
    ]);

    $this->actingAs($emptyUser)
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Belum ada KRS yang dapat diunduh.')
        ->assertDontSee('Download KRS PDF -');
});

test('KRS PDF uses the program chair from the student study program and the Word template letterhead', function () {
    $data = makeStudentKrsReviewData();
    $period = ['tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap'];

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.pdf'), $period)
        ->assertOk()
        ->assertDownload('KRS-1025207-2025-2026-Genap.pdf');

    $card = app(KrsCardService::class)->data(
        $data['student'],
        '2025/2026',
        'Genap',
        true
    );

    expect($card['namaKetuaProgramStudi'])->toBe('Ketua Program Studi Teknik Geologi')
        ->and($card['nipKetuaProgramStudi'])->toBe('198001012010011001')
        ->and($card['dosenWali']->nama)->toBe('Dosen Wali Dinamis')
        ->and($card['totalSks'])->toBe(3)
        ->and($card['templateLetterhead'])->toStartWith('data:image/png;base64,');

    $fallbackCard = app(KrsCardService::class)->data($data['student'], '2025/2026', 'Genap');
    $fallbackHtml = view('krs.card-pdf', $fallbackCard)->render();
    expect($fallbackHtml)
        ->toContain('Sekolah Tinggi Teknologi Mineral Indonesia')
        ->not->toContain('Mandala');
});

test('admin can save a program chair and old study programs without one keep working', function () {
    $data = makeStudentKrsReviewData();

    $this->actingAs($data['admin'])
        ->put(route('admin.prodi.update', $data['prodi']), [
            'kode_prodi' => $data['prodi']->kode_prodi,
            'nama_prodi' => $data['prodi']->nama_prodi,
            'jenjang' => $data['prodi']->jenjang,
            'fakultas_id' => $data['fakultas']->id,
            'ketua_program_studi_nama' => 'Ketua Prodi Baru',
            'ketua_program_studi_nip' => 'NIP-KAPRODI-02',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('prodis', [
        'id' => $data['prodi']->id,
        'ketua_program_studi_nama' => 'Ketua Prodi Baru',
        'ketua_program_studi_nip' => 'NIP-KAPRODI-02',
    ]);

    $data['prodi']->update([
        'ketua_program_studi_nama' => null,
        'ketua_program_studi_nip' => null,
    ]);
    $data['student']->unsetRelation('prodi');

    $card = app(KrsCardService::class)->data($data['student'], '2025/2026', 'Genap');
    expect($card['namaKetuaProgramStudi'])->toBeNull()
        ->and($card['nipKetuaProgramStudi'])->toBeNull()
        ->and(view('krs.card-pdf', $card)->render())->toContain('(....................................)');

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.pdf'), [
            'tahun_akademik' => '2025/2026',
            'semester_akademik' => 'Genap',
        ])
        ->assertOk()
        ->assertDownload('KRS-1025207-2025-2026-Genap.pdf');
});
