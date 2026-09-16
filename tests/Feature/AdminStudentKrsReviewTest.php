<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PembayaranKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function makeStudentKrsReviewData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $prodi = Prodi::create([
        'kode_prodi' => 'TG-KRS',
        'nama_prodi' => 'Teknik Geologi',
        'jenjang' => 'S1',
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
        'status_bayar' => 'belum_bayar',
    ]));

    $response->assertOk()
        ->assertSee('Windiye Maharani')
        ->assertDontSee('Mahasiswa KRS Lain')
        ->assertSee('3 SKS')
        ->assertSee('Belum Bayar');
    expect($response->viewData('summaries')->total())->toBe(1)
        ->and($response->viewData('summaries')->url(2))->toContain('search=1025207')
        ->and($response->viewData('summaries')->url(2))->toContain('status_bayar=belum_bayar');

    $this->get(route('admin.krs-mahasiswa.show', [
        'mahasiswa' => $data['student'],
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
    ]))
        ->assertOk()
        ->assertSee('Geologi Dinamik')
        ->assertSee('Dosen Wali Dinamis')
        ->assertSee('Ruang KRS');
});

test('admin can record manual KRS payment and filter paid students', function () {
    $data = makeStudentKrsReviewData();

    $this->actingAs($data['admin'])->patch(route('admin.krs-mahasiswa.payment', $data['student']), [
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'semester' => 2,
        'status_bayar' => 'lunas',
        'tanggal_bayar' => '2026-02-14',
        'catatan' => 'Diverifikasi manual oleh admin.',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('pembayaran_krs', [
        'mahasiswa_id' => $data['student']->id,
        'semester' => 2,
        'tahun_akademik' => '2025/2026',
        'semester_akademik' => 'Genap',
        'status_bayar' => 'lunas',
        'diverifikasi_oleh' => $data['admin']->id,
    ]);
    expect(PembayaranKrs::firstOrFail()->tanggal_bayar->toDateString())->toBe('2026-02-14');

    $paid = $this->get(route('admin.krs-mahasiswa.index', ['status_bayar' => 'lunas']));
    $paid->assertOk()->assertSee('Windiye Maharani')->assertDontSee('Mahasiswa KRS Lain');
    expect($paid->viewData('summaries')->total())->toBe(1);
});

test('admin and each student download a dynamic KRS PDF while role access stays isolated', function () {
    $data = makeStudentKrsReviewData();
    $period = ['tahun_akademik' => '2025/2026', 'semester_akademik' => 'Genap'];

    $this->actingAs($data['admin'])
        ->get(route('admin.krs-mahasiswa.pdf', ['mahasiswa' => $data['student']] + $period))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KRS Semester 2-Windiye Maharani-1025207.pdf');

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs.pdf', $period))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KRS Semester 2-Windiye Maharani-1025207.pdf');

    $this->actingAs($data['otherStudentUser'])
        ->get(route('mahasiswa.krs.pdf', $period))
        ->assertOk()
        ->assertDownload('KRS Semester 2-Mahasiswa KRS Lain-1025208.pdf');

    $this->actingAs($data['studentUser'])
        ->get(route('admin.krs-mahasiswa.show', ['mahasiswa' => $data['otherStudent']] + $period))
        ->assertForbidden();
    $this->actingAs($data['lecturerUser'])
        ->get(route('admin.krs-mahasiswa.index'))
        ->assertForbidden();
});
