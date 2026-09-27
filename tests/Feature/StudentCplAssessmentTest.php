<?php

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Models\User;
use App\Services\StudentCplAssessmentService;
use Illuminate\Support\Facades\DB;

function makeStudentCplAssessmentData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $advisorUser = User::factory()->create(['role' => 'dosen']);
    $otherAdvisorUser = User::factory()->create(['role' => 'dosen']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $program = Prodi::create(['kode_prodi' => 'TP-CPL-MHS', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $advisor = Dosen::create(['nidn' => 'CPL-WALI-1', 'nama' => 'Wali CPL', 'prodi_id' => $program->id, 'user_id' => $advisorUser->id]);
    $otherAdvisor = Dosen::create(['nidn' => 'CPL-WALI-2', 'nama' => 'Wali Lain CPL', 'prodi_id' => $program->id, 'user_id' => $otherAdvisorUser->id]);
    $student = Mahasiswa::create(['nim' => 'CPL26001', 'nama' => 'Mahasiswa Profil CPL', 'angkatan' => 2026, 'semester' => 3, 'prodi_id' => $program->id, 'dosen_wali_id' => $advisor->id, 'user_id' => $studentUser->id]);
    $otherStudent = Mahasiswa::create(['nim' => 'CPL26002', 'nama' => 'Mahasiswa Rahasia CPL', 'angkatan' => 2026, 'semester' => 3, 'prodi_id' => $program->id, 'dosen_wali_id' => $otherAdvisor->id, 'user_id' => $otherStudentUser->id]);

    $cpl1 = Cpl::create(['program_studi_id' => $program->id, 'kode_cpl' => 'CPL 1', 'nama_cpl' => 'CPL Rekayasa', 'sort_order' => 1]);
    $cpl2 = Cpl::create(['program_studi_id' => $program->id, 'kode_cpl' => 'CPL 2', 'nama_cpl' => 'CPL Lingkungan', 'sort_order' => 2]);
    $cpl9 = Cpl::create(['program_studi_id' => $program->id, 'kode_cpl' => 'CPL 9', 'nama_cpl' => 'CPL Tersembunyi', 'sort_order' => 9]);

    $courseA = MataKuliah::create(['kode_mk' => 'TPC101', 'nama_mk' => 'Rekayasa Dasar CPL', 'sks' => 3, 'semester' => 1, 'prodi_id' => $program->id]);
    $courseB = MataKuliah::create(['kode_mk' => 'TPC102', 'nama_mk' => 'Analisis Dasar CPL', 'sks' => 2, 'semester' => 1, 'prodi_id' => $program->id]);
    $excludedCourse = MataKuliah::create(['kode_mk' => 'KU 302', 'nama_mk' => 'Matriks Ruang Vektor', 'sks' => 3, 'semester' => 1, 'prodi_id' => $program->id]);
    foreach ([[$cpl1, $courseA], [$cpl1, $courseB], [$cpl2, $courseA], [$cpl9, $courseB]] as [$cpl, $course]) {
        CplMataKuliah::create(['cpl_id' => $cpl->id, 'mata_kuliah_id' => $course->id, 'kode_sumber' => $course->kode_mk, 'nama_sumber' => $course->nama_mk, 'semester' => 1, 'sks' => $course->sks]);
    }
    CplMataKuliah::create(['cpl_id' => $cpl1->id, 'mata_kuliah_id' => $excludedCourse->id, 'kode_sumber' => 'KU 302', 'nama_sumber' => 'Matriks Ruang Vektor', 'semester' => 1, 'sks' => 3]);

    $createGrade = function (Mahasiswa $owner, MataKuliah $course, float $weight, bool $answered = true) use ($advisor): Khs {
        $schedule = Jadwal::create(['mata_kuliah_id' => $course->id, 'dosen_id' => $advisor->id, 'hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00', 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
        $krs = Krs::create(['mahasiswa_id' => $owner->id, 'jadwal_id' => $schedule->id, 'status' => 'Disetujui', 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
        $grade = Khs::create(['krs_id' => $krs->id, 'nilai_angka' => $weight === 4.0 ? 90 : 65, 'nilai_huruf' => $weight === 4.0 ? 'A' : 'C', 'bobot' => $weight, 'sks' => $course->sks, 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
        if ($answered) {
            DB::table('kuesioners')->insert(['krs_id' => $krs->id, 'penguasaan_materi' => 4, 'kejelasan_penyampaian' => 4, 'kesesuaian_rps' => 4, 'ketepatan_waktu' => 4, 'kesempatan_bertanya' => 4, 'objektivitas_penilaian' => 4, 'penggunaan_media' => 4, 'motivasi_belajar' => 4, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }

        return $grade;
    };
    $createGrade($student, $courseA, 4.0);
    $createGrade($student, $courseB, 2.0);
    $createGrade($student, $excludedCourse, 4.0);
    $createGrade($otherStudent, $courseA, 3.0, false);

    return compact('admin', 'advisorUser', 'otherAdvisorUser', 'studentUser', 'otherStudentUser', 'student', 'otherStudent', 'program');
}

test('student CPL assessment calculates SKS weighted score and supports multi CPL courses', function () {
    $data = makeStudentCplAssessmentData();
    $profile = app(StudentCplAssessmentService::class)->profile($data['student']);

    expect($profile['aspects']->firstWhere('kode_cpl', 'CPL 1')->score)->toBe(3.2)
        ->and($profile['aspects']->firstWhere('kode_cpl', 'CPL 2')->score)->toBe(4.0)
        ->and($profile['strongest']->kode_cpl)->toBe('CPL 2')
        ->and($profile['aspects']->pluck('kode_cpl'))->not->toContain('CPL 9');
});

test('student sees friendly labels and can only open their own assessment', function () {
    $data = makeStudentCplAssessmentData();

    $this->actingAs($data['studentUser'])->get(route('mahasiswa.penilaian'))
        ->assertOk()
        ->assertSee('Penilaian Mahasiswa')
        ->assertSee('Kemampuan Rekayasa, Sains, dan Matematika')
        ->assertDontSee('CPL 1')
        ->assertDontSee('Mahasiswa Rahasia CPL');

    $this->get('/admin/penilaian-cpl-mahasiswa/'.$data['otherStudent']->id)->assertForbidden();
    $this->get('/dosen/penilaian-mahasiswa/'.$data['otherStudent']->id)->assertForbidden();
});

test('student visibility excludes grades whose questionnaire is still locked', function () {
    $data = makeStudentCplAssessmentData();
    $service = app(StudentCplAssessmentService::class);

    expect($service->profile($data['otherStudent'], [], true)['scored_aspects'])->toBe(0)
        ->and($service->profile($data['otherStudent'])['scored_aspects'])->toBeGreaterThan(0);
});

test('advisor only sees advisees while admin sees all mining students', function () {
    $data = makeStudentCplAssessmentData();

    $this->actingAs($data['advisorUser'])->get(route('dosen.penilaian-mahasiswa.index'))
        ->assertOk()->assertSee('Mahasiswa Profil CPL')->assertDontSee('Mahasiswa Rahasia CPL');
    $this->get(route('dosen.penilaian-mahasiswa.show', $data['otherStudent']))->assertForbidden();

    $this->actingAs($data['admin'])->get(route('admin.penilaian-cpl-mahasiswa.index'))
        ->assertOk()->assertSee('Mahasiswa Profil CPL')->assertSee('Mahasiswa Rahasia CPL');
});
