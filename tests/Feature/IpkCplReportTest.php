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
    $cpl = Cpl::create(['program_studi_id' => $mining->id, 'kode_cpl' => 'CPL 1', 'nama_cpl' => 'Rekayasa pertambangan', 'sort_order' => 1]);
    $secondCpl = Cpl::create(['program_studi_id' => $mining->id, 'kode_cpl' => 'CPL 2', 'nama_cpl' => 'Rekayasa berkelanjutan', 'sort_order' => 2]);
    $mappingA = CplMataKuliah::create(['cpl_id' => $cpl->id, 'mata_kuliah_id' => $courseA->id, 'kode_sumber' => 'TP 101', 'nama_sumber' => $courseA->nama_mk, 'semester' => 1, 'sks' => 3]);
    $mappingB = CplMataKuliah::create(['cpl_id' => $cpl->id, 'mata_kuliah_id' => $courseB->id, 'kode_sumber' => 'TP 301', 'nama_sumber' => $courseB->nama_mk, 'semester' => 3, 'sks' => 2]);
    CplMataKuliah::create(['cpl_id' => $secondCpl->id, 'mata_kuliah_id' => null, 'kode_sumber' => 'TP 999', 'nama_sumber' => 'Belum Ada di Master', 'semester' => 8, 'sks' => 2]);

    $oldDuplicate = createMappedCplGrade($studentA, $courseA, $lecturer, '2024/2025', 'Ganjil', 60, 'C', 2);
    $latestA = createMappedCplGrade($studentA, $courseA, $lecturer, '2024/2025', 'Ganjil', 90, 'A', 4);
    $latestB = createMappedCplGrade($studentB, $courseA, $lecturer, '2024/2025', 'Ganjil', 80, 'B', 3);
    $courseBGrade = createMappedCplGrade($studentA, $courseB, $lecturer, '2024/2025', 'Ganjil', 65, 'C', 2);
    $geologyGrade = createMappedCplGrade($studentGeology, $courseA, $lecturer, '2024/2025', 'Ganjil', 95, 'A', 4);

    return compact(
        'admin', 'studentUser', 'lecturerUser', 'mining', 'geology', 'studentA', 'studentB',
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
        ->and(CplMataKuliah::whereNotNull('mata_kuliah_id')->count())->toBe(1)
        ->and(CplMataKuliah::whereNull('mata_kuliah_id')->count())->toBe(145);

    $this->assertDatabaseHas('cpl_mata_kuliah', [
        'kode_sumber' => 'KU 103',
        'mata_kuliah_id' => $course->id,
    ]);
    $this->assertDatabaseHas('cpl_mata_kuliah', [
        'kode_sumber' => 'TA 801',
        'mata_kuliah_id' => null,
    ]);

    $this->artisan('ipk-cpl:import')->assertSuccessful();
    expect(CplMataKuliah::count())->toBe(146);
});

test('admin CPL report uses weighted SKS averages latest grades and mining students only', function () {
    $data = makeMappedCplFixture();

    $landing = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.index'));
    $landing->assertOk()
        ->assertSee('CPL Teknik Pertambangan')
        ->assertSee('CPL Teknik Geologi')
        ->assertSee('Data CPL Teknik Geologi belum tersedia.');

    $program = $this->get(route('admin.ipk-cpl.program', [
        'prodi' => $data['mining'],
        'tahun_akademik' => '2024/2025',
    ]));
    $program->assertOk()->assertSee('2.90')->assertSee('Rekayasa pertambangan');

    $first = $program->viewData('rows')->firstWhere('kode_cpl', 'CPL 1');
    expect($first->ipk_cpl)->toBe(2.9)
        ->and($first->courses->firstWhere('mata_kuliah_id', $data['courseA']->id)->rata_bobot)->toBe(3.5)
        ->and($first->courses->firstWhere('mata_kuliah_id', $data['courseA']->id)->jumlah_mahasiswa)->toBe(2);

    expect((float) $data['oldDuplicate']->fresh()->bobot)->toBe(2.0)
        ->and((float) $data['geologyGrade']->fresh()->bobot)->toBe(4.0);
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
    expect($students->viewData('grades'))->toHaveCount(1);
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
