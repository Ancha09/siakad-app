<?php

namespace Tests\Integration;

use App\Models\Pengumuman;
use App\Models\User;
use App\Services\AkademikDashboard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardPengumumanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated in-memory database: never migrate or reset the campus database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(now()->setDate(2026, 9, 6)->setTime(12, 0));
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('mahasiswa');
            $t->string('login')->nullable();
        });
        (require database_path('migrations/2026_09_06_120000_create_pengumuman_tables.php'))->up();
        foreach (['prodis', 'dosens', 'mahasiswas', 'mata_kuliahs', 'jadwals', 'krs', 'khs', 'presensis', 'presensi_pertemuans', 'periode_krs'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->timestamps();
                if ($table === 'prodis') $t->string('nama_prodi');
                if (in_array($table, ['dosens', 'mahasiswas'])) {
                    $t->unsignedBigInteger('prodi_id')->nullable();
                    $t->unsignedBigInteger('user_id')->nullable();
                    $t->string('nama')->default('Test');
                }
                if ($table === 'mahasiswas') $t->unsignedBigInteger('kelas_id')->nullable();
                if ($table === 'mata_kuliahs') $t->integer('sks');
                if ($table === 'jadwals') {
                    $t->unsignedBigInteger('dosen_id');
                    $t->unsignedBigInteger('mata_kuliah_id');
                }
                if ($table === 'krs') {
                    $t->unsignedBigInteger('mahasiswa_id');
                    $t->unsignedBigInteger('jadwal_id');
                    $t->string('status');
                }
                if (in_array($table, ['jadwals', 'krs', 'khs', 'periode_krs'])) $t->string('tahun_akademik');
                if (in_array($table, ['khs', 'presensis'])) $t->unsignedBigInteger('krs_id');
                if ($table === 'khs') $t->decimal('bobot')->nullable();
                if ($table === 'presensis') $t->string('status');
                if (in_array($table, ['presensis', 'presensi_pertemuans'])) $t->date('tanggal');
                if ($table === 'presensi_pertemuans') $t->unsignedBigInteger('jadwal_id');
            });
        }
    }

    private function account(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email' => fake()->unique()->safeEmail()]);
    }

    private function announcement(array $data = []): Pengumuman
    {
        return Pengumuman::create(array_merge(['judul' => 'Pengumuman mahasiswa', 'isi' => 'Informasi kampus',
            'penerima' => 'mahasiswa', 'status' => 'terbit', 'terbit_pada' => now()->subMinute()], $data));
    }

    public function test_visibility_is_limited_to_recipient_and_publication_window(): void
    {
        $student = $this->account('mahasiswa');
        $visible = $this->announcement();
        $hidden = [
            $this->announcement(['penerima' => 'dosen', 'judul' => 'Rahasia dosen']),
            $this->announcement(['status' => 'draft']),
            $this->announcement(['terbit_pada' => now()->addDay()]),
            $this->announcement(['berakhir_pada' => now()->subSecond()]),
        ];
        $this->assertSame([$visible->id], Pengumuman::terlihat($student)->pluck('id')->all());
        $this->actingAs($student)->get(route('mahasiswa.pemberitahuan'))->assertOk()->assertSee($visible->judul)->assertDontSee('Rahasia dosen');
        foreach ($hidden as $item) $this->post(route('mahasiswa.pemberitahuan.baca', $item))->assertNotFound();
        $this->get(route('admin.pengumuman.index'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('dosen.pemberitahuan'))->assertForbidden();
    }

    public function test_read_receipts_are_idempotent_and_private_to_each_account(): void
    {
        $first = $this->account('mahasiswa');
        $second = $this->account('mahasiswa');
        $item = $this->announcement();
        $this->actingAs($first)->post(route('mahasiswa.pemberitahuan.baca', $item))->assertRedirect();
        $this->post(route('mahasiswa.pemberitahuan.baca', $item))->assertRedirect();
        $this->assertSame(1, DB::table('pengumuman_reads')->count());
        $this->assertTrue((bool) Pengumuman::denganStatusBaca($first)->first()->sudah_dibaca);
        $this->assertFalse((bool) Pengumuman::denganStatusBaca($second)->first()->sudah_dibaca);
    }

    public function test_admin_can_manage_separate_announcements_and_updates_reset_reads(): void
    {
        $admin = $this->account('admin');
        $data = ['judul' => 'Rapat dosen', 'isi' => 'Informasi rapat', 'penerima' => 'dosen', 'status' => 'terbit'];
        $this->actingAs($admin)->post(route('admin.pengumuman.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $item = Pengumuman::firstOrFail();
        $this->assertSame($admin->id, $item->penulis_id);
        $this->assertNotNull($item->terbit_pada);
        $this->get(route('admin.pengumuman.index', ['penerima' => 'dosen']))->assertOk()->assertSee('Rapat dosen');
        $this->get(route('admin.pengumuman.index', ['penerima' => 'mahasiswa']))->assertOk()
            ->assertViewHas('pengumumans', fn ($items) => $items->total() === 0);
        $this->get(route('admin.pengumuman.edit', $item))->assertOk();
        $item->pembaca()->attach($admin, ['read_at' => now()]);
        $this->put(route('admin.pengumuman.update', $item), array_merge($data, ['judul' => 'Rapat diperbarui']))->assertSessionHasNoErrors();
        $this->assertSame(0, $item->pembaca()->count());
        $this->delete(route('admin.pengumuman.destroy', $item))->assertRedirect();
        $this->assertDatabaseMissing('pengumumans', ['id' => $item->id]);
    }

    public function test_invalid_links_and_dates_are_rejected_and_content_is_escaped(): void
    {
        $this->actingAs($this->account('admin'));
        $data = ['judul' => 'Test', 'isi' => 'Isi', 'penerima' => 'dosen', 'status' => 'terbit'];
        $this->post(route('admin.pengumuman.store'), $data + ['tautan' => 'javascript:alert(1)'])->assertSessionHasErrors('tautan');
        $this->post(route('admin.pengumuman.store'), $data + ['terbit_pada' => '2026-09-10 12:00', 'berakhir_pada' => '2026-09-09 12:00'])->assertSessionHasErrors('berakhir_pada');
        $this->post(route('admin.pengumuman.store'), array_replace($data, ['penerima' => 'admin']))->assertSessionHasErrors('penerima');
        $this->announcement(['isi' => '<script>alert(1)</script>']);
        $this->get(route('admin.pemberitahuan'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_summary_uses_weighted_cumulative_ipk_and_actual_attendance(): void
    {
        DB::table('prodis')->insert(['id' => 1, 'nama_prodi' => 'Teknik']);
        DB::table('dosens')->insert([['id' => 1, 'prodi_id' => 1], ['id' => 2, 'prodi_id' => 1]]);
        DB::table('mahasiswas')->insert([['id' => 1, 'prodi_id' => 1], ['id' => 2, 'prodi_id' => 1]]);
        DB::table('mata_kuliahs')->insert([['id' => 1, 'sks' => 3], ['id' => 2, 'sks' => 1]]);
        DB::table('jadwals')->insert([
            ['id' => 1, 'dosen_id' => 1, 'mata_kuliah_id' => 1, 'tahun_akademik' => '2025/2026'],
            ['id' => 2, 'dosen_id' => 1, 'mata_kuliah_id' => 2, 'tahun_akademik' => '2026/2027'],
        ]);
        DB::table('krs')->insert([
            ['id' => 1, 'mahasiswa_id' => 1, 'jadwal_id' => 1, 'status' => 'Disetujui', 'tahun_akademik' => '2025/2026'],
            ['id' => 2, 'mahasiswa_id' => 1, 'jadwal_id' => 2, 'status' => 'Disetujui', 'tahun_akademik' => '2026/2027'],
            ['id' => 3, 'mahasiswa_id' => 2, 'jadwal_id' => 2, 'status' => 'Disetujui', 'tahun_akademik' => '2026/2027'],
            ['id' => 4, 'mahasiswa_id' => 2, 'jadwal_id' => 2, 'status' => 'Ditolak', 'tahun_akademik' => '2026/2027'],
        ]);
        DB::table('khs')->insert([
            ['krs_id' => 1, 'bobot' => 4, 'tahun_akademik' => '2025/2026'],
            ['krs_id' => 2, 'bobot' => 2, 'tahun_akademik' => '2026/2027'],
            ['krs_id' => 3, 'bobot' => null, 'tahun_akademik' => '2026/2027'],
            ['krs_id' => 4, 'bobot' => 0, 'tahun_akademik' => '2026/2027'],
        ]);
        DB::table('presensis')->insert([
            ['krs_id' => 2, 'status' => 'Hadir', 'tanggal' => '2026-09-01'],
            ['krs_id' => 3, 'status' => 'Alpha', 'tanggal' => '2026-09-01'],
            ['krs_id' => 4, 'status' => 'Hadir', 'tanggal' => '2026-09-01'],
            ['krs_id' => 2, 'status' => 'Hadir', 'tanggal' => '2026-10-01'],
        ]);
        DB::table('presensi_pertemuans')->insert([['jadwal_id' => 2, 'tanggal' => '2026-09-01'], ['jadwal_id' => 2, 'tanggal' => '2026-10-01']]);
        $s = app(AkademikDashboard::class)->summary('2026/2027');
        $this->assertEquals(3.5, $s['ipk']);
        $this->assertEquals(4, $s['previousIpk']);
        $this->assertEquals(-0.5, $s['delta']);
        $this->assertSame(1, $s['activeLecturers']);
        $this->assertSame(2, $s['totalStudents']);
        $this->assertEquals(50, $s['attendance']);
        $this->assertEquals(6.3, $s['progress']);
        $this->actingAs($this->account('admin'))->get(route('admin.dashboard', ['tahun' => '2026/2027']))->assertOk()->assertSee('3,50')->assertSee('Turun');
    }

    public function test_empty_summary_does_not_invent_percentages_or_ipk_changes(): void
    {
        $s = app(AkademikDashboard::class)->summary('2026/2027');
        foreach (['ipk', 'previousIpk', 'delta', 'progress', 'attendance'] as $key) $this->assertNull($s[$key]);
        $this->actingAs($this->account('admin'))->get(route('admin.dashboard'))->assertOk()->assertSee('Belum cukup data');
        $this->get(route('admin.dashboard', ['tahun' => 'invalid']))->assertSessionHasErrors('tahun');
        $this->actingAs($this->account('dosen'))->get(route('dosen.dashboard'))->assertOk()->assertSee('Belum ada pengumuman');
    }

    public function test_dashboards_show_only_their_own_published_announcements(): void
    {
        $student = $this->account('mahasiswa');
        DB::table('mahasiswas')->insert(['user_id' => $student->id]);
        $this->announcement(['judul' => 'Khusus mahasiswa']);
        $this->announcement(['judul' => 'Khusus dosen', 'penerima' => 'dosen']);
        $this->actingAs($student)->get(route('mahasiswa.dashboard'))->assertOk()->assertSee('Khusus mahasiswa')->assertDontSee('Khusus dosen');
        $this->actingAs($this->account('dosen'))->get(route('dosen.dashboard'))->assertOk()->assertSee('Khusus dosen')->assertDontSee('Khusus mahasiswa');
    }

    public function test_schedule_uses_jakarta_time_and_becomes_visible_without_a_job(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->post(route('admin.pengumuman.store'), [
            'judul' => 'Terjadwal WIB', 'isi' => 'Jadwal', 'penerima' => 'mahasiswa', 'status' => 'terbit',
            'terbit_pada' => '2026-09-07T08:00', 'berakhir_pada' => '2026-09-07T09:00',
        ])->assertSessionHasNoErrors();
        $item = Pengumuman::firstOrFail();
        $this->assertSame('2026-09-07 08:00:00', $item->terbit_pada->format('Y-m-d H:i:s'));
        $student = $this->account('mahasiswa');
        $this->assertSame(0, Pengumuman::terlihat($student)->count());
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(8, 0));
        $this->assertSame(1, Pengumuman::terlihat($student)->count());
        $this->travelTo(now()->setTime(9, 0));
        $this->assertSame(0, Pengumuman::terlihat($student)->count());
    }
}
