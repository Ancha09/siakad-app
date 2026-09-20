<?php

use App\Models\Dosen;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;

function makeAdminSemesterKhsFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $program = Prodi::create(['kode_prodi' => 'KHS-TI', 'nama_prodi' => 'Informatika KHS', 'jenjang' => 'S1']);
    $lecturer = Dosen::create(['nidn' => 'KHS-DOSEN', 'nama' => 'Dosen KHS', 'prodi_id' => $program->id, 'user_id' => $lecturerUser->id]);
    $student = Mahasiswa::create([
        'nim' => 'KHS-001', 'nama' => 'Mahasiswa KHS Semester', 'angkatan' => 2024,
        'semester' => 2, 'prodi_id' => $program->id,
    ]);
    $oddCourse = MataKuliah::create([
        'kode_mk' => 'KHS101', 'nama_mk' => 'Mata Kuliah Ganjil', 'sks' => 3,
        'semester' => 1, 'prodi_id' => $program->id,
    ]);
    $evenCourse = MataKuliah::create([
        'kode_mk' => 'KHS102', 'nama_mk' => 'Mata Kuliah Genap', 'sks' => 2,
        'semester' => 2, 'prodi_id' => $program->id,
    ]);

    $makeGrade = function (MataKuliah $course, string $term, float $score, string $letter, float $weight) use ($student, $lecturer, $program): Khs {
        $krs = Krs::create([
            'mahasiswa_id' => $student->id,
            'mata_kuliah_id' => $course->id,
            'dosen_id' => $lecturer->id,
            'prodi_id' => $program->id,
            'angkatan' => $student->angkatan,
            'semester' => $course->semester,
            'status' => 'Disetujui',
            'tahun_akademik' => '2024/2025',
            'semester_akademik' => $term,
            'is_manual' => true,
        ]);

        return Khs::create([
            'krs_id' => $krs->id,
            'nilai_angka' => $score,
            'nilai_huruf' => $letter,
            'bobot' => $weight,
            'sks' => $course->sks,
            'tahun_akademik' => '2024/2025',
            'semester_akademik' => $term,
            'is_manual' => true,
            'dosen_id' => $lecturer->id,
            'dosen_override' => true,
        ]);
    };

    $makeGrade($oddCourse, 'Ganjil', 70, 'B', 3);
    $makeGrade($evenCourse, 'Genap', 90, 'A', 4);

    return compact('admin', 'lecturerUser', 'program', 'student', 'oddCourse', 'evenCourse');
}

test('admin KHS list groups students per semester and filters numeric course semester', function () {
    $data = makeAdminSemesterKhsFixture();

    $response = $this->actingAs($data['admin'])->get(route('admin.khs', [
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
        'semester_angka' => 1,
        'prodi_id' => $data['program']->id,
        'angkatan' => 2024,
        'search' => 'KHS-001',
    ]));

    $response->assertOk()
        ->assertSee('Mahasiswa KHS Semester')
        ->assertSee('Semester 1')
        ->assertSee('Detail KHS');

    expect($response->viewData('summaries')->total())->toBe(1)
        ->and($response->viewData('summaries')->first()->ips)->toBe(3.0);
});

test('admin KHS detail is isolated to the requested term and calculates cumulative GPA up to that term', function () {
    $data = makeAdminSemesterKhsFixture();

    $response = $this->actingAs($data['admin'])->get(route('admin.khs.show', [
        'mahasiswa' => $data['student'],
        'tahun_akademik' => '2024/2025',
        'semester_akademik' => 'Ganjil',
    ]));

    $response->assertOk()
        ->assertSee('Mata Kuliah Ganjil')
        ->assertDontSee('Mata Kuliah Genap')
        ->assertSee('IPK Kumulatif s.d. Semester Ini');

    expect($response->viewData('grades'))->toHaveCount(1)
        ->and($response->viewData('ips'))->toBe(3.0)
        ->and($response->viewData('ipk'))->toBe(3.0);

    $this->actingAs($data['lecturerUser'])
        ->get(route('admin.khs.show', [
            'mahasiswa' => $data['student'],
            'tahun_akademik' => '2024/2025',
            'semester_akademik' => 'Ganjil',
        ]))
        ->assertForbidden();
});
