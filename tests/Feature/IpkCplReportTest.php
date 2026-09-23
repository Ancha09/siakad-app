<?php

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\Dosen;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Services\CplManualOverrides;
use App\Services\CplMappingImporter;
use App\Services\IpkCplReportService;
use App\Support\MiningCplCatalog;
use Illuminate\Pagination\LengthAwarePaginator;

function createMappedCplGrade(
    Mahasiswa $student,
    MataKuliah $course,
    Dosen $lecturer,
    string $academicYear,
    string $academicSemester,
    float $score,
    string $letter,
    float $weight
): Khs {
    $krs = Krs::create([
        'mahasiswa_id' => $student->id,
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $lecturer->id,
        'prodi_id' => $student->prodi_id,
        'angkatan' => $student->angkatan,
        'semester' => $course->semester,
        'status' => 'Disetujui',
        'tahun_akademik' => $academicYear,
        'semester_akademik' => $academicSemester,
        'is_manual' => true,
    ]);

    return Khs::create([
        'krs_id' => $krs->id,
        'nilai_angka' => $score,
        'nilai_huruf' => $letter,
        'bobot' => $weight,
        'sks' => $course->sks,
        'tahun_akademik' => $academicYear,
        'semester_akademik' => $academicSemester,
        'is_manual' => true,
        'dosen_id' => $lecturer->id,
        'dosen_override' => true,
    ]);
}

function makeMappedCplFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $mining = Prodi::create(['kode_prodi' => 'TP-CPL', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $geology = Prodi::create(['kode_prodi' => 'TG-CPL', 'nama_prodi' => 'Teknik Geologi', 'jenjang' => 'S1']);
    $lecturer = Dosen::create(['nidn' => 'CPL-MAP-01', 'nama' => 'Dosen Mapping CPL', 'prodi_id' => $mining->id, 'user_id' => $lecturerUser->id]);
    $studentA = Mahasiswa::create(['nim' => 'CPL-M-001', 'nama' => 'Mahasiswa Tambang A', 'angkatan' => 2024, 'semester' => 3, 'prodi_id' => $mining->id]);
    $studentB = Mahasiswa::create(['nim' => 'CPL-M-002', 'nama' => 'Mahasiswa Tambang B', 'angkatan' => 2023, 'semester' => 3, 'prodi_id' => $mining->id]);
    $studentGeology = Mahasiswa::create(['nim' => 'CPL-G-001', 'nama' => 'Mahasiswa Geologi', 'angkatan' => 2024, 'semester' => 1, 'prodi_id' => $geology->id, 'user_id' => $studentUser->id]);
    $courseA = MataKuliah::create(['kode_mk' => 'TP101', 'nama_mk' => 'Matematika Tambang', 'sks' => 3, 'semester' => 1, 'prodi_id' => $mining->id]);
    $courseB = MataKuliah::create(['kode_mk' => 'TP301', 'nama_mk' => 'Perencanaan Tambang', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    $cpl = Cpl::create(['program_studi_id' => $mining->id, 'kode_cpl' => 'CPL 1', 'nama_cpl' => 'Rekayasa pertambangan', 'sort_order' => 1] + MiningCplCatalog::forCode('CPL 1'));
    $secondCpl = Cpl::create(['program_studi_id' => $mining->id, 'kode_cpl' => 'CPL 2', 'nama_cpl' => 'Rekayasa berkelanjutan', 'sort_order' => 2] + MiningCplCatalog::forCode('CPL 2'));
    $mappingA = CplMataKuliah::create(['cpl_id' => $cpl->id, 'mata_kuliah_id' => $courseA->id, 'kode_sumber' => 'TP 101', 'nama_sumber' => $courseA->nama_mk, 'semester' => 1, 'sks' => 3]);
    $mappingB = CplMataKuliah::create(['cpl_id' => $cpl->id, 'mata_kuliah_id' => $courseB->id, 'kode_sumber' => 'TP 301', 'nama_sumber' => $courseB->nama_mk, 'semester' => 3, 'sks' => 2]);
    CplMataKuliah::create(['cpl_id' => $secondCpl->id, 'mata_kuliah_id' => null, 'kode_sumber' => 'TP 999', 'nama_sumber' => 'Belum Ada di Master', 'semester' => 8, 'sks' => 2]);

    $oldDuplicate = createMappedCplGrade($studentA, $courseA, $lecturer, '2024/2025', 'Ganjil', 60, 'C', 2);
    $latestA = createMappedCplGrade($studentA, $courseA, $lecturer, '2024/2025', 'Ganjil', 90, 'A', 4);
    $latestB = createMappedCplGrade($studentB, $courseA, $lecturer, '2024/2025', 'Ganjil', 80, 'B', 3);
    $courseBGrade = createMappedCplGrade($studentA, $courseB, $lecturer, '2024/2025', 'Ganjil', 65, 'C', 2);
    $geologyGrade = createMappedCplGrade($studentGeology, $courseA, $lecturer, '2024/2025', 'Ganjil', 95, 'A', 4);

    return compact(
        'admin', 'studentUser', 'lecturerUser', 'lecturer', 'mining', 'geology', 'studentA', 'studentB',
        'courseA', 'courseB', 'cpl', 'secondCpl', 'mappingA', 'mappingB', 'oldDuplicate',
        'latestA', 'latestB', 'courseBGrade', 'geologyGrade'
    );
}

test('CPL Excel importer creates nine CPL blocks and keeps unmatched courses visible', function () {
    $program = Prodi::create(['kode_prodi' => 'TP-IMPORT', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $course = MataKuliah::create(['kode_mk' => 'KU103', 'nama_mk' => 'Matematika I', 'sks' => 4, 'semester' => 1, 'prodi_id' => $program->id]);

    $this->artisan('ipk-cpl:import')->assertSuccessful();

    expect(Cpl::where('program_studi_id', $program->id)->count())->toBe(9)
        ->and(CplMataKuliah::count())->toBe(146)
        ->and(CplMataKuliah::whereNotNull('mata_kuliah_id')->count())->toBe(3)
        ->and(CplMataKuliah::whereNull('mata_kuliah_id')->count())->toBe(143);

    $this->assertDatabaseHas('cpl_mata_kuliah', [
        'kode_sumber' => 'KU 103',
        'mata_kuliah_id' => $course->id,
    ]);
    $this->assertDatabaseHas('cpl_mata_kuliah', [
        'kode_sumber' => 'TA 801',
        'mata_kuliah_id' => null,
    ]);
    $this->assertDatabaseHas('cpls', [
        'program_studi_id' => $program->id,
        'kode_cpl' => 'CPL 1',
        'cpl_kkni' => 'KK1, P1',
        'turunan_visi_misi' => 'Misi 1: pendidikan secara profesional',
    ]);

    $this->artisan('ipk-cpl:import')->assertSuccessful();
    expect(CplMataKuliah::count())->toBe(146);
});

test('CPL matcher prioritizes mining courses and never maps TP sources to geology', function () {
    $mining = Prodi::create(['kode_prodi' => 'TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $geology = Prodi::create(['kode_prodi' => 'TG', 'nama_prodi' => 'Teknik Geologi', 'jenjang' => 'S1']);
    $miningCourse = MataKuliah::create([
        'kode_mk' => 'KU 301 (TP)',
        'nama_mk' => 'Kimia Analitik',
        'sks' => 2,
        'semester' => 3,
        'prodi_id' => $mining->id,
    ]);
    MataKuliah::create([
        'kode_mk' => 'KU 302 (TG)',
        'nama_mk' => 'Statistika Teknik Geologi',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $geology->id,
    ]);
    $nameFallbackCourse = MataKuliah::create([
        'kode_mk' => 'KU 304 (TP)',
        'nama_mk' => 'Pengantar Sistem Informasi Geografi (GIS)',
        'sks' => 2,
        'semester' => 4,
        'prodi_id' => $mining->id,
    ]);
    $citizenshipCourse = MataKuliah::create([
        'kode_mk' => 'KU 206',
        'nama_mk' => 'Kewarganegaraan',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $mining->id,
    ]);
    MataKuliah::create([
        'kode_mk' => 'GL 301 (TP)',
        'nama_mk' => 'Petrologi (P)',
        'sks' => 2,
        'semester' => 3,
        'prodi_id' => $mining->id,
    ]);
    $basicComputingCourse = MataKuliah::create([
        'kode_mk' => 'KU 302 (TP)',
        'nama_mk' => 'Dasar Komputasi',
        'sks' => 2,
        'semester' => 2,
        'prodi_id' => $mining->id,
    ]);
    MataKuliah::create(['kode_mk' => 'KU 303 (TP)', 'nama_mk' => 'Statistika', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 301', 'nama_mk' => 'Bahan Galian Indonesia', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 401', 'nama_mk' => 'Metode Numerik', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 501', 'nama_mk' => 'Sistem Penambangan', 'sks' => 2, 'semester' => 5, 'prodi_id' => $mining->id]);
    $courses = MataKuliah::with('prodi')->get();
    $matcher = app(CplMappingImporter::class);

    expect($matcher->normalizeCourseCode('KU 301'))->toBe('KU301')
        ->and($matcher->normalizeCourseCode('KU301'))->toBe('KU301')
        ->and($matcher->normalizeCourseCode('KU-301'))->toBe('KU301')
        ->and($matcher->normalizeCourseCode('  KU   301 (TP)  '))->toBe('KU301')
        ->and($matcher->courseCodeParts('TA-601'))->toBe(['prefix' => 'TA', 'number' => '601'])
        ->and($matcher->normalizeCourseCode('TA 601'))->toBe('TA601')
        ->and($matcher->normalizeCourseCode('TA 601'))->not->toBe($matcher->normalizeCourseCode('TA 301'))
        ->and($matcher->matchCourse($courses, 'KU301 (TP)', 'Kimia Analitik', $mining)?->id)
        ->toBe($miningCourse->id)
        ->and($matcher->matchCourse($courses, 'KU-301 (TP)', 'Kimia Analitik', $mining)?->id)
        ->toBe($miningCourse->id)
        ->and($matcher->matchCourse($courses, 'KU   301 (TP)', 'Kimia Analitik', $mining)?->id)
        ->toBe($miningCourse->id)
        ->and($matcher->matchCourse($courses, 'KU-304 (TP)', 'Pengantar   GIS', $mining)?->id)
        ->toBe($nameFallbackCourse->id)
        ->and($matcher->matchCourse($courses, 'KODE-LAMA (TP)', 'Pengantar GIS', $mining))
        ->toBeNull()
        ->and($matcher->matchCourse($courses, 'KU 206', 'Pendidikan Kewarganegaraan', $mining)?->id)
        ->toBe($citizenshipCourse->id)
        ->and($matcher->matchCourse($courses, 'GL 301', 'Geologi Struktur', $mining))
        ->toBeNull()
        ->and($matcher->matchCourse($courses, 'KU 302', 'Matriks Ruang Vektor', $mining))
        ->toBeNull()
        ->and($matcher->matchCourse($courses, 'KU 302 (TP)', 'Statistika Teknik Geologi', $mining))
        ->toBeNull()
        ->and($matcher->exactCodeCandidates($courses, 'KU 302', $mining)->pluck('id')->all())
        ->toBe([$basicComputingCourse->id])
        ->and($matcher->exactCodeCandidates($courses, 'TA 601', $mining))->toBeEmpty()
        ->and($matcher->eligibleCourses($courses, $mining)->pluck('prodi_id')->unique()->all())
        ->toBe([$mining->id]);
});

test('TP import preserves reviewed automatic mappings and applies approved manual overrides', function () {
    $mining = Prodi::create(['kode_prodi' => 'TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $geology = Prodi::create(['kode_prodi' => 'TG', 'nama_prodi' => 'Teknik Geologi', 'jenjang' => 'S1']);

    $wrongStructure = MataKuliah::create(['kode_mk' => 'GL 407 (TG)', 'nama_mk' => 'Geologi Struktur', 'sks' => 2, 'semester' => 4, 'prodi_id' => $geology->id]);
    $wrongSediment = MataKuliah::create(['kode_mk' => 'GL 402', 'nama_mk' => 'Sedimentologi (P)', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    $petrology = MataKuliah::create(['kode_mk' => 'GL 301 (TP)', 'nama_mk' => 'Petrologi (P)', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    $structure = MataKuliah::create(['kode_mk' => 'GL 401 (TP)', 'nama_mk' => 'Geologi Struktur (P)', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    $microscopy = MataKuliah::create(['kode_mk' => 'GL 402 (TP)', 'nama_mk' => 'Mikroskopik Bijih', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    $citizenship = MataKuliah::create(['kode_mk' => 'KU 206', 'nama_mk' => 'Kewarganegaraan', 'sks' => 2, 'semester' => 2, 'prodi_id' => $mining->id]);
    $chemistry = MataKuliah::create(['kode_mk' => 'KU 301', 'nama_mk' => 'Kimia Analitik (P)', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    $gis = MataKuliah::create(['kode_mk' => 'KU 304 (TP)', 'nama_mk' => 'Pengantar Sistem Informasi Geografi (GIS)', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'KU 302 (TP)', 'nama_mk' => 'Dasar Komputasi', 'sks' => 2, 'semester' => 2, 'prodi_id' => $mining->id]);

    $importer = app(CplMappingImporter::class);
    $importer->import(database_path('data/ipkcpl.xlsx'));

    CplMataKuliah::where('kode_sumber', 'GL 301')->update(['mata_kuliah_id' => $wrongStructure->id]);
    CplMataKuliah::where('kode_sumber', 'GL 402')->update(['mata_kuliah_id' => $wrongSediment->id]);
    $geologyCpl = Cpl::create([
        'program_studi_id' => $geology->id,
        'kode_cpl' => 'CPL 1',
        'nama_cpl' => 'CPL Teknik Geologi',
        'sort_order' => 1,
    ]);
    $geologyMapping = CplMataKuliah::create([
        'cpl_id' => $geologyCpl->id,
        'mata_kuliah_id' => $wrongStructure->id,
        'kode_sumber' => 'GL 301',
        'nama_sumber' => 'Geologi Struktur',
        'semester' => 3,
        'sks' => 2,
    ]);

    $importer->import(database_path('data/ipkcpl.xlsx'));

    foreach ([
        'GL 301' => $structure->id,
        'GL 402' => $microscopy->id,
        'KU 206' => $citizenship->id,
        'KU 301' => $chemistry->id,
        'KU 304' => $gis->id,
    ] as $sourceCode => $expectedCourseId) {
        $mappings = CplMataKuliah::query()
            ->where('kode_sumber', $sourceCode)
            ->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $mining->id))
            ->get();

        expect($mappings, $sourceCode)->not->toBeEmpty();
        expect($mappings->pluck('mata_kuliah_id')->map(fn ($id) => (int) $id)->unique()->values()->all(), $sourceCode)
            ->toBe([$expectedCourseId]);
    }

    foreach (['KU 302', 'TA 601'] as $sourceCode) {
        $mappings = CplMataKuliah::query()
            ->where('kode_sumber', $sourceCode)
            ->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $mining->id))
            ->get();

        expect($mappings)->not->toBeEmpty()
            ->and($mappings->every(fn (CplMataKuliah $mapping) => $mapping->mata_kuliah_id !== null))->toBeTrue();
    }

    expect(CplMataKuliah::query()
        ->where('kode_sumber', 'GL 401')
        ->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $mining->id))
        ->get()
        ->every(fn (CplMataKuliah $mapping) => (int) $mapping->mata_kuliah_id === $petrology->id))->toBeTrue();
    expect($geologyMapping->fresh()->mata_kuliah_id)->toBe($wrongStructure->id);
});

test('admin CPL report uses weighted SKS averages latest grades and mining students only', function () {
    $data = makeMappedCplFixture();
    $courseWithoutGrade = MataKuliah::create([
        'kode_mk' => 'TP-CPL-NILAI-KOSONG',
        'nama_mk' => 'Mata Kuliah Tanpa Nilai',
        'sks' => 2,
        'semester' => 4,
        'prodi_id' => $data['mining']->id,
    ]);
    $courseFromCpl9 = MataKuliah::create([
        'kode_mk' => 'TP-CPL-REPORT-9',
        'nama_mk' => 'Pelaporan Teknis Pertambangan',
        'sks' => 2,
        'semester' => 8,
        'prodi_id' => $data['mining']->id,
    ]);
    createMappedCplGrade($data['studentA'], $courseFromCpl9, $data['lecturer'], '2024/2025', 'Genap', 80, 'B', 3);
    $cpl9Mapping = null;
    foreach (range(3, 9) as $number) {
        $extraCpl = Cpl::create([
            'program_studi_id' => $data['mining']->id,
            'kode_cpl' => 'CPL '.$number,
            'nama_cpl' => 'CPL tambahan '.$number,
            'sort_order' => $number,
        ] + MiningCplCatalog::forCode('CPL '.$number));
        $mapping = CplMataKuliah::create([
            'cpl_id' => $extraCpl->id,
            'mata_kuliah_id' => match ($number) {
                3 => $courseWithoutGrade->id,
                9 => $courseFromCpl9->id,
                default => null,
            },
            'kode_sumber' => match ($number) {
                3 => 'TP-CPL-NILAI-KOSONG',
                9 => 'TP-CPL-REPORT-9',
                default => 'TP-CPL-BELUM-'.$number,
            },
            'nama_sumber' => match ($number) {
                3 => 'Mata Kuliah Tanpa Nilai',
                9 => 'Pelaporan Teknis Pertambangan',
                default => 'Belum Ada '.$number,
            },
            'semester' => $number,
            'sks' => 2,
        ]);
        if ($number === 9) {
            $cpl9Mapping = $mapping;
        }
    }
    $wrongGeologyCourse = MataKuliah::create([
        'kode_mk' => 'TP-CPL-BELUM-4 (TG)',
        'nama_mk' => 'Mata Kuliah Geologi yang Salah',
        'sks' => 2,
        'semester' => 4,
        'prodi_id' => $data['geology']->id,
    ]);
    CplMataKuliah::where('kode_sumber', 'TP-CPL-BELUM-4')
        ->update(['mata_kuliah_id' => $wrongGeologyCourse->id]);

    $landing = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.index'));
    $landing->assertOk()
        ->assertSee('CPL Teknik Pertambangan')
        ->assertSee('8 CPL aktif')
        ->assertDontSee('CPL Teknik Geologi')
        ->assertDontSee('Data CPL Teknik Geologi belum tersedia.');

    $program = $this->get(route('admin.ipk-cpl.program', [
        'prodi' => $data['mining'],
        'tahun_akademik' => '2024/2025',
    ]));
    $program->assertOk()->assertSee('2.90')->assertSee('Rekayasa pertambangan');

    $program->assertSee('Grafik IPK CPL Tahun Akademik 2024/2025')
        ->assertDontSee('Kelengkapan Data CPL')
        ->assertDontSee('cplCompletenessChart', false)
        ->assertDontSee('<th>Status</th>', false)
        ->assertSee('CPL Aktif')
        ->assertDontSee('Aksi Mapping Manual')
        ->assertSee('ipkCplChartPayload', false)
        ->assertSee('chartInitialized', false)
        ->assertSee("destroyChart('ipkCplChart'", false)
        ->assertSee('animation: false', false)
        ->assertSee('Belum ada data IPK CPL untuk filter ini.')
        ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false)
        ->assertDontSee('parsing: false', false)
        ->assertDontSee('setInterval', false)
        ->assertDontSee('Mahasiswa Tambang A')
        ->assertDontSee('Mahasiswa Tambang B')
        ->assertSee('Download PDF')
        ->assertSee('Download Excel');

    $first = $program->viewData('rows')->firstWhere('kode_cpl', 'CPL 1');
    expect($first->ipk_cpl)->toBe(2.9)
        ->and($first->sks_dihitung)->toBe(5)
        ->and($first->courses)->toBeEmpty()
        ->and($program->viewData('mappingSummary')['cocok_master'])->toBe(4)
        ->and($program->viewData('mappingSummary')['jumlah_cpl'])->toBe(8)
        ->and($program->viewData('reportConfiguration')['redirected_mapping_count'])->toBe(1);
    expect(array_keys($program->viewData('chart')))->toBe(['labels', 'ipk'])
        ->and($program->viewData('chart')['labels'])->toBe([
            'CPL 1', 'CPL 2', 'CPL 3', 'CPL 4', 'CPL 5', 'CPL 6', 'CPL 7', 'CPL 8',
        ])
        ->and($program->viewData('chart')['ipk'][0])->toBe(2.9)
        ->and(substr_count($program->getContent(), '<canvas'))->toBe(1);

    $report = app(IpkCplReportService::class)->cplOverview($data['mining'], ['tahun_akademik' => '2024/2025']);
    $third = $report['rows']->firstWhere('kode_cpl', 'CPL 3');
    expect($third->courses->pluck('nama_mata_kuliah')->all())->toContain('Pelaporan Teknis Pertambangan')
        ->and($third->ipk_cpl)->toBe(3.0)
        ->and($report['rows']->pluck('kode_cpl')->all())->not->toContain('CPL 9')
        ->and(CplMataKuliah::find($cpl9Mapping->id)?->cpl_id)->toBe($cpl9Mapping->cpl_id);

    $cpl3 = Cpl::where('program_studi_id', $data['mining']->id)->where('kode_cpl', 'CPL 3')->firstOrFail();
    $this->get(route('admin.ipk-cpl.cpl', ['prodi' => $data['mining'], 'cpl' => $cpl3, 'tahun_akademik' => '2024/2025']))
        ->assertOk()->assertSee('Pelaporan Teknis Pertambangan')->assertSee('Dialihkan dari CPL 9');
    $this->get(route('admin.ipk-cpl.course', ['prodi' => $data['mining'], 'cpl' => $cpl3, 'mapping' => $cpl9Mapping, 'tahun_akademik' => '2024/2025']))
        ->assertOk()->assertSee('Mahasiswa Tambang A');
    $cpl9 = Cpl::where('program_studi_id', $data['mining']->id)->where('kode_cpl', 'CPL 9')->firstOrFail();
    $this->get(route('admin.ipk-cpl.cpl', ['prodi' => $data['mining'], 'cpl' => $cpl9]))
        ->assertNotFound();

    $defaultYear = $this->get(route('admin.ipk-cpl.program', ['prodi' => $data['mining']]));
    $defaultYear->assertOk();
    expect($defaultYear->viewData('filters')['tahun_akademik'])->toBe('2024/2025');

    expect((float) $data['oldDuplicate']->fresh()->bobot)->toBe(2.0)
        ->and((float) $data['geologyGrade']->fresh()->bobot)->toBe(4.0);
});

test('admin can manually connect an unmatched CPL mapping only to a mining course', function () {
    $data = makeMappedCplFixture();
    $unmatched = CplMataKuliah::whereNull('mata_kuliah_id')->firstOrFail();

    $this->actingAs($data['admin'])->patch(route('admin.ipk-cpl.mapping.update', [
        'prodi' => $data['mining'],
        'cpl' => $data['secondCpl'],
        'mapping' => $unmatched,
    ]), [
        'mata_kuliah_id' => $data['courseB']->id,
        'return_url' => route('admin.ipk-cpl.program', $data['mining']).'?tahun_akademik=2024%2F2025',
    ])->assertRedirect(route('admin.ipk-cpl.program', $data['mining']).'?tahun_akademik=2024%2F2025');

    expect($unmatched->fresh()->mata_kuliah_id)->toBe($data['courseB']->id);

    $geologyCourse = MataKuliah::create([
        'kode_mk' => 'TG999',
        'nama_mk' => 'Khusus Geologi',
        'sks' => 2,
        'semester' => 8,
        'prodi_id' => $data['geology']->id,
    ]);
    $anotherUnmatched = CplMataKuliah::create([
        'cpl_id' => $data['secondCpl']->id,
        'mata_kuliah_id' => null,
        'kode_sumber' => 'TP 998',
        'nama_sumber' => 'Khusus Tambang',
        'semester' => 8,
        'sks' => 2,
    ]);

    $this->patch(route('admin.ipk-cpl.mapping.update', [
        'prodi' => $data['mining'],
        'cpl' => $data['secondCpl'],
        'mapping' => $anotherUnmatched,
    ]), ['mata_kuliah_id' => $geologyCourse->id])
        ->assertSessionHasErrors('mata_kuliah_id');

    expect($anotherUnmatched->fresh()->mata_kuliah_id)->toBeNull();
});

test('CPL 9 report override does not duplicate a course already mapped to CPL 3', function () {
    $data = makeMappedCplFixture();
    $cpl3 = Cpl::create(['program_studi_id' => $data['mining']->id, 'kode_cpl' => 'CPL 3', 'nama_cpl' => 'Pelaporan teknis', 'sort_order' => 3] + MiningCplCatalog::forCode('CPL 3'));
    $cpl9 = Cpl::create(['program_studi_id' => $data['mining']->id, 'kode_cpl' => 'CPL 9', 'nama_cpl' => 'CPL historis', 'sort_order' => 9] + MiningCplCatalog::forCode('CPL 9'));
    $course = MataKuliah::create(['kode_mk' => 'TP-REDIRECT', 'nama_mk' => 'Pelaporan Tambang', 'sks' => 2, 'semester' => 8, 'prodi_id' => $data['mining']->id]);
    CplMataKuliah::create(['cpl_id' => $cpl3->id, 'mata_kuliah_id' => $course->id, 'kode_sumber' => 'TP-REDIRECT', 'nama_sumber' => $course->nama_mk, 'semester' => 8, 'sks' => 2]);
    CplMataKuliah::create(['cpl_id' => $cpl9->id, 'mata_kuliah_id' => $course->id, 'kode_sumber' => 'TP-REDIRECT', 'nama_sumber' => $course->nama_mk, 'semester' => 8, 'sks' => 2]);

    $report = app(IpkCplReportService::class)->cplOverview($data['mining'], ['tahun_akademik' => '2024/2025']);
    $third = $report['rows']->firstWhere('kode_cpl', 'CPL 3');

    expect($third->courses)->toHaveCount(1)
        ->and($report['reportConfiguration']['redirected_mapping_count'])->toBe(0)
        ->and($report['reportConfiguration']['duplicate_mapping_count'])->toBe(1)
        ->and($report['rows']->pluck('kode_cpl')->all())->not->toContain('CPL 9');
});

test('CPL mapping audit is read only and reports safe exact candidates', function () {
    $data = makeMappedCplFixture();
    $candidate = MataKuliah::create([
        'kode_mk' => 'KU 301 (TP)',
        'nama_mk' => 'Kimia Analitik',
        'sks' => 2,
        'semester' => 3,
        'prodi_id' => $data['mining']->id,
    ]);
    $unmatched = CplMataKuliah::whereNull('mata_kuliah_id')->firstOrFail();
    $unmatched->update(['kode_sumber' => 'KU-301 (TP)', 'nama_sumber' => 'Kimia Analitik']);

    $this->artisan('ipk-cpl:audit-mapping')
        ->expectsOutputToContain('CPL aktif laporan: CPL 1, CPL 2, CPL 3, CPL 4, CPL 5, CPL 6, CPL 7, CPL 8')
        ->expectsOutputToContain('CPL 9 disembunyikan sementara')
        ->expectsOutputToContain('Pilihan Teknik Geologi disembunyikan sementara')
        ->expectsOutputToContain('Mapping bermasalah: 1')
        ->expectsOutputToContain('dapat diperbaiki aman dengan command import')
        ->assertSuccessful();

    expect($unmatched->fresh()->mata_kuliah_id)->toBeNull()
        ->and($candidate->fresh()->kode_mk)->toBe('KU 301 (TP)');
});

test('CPL mapping audit never recommends courses with a different strict code', function () {
    $mining = Prodi::create(['kode_prodi' => 'TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $cpl = Cpl::create(['program_studi_id' => $mining->id, 'kode_cpl' => 'CPL 1', 'nama_cpl' => 'CPL Audit', 'sort_order' => 1]);
    CplMataKuliah::create(['cpl_id' => $cpl->id, 'kode_sumber' => 'KU 302', 'nama_sumber' => 'Matriks Ruang Vektor', 'semester' => 2, 'sks' => 2]);
    CplMataKuliah::create(['cpl_id' => $cpl->id, 'kode_sumber' => 'TA 601', 'nama_sumber' => 'MK Pilihan 2', 'semester' => 6, 'sks' => 2]);

    MataKuliah::create(['kode_mk' => 'KU 302 (TP)', 'nama_mk' => 'Dasar Komputasi', 'sks' => 2, 'semester' => 2, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'KU 301', 'nama_mk' => 'Kimia Analitik (P)', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'KU 303 (TP)', 'nama_mk' => 'Statistika', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 301', 'nama_mk' => 'Bahan Galian Indonesia', 'sks' => 2, 'semester' => 3, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 401', 'nama_mk' => 'Metode Numerik', 'sks' => 2, 'semester' => 4, 'prodi_id' => $mining->id]);
    MataKuliah::create(['kode_mk' => 'TA 501', 'nama_mk' => 'Sistem Penambangan', 'sks' => 2, 'semester' => 5, 'prodi_id' => $mining->id]);

    $this->artisan('ipk-cpl:audit-mapping')
        ->expectsOutputToContain('KU 302 (TP) - Dasar Komputasi')
        ->expectsOutputToContain('Tidak ditemukan master dengan kode exact')
        ->doesntExpectOutputToContain('KU 301 - Kimia Analitik')
        ->doesntExpectOutputToContain('KU 303 (TP) - Statistika')
        ->doesntExpectOutputToContain('TA 301 - Bahan Galian Indonesia')
        ->doesntExpectOutputToContain('TA 401 - Metode Numerik')
        ->doesntExpectOutputToContain('TA 501 - Sistem Penambangan')
        ->assertSuccessful();
});

test('CPL detail and course detail preserve filters and list only calculated students', function () {
    $data = makeMappedCplFixture();
    $filters = ['tahun_akademik' => '2024/2025', 'angkatan' => 2024, 'tahun_studi' => 1];

    $detail = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.cpl', [
        'prodi' => $data['mining'],
        'cpl' => $data['cpl'],
    ] + $filters));

    $detail->assertOk()
        ->assertSee('Matematika Tambang')
        ->assertDontSee('Perencanaan Tambang')
        ->assertDontSee('Kelengkapan Data')
        ->assertDontSee('<th>Status</th>', false)
        ->assertSee(MiningCplCatalog::forCode('CPL 1')['deskripsi'])
        ->assertSee('KK1, P1')
        ->assertSee('4.00');

    expect($detail->viewData('row')->courses)->toHaveCount(1)
        ->and($detail->viewData('row')->ipk_cpl)->toBe(4.0);

    $students = $this->get(route('admin.ipk-cpl.course', [
        'prodi' => $data['mining'],
        'cpl' => $data['cpl'],
        'mapping' => $data['mappingA'],
    ] + $filters));

    $students->assertOk()
        ->assertSee('Mahasiswa Tambang A')
        ->assertDontSee('Mahasiswa Tambang B')
        ->assertDontSee('Mahasiswa Geologi');
    expect($students->viewData('grades'))
        ->toBeInstanceOf(LengthAwarePaginator::class)
        ->toHaveCount(1)
        ->and($students->viewData('grades')->perPage())->toBe(50);
});

test('admin can download filtered CPL reports as PDF and Excel', function () {
    $data = makeMappedCplFixture();
    $parameters = [
        'prodi' => $data['mining'],
        'tahun_akademik' => '2024/2025',
        'cpl_id' => $data['cpl']->id,
    ];

    $this->actingAs($data['admin'])
        ->get(route('admin.ipk-cpl.program.excel', $parameters))
        ->assertOk()
        ->assertDownload();

    $this->get(route('admin.ipk-cpl.program.pdf', $parameters))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($data['studentUser'])
        ->get(route('admin.ipk-cpl.program.pdf', $parameters))
        ->assertForbidden();
    $this->get(route('admin.ipk-cpl.program.excel', $parameters))
        ->assertForbidden();

    $filters = ['tahun_akademik' => '2024/2025', 'cpl_id' => $data['cpl']->id];
    $report = app(IpkCplReportService::class)->cplOverview($data['mining'], $filters);
    $webRow = $report['rows']->firstOrFail();
    $pdfHtml = view('admin.ipk-cpl.pdf', $report + [
        'filters' => $filters,
        'filterDescription' => 'Tahun Akademik 2024/2025',
    ])->render();

    expect($pdfHtml)->toContain(number_format($webRow->ipk_cpl, 2))
        ->toContain((string) $webRow->total_sks)
        ->toContain((string) $webRow->sks_dihitung)
        ->toContain((string) $webRow->mata_kuliah_bernilai);
    expect($pdfHtml)
        ->not->toContain('Kelengkapan')
        ->not->toContain('<th>Status</th>')
        ->not->toContain('Mapping Belum Cocok Master');
});

test('CPL routes are admin only and do not expose unavailable geology reports', function () {
    $data = makeMappedCplFixture();

    $this->actingAs($data['studentUser'])
        ->get(route('admin.ipk-cpl.program', $data['mining']))
        ->assertForbidden();
    $this->actingAs($data['lecturerUser'])
        ->get(route('admin.ipk-cpl.program', $data['mining']))
        ->assertForbidden();
    $this->actingAs($data['admin'])
        ->get(route('admin.ipk-cpl.program', $data['geology']))
        ->assertNotFound();
});

function makeApprovedOverrideFixture(): array
{
    $data = makeMappedCplFixture();
    $targets = [
        MataKuliah::create(['kode_mk' => 'KU 302 (TP)', 'nama_mk' => 'Dasar Komputasi', 'prodi_id' => $data['mining']->id, 'sks' => 2, 'semester' => 3]),
        MataKuliah::create(['kode_mk' => 'KU 106', 'nama_mk' => 'Pengantar Ilmu Kebumian dan Pertambangan', 'prodi_id' => $data['mining']->id, 'sks' => 2, 'semester' => 1]),
    ];
    $seventh = Cpl::create(['program_studi_id' => $data['mining']->id, 'kode_cpl' => 'CPL 7', 'nama_cpl' => 'CPL 7', 'sort_order' => 7]);
    $approved = collect([
        [$data['cpl'], 'KU-302', 'Matriks Ruang Vektor', 3],
        [$data['cpl'], 'TA 106', 'Pengantar Ilmu Kebumian & Pertambangan', 1],
        [$data['secondCpl'], 'TA601', 'MK Pilihan 2', 6],
        [$seventh, 'TA 601', 'MK Pilihan 2', 6],
    ])->map(fn ($row) => CplMataKuliah::create([
        'cpl_id' => $row[0]->id, 'kode_sumber' => $row[1], 'nama_sumber' => $row[2], 'semester' => $row[3], 'sks' => 2,
    ]));

    return $data + compact('targets', 'approved');
}

test('approved override command updates four mappings idempotently and preserves academic records', function () {
    $data = makeApprovedOverrideFixture();
    $beforeGrades = Khs::orderBy('id')->get()->toArray();
    $beforeKrs = Krs::orderBy('id')->get()->toArray();
    $beforeCourses = MataKuliah::orderBy('id')->get()->toArray();
    $beforeUnrelated = CplMataKuliah::whereNotIn('id', $data['approved']->pluck('id'))->orderBy('id')->get()->toArray();

    $this->artisan('ipk-cpl:audit-mapping')->expectsOutputToContain('Mapping bermasalah: 5')->assertSuccessful();
    $this->artisan('ipk-cpl:apply-overrides')
        ->expectsOutputToContain('Mapping diperbarui: 4')
        ->expectsOutputToContain('Master TA 601 dibuat.')
        ->expectsOutputToContain('Mapping manual override: 4')
        ->expectsOutputToContain('Mapping bermasalah: 1')->assertSuccessful();
    $elective = MataKuliah::where('kode_mk', 'TA 601')->sole();
    expect($elective->nama_mk)->toBe('MK Pilihan 2')
        ->and((int) $elective->prodi_id)->toBe($data['mining']->id)
        ->and((int) $elective->sks)->toBe(2)->and((int) $elective->semester)->toBe(6);
    expect($data['approved'][0]->fresh()->mata_kuliah_id)->toBe($data['targets'][0]->id)
        ->and($data['approved'][1]->fresh()->mata_kuliah_id)->toBe($data['targets'][1]->id)
        ->and($data['approved'][2]->fresh()->mata_kuliah_id)->toBe($elective->id)
        ->and($data['approved'][3]->fresh()->mata_kuliah_id)->toBe($elective->id);
    $firstMappings = CplMataKuliah::orderBy('id')->get()->toArray();
    $this->travel(1)->days();
    $this->artisan('ipk-cpl:apply-overrides')->expectsOutputToContain('Mapping diperbarui: 0')->assertSuccessful();
    expect(MataKuliah::where('kode_mk', 'TA 601')->count())->toBe(1)
        ->and(CplMataKuliah::orderBy('id')->get()->toArray())->toBe($firstMappings)
        ->and(Khs::orderBy('id')->get()->toArray())->toBe($beforeGrades)
        ->and(Krs::orderBy('id')->get()->toArray())->toBe($beforeKrs)
        ->and(MataKuliah::where('id', '!=', $elective->id)->orderBy('id')->get()->toArray())->toBe($beforeCourses)
        ->and(CplMataKuliah::whereNotIn('id', $data['approved']->pluck('id'))->orderBy('id')->get()->toArray())->toBe($beforeUnrelated);
});

test('manual targets are used by web PDF Excel and course filters without changing grade values', function () {
    $data = makeApprovedOverrideFixture();
    $lecturer = Dosen::firstOrFail();
    $grade = createMappedCplGrade($data['studentA'], $data['targets'][0], $lecturer, '2024/2025', 'Ganjil', 80, 'B', 3);
    $beforeGrades = Khs::orderBy('id')->get()->toArray();
    $this->artisan('ipk-cpl:apply-overrides')->assertSuccessful();
    $parameters = ['prodi' => $data['mining'], 'tahun_akademik' => '2024/2025', 'mata_kuliah_id' => $data['targets'][0]->id];
    $response = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.program', $parameters));
    $response->assertOk()->assertSee('3.00')->assertSee('Mapping Manual Override')->assertDontSee('Status CPL')->assertDontSee('Kelengkapan Data CPL');
    expect($response->viewData('rows')->first()->ipk_cpl)->toBe(3.0)
        ->and($response->viewData('mappingSummary')['manual_override'])->toBe(4)
        ->and($response->viewData('courseOptions')->pluck('mata_kuliah_id')->all())->toContain($data['targets'][0]->id)
        ->and(substr_count($response->getContent(), '<canvas'))->toBe(1);
    $this->get(route('admin.ipk-cpl.course', ['prodi' => $data['mining'], 'cpl' => $data['cpl'], 'mapping' => $data['approved'][0], 'tahun_akademik' => '2024/2025']))->assertOk()->assertSee('Mahasiswa Tambang A');
    $this->get(route('admin.ipk-cpl.program.pdf', $parameters))->assertOk()->assertDownload();
    $this->get(route('admin.ipk-cpl.program.excel', $parameters))->assertOk()->assertDownload();
    $report = app(IpkCplReportService::class)->cplOverview($data['mining'], $parameters);
    $pdfHtml = view('admin.ipk-cpl.pdf', $report + ['filterDescription' => '2024/2025'])->render();
    expect($pdfHtml)->toContain('3.00')->toContain('Dasar Komputasi')->toContain('keputusan admin/prodi')
        ->not->toContain('Status CPL')->not->toContain('Kelengkapan Data CPL');
    expect(Khs::orderBy('id')->get()->toArray())->toBe($beforeGrades);
});

test('TA601 uses an existing normalized TP master and never selects a different elective or final project', function () {
    $data = makeApprovedOverrideFixture();
    foreach (['TA 301', 'TA 401', 'TA 501', 'TA 801'] as $code) {
        MataKuliah::create(['kode_mk' => $code, 'nama_mk' => $code === 'TA 801' ? 'Tugas Akhir' : 'MK Pilihan 2', 'sks' => 2, 'semester' => 6, 'prodi_id' => $data['mining']->id]);
    }
    $existing = MataKuliah::create(['kode_mk' => 'TA-601 (TP)', 'nama_mk' => 'MK Pilihan 2', 'sks' => 2, 'semester' => 6, 'prodi_id' => $data['mining']->id]);
    $before = MataKuliah::orderBy('id')->get()->toArray();
    $this->artisan('ipk-cpl:apply-overrides')->expectsOutputToContain('Tidak ada master baru dibuat.')->assertSuccessful();
    expect($data['approved'][2]->fresh()->mata_kuliah_id)->toBe($existing->id)
        ->and(MataKuliah::orderBy('id')->get()->toArray())->toBe($before);
});

test('manual overrides reject missing required SKS and do not cross program or CPL boundaries', function () {
    $data = makeApprovedOverrideFixture();
    $data['approved'][2]->update(['sks' => null]);
    $data['approved'][3]->update(['sks' => null]);
    $this->artisan('ipk-cpl:apply-overrides')->expectsOutputToContain('membutuhkan SKS')->assertFailed();
    expect(MataKuliah::where('kode_mk', 'TA 601')->exists())->toBeFalse();
    $overrides = app(CplManualOverrides::class);
    expect($overrides->decision('KU302', 'Matriks Ruang Vektor', 'CPL 2', $data['mining']))->toBeNull()
        ->and($overrides->decision('KU302', 'Matriks Ruang Vektor', 'CPL 1', $data['geology']))->toBeNull()
        ->and($overrides->decision('KU302 (TG)', 'Matriks Ruang Vektor', 'CPL 1', $data['mining']))->toBeNull();
    $data['targets'][0]->update(['prodi_id' => $data['geology']->id]);
    expect($overrides->accepted($data['approved'][0]->fresh(), $data['mining']))->toBeFalse();
});

test('TA601 collision with a geology or differently named master is reported without mutation', function () {
    $data = makeApprovedOverrideFixture();
    $collision = MataKuliah::create(['kode_mk' => 'TA 601', 'nama_mk' => 'Tugas Akhir', 'sks' => 6, 'semester' => 8, 'prodi_id' => $data['geology']->id]);
    $before = $collision->fresh()->toArray();
    $this->artisan('ipk-cpl:apply-overrides')->expectsOutputToContain('nama/prodi berbeda')->assertFailed();
    expect($data['approved'][2]->fresh()->mata_kuliah_id)->toBeNull()
        ->and($data['approved'][3]->fresh()->mata_kuliah_id)->toBeNull()
        ->and($collision->fresh()->toArray())->toBe($before)
        ->and(MataKuliah::where('kode_mk', 'TA 601')->count())->toBe(1);
});

test('full Excel catalog audits 146 mappings as 142 automatic plus four manual after repair', function () {
    $program = Prodi::create(['kode_prodi' => 'TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $importer = app(CplMappingImporter::class);
    $importer->import(database_path('data/ipkcpl.xlsx'));
    $overrides = app(CplManualOverrides::class);
    $mappings = CplMataKuliah::with('cpl')->get();
    foreach ($mappings->unique('kode_sumber') as $mapping) {
        $decision = $overrides->decision($mapping->kode_sumber, $mapping->nama_sumber, $mapping->cpl->kode_cpl, $program);
        $code = $decision['target_code'] ?? $mapping->kode_sumber;
        $name = $decision['target_name'] ?? $mapping->nama_sumber;
        MataKuliah::firstOrCreate(['kode_mk' => $code], [
            'nama_mk' => $name, 'sks' => $mapping->sks, 'semester' => $mapping->semester, 'prodi_id' => $program->id,
        ]);
    }
    $result = $importer->import(database_path('data/ipkcpl.xlsx'));
    expect($result['mappings'])->toBe(146)->and($result['manual_overrides'])->toBe(4)->and($result['unmatched'])->toBeEmpty();
    foreach ($mappings as $mapping) {
        if ($overrides->decision($mapping->kode_sumber, $mapping->nama_sumber, $mapping->cpl->kode_cpl, $program)) {
            $mapping->refresh()->update(['mata_kuliah_id' => null]);
        }
    }
    $this->artisan('ipk-cpl:audit-mapping')->expectsOutputToContain('Total mapping: 146')
        ->expectsOutputToContain('Mapping aman: 142')->expectsOutputToContain('Mapping bermasalah: 4')->assertSuccessful();
    $this->artisan('ipk-cpl:apply-overrides')->expectsOutputToContain('Total mapping: 146')
        ->expectsOutputToContain('Mapping aman: 142')->expectsOutputToContain('Mapping manual override: 4')
        ->expectsOutputToContain('Mapping bermasalah: 0')->expectsOutputToContain('Mata kuliah sumber unik bermasalah: 0')->assertSuccessful();
    $masterCount = MataKuliah::count();
    $result = $importer->import(database_path('data/ipkcpl.xlsx'));
    expect($result['manual_overrides'])->toBe(4)->and($result['unmatched'])->toBeEmpty()
        ->and(CplMataKuliah::count())->toBe(146)->and(MataKuliah::count())->toBe($masterCount);
});

test('manual mapping endpoint rejects TA301 for an approved TA601 source', function () {
    $data = makeApprovedOverrideFixture();
    $wrong = MataKuliah::create(['kode_mk' => 'TA 301', 'nama_mk' => 'MK Pilihan 2', 'sks' => 2, 'semester' => 3, 'prodi_id' => $data['mining']->id]);
    $this->actingAs($data['admin'])->patch(route('admin.ipk-cpl.mapping.update', [
        'prodi' => $data['mining'], 'cpl' => $data['secondCpl'], 'mapping' => $data['approved'][2],
    ]), ['mata_kuliah_id' => $wrong->id])->assertSessionHasErrors('mata_kuliah_id');
    expect($data['approved'][2]->fresh()->mata_kuliah_id)->toBeNull()
        ->and($data['approved'][3]->fresh()->mata_kuliah_id)->toBeNull();
});

test('approved KU106 prefers TP but can use an unambiguous general master', function () {
    $data = makeApprovedOverrideFixture();
    $data['targets'][1]->update(['prodi_id' => null]);
    $this->artisan('ipk-cpl:apply-overrides')->assertSuccessful();
    expect($data['approved'][1]->fresh()->mata_kuliah_id)->toBe($data['targets'][1]->id);
    $preferred = MataKuliah::create(['kode_mk' => 'KU-106 (TP)', 'nama_mk' => 'Pengantar Ilmu Kebumian dan Pertambangan', 'sks' => 2, 'semester' => 1, 'prodi_id' => $data['mining']->id]);
    $this->artisan('ipk-cpl:apply-overrides')->assertSuccessful();
    expect($data['approved'][1]->fresh()->mata_kuliah_id)->toBe($preferred->id);
});
