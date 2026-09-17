<?php

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function makePerStudentKrsAccessData(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $studentUser = User::factory()->create(['role' => 'mahasiswa']);
    $otherStudentUser = User::factory()->create(['role' => 'mahasiswa']);
    $lecturerUser = User::factory()->create(['role' => 'dosen']);
    $prodi = Prodi::create([
        'kode_prodi' => 'IF-AKSES',
        'nama_prodi' => 'Informatika Akses KRS',
        'jenjang' => 'S1',
    ]);
    $dosen = Dosen::create([
        'nidn' => 'DOSEN-AKSES-01',
        'nama' => 'Dosen Akses KRS',
        'prodi_id' => $prodi->id,
        'user_id' => $lecturerUser->id,
    ]);
    $kelas = Kelas::create([
        'nama_kelas' => 'IF 2026 A',
        'prodi_id' => $prodi->id,
        'angkatan' => 2026,
        'semester' => 1,
        'dosen_wali_id' => $dosen->id,
    ]);
    $student = Mahasiswa::create([
        'nim' => '260001',
        'nama' => 'Mahasiswa Akses A',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $studentUser->id,
    ]);
    $otherStudent = Mahasiswa::create([
        'nim' => '260002',
        'nama' => 'Mahasiswa Akses B',
        'angkatan' => 2026,
        'semester' => 1,
        'prodi_id' => $prodi->id,
        'kelas_id' => $kelas->id,
        'dosen_wali_id' => $dosen->id,
        'user_id' => $otherStudentUser->id,
    ]);
    $course = MataKuliah::create([
        'kode_mk' => 'IF101-A',
        'nama_mk' => 'Algoritma Akses KRS',
        'sks' => 3,
        'semester' => 1,
        'prodi_id' => $prodi->id,
    ]);
    $room = Ruangan::create([
        'kode_ruangan' => 'AKSES-1',
        'nama_ruangan' => 'Ruang Akses',
        'kapasitas' => 30,
    ]);
    $schedule = Jadwal::create([
        'mata_kuliah_id' => $course->id,
        'dosen_id' => $dosen->id,
        'ruangan_id' => $room->id,
        'kelas_id' => $kelas->id,
        'hari' => 'Senin',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '10:00:00',
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);
    $period = PeriodeKrs::create([
        'tahun_akademik' => '2026/2027',
        'semester' => 'Ganjil',
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'minimal_sks' => 0,
        'maksimal_sks' => 24,
        'status' => 'Dibuka',
    ]);

    return compact('admin', 'studentUser', 'otherStudentUser', 'prodi', 'kelas', 'student', 'otherStudent', 'schedule', 'period');
}

test('admin opens and closes one student KRS access without affecting another student', function () {
    $data = makePerStudentKrsAccessData();
    $returnUrl = route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'search' => '260001',
        'status_akses' => 'belum_dibuka',
        'page' => 1,
    ]);

    $this->actingAs($data['admin'])
        ->get($returnUrl)
        ->assertOk()
        ->assertSee('Mahasiswa Akses A')
        ->assertDontSee('Mahasiswa Akses B')
        ->assertSee('Belum Dibuka');

    $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
        'status_akses' => 1,
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'dibuka_oleh' => $data['admin']->id,
    ]);
    $this->assertDatabaseMissing('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['otherStudent']->id,
    ]);

    $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
        'status_akses' => 0,
        'return_url' => $returnUrl,
    ])->assertRedirect($returnUrl);

    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => false,
    ]);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 1);

    $this->get(route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'status_akses' => 'ditutup',
    ]))->assertOk()->assertSee('Mahasiswa Akses A')->assertDontSee('Mahasiswa Akses B');
});

test('student can submit KRS only when global period and personal access are both open', function () {
    $data = makePerStudentKrsAccessData();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Akses KRS Anda belum dibuka oleh admin.')
        ->assertDontSee('Algoritma Akses KRS');

    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseCount('krs', 0);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk()
        ->assertSee('Algoritma Akses KRS');
    $this->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHasNoErrors();
    $this->assertDatabaseHas('krs', [
        'mahasiswa_id' => $data['student']->id,
        'jadwal_id' => $data['schedule']->id,
        'tahun_akademik' => '2026/2027',
        'semester_akademik' => 'Ganjil',
    ]);

    $this->actingAs($data['otherStudentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseMissing('krs', ['mahasiswa_id' => $data['otherStudent']->id]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 0]
    )->assertSessionHasNoErrors();
    $this->assertDatabaseCount('periode_krs_mahasiswas', 1);

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Akses KRS Anda belum dibuka oleh admin.');
    $this->assertDatabaseCount('krs', 1);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $data['period']->update(['status' => 'Ditutup']);
    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Pengisian KRS sedang ditutup atau periode KRS telah berakhir.');
});

test('ended period rejects KRS even when personal access is open', function () {
    $data = makePerStudentKrsAccessData();
    $data['period']->update([
        'tanggal_mulai' => now()->subDays(2),
        'tanggal_selesai' => now()->subDay(),
    ]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access', [$data['period'], $data['student']]),
        ['status_akses' => 1]
    )->assertSessionHasNoErrors();

    $this->actingAs($data['studentUser'])
        ->post(route('mahasiswa.krs.store'), ['jadwal_id' => $data['schedule']->id])
        ->assertSessionHas('error', 'Pengisian KRS sedang ditutup atau periode KRS telah berakhir.');
    $this->assertDatabaseCount('krs', 0);
});

test('admin can open and close KRS access in bulk without duplicate pivot rows', function () {
    $data = makePerStudentKrsAccessData();
    $studentIds = [$data['student']->id, $data['otherStudent']->id];
    $url = route('admin.periode-krs.students', [
        'periodeKrs' => $data['period'],
        'angkatan' => 2026,
        'page' => 1,
    ]);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access-bulk', $data['period']),
        [
            'mahasiswa_ids' => $studentIds,
            'status_akses' => 0,
            'return_url' => $url,
        ]
    )->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 0);

    $this->actingAs($data['admin'])->patch(
        route('admin.periode-krs.students.access-bulk', $data['period']),
        [
            'mahasiswa_ids' => $studentIds,
            'status_akses' => 1,
            'return_url' => $url,
        ]
    )->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 2);
    foreach ($studentIds as $studentId) {
        $this->assertDatabaseHas('periode_krs_mahasiswas', [
            'periode_krs_id' => $data['period']->id,
            'mahasiswa_id' => $studentId,
            'status_akses' => true,
        ]);
    }

    $this->patch(route('admin.periode-krs.students.access-bulk', $data['period']), [
        'mahasiswa_ids' => $studentIds,
        'status_akses' => 0,
        'return_url' => $url,
    ])->assertRedirect($url);
    $this->assertDatabaseCount('periode_krs_mahasiswas', 2);
    foreach ($studentIds as $studentId) {
        $this->assertDatabaseHas('periode_krs_mahasiswas', [
            'periode_krs_id' => $data['period']->id,
            'mahasiswa_id' => $studentId,
            'status_akses' => false,
        ]);
    }
});

test('KRS pages never query a payment table', function () {
    $data = makePerStudentKrsAccessData();
    expect(Schema::hasTable('pembayaran_krs'))->toBeFalse();

    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = strtolower($query->sql);
    });

    $this->actingAs($data['admin'])
        ->get(route('admin.periode-krs.students', $data['period']))
        ->assertOk();
    $this->actingAs($data['studentUser'])
        ->get(route('mahasiswa.krs'))
        ->assertOk();

    expect(collect($queries)->contains(
        fn (string $sql) => str_contains($sql, 'pembayaran_krs')
    ))->toBeFalse();
});

test('legacy string access rows are normalized without losing their owner', function () {
    $data = makePerStudentKrsAccessData();

    Schema::drop('periode_krs_mahasiswas');
    Schema::create('periode_krs_mahasiswas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('periode_krs_id')->constrained('periode_krs')->cascadeOnDelete();
        $table->foreignId('mahasiswa_id')->constrained('mahasiswas')->cascadeOnDelete();
        $table->string('status_akses', 20)->default('ditutup');
        $table->timestamp('tanggal_dibuka')->nullable();
        $table->timestamp('tanggal_ditutup')->nullable();
        $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
        $table->text('catatan')->nullable();
        $table->timestamps();
        $table->unique(['periode_krs_id', 'mahasiswa_id'], 'periode_krs_mahasiswa_unique');
        $table->index(['periode_krs_id', 'status_akses'], 'periode_krs_akses_filter_index');
    });

    DB::table('periode_krs_mahasiswas')->insert([
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => 'dibuka',
        'admin_id' => $data['admin']->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_09_17_000000_normalize_periode_krs_mahasiswa_access.php');
    $migration->up();

    expect(Schema::hasColumn('periode_krs_mahasiswas', 'dibuka_oleh'))->toBeTrue();
    $this->assertDatabaseHas('periode_krs_mahasiswas', [
        'periode_krs_id' => $data['period']->id,
        'mahasiswa_id' => $data['student']->id,
        'status_akses' => true,
        'dibuka_oleh' => $data['admin']->id,
    ]);
});

test('student and lecturer cannot manage per student KRS access', function () {
    $data = makePerStudentKrsAccessData();

    foreach ([$data['studentUser'], User::factory()->create(['role' => 'dosen'])] as $user) {
        $this->actingAs($user)
            ->get(route('admin.periode-krs.students', $data['period']))
            ->assertForbidden();
        $this->patch(route('admin.periode-krs.students.access', [$data['period'], $data['student']]), [
            'status_akses' => 1,
        ])->assertForbidden();
    }
});
