<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function makeAdvisorKrsApprovalFlowData(): array
{
    $advisorUser = User::factory()->create(['role' => 'dosen']);
    $otherAdvisorUser = User::factory()->create(['role' => 'dosen']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::create([
        'kode_prodi' => 'IF-APPROVAL',
        'nama_prodi' => 'Informatika Persetujuan KRS',
        'jenjang' => 'S1',
    ]);
    $advisor = Dosen::create([
        'nidn' => 'WALI-APPROVAL-01',
        'nama' => 'Dosen Wali Utama',
        'prodi_id' => $prodi->id,
        'user_id' => $advisorUser->id,
    ]);
    $otherAdvisor = Dosen::create([
        'nidn' => 'WALI-APPROVAL-02',
        'nama' => 'Dosen Wali Lain',
        'prodi_id' => $prodi->id,
        'user_id' => $otherAdvisorUser->id,
    ]);
    $student = Mahasiswa::create([
        'nim' => '270001',
        'nama' => 'Mahasiswa Bimbingan Utama',
        'angkatan' => 2027,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'dosen_wali_id' => $advisor->id,
        'user_id' => $studentUser->id,
    ]);
    $otherStudent = Mahasiswa::create([
        'nim' => '270002',
        'nama' => 'Mahasiswa Bimbingan Dosen Lain',
        'angkatan' => 2027,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'dosen_wali_id' => $otherAdvisor->id,
        'user_id' => $otherStudentUser->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'R-APPROVAL',
        'nama_ruangan' => 'Ruang Persetujuan',
        'kapasitas' => 30,
    ]);

    $createKrs = function (Mahasiswa $owner, string $code, string $name, int $sks) use ($advisor, $room, $prodi): Krs {
        $course = MataKuliah::create([
            'kode_mk' => $code,
            'nama_mk' => $name,
            'sks' => $sks,
            'semester' => 1,
            'prodi_id' => $prodi->id,
        ]);
        $schedule = Jadwal::create([
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $advisor->id,
            'ruangan_id' => $room->id,
            'kelas_id' => null,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tahun_akademik' => '2027/2028',
            'semester_akademik' => 'Ganjil',
        ]);

        return Krs::create([
            'mahasiswa_id' => $owner->id,
            'jadwal_id' => $schedule->id,
            'status' => 'Menunggu',
            'tahun_akademik' => '2027/2028',
            'semester_akademik' => 'Ganjil',
        ]);
    };

    $firstKrs = $createKrs($student, 'APP101', 'Algoritma Persetujuan', 3);
    $secondKrs = $createKrs($student, 'APP102', 'Basis Data Persetujuan', 2);
    $otherKrs = $createKrs($otherStudent, 'APP103', 'Jaringan Dosen Lain', 4);

    return compact(
        'advisorUser',
        'otherAdvisorUser',
        'student',
        'otherStudent',
        'firstKrs',
        'secondKrs',
        'otherKrs',
        'prodi'
    );
}

test('advisor KRS index groups courses by student and period with matching total credits', function () {
    $data = makeAdvisorKrsApprovalFlowData();

    $response = $this->actingAs($data['advisorUser'])->get(route('dosen.krs'));

    $response->assertOk()
        ->assertSee('Mahasiswa Bimbingan Utama')
        ->assertDontSee('Mahasiswa Bimbingan Dosen Lain')
        ->assertSee('Informatika Persetujuan KRS')
        ->assertSee('2 mata kuliah')
        ->assertSee('Total 5 SKS')
        ->assertSee('Lihat Detail');

    $summaries = $response->viewData('summaries');
    expect($summaries->total())->toBe(1)
        ->and((int) $summaries->first()->jumlah_mata_kuliah)->toBe(2)
        ->and((int) $summaries->first()->total_sks)->toBe(5);
});

test('advisor detail lists all submitted courses and blocks another advisor', function () {
    $data = makeAdvisorKrsApprovalFlowData();
    $parameters = [
        'mahasiswa' => $data['student'],
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Ganjil',
    ];

    $response = $this->actingAs($data['advisorUser'])
        ->get(route('dosen.krs.show', $parameters));

    $response->assertOk()
        ->assertSee('Mahasiswa Bimbingan Utama')
        ->assertSee('Informatika Persetujuan KRS')
        ->assertSee('Dosen Wali Utama')
        ->assertSee('Algoritma Persetujuan')
        ->assertSee('Basis Data Persetujuan')
        ->assertSee('Total 5 SKS');
    expect($response->viewData('krs'))->toHaveCount(2)
        ->and($response->viewData('totalSks'))->toBe(5);

    $this->actingAs($data['otherAdvisorUser'])
        ->get(route('dosen.krs.show', $parameters))
        ->assertNotFound();
});

test('advisor decisions return to the filtered list and cannot alter another advisors KRS', function () {
    $data = makeAdvisorKrsApprovalFlowData();
    $listUrl = route('dosen.krs', [
        'search' => '270001',
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Ganjil',
        'page' => 1,
    ]);

    $this->actingAs($data['advisorUser'])
        ->put(route('dosen.krs.setujui', $data['firstKrs']), ['return_url' => $listUrl])
        ->assertRedirect($listUrl);
    expect($data['firstKrs']->fresh()->status)->toBe('Disetujui');

    $this->actingAs($data['otherAdvisorUser'])
        ->put(route('dosen.krs.setujui', $data['secondKrs']), ['return_url' => $listUrl])
        ->assertNotFound();
    expect($data['secondKrs']->fresh()->status)->toBe('Menunggu');
});

test('advisor approval pages never query a payment table', function () {
    $data = makeAdvisorKrsApprovalFlowData();
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = strtolower($query->sql);
    });

    $this->actingAs($data['advisorUser'])->get(route('dosen.krs'))->assertOk();
    $this->get(route('dosen.krs.show', [
        'mahasiswa' => $data['student'],
        'tahun_akademik' => '2027/2028',
        'semester_akademik' => 'Ganjil',
    ]))->assertOk();

    expect(collect($queries)->contains(
        fn (string $sql) => str_contains($sql, 'pembayaran_krs')
    ))->toBeFalse();
});
