<?php

use App\Models\Dosen;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;

function createIpkCplGrade(
    Mahasiswa $student,
    MataKuliah $course,
    Dosen $lecturer,
    Prodi $program,
    string $academicYear,
    string $academicSemester,
    mixed $score,
    ?string $letter,
    mixed $weight
): Khs {
    $krs = Krs::create([
        'mahasiswa_id' => $student->id,
        'jadwal_id' => null,
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $lecturer->id,
        'prodi_id' => $program->id,
        'angkatan' => $student->angkatan,
        'semester' => $student->semester,
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

function makeIpkCplReportFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $programA = Prodi::create(['kode_prodi' => 'CPL-A', 'nama_prodi' => 'Program CPL A', 'jenjang' => 'S1']);
    $programB = Prodi::create(['kode_prodi' => 'CPL-B', 'nama_prodi' => 'Program CPL B', 'jenjang' => 'S1']);
    $lecturerA = Dosen::create(['nidn' => 'CPL-DOSEN-A', 'nama' => 'Dosen CPL A', 'prodi_id' => $programA->id]);
    $lecturerB = Dosen::create(['nidn' => 'CPL-DOSEN-B', 'nama' => 'Dosen CPL B', 'prodi_id' => $programB->id]);
    $studentA1 = Mahasiswa::create([
        'nim' => 'CPL-A-001', 'nama' => 'Mahasiswa CPL A1', 'angkatan' => 2024,
        'semester' => 1, 'prodi_id' => $programA->id, 'user_id' => $studentUser->id,
    ]);
    $studentA2 = Mahasiswa::create([
        'nim' => 'CPL-A-002', 'nama' => 'Mahasiswa CPL A2', 'angkatan' => 2024,
        'semester' => 1, 'prodi_id' => $programA->id,
    ]);
    $studentA3 = Mahasiswa::create([
        'nim' => 'CPL-A-003', 'nama' => 'Mahasiswa CPL A3', 'angkatan' => 2024,
        'semester' => 1, 'prodi_id' => $programA->id,
    ]);
    $studentB = Mahasiswa::create([
        'nim' => 'CPL-B-001', 'nama' => 'Mahasiswa CPL B', 'angkatan' => 2023,
        'semester' => 2, 'prodi_id' => $programB->id,
    ]);
    $courseA = MataKuliah::create([
        'kode_mk' => 'CPL-A-101', 'nama_mk' => 'Matematika I', 'sks' => 3,
        'semester' => 1, 'prodi_id' => $programA->id,
    ]);
    $courseB = MataKuliah::create([
        'kode_mk' => 'CPL-B-101', 'nama_mk' => 'Matematika I', 'sks' => 3,
        'semester' => 2, 'prodi_id' => $programB->id,
    ]);

    $oldDuplicate = createIpkCplGrade($studentA1, $courseA, $lecturerA, $programA, '2024/2025', 'Ganjil', 40, 'D', 1);
    $latestDuplicate = createIpkCplGrade($studentA1, $courseA, $lecturerA, $programA, '2024/2025', 'Ganjil', 80, 'A-', 3.75);
    $gradeA2 = createIpkCplGrade($studentA2, $courseA, $lecturerA, $programA, '2024/2025', 'Ganjil', 60, 'C+', 2.5);
    $emptyGrade = createIpkCplGrade($studentA3, $courseA, $lecturerA, $programA, '2024/2025', 'Ganjil', null, null, null);
    $gradeB = createIpkCplGrade($studentB, $courseB, $lecturerB, $programB, '2024/2025', 'Genap', 90, 'A', 4);

    return compact(
        'admin', 'studentUser', 'programA', 'programB', 'lecturerA', 'lecturerB',
        'courseA', 'courseB', 'oldDuplicate', 'latestDuplicate', 'gradeA2', 'emptyGrade', 'gradeB'
    );
}

test('IPK CPL reports latest valid grades per course without mixing course codes', function () {
    $data = makeIpkCplReportFixture();

    $response = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.index', [
        'tahun_akademik' => '2024/2025',
    ]));

    $response->assertOk()
        ->assertSee('IPK CPL')
        ->assertSee('CPL-A-101')
        ->assertSee('CPL-B-101')
        ->assertSee('Matematika I')
        ->assertSee('Download Excel');

    $rows = collect($response->viewData('rows')->items())->keyBy('kode_mata_kuliah');
    $rowA = $rows['CPL-A-101'];
    $rowB = $rows['CPL-B-101'];

    expect($rows)->toHaveCount(2)
        ->and($rowA->jumlah_mahasiswa)->toBe(2)
        ->and($rowA->rata_nilai)->toBe(70.0)
        ->and($rowA->rata_bobot)->toBe(3.13)
        ->and($rowA->nilai_a)->toBe(1)
        ->and($rowA->nilai_c)->toBe(1)
        ->and($rowA->nilai_d)->toBe(0)
        ->and($rowA->persentase_lulus)->toBe(100.0)
        ->and($rowB->jumlah_mahasiswa)->toBe(1)
        ->and($response->viewData('summary')['total_data_nilai'])->toBe(3)
        ->and($response->viewData('summary')['rata_rata_keseluruhan'])->toBe(76.67);

    expect((float) $data['oldDuplicate']->fresh()->nilai_angka)->toBe(40.0)
        ->and((float) $data['latestDuplicate']->fresh()->nilai_angka)->toBe(80.0)
        ->and($data['emptyGrade']->fresh()->nilai_angka)->toBeNull();
});

test('IPK CPL filters program semester cohort course and lecturer', function () {
    $data = makeIpkCplReportFixture();

    $response = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.index', [
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
        'prodi_id' => $data['programA']->id,
        'angkatan' => 2024,
        'mata_kuliah_id' => $data['courseA']->id,
        'dosen_id' => $data['lecturerA']->id,
    ]));

    $response->assertOk()
        ->assertSee('CPL-A-101');

    expect($response->viewData('rows')->total())->toBe(1)
        ->and($response->viewData('rows')->first()->kode_mata_kuliah)->toBe('CPL-A-101')
        ->and($response->viewData('summary')['total_mata_kuliah'])->toBe(1);
});

test('IPK CPL separates numeric course semesters and shows only matching latest student grades', function () {
    $data = makeIpkCplReportFixture();

    $index = $this->actingAs($data['admin'])->get(route('admin.ipk-cpl.index', [
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
        'semester_angka' => 1,
    ]));

    $index->assertOk()
        ->assertSee('Semester 1')
        ->assertSee('Detail Mahasiswa');

    expect($index->viewData('rows')->total())->toBe(1)
        ->and($index->viewData('rows')->first()->semester_angka)->toBe(1);

    $detail = $this->get(route('admin.ipk-cpl.show', [
        'mataKuliah' => $data['courseA'],
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
        'prodi_id' => $data['programA']->id,
        'semester_angka' => 1,
    ]));

    $detail->assertOk()
        ->assertSee('Mahasiswa CPL A1')
        ->assertSee('Mahasiswa CPL A2')
        ->assertDontSee('Mahasiswa CPL A3');

    expect($detail->viewData('grades'))->toHaveCount(2)
        ->and($detail->viewData('summary')->rata_nilai)->toBe(70.0);
});

test('IPK CPL Excel follows filters and routes are admin only', function () {
    $data = makeIpkCplReportFixture();

    $this->actingAs($data['admin'])
        ->get(route('admin.ipk-cpl.excel', [
            'tahun_akademik' => '2024/2025',
            'prodi_id' => $data['programA']->id,
        ]))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($data['studentUser'])
        ->get(route('admin.ipk-cpl.index'))
        ->assertForbidden();
});

test('IPK CPL shows a safe empty state when no final grades exist', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.ipk-cpl.index'));

    $response->assertOk()->assertSee('Belum ada data untuk filter yang dipilih.');
    expect($response->viewData('summary'))->toMatchArray([
        'total_mata_kuliah' => 0,
        'total_data_nilai' => 0,
        'rata_rata_keseluruhan' => null,
    ]);
});
