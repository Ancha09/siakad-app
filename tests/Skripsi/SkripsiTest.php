<?php

namespace Tests\Skripsi;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanSkripsi;
use App\Models\PeriodeSkripsi;
use App\Models\Prodi;
use App\Models\RiwayatSkripsi;
use App\Models\User;
use App\Services\SkripsiService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SkripsiTest extends TestCase
{
    private User $admin;

    private Mahasiswa $student;

    private Dosen $dosen;

    private Dosen $otherDosen;

    private PeriodeSkripsi $period;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        self::assertSame(':memory:', DB::connection()->getDatabaseName());
        // Include the announcement tables read by the shared dashboard layouts.
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_07_05_041846_add_role_to_users_table.php',
            '2026_07_05_030858_create_prodis_table.php',
            '2026_07_05_030905_create_dosens_table.php',
            '2026_07_05_030913_create_mahasiswas_table.php',
            '2026_08_19_091444_create_kelas_table.php',
            '2026_08_19_091740_add_kelas_id_to_mahasiswas_table.php',
            '2026_09_05_000000_create_skripsi_tables.php',
            '2026_09_06_120000_create_pengumuman_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
        $this->admin = $this->user('admin');
        $this->student = $this->student($prodi->id);
        $this->dosen = $this->dosen($prodi->id);
        $this->otherDosen = $this->dosen($prodi->id);
        $this->period = PeriodeSkripsi::create(['nama' => 'Skripsi 2026', 'mulai' => now()->subDay(), 'berakhir' => now()->addDay()]);
    }

    private function user(string $role): User
    {
        $id = bin2hex(random_bytes(5));

        return User::create(['name' => $role.' '.$id, 'login' => $id, 'email' => $id.'@example.test', 'role' => $role, 'password' => 'password']);
    }

    private function student(int $prodi): Mahasiswa
    {
        $user = $this->user('mahasiswa');

        return Mahasiswa::create(['nim' => $user->login, 'nama' => $user->name, 'semester' => 7, 'prodi_id' => $prodi, 'user_id' => $user->id]);
    }

    private function dosen(int $prodi): Dosen
    {
        $user = $this->user('dosen');
        $dosen = Dosen::create(['nidn' => $user->login, 'nama' => $user->name, 'prodi_id' => $prodi, 'user_id' => $user->id]);
        $dosen->skripsi_aktif = true;
        $dosen->save();

        return $dosen;
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + ['periode_skripsi_id' => $this->period->id, 'judul' => 'Sistem Informasi Perpustakaan', 'dosen_id' => $this->dosen->id];
    }

    private function submit(): PengajuanSkripsi
    {
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();

        return PengajuanSkripsi::latest('id')->firstOrFail();
    }

    private function decide(PengajuanSkripsi $submission, string $status, ?string $reason = null)
    {
        return $this->actingAs($submission->dosen->user)->put(route('dosen.skripsi.decide', $submission), ['status' => $status, 'alasan' => $reason]);
    }

    public function test_student_submits_snapshot_without_assigning_supervisor(): void
    {
        $submission = $this->submit();
        self::assertSame('Menunggu', $submission->status);
        self::assertSame($this->student->id, $submission->mahasiswa_id);
        self::assertSame(0, $this->student->pembimbingSkripsi()->count());
        $this->assertDatabaseHas('riwayat_skripsis', ['pengajuan_skripsi_id' => $submission->id, 'pelaku_id' => $this->student->user_id, 'tindakan' => 'Pengajuan']);
        $this->get(route('mahasiswa.skripsi'))->assertOk()->assertSee($submission->judul)->assertSee('Pengajuan Skripsi');
    }

    public function test_duplicate_and_forged_student_requests_are_rejected_or_scoped(): void
    {
        $other = $this->student($this->student->prodi_id);
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload(['mahasiswa_id' => $other->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengajuan_skripsis', ['mahasiswa_id' => $this->student->id]);
        $this->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasErrors('skripsi');
        self::assertSame(1, PengajuanSkripsi::count());
    }

    public function test_student_cannot_read_another_student_submission(): void
    {
        $submission = $this->submit();
        $other = $this->student($this->student->prodi_id);
        $this->actingAs($other->user)->get(route('mahasiswa.skripsi.show', $submission))->assertForbidden();
        $this->get(route('mahasiswa.skripsi'))->assertOk()->assertDontSee($submission->judul);
    }

    public function test_dosen_cannot_read_or_decide_another_dosen_submission(): void
    {
        $submission = $this->submit();
        $this->actingAs($this->otherDosen->user)->get(route('dosen.skripsi.show', $submission))->assertForbidden();
        $this->put(route('dosen.skripsi.decide', $submission), ['status' => 'Diterima'])->assertForbidden();
        $this->get(route('dosen.skripsi'))->assertOk()->assertDontSee($submission->judul);
        self::assertSame('Menunggu', $submission->fresh()->status);
    }

    public function test_acceptance_assigns_one_official_supervisor_and_repeated_decision_fails(): void
    {
        $submission = $this->submit();
        $this->decide($submission, 'Diterima')->assertSessionHasNoErrors();
        self::assertSame($this->dosen->id, $this->student->pembimbingSkripsi()->sole()->dosen_id);
        $this->decide($submission, 'Ditolak', 'Terlambat')->assertSessionHasErrors('skripsi');
        $this->decide($submission, 'Diterima')->assertSessionHasErrors('skripsi');
        self::assertSame('Diterima', $submission->fresh()->status);
        self::assertSame(1, RiwayatSkripsi::where('tindakan', 'Diterima')->count());
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload(['dosen_id' => $this->otherDosen->id]))->assertSessionHasErrors('skripsi');
        $this->actingAs($this->dosen->user)->get(route('dosen.skripsi'))->assertOk()->assertSee('Mahasiswa bimbingan resmi (1)');
    }

    public function test_rejection_requires_reason_and_resubmission_preserves_old_title_and_decision(): void
    {
        $old = $this->submit();
        $this->decide($old, 'Ditolak', '  ')->assertSessionHasErrors('alasan');
        self::assertSame('Menunggu', $old->fresh()->status);
        $this->decide($old, 'Ditolak', 'Topik belum sesuai')->assertSessionHasNoErrors();
        self::assertSame(0, $this->student->pembimbingSkripsi()->count());
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasErrors('skripsi');
        $this->post(route('mahasiswa.skripsi.store'), $this->payload(['judul' => 'Judul Baru', 'dosen_id' => $this->otherDosen->id]))->assertSessionHasNoErrors();
        self::assertSame('Sistem Informasi Perpustakaan', $old->fresh()->judul);
        self::assertSame('Topik belum sesuai', $old->fresh()->alasan_keputusan);
        self::assertSame('Judul Baru', PengajuanSkripsi::latest('id')->first()->judul);
        $audit = RiwayatSkripsi::where('tindakan', 'Pengajuan ulang')->sole();
        self::assertSame($old->judul, $audit->perubahan['judul_lama']);
        self::assertSame('Judul Baru', $audit->perubahan['judul_baru']);
        $this->get(route('mahasiswa.skripsi.show', $old))->assertOk()->assertSee('Topik belum sesuai');
    }

    public function test_admin_finds_unsubmitted_pending_rejected_and_accepted_students(): void
    {
        $submission = $this->submit();
        $unsubmitted = $this->student($this->student->prodi_id);
        $this->actingAs($this->admin)->get(route('admin.skripsi', ['status' => 'Belum mengajukan']))->assertOk()
            ->assertViewHas('students', fn ($s) => $s->pluck('id')->all() === [$unsubmitted->id]);
        $this->get(route('admin.skripsi', ['status' => 'Belum mendapat pembimbing']))->assertOk()
            ->assertViewHas('students', fn ($s) => $s->total() === 2)
            ->assertViewHas('summary', fn ($s) => $s['Menunggu'] === 1 && $s['Belum mengajukan'] === 1);
        $this->decide($submission, 'Ditolak', 'Topik')->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get(route('admin.skripsi', ['status' => 'Ditolak']))->assertOk()->assertViewHas('students', fn ($s) => $s->total() === 1);
        $new = app(SkripsiService::class)->transfer($this->admin, $submission, ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Penyesuaian']);
        $this->decide($new, 'Diterima')->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get(route('admin.skripsi', ['status' => 'Belum mendapat pembimbing']))->assertOk()->assertViewHas('students', fn ($s) => $s->pluck('id')->all() === [$unsubmitted->id]);
        $this->get(route('admin.skripsi'))->assertOk()->assertViewHas('summary', fn ($s) => $s['Ditolak'] === 0 && $s['Diterima'] === 1);
    }

    public function test_admin_searches_name_nim_title_and_filters_prodi_dosen_period(): void
    {
        $submission = $this->submit();
        $this->actingAs($this->admin);
        foreach ([$this->student->nama, $this->student->nim, 'Perpustakaan'] as $search) {
            $this->get(route('admin.skripsi', ['q' => $search, 'prodi_id' => $this->student->prodi_id, 'dosen_id' => $this->dosen->id, 'periode_id' => $this->period->id]))->assertOk()->assertViewHas('students', fn ($s) => $s->total() === 1);
        }
        $this->get(route('admin.skripsi', ['dosen_id' => $this->otherDosen->id]))->assertOk()->assertViewHas('students', fn ($s) => $s->total() === 0);
        $this->get(route('admin.skripsi'))->assertOk()->assertViewHas('loads', fn ($loads) => $loads->firstWhere('id', $this->dosen->id)->menunggu_count === 1);
    }

    #[DataProvider('transferStatuses')]
    public function test_transfer_retains_history_and_requires_new_dosen_approval(string $status): void
    {
        $old = $this->submit();
        if ($status === 'Ditolak') {
            $this->decide($old, 'Ditolak', 'Alasan lama')->assertSessionHasNoErrors();
        }
        $this->actingAs($this->admin)->post(route('admin.skripsi.transfer', $old), ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Pemerataan beban'])->assertSessionHasNoErrors();
        $new = PengajuanSkripsi::latest('id')->first();
        self::assertSame($status === 'Menunggu' ? 'Dialihkan' : 'Ditolak', $old->fresh()->status);
        self::assertSame($old->judul, $new->judul);
        self::assertSame('Menunggu', $new->status);
        self::assertSame($this->admin->id, $new->dibuat_oleh);
        self::assertSame($old->id, $new->pengajuan_asal_id);
        self::assertSame(0, $this->student->pembimbingSkripsi()->count());
        if ($status === 'Ditolak') {
            self::assertSame('Alasan lama', $old->fresh()->alasan_keputusan);
        }
        $audit = RiwayatSkripsi::where('tindakan', 'Pengalihan')->sole();
        self::assertSame($this->dosen->id, $audit->perubahan['dosen_asal_id']);
        self::assertSame($this->otherDosen->id, $audit->perubahan['dosen_tujuan_id']);
        $this->decide($old, 'Diterima')->assertSessionHasErrors('skripsi');
        $this->actingAs($this->dosen->user)->put(route('dosen.skripsi.decide', $new), ['status' => 'Diterima'])->assertForbidden();
        $this->decide($new, 'Diterima')->assertSessionHasNoErrors();
        self::assertSame($this->otherDosen->id, $this->student->pembimbingSkripsi()->sole()->dosen_id);
    }

    public static function transferStatuses(): array
    {
        return [['Menunggu'], ['Ditolak']];
    }

    public function test_transfer_validation_repeated_transfer_and_accepted_transfer_are_blocked(): void
    {
        $old = $this->submit();
        $this->actingAs($this->admin)->post(route('admin.skripsi.transfer', $old), ['dosen_id' => $this->otherDosen->id])->assertSessionHasErrors('alasan');
        $this->post(route('admin.skripsi.transfer', $old), ['dosen_id' => $this->dosen->id, 'alasan' => 'Sama'])->assertSessionHasErrors('skripsi');
        $this->post(route('admin.skripsi.transfer', $old), ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Alihkan'])->assertSessionHasNoErrors();
        $this->post(route('admin.skripsi.transfer', $old), ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Ulang'])->assertSessionHasErrors('skripsi');
        $new = PengajuanSkripsi::latest('id')->first();
        $this->decide($new, 'Diterima')->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.skripsi.transfer', $new), ['dosen_id' => $this->dosen->id, 'alasan' => 'Ganti'])->assertSessionHasErrors('skripsi');
        self::assertSame(2, PengajuanSkripsi::count());
    }

    #[DataProvider('outsideTimes')]
    public function test_direct_mutations_are_blocked_outside_period_while_pages_remain_readable(string $time): void
    {
        $submission = $this->submit();
        $other = $this->student($this->student->prodi_id);
        $this->travelTo($time === 'before' ? $this->period->mulai->copy()->subSecond() : $this->period->berakhir->copy()->addSecond());
        $this->actingAs($other->user)->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasErrors('skripsi');
        $this->decide($submission, 'Diterima')->assertSessionHasErrors('skripsi');
        $this->decide($submission, 'Ditolak', 'Alasan')->assertSessionHasErrors('skripsi');
        $this->actingAs($this->admin)->post(route('admin.skripsi.transfer', $submission), ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Alasan'])->assertSessionHasErrors('skripsi');
        $this->get(route('admin.skripsi'))->assertOk();
        $this->actingAs($this->student->user)->get(route('mahasiswa.skripsi'))->assertOk();
        $this->get(route('mahasiswa.skripsi.show', $submission))->assertOk();
        self::assertSame('Menunggu', $submission->fresh()->status);
        self::assertSame(1, PengajuanSkripsi::count());
        $this->travelBack();
    }

    public static function outsideTimes(): array
    {
        return [['before'], ['after']];
    }

    public function test_admin_extends_deadline_with_audit_and_actions_reopen(): void
    {
        $submission = $this->submit();
        $this->period->update(['berakhir' => now()->subMinute()]);
        $this->decide($submission, 'Diterima')->assertSessionHasErrors('skripsi');
        $this->actingAs($this->admin)->put(route('admin.skripsi.periode.update', $this->period), ['nama' => 'Diperpanjang', 'mulai' => $this->period->mulai->format('Y-m-d\TH:i'), 'berakhir' => now()->addDays(2)->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();
        $audit = RiwayatSkripsi::where('tindakan', 'Pengaturan periode')->sole();
        self::assertArrayHasKey('berakhir', $audit->perubahan['sebelum']);
        self::assertNotSame($audit->perubahan['sebelum']['berakhir'], $audit->perubahan['sesudah']['berakhir']);
        $this->decide($submission, 'Diterima')->assertSessionHasNoErrors();
    }

    public function test_period_validation_and_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');
        $this->actingAs($this->admin)->post(route('admin.skripsi.periode.store'), ['nama' => 'Periode WIB', 'mulai' => '2026-10-01T09:00', 'berakhir' => '2026-10-01T08:00'])->assertSessionHasErrors('berakhir');
        $this->post(route('admin.skripsi.periode.store'), ['nama' => 'Periode WIB', 'mulai' => '2026-10-01T09:00', 'berakhir' => '2026-10-01T10:00'])->assertSessionHasNoErrors();
        $period = PeriodeSkripsi::latest('id')->first();
        self::assertSame('09:00', $period->mulai->format('H:i'));
        $this->travelTo($period->mulai);
        self::assertTrue($period->terbuka());
        $this->travelTo($period->berakhir);
        self::assertTrue($period->terbuka());
        $this->get(route('admin.skripsi', ['periode_id' => $period->id]))->assertOk()->assertSee('Asia/Jakarta');
        $this->travelBack();
        date_default_timezone_set('UTC');
    }

    public function test_roles_and_guest_are_protected(): void
    {
        $this->get(route('admin.skripsi'))->assertRedirect(route('login'));
        $submission = $this->submit();
        $this->get(route('admin.skripsi'))->assertForbidden();
        $this->get(route('dosen.skripsi'))->assertForbidden();
        $this->put(route('dosen.skripsi.decide', $submission), ['status' => 'Diterima'])->assertForbidden();
        $this->post(route('admin.skripsi.periode.store'), [])->assertForbidden();
        $this->post(route('admin.skripsi.transfer', $submission), [])->assertForbidden();
        $this->actingAs($this->admin)->post(route('mahasiswa.skripsi.store'), $this->payload())->assertForbidden();
    }

    public function test_ineligible_dosen_student_and_required_fields_are_validated(): void
    {
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), ['periode_skripsi_id' => $this->period->id])->assertSessionHasErrors(['judul', 'dosen_id']);
        $this->dosen->skripsi_aktif = false;
        $this->dosen->save();
        $this->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasErrors('skripsi');
        $this->actingAs($this->admin)->put(route('admin.skripsi.dosen', $this->dosen), ['aktif' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('riwayat_skripsis', ['tindakan' => 'Kelayakan dosen']);
        $this->dosen->user->update(['role' => 'mahasiswa']);
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasErrors('skripsi');
        $this->student->update(['prodi_id' => null]);
        $this->post(route('mahasiswa.skripsi.store'), $this->payload(['dosen_id' => $this->otherDosen->id]))->assertSessionHasErrors('skripsi');
        self::assertSame(0, PengajuanSkripsi::count());
    }

    public function test_database_unique_constraint_rejects_second_active_or_accepted_submission(): void
    {
        $submission = $this->submit();
        foreach (['Menunggu', 'Diterima'] as $status) {
            try {
                PengajuanSkripsi::create($this->payload(['dosen_id' => $this->otherDosen->id]) + ['mahasiswa_id' => $this->student->id, 'status' => $status, 'dibuat_oleh' => $this->admin->id, 'jenis_pembuat' => 'admin']);
                self::fail('Database must reject a second active submission.');
            } catch (QueryException $e) {
                self::assertStringContainsString('UNIQUE constraint failed', $e->getMessage());
            }
        }
        self::assertSame(1, PengajuanSkripsi::count());
    }

    public function test_empty_period_pages_are_readable(): void
    {
        $this->period->delete();
        foreach (['admin' => $this->admin, 'mahasiswa' => $this->student->user, 'dosen' => $this->dosen->user] as $role => $user) {
            $this->actingAs($user)->get(route($role.'.skripsi'))->assertOk()->assertSee('Belum ada periode');
        }
    }

    public function test_title_has_no_update_endpoint_and_html_is_escaped(): void
    {
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload(['judul' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $submission = PengajuanSkripsi::sole();
        $this->put(route('mahasiswa.skripsi.show', $submission), ['judul' => 'Diganti'])->assertStatus(405);
        $this->get(route('mahasiswa.skripsi.show', $submission))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    #[DataProvider('concurrentActions')]
    public function test_concurrent_processes_preserve_one_active_submission_and_supervisor(string $action): void
    {
        $submission = $action === 'submit' ? null : $this->submit();
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $file = $directory.'/skripsi-'.bin2hex(random_bytes(8)).'.sqlite';
        touch($file);
        $pdo = DB::connection()->getPdo();
        $pdo->exec('VACUUM INTO '.$pdo->quote($file));
        unset($pdo);
        config(['database.connections.sqlite.database' => $file, 'database.connections.sqlite.busy_timeout' => 10000]);
        DB::purge('sqlite');
        $processes = [];
        try {
            // Hold a real DB write lock until BOTH competing processes have started.
            DB::beginTransaction();
            PeriodeSkripsi::whereKey($this->period->id)->increment('lock_version');
            for ($i = 0; $i < 2; $i++) {
                $workerAction = $action === 'race' ? ($i === 0 ? 'decide' : 'transfer') : $action;
                $actor = match ($workerAction) {
                    'submit' => $this->student->user_id,
                    'decide' => $this->dosen->user_id,
                    default => $this->admin->id,
                };
                $data = $workerAction === 'submit' ? $this->payload(['dosen_id' => $i === 0 ? $this->dosen->id : $this->otherDosen->id])
                    : ['id' => $submission->id, 'dosen_id' => $this->otherDosen->id, 'alasan' => 'Pengalihan bersamaan'];
                $process = new Process([PHP_BINARY, '-d', 'extension=pdo_sqlite', base_path('tests/Skripsi/concurrency-worker.php'), $file, (string) $actor, $workerAction, json_encode($data, JSON_THROW_ON_ERROR)], base_path(), ['APP_ENV' => 'testing']);
                $process->setTimeout(15);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 5;
            do {
                $bothReady = str_contains($processes[0]->getOutput(), 'ready') && str_contains($processes[1]->getOutput(), 'ready');
                if (! $bothReady) {
                    usleep(10000);
                }
            } while (! $bothReady && microtime(true) < $deadline);
            self::assertTrue($bothReady, implode('\n', array_map(fn ($p) => $p->getErrorOutput().$p->getOutput(), $processes)));
            DB::commit();
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                self::assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = trim(str_replace('ready', '', $process->getOutput()));
            }
            sort($results);
            self::assertSame(['rejected', 'success'], $results);
            self::assertSame(1, PengajuanSkripsi::whereIn('status', ['Menunggu', 'Diterima'])->count());
            self::assertLessThanOrEqual(1, $this->student->pembimbingSkripsi()->count());
            if ($action === 'decide') {
                self::assertSame(1, $this->student->pembimbingSkripsi()->count());
                self::assertSame(1, RiwayatSkripsi::where('tindakan', 'Diterima')->count());
            }
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            DB::purge('sqlite');
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public static function concurrentActions(): array
    {
        return [['submit'], ['decide'], ['transfer'], ['race']];
    }

    public function test_each_period_has_independent_submission_and_supervisor(): void
    {
        $old = $this->submit();
        $this->decide($old, 'Diterima')->assertSessionHasNoErrors();
        $newPeriod = PeriodeSkripsi::create(['nama' => 'Periode berikutnya', 'mulai' => now()->subHour(), 'berakhir' => now()->addDay()]);
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload(['periode_skripsi_id' => $newPeriod->id, 'dosen_id' => $this->otherDosen->id]))->assertSessionHasNoErrors();
        $new = PengajuanSkripsi::latest('id')->first();
        $this->decide($new, 'Diterima')->assertSessionHasNoErrors();
        self::assertSame($this->dosen->id, $this->student->pembimbingSkripsi()->where('periode_skripsi_id', $this->period->id)->sole()->dosen_id);
        self::assertSame($this->otherDosen->id, $this->student->pembimbingSkripsi()->where('periode_skripsi_id', $newPeriod->id)->sole()->dosen_id);
    }

    public function test_audit_actor_snapshot_and_creator_type_survive_account_changes(): void
    {
        $submission = $this->submit();
        $originalName = $this->student->user->name;
        $this->student->user->update(['name' => 'Nama diganti', 'role' => 'admin']);
        $audit = $submission->riwayat()->sole();
        self::assertSame($originalName, $audit->pelaku_nama);
        self::assertSame('mahasiswa', $audit->pelaku_role);
        self::assertSame('mahasiswa', $submission->fresh()->jenis_pembuat);
        $this->actingAs($this->admin)->get(route('admin.skripsi.show', $submission))->assertOk()->assertSee($originalName);
    }

    #[DataProvider('skripsiSemesters')]
    public function test_semester_requirement_is_enforced_on_form_and_direct_submission(?int $semester, bool $allowed): void
    {
        $this->student->update(['semester' => $semester]);
        $this->actingAs($this->student->user);
        $page = $this->get(route('mahasiswa.skripsi'))->assertOk();
        if ($allowed) {
            $page->assertSee('Kirim pengajuan');
            $this->post(route('mahasiswa.skripsi.store'), $this->payload())->assertSessionHasNoErrors();
            self::assertSame(1, PengajuanSkripsi::count());
        } else {
            $page->assertSee('Anda belum memenuhi syarat semester')->assertDontSee('Kirim pengajuan');
            // The submitted semester must not override the student's stored semester.
            $this->post(route('mahasiswa.skripsi.store'), $this->payload(['semester' => 8]))->assertSessionHasErrors('skripsi');
            self::assertSame(0, PengajuanSkripsi::count());
            self::assertSame(0, RiwayatSkripsi::count());
        }
    }

    public static function skripsiSemesters(): array
    {
        return [[null, false], [1, false], [6, false], [7, true], [8, true], [9, true], [14, true]];
    }

    public function test_admin_student_list_counts_only_semester_seven_and_above(): void
    {
        foreach ([null, 6, 8, 9] as $semester) {
            $this->student($this->student->prodi_id)->update(['semester' => $semester]);
        }
        $this->actingAs($this->admin)->get(route('admin.skripsi', ['status' => 'Belum mengajukan']))->assertOk()
            ->assertViewHas('students', fn ($students) => $students->total() === 3 && $students->every(fn ($s) => $s->semester >= 7))
            ->assertViewHas('summary', fn ($summary) => $summary['Belum mengajukan'] === 3);
    }

    public function test_semester_cannot_be_bypassed_by_resubmission_decision_or_admin_transfer(): void
    {
        $submission = $this->submit();
        $this->student->update(['semester' => 6]);
        $this->decide($submission, 'Diterima')->assertSessionHasErrors('skripsi');
        $this->actingAs($this->dosen->user)->get(route('dosen.skripsi'))->assertOk()->assertDontSee('Berikan keputusan');
        $this->actingAs($this->admin)->post(route('admin.skripsi.transfer', $submission), ['dosen_id' => $this->otherDosen->id, 'alasan' => 'Pengalihan'])->assertSessionHasErrors('skripsi');
        $this->get(route('admin.skripsi.show', $submission))->assertOk();
        self::assertSame('Menunggu', $submission->fresh()->status);
        $this->student->update(['semester' => 7]);
        $this->decide($submission, 'Ditolak', 'Topik belum sesuai')->assertSessionHasNoErrors();
        $this->student->update(['semester' => 6]);
        $this->actingAs($this->student->user)->post(route('mahasiswa.skripsi.store'), $this->payload(['dosen_id' => $this->otherDosen->id]))->assertSessionHasErrors('skripsi');
        self::assertSame(1, PengajuanSkripsi::count());
    }
}
