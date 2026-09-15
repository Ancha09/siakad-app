<?php

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Facades\View;

function buatDataTranskrip(bool $kuesionerSelesai = false): array
{
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $prodi = Prodi::create([
        'kode_prodi' => fake()->unique()->bothify('P###'),
        'nama_prodi' => 'Teknik Industri',
        'jenjang' => 'S1',
    ]);
    $mahasiswa = Mahasiswa::create([
        'nim' => fake()->unique()->numerify('##########'),
        'nama' => 'Mahasiswa Transkrip',
        'prodi_id' => $prodi->id,
        'user_id' => $user->id,
    ]);
    $mataKuliah = MataKuliah::create([
        'kode_mk' => fake()->unique()->bothify('MK###'),
        'nama_mk' => 'Pengujian Perangkat Lunak',
        'sks' => 3,
        'semester' => 4,
        'prodi_id' => $prodi->id,
    ]);
    $jadwal = Jadwal::create([
        'mata_kuliah_id' => $mataKuliah->id,
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
    Khs::create([
        'krs_id' => $krs->id,
        'nilai_angka' => 87,
        'nilai_huruf' => 'A-',
        'bobot' => 3.67,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    if ($kuesionerSelesai) {
        $jawaban = collect(array_keys(Kuesioner::PERTANYAAN))
            ->mapWithKeys(fn (string $kolom) => [$kolom => 5])
            ->all();

        $krs->kuesioner()->create([
            ...$jawaban,
            'submitted_at' => now(),
        ]);
    }

    return compact('user', 'mahasiswa', 'krs');
}

test('transcript pdf route requires an authenticated student', function () {
    $this->get(route('mahasiswa.transkrip.pdf'))
        ->assertRedirect(route('login'));

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('mahasiswa.transkrip.pdf'))
        ->assertForbidden();
});

test('ipk is hidden on the transcript page while a questionnaire is pending', function () {
    ['user' => $user] = buatDataTranskrip();

    $this->actingAs($user)
        ->get(route('mahasiswa.khs'))
        ->assertOk()
        ->assertSee('IPK')
        ->assertDontSee('3.67');
});

test('ipk is hidden on dashboard and krs while a questionnaire is pending', function () {
    ['user' => $user] = buatDataTranskrip();

    foreach (['mahasiswa.dashboard', 'mahasiswa.krs'] as $route) {
        $this->actingAs($user)
            ->get(route($route))
            ->assertOk()
            ->assertDontSee('3.67');
    }
});

test('pending questionnaire blocks transcript pdf and cannot expose ipk', function () {
    ['user' => $user] = buatDataTranskrip();

    $response = $this->actingAs($user)->get(route('mahasiswa.transkrip.pdf'));

    $response
        ->assertRedirect(route('mahasiswa.kuesioner'))
        ->assertSessionHas('info');
    expect($response->headers->get('content-type'))->not->toBe('application/pdf')
        ->and($response->getContent())->not->toContain('3.67');
});

test('student with completed questionnaire can download a real transcript pdf', function () {
    ['user' => $user, 'mahasiswa' => $mahasiswa] = buatDataTranskrip(true);

    $response = $this->actingAs($user)->get(route('mahasiswa.transkrip.pdf'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('transkrip-'.$mahasiswa->nim.'.pdf');
    expect($response->getContent())->toStartWith('%PDF-');
});

test('transcript pdf is rendered from the dedicated transcript template', function () {
    ['user' => $user, 'mahasiswa' => $mahasiswa] = buatDataTranskrip(true);
    $templateRendered = false;

    View::composer('mahasiswa.khs.transkrip-pdf', function () use (&$templateRendered) {
        $templateRendered = true;
    });

    $this->actingAs($user)
        ->get(route('mahasiswa.transkrip.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('transkrip-'.$mahasiswa->nim.'.pdf');

    expect($templateRendered)->toBeTrue();
});
