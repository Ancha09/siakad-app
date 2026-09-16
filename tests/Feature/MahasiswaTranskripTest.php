<?php

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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

test('transcript pdf embeds the official local letterhead and chair signature without changing grades', function () {
    ['user' => $user, 'mahasiswa' => $mahasiswa, 'krs' => $krs] = buatDataTranskrip(true);
    $grade = $krs->khs->load('krs.jadwal.mataKuliah');
    $data = ['mahasiswa' => $mahasiswa, 'khs' => collect([$grade]), 'totalSks' => 3, 'ipk' => 3.67];
    $html = view('mahasiswa.khs.transkrip-pdf', $data)->render();
    expect($html)->toContain(public_path('images/kop-sttmi.jpeg.jpeg'), 'Ketua STTMI,', 'Dr. Ir. Awang Suwandhi, M.Sc.', 'signature-space', 'Pengujian Perangkat Lunak', 'A-', '3.67')
        ->not->toContain('Mandala Indonesia', 'https://', 'http://');
    expect(getimagesize(public_path('images/kop-sttmi.jpeg.jpeg'))['mime'])->toBe('image/jpeg');

    $pdf = Pdf::loadView('mahasiswa.khs.transkrip-pdf', $data)->setPaper('a4', 'portrait');
    $binary = $pdf->output();
    expect($binary)->toStartWith('%PDF-')->toContain('/Subtype /Image');
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
    expect((float) $grade->fresh()->nilai_angka)->toBe(87.0)->and((float) $grade->fresh()->bobot)->toBe(3.67);
    $this->actingAs($user)->get(route('mahasiswa.transkrip.pdf'))->assertOk()
        ->assertHeader('content-type', 'application/pdf')->assertDownload('transkrip-'.$mahasiswa->nim.'.pdf');
});

test('long transcript pdf repeats its letterhead on every page', function () {
    ['mahasiswa' => $mahasiswa, 'krs' => $krs] = buatDataTranskrip(true);
    $grade = $krs->khs->load('krs.jadwal.mataKuliah');
    $records = collect(range(1, 65))->map(fn () => clone $grade);
    $pdf = Pdf::loadView('mahasiswa.khs.transkrip-pdf', [
        'mahasiswa' => $mahasiswa, 'khs' => $records, 'totalSks' => 195, 'ipk' => 3.67,
    ])->setPaper('a4', 'portrait');
    $binary = $pdf->output(['compress' => false]);
    $pages = $pdf->getDomPDF()->getCanvas()->get_page_count();
    expect($pages)->toBeGreaterThan(1);
    expect(preg_match_all('/\/I\d+\s+Do\b/', $binary))->toBe($pages);
});

test('manual grades without schedule are hidden in khs dashboard json requests and pdf', function () {
    ['user' => $user, 'krs' => $krs] = buatDataTranskrip();
    $courseId = $krs->jadwal->mata_kuliah_id;
    $krs->update(['is_manual' => true, 'jadwal_id' => null, 'mata_kuliah_id' => $courseId]);
    $grade = $krs->khs;
    $grade->update(['is_manual' => true, 'nilai_angka' => 93.37]);
    $this->actingAs($user);
    foreach (['mahasiswa.khs', 'mahasiswa.dashboard', 'mahasiswa.krs'] as $route) {
        $response = $this->get(route($route))->assertOk()->assertDontSee('93.37')->assertDontSee('3.67')->assertSee('Isi kuesioner untuk melihat nilai');
        if ($route === 'mahasiswa.khs') {
            expect($response->viewData('ipk'))->toBeNull()
                ->and($response->viewData('ipsPerSemester')['2026/2027 - Ganjil'])->toBeNull()
                ->and($response->viewData('jumlahKuesionerTertunda'))->toBe(1);
            $masked = $response->viewData('khs')->first()->toArray();
        } elseif ($route === 'mahasiswa.dashboard') {
            expect($response->viewData('ipkTerlihat'))->toBeNull();
            $masked = $response->viewData('krs')->first()->khs->toArray();
        } else {
            continue;
        }
        foreach (['nilai_angka', 'nilai_huruf', 'bobot'] as $field) {
            expect($masked[$field])->toBeNull();
        }
    }
    $this->getJson(route('mahasiswa.khs'))->assertOk()->assertDontSee('93.37')->assertDontSee('3.67');
    $this->get(route('mahasiswa.transkrip.pdf'))->assertRedirect(route('mahasiswa.kuesioner'));
    $this->assertDatabaseHas('khs', ['id' => $grade->id, 'nilai_angka' => 93.37, 'nilai_huruf' => 'A-', 'bobot' => 3.67]);
    $this->get(route('mahasiswa.kuesioner'))->assertOk()->assertSee('Pengujian Perangkat Lunak');
    $this->get(route('mahasiswa.kuesioner.create', $krs))->assertOk()->assertSee('Pengujian Perangkat Lunak')->assertDontSee('93.37');
});

test('submitting questionnaire unlocks a manual course and transcript without changing its grade', function () {
    ['user' => $user, 'mahasiswa' => $mahasiswa, 'krs' => $krs] = buatDataTranskrip();
    $krs->update(['is_manual' => true, 'mata_kuliah_id' => $krs->jadwal->mata_kuliah_id, 'jadwal_id' => null]);
    $krs->khs->update(['is_manual' => true, 'nilai_angka' => 93.37]);
    $answers = array_fill_keys(array_keys(Kuesioner::PERTANYAAN), 5);
    $this->actingAs($user)->post(route('mahasiswa.kuesioner.store', $krs), $answers)->assertSessionHasNoErrors()->assertRedirect(route('mahasiswa.khs'));
    $response = $this->get(route('mahasiswa.khs'))->assertOk()->assertSee('93.37')->assertSee('3.67')->assertSee('Nilai terbuka');
    expect($response->viewData('ipk'))->toBe(3.67)
        ->and($response->viewData('ipsPerSemester')['2026/2027 - Ganjil'])->toBe(3.67)
        ->and($response->viewData('jumlahKuesionerTertunda'))->toBe(0);
    $this->get(route('mahasiswa.transkrip.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf')->assertDownload('transkrip-'.$mahasiswa->nim.'.pdf');
});

test('one questionnaire opens only its manual course while other grades and cumulative ipk stay locked', function () {
    ['user' => $user, 'mahasiswa' => $mahasiswa, 'krs' => $firstKrs] = buatDataTranskrip();
    $firstKrs->update(['is_manual' => true, 'mata_kuliah_id' => $firstKrs->jadwal->mata_kuliah_id, 'jadwal_id' => null]);
    $firstKrs->khs->update(['is_manual' => true, 'nilai_angka' => 93.37]);
    $secondCourse = MataKuliah::create(['kode_mk' => 'LEGACY002', 'nama_mk' => 'Matematika Historis', 'sks' => 3, 'semester' => 4, 'prodi_id' => $mahasiswa->prodi_id]);
    $secondKrs = Krs::create(['mahasiswa_id' => $mahasiswa->id, 'mata_kuliah_id' => $secondCourse->id, 'is_manual' => true, 'status' => 'Disetujui', 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
    $secondGrade = Khs::create(['krs_id' => $secondKrs->id, 'is_manual' => true, 'nilai_angka' => 41.23, 'nilai_huruf' => 'D', 'bobot' => 1, 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
    $answers = array_fill_keys(array_keys(Kuesioner::PERTANYAAN), 5);
    $this->actingAs($user)->post(route('mahasiswa.kuesioner.store', $firstKrs), $answers)->assertSessionHasNoErrors();
    $response = $this->get(route('mahasiswa.khs'))->assertOk()->assertSee('93.37')->assertDontSee('41.23')->assertSee('Isi kuesioner untuk melihat nilai');
    $grades = $response->viewData('khs')->keyBy('krs_id');
    expect((float) $grades[$firstKrs->id]->nilai_angka)->toBe(93.37)
        ->and($grades[$secondKrs->id]->nilai_angka)->toBeNull()
        ->and($grades[$secondKrs->id]->nilai_huruf)->toBeNull()
        ->and($grades[$secondKrs->id]->bobot)->toBeNull()
        ->and($response->viewData('ipk'))->toBeNull()
        ->and($response->viewData('ipsPerSemester')['2026/2027 - Ganjil'])->toBeNull()
        ->and($response->viewData('jumlahKuesionerTertunda'))->toBe(1);
    $this->get(route('mahasiswa.transkrip.pdf'))->assertRedirect(route('mahasiswa.kuesioner'));
    $this->get(route('mahasiswa.dashboard'))->assertOk()->assertViewHas('ipkTerlihat', null);
    $this->assertDatabaseHas('khs', ['id' => $secondGrade->id, 'nilai_angka' => 41.23, 'nilai_huruf' => 'D']);
    $this->post(route('mahasiswa.kuesioner.store', $secondKrs), $answers)->assertSessionHasNoErrors();
    $unlocked = $this->get(route('mahasiswa.khs'))->assertOk()->assertSee('93.37')->assertSee('41.23');
    expect($unlocked->viewData('jumlahKuesionerTertunda'))->toBe(0)
        ->and($unlocked->viewData('ipk'))->toBe(2.34);
    $this->get(route('mahasiswa.transkrip.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
});
