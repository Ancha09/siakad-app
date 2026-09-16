<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Str;

function buatDataEvaluasiDosenMandiri(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $userA = User::factory()->create(['role' => 'dosen', 'name' => 'Akun Dosen A']);
    $userB = User::factory()->create(['role' => 'dosen', 'name' => 'Akun Dosen B']);
    $prodi = Prodi::create([
        'kode_prodi' => 'SELF-EVAL',
        'nama_prodi' => 'Teknik Evaluasi',
        'jenjang' => 'S1',
    ]);
    $dosenA = Dosen::create([
        'nidn' => 'SELF-001',
        'nama' => 'Dosen Evaluasi A',
        'prodi_id' => $prodi->id,
        'user_id' => $userA->id,
    ]);
    $dosenB = Dosen::create([
        'nidn' => 'SELF-002',
        'nama' => 'Dosen Evaluasi B',
        'prodi_id' => $prodi->id,
        'user_id' => $userB->id,
    ]);
    $kelasA = Kelas::create([
        'nama_kelas' => 'Kelas Evaluasi A',
        'prodi_id' => $prodi->id,
        'angkatan' => '2026',
        'semester' => 1,
    ]);
    $kelasB = Kelas::create([
        'nama_kelas' => 'Kelas Evaluasi B',
        'prodi_id' => $prodi->id,
        'angkatan' => '2026',
        'semester' => 1,
    ]);
    $mataKuliahA = MataKuliah::create([
        'kode_mk' => 'SELF101',
        'nama_mk' => 'Mata Kuliah Dosen A',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $mataKuliahB = MataKuliah::create([
        'kode_mk' => 'SELF102',
        'nama_mk' => 'Mata Kuliah Dosen B',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);

    $createEvaluation = function (Dosen $dosen, Kelas $kelas, MataKuliah $mataKuliah, string $suffix, string $comment) use ($prodi) {
        $studentUser = User::factory()->create(['role' => 'mahasiswa', 'name' => 'User Rahasia '.$suffix]);
        $mahasiswa = Mahasiswa::create([
            'nim' => 'NIM-RAHASIA-'.$suffix,
            'nama' => 'Mahasiswa Rahasia '.$suffix,
            'prodi_id' => $prodi->id,
            'kelas_id' => $kelas->id,
            'user_id' => $studentUser->id,
        ]);
        $jadwal = Jadwal::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'dosen_id' => $dosen->id,
            'kelas_id' => $kelas->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]);
        $krs = Krs::create([
            'mahasiswa_id' => $mahasiswa->id,
            'jadwal_id' => $jadwal->id,
            'status' => 'Disetujui',
            'tahun_akademik' => '2026/2027',
            'semester_akademik' => 'Ganjil',
        ]);
        $answers = collect(array_keys(Kuesioner::PERTANYAAN))
            ->mapWithKeys(fn (string $column) => [$column => $suffix === 'A' ? 4 : 2])
            ->all();
        $krs->kuesioner()->create([
            ...$answers,
            'komentar' => $comment,
            'submitted_at' => now(),
        ]);

        return compact('mahasiswa', 'jadwal', 'krs');
    };

    $dataA = $createEvaluation($dosenA, $kelasA, $mataKuliahA, 'A', 'Komentar khusus untuk dosen A.');
    $dataB = $createEvaluation($dosenB, $kelasB, $mataKuliahB, 'B', 'Komentar khusus untuk dosen B.');

    return compact(
        'admin', 'userA', 'userB', 'dosenA', 'dosenB', 'kelasA', 'kelasB',
        'mataKuliahA', 'mataKuliahB', 'dataA', 'dataB'
    );
}

test('lecturer sees only their own anonymous evaluation even when another lecturer id is sent', function () {
    $data = buatDataEvaluasiDosenMandiri();

    $response = $this->actingAs($data['userA'])->get(route('dosen.evaluasi', [
        'dosen_id' => $data['dosenB']->id,
    ]));

    $response
        ->assertOk()
        ->assertSee('Evaluasi Saya')
        ->assertSee('Komentar khusus untuk dosen A.')
        ->assertSee('4.00')
        ->assertDontSee('Komentar khusus untuk dosen B.')
        ->assertDontSee('Mahasiswa Rahasia A')
        ->assertDontSee('NIM-RAHASIA-A')
        ->assertDontSee('User Rahasia A')
        ->assertDontSee('RESP-');
});

test('lecturer evaluation filters include course semester year and class', function () {
    $data = buatDataEvaluasiDosenMandiri();

    $this->actingAs($data['userA'])
        ->get(route('dosen.evaluasi', [
            'mata_kuliah_id' => $data['mataKuliahA']->id,
            'semester_akademik' => 'Ganjil',
            'tahun_akademik' => '2026/2027',
            'kelas_id' => $data['kelasA']->id,
        ]))
        ->assertOk()
        ->assertSee('Komentar khusus untuk dosen A.')
        ->assertSee('Kelas Evaluasi A');

    $this->actingAs($data['userA'])
        ->get(route('dosen.evaluasi', ['kelas_id' => $data['kelasB']->id]))
        ->assertOk()
        ->assertSee('Belum ada evaluasi pada filter ini.')
        ->assertDontSee('Komentar khusus untuk dosen B.');
});

test('lecturer without evaluation gets an empty state without error', function () {
    buatDataEvaluasiDosenMandiri();
    $user = User::factory()->create(['role' => 'dosen']);
    Dosen::create(['nidn' => 'SELF-EMPTY', 'nama' => 'Dosen Belum Dinilai', 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dosen.evaluasi'))
        ->assertOk()
        ->assertSee('Belum ada evaluasi pada filter ini.');
});

test('lecturer can download only their own anonymous evaluation pdf', function () {
    $data = buatDataEvaluasiDosenMandiri();

    $response = $this->actingAs($data['userA'])->get(route('dosen.evaluasi.pdf', [
        'dosen_id' => $data['dosenB']->id,
    ]));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('evaluasi-saya-'.Str::slug($data['dosenA']->nidn).'.pdf');
    expect($response->getContent())->toStartWith('%PDF-');

    $html = view('evaluasi.detail-pdf', array_merge(
        app(\App\Services\LecturerEvaluationService::class)->report($data['dosenA']),
        [
            'dosen' => $data['dosenA']->load('prodi'),
            'komentar' => collect([$data['dataA']['krs']->kuesioner]),
            'reportTitle' => 'Evaluasi Saya',
            'deskripsiFilter' => 'Semua evaluasi',
        ]
    ))->render();
    expect($html)
        ->toContain('Komentar khusus untuk dosen A.')
        ->not->toContain('Mahasiswa Rahasia A', 'NIM-RAHASIA-A', 'User Rahasia A', 'mahasiswa_id', 'user_id');
});

test('admin can download filtered full and per lecturer evaluation pdf', function () {
    $data = buatDataEvaluasiDosenMandiri();

    $detail = $this->actingAs($data['admin'])->get(route('admin.kuesioner.dosen.pdf', [
        'dosen' => $data['dosenA'],
        'tahun_akademik' => '2026/2027',
    ]));
    $detail
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('evaluasi-dosen-'.Str::slug($data['dosenA']->nidn).'.pdf');
    expect($detail->getContent())->toStartWith('%PDF-');

    $full = $this->actingAs($data['admin'])->get(route('admin.kuesioner.pdf', [
        'semester_akademik' => 'Ganjil',
        'tahun_akademik' => '2026/2027',
    ]));
    $full->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($full->headers->get('content-disposition'))->toContain('laporan-evaluasi-dosen-');
    expect($full->getContent())->toStartWith('%PDF-');
});

test('evaluation pdf routes enforce lecturer and admin roles', function () {
    $data = buatDataEvaluasiDosenMandiri();

    $this->actingAs($data['admin'])->get(route('dosen.evaluasi.pdf'))->assertForbidden();
    $this->actingAs($data['userA'])->get(route('admin.kuesioner.pdf'))->assertForbidden();
    $this->actingAs($data['userA'])
        ->get(route('admin.kuesioner.dosen.pdf', $data['dosenB']))
        ->assertForbidden();
});
