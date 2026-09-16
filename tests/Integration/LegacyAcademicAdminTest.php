<?php

namespace Tests\Integration;

use App\Models\Khs;
use App\Models\Krs;
use App\Models\Presensi;
use App\Models\User;
use App\Services\LegacyListNavigation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyAcademicAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->unique();
            $table->string('role');
            $table->boolean('is_active')->default(true);
        });
        (require database_path('migrations/2026_09_06_120000_create_pengumuman_tables.php'))->up();
        foreach (['prodis' => ['nama_prodi'], 'dosens' => ['nama'], 'kelas' => ['nama_kelas']] as $name => $fields) {
            Schema::create($name, function (Blueprint $table) use ($fields) {
                $table->id();
                foreach ($fields as $field) {
                    $table->string($field);
                }
                $table->timestamps();
            });
        }
        Schema::table('dosens', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        Schema::create('mahasiswas', function (Blueprint $table) {
            $table->id();
            $table->string('nim')->unique();
            $table->string('nama');
            $table->integer('angkatan')->nullable();
            $table->unsignedBigInteger('prodi_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('mata_kuliahs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mk');
            $table->string('nama_mk');
            $table->integer('sks');
            $table->timestamps();
        });
        Schema::create('jadwals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mata_kuliah_id');
            $table->unsignedBigInteger('dosen_id')->nullable();
            $table->unsignedBigInteger('kelas_id')->nullable();
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->timestamps();
        });
        Schema::create('krs', function (Blueprint $table) {
            $table->id();
            foreach (['mahasiswa_id', 'jadwal_id', 'mata_kuliah_id', 'dosen_id', 'prodi_id', 'kelas_id'] as $field) {
                $table->unsignedBigInteger($field)->nullable();
            }
            $table->integer('angkatan')->nullable();
            $table->integer('semester')->nullable();
            $table->string('status');
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->boolean('is_manual')->default(false);
            $table->string('manual_identity')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('khs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('krs_id')->unique();
            $table->decimal('nilai_angka');
            $table->string('nilai_huruf');
            $table->decimal('bobot');
            $table->integer('sks')->nullable();
            $table->string('tahun_akademik');
            $table->string('semester_akademik');
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });
        Schema::create('presensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('krs_id');
            $table->date('tanggal');
            $table->integer('pertemuan')->nullable();
            $table->string('status');
            $table->text('keterangan')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->string('manual_identity')->nullable()->unique();
            $table->unique(['krs_id', 'pertemuan']);
            $table->timestamps();
        });
        (require database_path('migrations/2026_09_15_010000_add_manual_lecturer_overrides.php'))->up();
        Schema::create('presensi_pertemuans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jadwal_id');
            $table->integer('pertemuan');
            $table->date('tanggal');
        });
        DB::table('prodis')->insert(['id' => 1, 'nama_prodi' => 'Informatika']);
        DB::table('mahasiswas')->insert(['id' => 1, 'nim' => '200001', 'nama' => 'Mahasiswa Historis', 'angkatan' => 2020, 'prodi_id' => 1]);
        DB::table('mata_kuliahs')->insert(['id' => 1, 'kode_mk' => 'MK001', 'nama_mk' => 'Algoritma', 'sks' => 3]);
        DB::table('dosens')->insert([['id' => 1, 'nama' => 'Dosen Jadwal'], ['id' => 2, 'nama' => 'Dosen Historis']]);
        DB::table('jadwals')->insert(['id' => 1, 'mata_kuliah_id' => 1, 'dosen_id' => 1, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil']);
        $this->actingAs($this->account('admin'));
    }

    private function account(string $role): User
    {
        return User::create(['name' => $role, 'login' => $role, 'email' => $role.'@example.test', 'password' => 'password', 'role' => $role]);
    }

    private function input(array $extra = []): array
    {
        return $extra + ['mahasiswa_id' => 1, 'mata_kuliah_id' => 1, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil', 'nilai_angka' => 85];
    }

    private function grade(array $extra = []): Khs
    {
        $this->post(route('admin.nilai-manual.store'), $this->input($extra))->assertSessionHasNoErrors()->assertRedirect();

        return Khs::latest('id')->firstOrFail();
    }

    public function test_lecturer_can_be_changed_with_a_schedule_and_cleared(): void
    {
        $grade = $this->grade(['jadwal_id' => 1, 'dosen_id' => 1]);
        $this->put(route('admin.nilai-manual.update', $grade), $this->input(['jadwal_id' => 1, 'dosen_id' => 2]))->assertSessionHasNoErrors();
        $this->assertSame('Dosen Historis', $grade->fresh()->dosen_efektif->nama);
        $this->assertDatabaseHas('jadwals', ['id' => 1, 'dosen_id' => 1]);
        $this->get(route('admin.nilai-manual.edit', $grade))->assertOk()->assertSee('Dosen Historis');
        $this->put(route('admin.nilai-manual.update', $grade), $this->input(['jadwal_id' => 1, 'dosen_id' => null]))->assertSessionHasNoErrors();
        $this->assertNull($grade->fresh()->dosen_efektif);
    }

    public function test_edit_does_not_modify_an_active_krs(): void
    {
        $active = Krs::create($this->input(['jadwal_id' => 1, 'dosen_id' => 1, 'status' => 'Disetujui']));
        $attendance = Presensi::create(['krs_id' => $active->id, 'tanggal' => '2020-09-01', 'pertemuan' => 1, 'status' => 'Hadir']);
        $grade = Khs::create(['krs_id' => $active->id, 'nilai_angka' => 85, 'nilai_huruf' => 'A', 'bobot' => 4, 'is_manual' => true, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil']);
        $this->put(route('admin.nilai-manual.update', $grade), $this->input(['jadwal_id' => 1, 'dosen_id' => 2, 'semester' => 1]))->assertSessionHasNoErrors();
        $this->assertEquals($active->id, $grade->fresh()->krs_id);
        $this->assertSame(2, $grade->fresh()->dosen_efektif->id);
        $this->assertEquals($attendance->krs_id, $grade->fresh()->krs_id);
        $this->assertSame(1, $grade->fresh()->krs->presensis->count());
        $this->assertFalse($active->fresh()->is_manual);
        $this->assertSame(1, $active->fresh()->dosen_efektif->id);
    }

    public function test_grade_filters_can_be_combined_and_kept_on_pagination(): void
    {
        $grade = $this->grade(['jadwal_id' => 1, 'dosen_id' => 2, 'semester' => 1]);
        $response = $this->get(route('admin.nilai-manual.index', ['search' => '200001', 'angkatan' => 2020, 'semester' => 1, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil', 'prodi_id' => 1, 'mata_kuliah_id' => 1, 'dosen_id' => 2]));
        $response->assertOk();
        $this->assertSame([$grade->id], $response->viewData('nilai')->pluck('id')->all());
        $this->assertStringContainsString('search=200001', $response->viewData('nilai')->url(2));
        $this->assertSame(0, $this->get(route('admin.nilai-manual.index', ['dosen_id' => 1]))->viewData('nilai')->total());
        $this->assertSame(0, $this->get(route('admin.nilai-manual.index', ['search' => 'Tidak Ada']))->viewData('nilai')->total());
    }

    public function test_attendance_edit_and_filters(): void
    {
        $grade = $this->grade(['dosen_id' => 1, 'semester' => 1]);
        $input = $this->input(['tanggal' => '2020-09-01', 'status' => 'Hadir', 'pertemuan' => 1, 'dosen_id' => 1]);
        $this->post(route('admin.presensi-manual.store'), $input)->assertSessionHasNoErrors();
        $this->assertSame(1, $grade->fresh()->krs->semester);
        $attendance = Presensi::firstOrFail();
        $this->put(route('admin.presensi-manual.update', $attendance), array_replace($input, ['status' => 'Izin', 'dosen_id' => 2]))->assertSessionHasNoErrors();
        $this->assertSame(2, $attendance->fresh()->dosen_efektif->id);
        $this->assertSame(1, $grade->fresh()->dosen_efektif->id);
        $response = $this->get(route('admin.presensi-manual.index', ['status' => 'Izin', 'tanggal_mulai' => '2020-09-01', 'tanggal_selesai' => '2020-09-02', 'dosen_id' => 2]));
        $response->assertOk();
        $this->assertSame(1, $response->viewData('presensis')->total());
        $this->assertSame(0, $this->get(route('admin.presensi-manual.index', ['status' => 'Hadir']))->viewData('presensis')->total());
        $this->assertSame(0, $this->get(route('admin.presensi-manual.index', ['tanggal_mulai' => '2020-10-01']))->viewData('presensis')->total());
    }

    public function test_duplicates_still_rejected_after_lecturer_changes(): void
    {
        $this->grade(['dosen_id' => 2]);
        $this->post(route('admin.nilai-manual.store'), $this->input(['dosen_id' => 1]))->assertSessionHasErrors('mata_kuliah_id');
        $input = $this->input(['tanggal' => '2020-09-01', 'pertemuan' => 1, 'status' => 'Hadir']);
        $this->post(route('admin.presensi-manual.store'), $input)->assertSessionHasNoErrors();
        $this->post(route('admin.presensi-manual.store'), array_replace($input, ['pertemuan' => 2]))->assertSessionHasErrors('tanggal');
        $this->post(route('admin.presensi-manual.store'), array_replace($input, ['tanggal' => '2020-09-02']))->assertSessionHasErrors('tanggal');
    }

    public function test_delete_is_limited_to_manual_records_and_leaves_related_data(): void
    {
        $grade = $this->grade();
        $this->post(route('admin.presensi-manual.store'), $this->input(['tanggal' => '2020-09-01', 'status' => 'Hadir']))->assertSessionHasNoErrors();
        $attendance = Presensi::firstOrFail();
        $this->delete(route('admin.nilai-manual.destroy', $grade))->assertRedirect();
        $this->assertDatabaseMissing('khs', ['id' => $grade->id]);
        $this->assertDatabaseHas('presensis', ['id' => $attendance->id]);
        $this->assertDatabaseHas('krs', ['id' => $grade->krs_id]);
        $attendance->update(['is_manual' => false]);
        $this->delete(route('admin.presensi-manual.destroy', $attendance))->assertNotFound();
        $attendance->update(['is_manual' => true]);
        $this->delete(route('admin.presensi-manual.destroy', $attendance))->assertRedirect();
        $this->assertDatabaseMissing('presensis', ['id' => $attendance->id]);
        $this->grade();
        $this->post(route('admin.presensi-manual.store'), $this->input(['tanggal' => '2020-09-01', 'status' => 'Hadir']))->assertSessionHasNoErrors();
    }

    public function test_manual_routes_reject_non_admin_users(): void
    {
        $grade = $this->grade();
        foreach (['mahasiswa', 'dosen'] as $role) {
            $this->actingAs($this->account($role));
            $this->get(route('admin.nilai-manual.index'))->assertForbidden();
            $this->get(route('admin.presensi-manual.index'))->assertForbidden();
            $this->put(route('admin.nilai-manual.update', $grade), $this->input())->assertForbidden();
            $this->delete(route('admin.nilai-manual.destroy', $grade))->assertForbidden();
        }
    }

    public function test_lecturer_cannot_overwrite_a_manual_krs_through_active_grade_input(): void
    {
        Krs::create($this->input(['jadwal_id' => 1, 'status' => 'Disetujui']));
        $grade = $this->grade(['jadwal_id' => 1, 'dosen_id' => 2]);
        $lecturer = $this->account('dosen');
        DB::table('dosens')->where('id', 1)->update(['user_id' => $lecturer->id]);
        $this->actingAs($lecturer)->post(route('dosen.nilai.store'), ['krs_id' => [$grade->krs_id], 'nilai_angka' => [10]])->assertForbidden();
        $this->assertSame(85.0, (float) $grade->fresh()->nilai_angka);
    }

    public function test_old_records_keep_inherited_lecturer_until_explicitly_edited(): void
    {
        $grade = $this->grade(['jadwal_id' => 1, 'dosen_id' => 1]);
        $grade->update(['dosen_override' => false, 'dosen_id' => null]);
        $this->assertSame('Dosen Jadwal', $grade->fresh()->dosen_efektif->nama);
        $this->assertSame(1, $this->get(route('admin.nilai-manual.index', ['dosen_id' => 1]))->viewData('nilai')->total());
        $this->put(route('admin.nilai-manual.update', $grade), $this->input(['dosen_id' => 2]))->assertSessionHasNoErrors();
        $this->assertSame(2, $grade->fresh()->dosen_efektif->id);
    }

    public function test_attendance_report_uses_corrected_lecturer(): void
    {
        $input = $this->input(['jadwal_id' => 1, 'dosen_id' => 1, 'tanggal' => '2020-09-01', 'status' => 'Hadir']);
        $this->post(route('admin.presensi-manual.store'), $input)->assertSessionHasNoErrors();
        $attendance = Presensi::firstOrFail();
        $this->put(route('admin.presensi-manual.update', $attendance), array_replace($input, ['dosen_id' => 2]))->assertSessionHasNoErrors();
        $response = $this->get(route('admin.presensi', ['dosen_id' => 2]));
        $response->assertOk()->assertSee('Dosen Historis');
        $this->assertSame(1, $response->viewData('totalPresensi'));
        $this->assertSame('Dosen Historis', $response->viewData('rekap')->first()->dosen_pengampu);
        $this->assertSame(0, $this->get(route('admin.presensi', ['dosen_id' => 1]))->viewData('totalPresensi'));
    }

    public function test_class_filter_matches_schedule_manual_and_student_precedence(): void
    {
        DB::table('kelas')->insert([['id' => 1, 'nama_kelas' => 'IF Lama'], ['id' => 2, 'nama_kelas' => 'IF Baru']]);
        DB::table('mahasiswas')->where('id', 1)->update(['kelas_id' => 2]);
        $grade = $this->grade(['kelas_id' => 1]);
        $this->assertSame(1, $this->get(route('admin.nilai-manual.index', ['kelas_id' => 1]))->viewData('nilai')->total());
        $this->assertSame(0, $this->get(route('admin.nilai-manual.index', ['kelas_id' => 2]))->viewData('nilai')->total());
        $grade->krs->update(['kelas_id' => null]);
        $this->assertSame(1, $this->get(route('admin.nilai-manual.index', ['kelas_id' => 2]))->viewData('nilai')->total());
        DB::table('jadwals')->where('id', 1)->update(['kelas_id' => 1]);
        $grade->krs->update(['jadwal_id' => 1]);
        $this->assertSame(1, $this->get(route('admin.nilai-manual.index', ['kelas_id' => 1]))->viewData('nilai')->total());
        $this->assertSame(0, $this->get(route('admin.nilai-manual.index', ['kelas_id' => 2]))->viewData('nilai')->total());
    }

    public function test_second_page_keeps_all_grade_filters(): void
    {
        for ($id = 2; $id <= 18; $id++) {
            DB::table('mahasiswas')->insert(['id' => $id, 'nim' => '2000'.$id, 'nama' => 'Mahasiswa Historis '.$id, 'angkatan' => 2020, 'prodi_id' => 1]);
            $krs = Krs::create($this->input(['mahasiswa_id' => $id, 'dosen_id' => 2, 'semester' => 1, 'is_manual' => true, 'status' => 'Disetujui']));
            Khs::create(['krs_id' => $krs->id, 'nilai_angka' => 85, 'nilai_huruf' => 'A', 'bobot' => 4, 'is_manual' => true, 'dosen_id' => 2, 'dosen_override' => true, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil']);
        }
        $filters = ['search' => 'Historis', 'angkatan' => 2020, 'semester' => 1, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil', 'prodi_id' => 1, 'mata_kuliah_id' => 1, 'dosen_id' => 2];
        $first = $this->get(route('admin.nilai-manual.index', $filters))->assertOk();
        $url = $first->viewData('nilai')->url(2);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        foreach ($filters as $key => $value) {
            $this->assertEquals($value, $query[$key]);
        }
        $second = $this->get($url)->assertOk();
        $this->assertSame(17, $second->viewData('nilai')->total());
        $this->assertCount(7, $second->viewData('nilai')->items());
    }

    public function test_regular_grade_cannot_be_deleted_from_manual_routes(): void
    {
        $grade = $this->grade();
        $grade->update(['is_manual' => false]);
        $this->delete(route('admin.nilai-manual.destroy', $grade))->assertNotFound();
        $this->assertDatabaseHas('khs', ['id' => $grade->id]);
        $this->assertDatabaseHas('mahasiswas', ['id' => 1]);
        $this->assertDatabaseHas('mata_kuliahs', ['id' => 1]);
    }

    private function seedManualPages(int $lastStudent = 102): void
    {
        for ($id = 2; $id <= $lastStudent; $id++) {
            DB::table('mahasiswas')->insert(['id' => $id, 'nim' => '2000'.$id, 'nama' => 'Mahasiswa Historis '.$id, 'angkatan' => 2020, 'prodi_id' => 1]);
            $krs = Krs::create($this->input(['mahasiswa_id' => $id, 'dosen_id' => 2, 'semester' => 1, 'is_manual' => true, 'status' => 'Disetujui']));
            Khs::create(['krs_id' => $krs->id, 'nilai_angka' => 85, 'nilai_huruf' => 'A', 'bobot' => 4, 'is_manual' => true, 'dosen_id' => 2, 'dosen_override' => true, 'tahun_akademik' => '2020/2021', 'semester_akademik' => 'Ganjil']);
            Presensi::create(['krs_id' => $krs->id, 'tanggal' => '2020-09-01', 'pertemuan' => 1, 'status' => 'Hadir', 'is_manual' => true, 'dosen_id' => 2, 'dosen_override' => true]);
        }
    }

    public function test_grade_navigation_preserves_filters_page_ten_and_row_numbers_after_mutations(): void
    {
        $this->seedManualPages();
        $returnUrl = route('admin.nilai-manual.index', ['search' => 'Historis', 'angkatan' => 2020, 'semester' => 1, 'tahun_akademik' => '2020/2021', 'dosen_id' => 2, 'page' => 10]);
        $list = $this->get($returnUrl)->assertOk()->assertSee('<td>91</td>', false)->assertSee('<td>100</td>', false);
        $grade = $list->viewData('nilai')->first();
        $form = $this->get(route('admin.nilai-manual.edit', ['khs' => $grade, 'return_url' => $returnUrl]))->assertOk();
        $this->assertSame($returnUrl, $form->viewData('returnUrl'));
        $form->assertSee('name="return_url"', false);
        $this->put(route('admin.nilai-manual.update', $grade), $this->input(['mahasiswa_id' => $grade->krs->mahasiswa_id, 'dosen_id' => 2, 'semester' => 1, 'return_url' => $returnUrl]))->assertSessionHasNoErrors()->assertRedirect($returnUrl);
        $this->get(route('admin.nilai-manual.create', ['return_url' => $returnUrl]))->assertOk()->assertViewHas('returnUrl', $returnUrl);
        $this->post(route('admin.nilai-manual.store'), $this->input(['dosen_id' => 2, 'semester' => 1, 'return_url' => $returnUrl]))->assertSessionHasNoErrors()->assertRedirect($returnUrl);
        $this->delete(route('admin.nilai-manual.destroy', $grade), ['return_url' => $returnUrl])->assertRedirect($returnUrl);
        $this->get($returnUrl)->assertOk()->assertSee('<td>91</td>', false);
    }

    public function test_attendance_navigation_preserves_page_ten_after_create_update_and_delete(): void
    {
        $this->seedManualPages();
        $returnUrl = route('admin.presensi-manual.index', ['search' => 'Historis', 'status' => 'Hadir', 'tanggal_mulai' => '2020-09-01', 'tanggal_selesai' => '2020-09-02', 'page' => 10]);
        $list = $this->get($returnUrl)->assertOk()->assertSee('<td>91</td>', false)->assertSee('<td>100</td>', false);
        $attendance = $list->viewData('presensis')->first();
        $this->get(route('admin.presensi-manual.edit', ['presensi' => $attendance, 'return_url' => $returnUrl]))->assertOk()->assertViewHas('returnUrl', $returnUrl);
        $input = $this->input(['mahasiswa_id' => $attendance->krs->mahasiswa_id, 'tanggal' => '2020-09-01', 'status' => 'Hadir', 'pertemuan' => 1, 'return_url' => $returnUrl]);
        $this->put(route('admin.presensi-manual.update', $attendance), $input)->assertSessionHasNoErrors()->assertRedirect($returnUrl);
        $this->get(route('admin.presensi-manual.create', ['return_url' => $returnUrl]))->assertOk()->assertViewHas('returnUrl', $returnUrl);
        $this->post(route('admin.presensi-manual.store'), array_replace($input, ['mahasiswa_id' => 1]))->assertSessionHasNoErrors()->assertRedirect($returnUrl);
        $this->delete(route('admin.presensi-manual.destroy', $attendance), ['return_url' => $returnUrl])->assertRedirect($returnUrl);
        $this->get($returnUrl)->assertOk()->assertSee('<td>91</td>', false);
    }

    public function test_untrusted_return_urls_cannot_redirect_outside_the_correct_admin_list(): void
    {
        $navigation = new LegacyListNavigation;
        $fallback = route('admin.nilai-manual.index');
        foreach (['https://evil.test/admin/nilai-manual?page=10', '//evil.test/admin/nilai-manual', 'javascript:alert(1)', '/admin/presensi-manual?page=10', '/admin/nilai-manual#fragment', '/admin/nilai-manual/../dashboard', "/admin/nilai-manual\r\nLocation: https://evil.test", ['page' => 10]] as $url) {
            $request = Request::create('/', 'POST', ['return_url' => $url]);
            $this->assertSame($fallback, $navigation->returnUrl($request, 'admin.nilai-manual.index'));
        }
        $this->post(route('admin.nilai-manual.store'), $this->input(['return_url' => 'https://evil.test']))->assertSessionHasNoErrors()->assertRedirect($fallback);
        $safe = $navigation->returnUrl(Request::create('/', 'POST', ['return_url' => '/admin/nilai-manual?search=Mahasiswa%20Historis&page=10&return_url=https://evil.test']), 'admin.nilai-manual.index');
        parse_str(parse_url($safe, PHP_URL_QUERY), $query);
        $this->assertSame(['search' => 'Mahasiswa Historis', 'page' => '10'], $query);
    }

    public function test_deleting_last_row_of_last_page_keeps_filters_and_returns_to_last_valid_page(): void
    {
        $this->seedManualPages(12);
        $returnUrl = route('admin.nilai-manual.index', ['search' => 'Historis', 'page' => 2]);
        $grade = $this->get($returnUrl)->assertOk()->viewData('nilai')->first();
        $this->delete(route('admin.nilai-manual.destroy', $grade), ['return_url' => $returnUrl])->assertRedirect($returnUrl);
        $this->get($returnUrl)->assertRedirect(route('admin.nilai-manual.index', ['search' => 'Historis', 'page' => 1]));
    }
}
