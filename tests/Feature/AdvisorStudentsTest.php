<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;

function makeAdvisorStudentsData(): array
{
    $advisorUser = User::factory()->create(['role' => 'dosen']);
    $otherAdvisorUser = User::factory()->create(['role' => 'dosen']);
    $advisor = Dosen::create([
        'nidn' => 'WALI-STUDENT-01',
        'nama' => 'Dosen Wali Akademik',
        'user_id' => $advisorUser->id,
    ]);
    $otherAdvisor = Dosen::create([
        'nidn' => 'WALI-STUDENT-02',
        'nama' => 'Dosen Wali Lain',
        'user_id' => $otherAdvisorUser->id,
    ]);
    $prodi = Prodi::create([
        'kode_prodi' => 'IF-WALI',
        'nama_prodi' => 'Informatika Wali',
        'jenjang' => 'S1',
    ]);
    $advisor->update(['prodi_id' => $prodi->id]);
    $otherAdvisor->update(['prodi_id' => $prodi->id]);

    $student = Mahasiswa::create([
        'nim' => 'WALI001',
        'nama' => 'Andi Mahasiswa Wali',
        'angkatan' => 2026,
        'semester' => 2,
        'prodi_id' => $prodi->id,
        'dosen_wali_id' => $advisor->id,
    ]);
    $otherStudent = Mahasiswa::create([
        'nim' => 'LAIN001',
        'nama' => 'Budi Bukan Mahasiswa Wali',
        'angkatan' => 2026,
        'semester' => 2,
        'prodi_id' => $prodi->id,
        'dosen_wali_id' => $otherAdvisor->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'R-WALI',
        'nama_ruangan' => 'Ruang Wali',
        'kapasitas' => 30,
    ]);

    $createGrade = function (
        Mahasiswa $owner,
        string $code,
        string $name,
        int $semester,
        int $sks,
        float $score,
        string $letter,
        float $weight
    ) use ($advisor, $prodi, $room): Khs {
        $course = MataKuliah::create([
            'kode_mk' => $code,
            'nama_mk' => $name,
            'semester' => $semester,
            'sks' => $sks,
            'prodi_id' => $prodi->id,
        ]);
        $schedule = Jadwal::create([
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $advisor->id,
            'ruangan_id' => $room->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tahun_akademik' => $semester === 1 ? '2026/2027' : '2027/2028',
            'semester_akademik' => $semester % 2 === 1 ? 'Ganjil' : 'Genap',
        ]);
        $krs = Krs::create([
            'mahasiswa_id' => $owner->id,
            'jadwal_id' => $schedule->id,
            'status' => 'Disetujui',
            'tahun_akademik' => $schedule->tahun_akademik,
            'semester_akademik' => $schedule->semester_akademik,
        ]);

        return Khs::create([
            'krs_id' => $krs->id,
            'nilai_angka' => $score,
            'nilai_huruf' => $letter,
            'bobot' => $weight,
            'sks' => $sks,
            'tahun_akademik' => $schedule->tahun_akademik,
            'semester_akademik' => $schedule->semester_akademik,
        ]);
    };

    $firstGrade = $createGrade($student, 'WAL101', 'Dasar Perwalian', 1, 2, 65, 'C', 2);
    $createGrade($student, 'WAL202', 'Lanjut Perwalian', 2, 3, 90, 'A', 4);
    $createGrade($otherStudent, 'WAL303', 'Data Mahasiswa Lain', 1, 2, 90, 'A', 4);

    return compact(
        'advisorUser',
        'otherAdvisorUser',
        'advisor',
        'otherAdvisor',
        'student',
        'otherStudent',
        'prodi',
        'firstGrade'
    );
}

test('advisor student index only shows assigned students and preserves filters', function () {
    $data = makeAdvisorStudentsData();

    $response = $this->actingAs($data['advisorUser'])->get(route('dosen.mahasiswa-wali', [
        'search' => 'Andi',
        'prodi_id' => $data['prodi']->id,
        'angkatan' => 2026,
        'semester' => 2,
    ]));

    $response->assertOk()
        ->assertSee('Andi Mahasiswa Wali')
        ->assertSee('WALI001')
        ->assertSee('Informatika Wali')
        ->assertSee('Lihat Detail')
        ->assertDontSee('Budi Bukan Mahasiswa Wali');

    $paginator = $response->viewData('mahasiswas');
    expect($paginator->total())->toBe(1)
        ->and($paginator->url(2))->toContain('search=Andi', 'prodi_id='.$data['prodi']->id, 'angkatan=2026', 'semester=2');
});

test('advisor sees current transcript grouped per semester and updated grades immediately', function () {
    $data = makeAdvisorStudentsData();
    $url = route('dosen.mahasiswa-wali.show', [
        'mahasiswa' => $data['student'],
        'return_url' => route('dosen.mahasiswa-wali', ['search' => 'Andi', 'page' => 2]),
    ]);

    $response = $this->actingAs($data['advisorUser'])->get($url);
    $response->assertOk()
        ->assertSee('Semester 1')
        ->assertSee('Semester 2')
        ->assertSee('Dasar Perwalian')
        ->assertSee('Lanjut Perwalian')
        ->assertSee('Riwayat KRS')
        ->assertSee('2026/2027')
        ->assertSee('2027/2028');

    expect($response->viewData('ipsPerSemester')[1])->toBe(2.0)
        ->and($response->viewData('ipsPerSemester')[2])->toBe(4.0)
        ->and($response->viewData('ipk'))->toBe(3.2)
        ->and($response->viewData('totalSks'))->toBe(5)
        ->and($response->viewData('listUrl'))->toContain('search=Andi', 'page=2');

    $data['firstGrade']->update([
        'nilai_angka' => 90,
        'nilai_huruf' => 'A',
        'bobot' => 4,
    ]);

    $updated = $this->actingAs($data['advisorUser'])->get($url);
    expect($updated->viewData('ipsPerSemester')[1])->toBe(4.0)
        ->and($updated->viewData('ipk'))->toBe(4.0);
});

test('advisor cannot access another advisors student detail by changing the URL', function () {
    $data = makeAdvisorStudentsData();

    $this->actingAs($data['advisorUser'])
        ->get(route('dosen.mahasiswa-wali.show', $data['otherStudent']))
        ->assertForbidden();

    $this->actingAs($data['otherAdvisorUser'])
        ->get(route('dosen.mahasiswa-wali.show', $data['student']))
        ->assertForbidden();
});
