<?php

// Only the disposable local PostgreSQL server on port 55439 is allowed.
// No .env database credentials or application data are used.
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\PeriodeSkripsi;
use App\Models\Prodi;
use App\Models\RiwayatSkripsi;
use App\Models\User;
use App\Services\SkripsiService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';

foreach ([
    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => 'storage/framework/testing-pg-config.php',
    'DB_CONNECTION' => 'pgsql', 'DB_URL' => '', 'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '55439', 'DB_DATABASE' => 'postgres', 'DB_USERNAME' => 'siakad_test',
    'DB_PASSWORD' => '', 'DB_CHARSET' => 'utf8', 'DB_SSLMODE' => 'disable',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'BCRYPT_ROUNDS' => '4', 'APP_FORCE_HTTPS' => 'false',
] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

$schema = 'test_siakad_'.bin2hex(random_bytes(6));
putenv('DB_SCHEMA='.$schema);
$_ENV['DB_SCHEMA'] = $_SERVER['DB_SCHEMA'] = $schema;
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function check(bool $result, string $message): void
{
    if (! $result) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function account(string $login, string $role): User
{
    return User::create(['login' => $login, 'name' => $login, 'email' => $login.'@example.test', 'role' => $role, 'password' => 'test-password']);
}

DB::statement('CREATE SCHEMA "'.$schema.'"');
try {
    check(Artisan::call('migrate', ['--force' => true]) === 0, 'All migrations run on PostgreSQL');
    check(DB::table('migrations')->count() === count(glob(__DIR__.'/../../database/migrations/*.php')), 'Every migration is recorded');

    $admin = account('admin', 'admin');
    $studentUser = account('mahasiswa', 'mahasiswa');
    $prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika']);
    $student = Mahasiswa::create(['nim' => 'TEST01', 'nama' => 'Mahasiswa Uji', 'semester' => 7, 'prodi_id' => $prodi->id, 'user_id' => $studentUser->id]);
    $service = app(SkripsiService::class);
    $dosens = [];
    foreach (['dosen1', 'dosen2'] as $login) {
        $user = account($login, 'dosen');
        $dosen = Dosen::create(['nidn' => $login, 'nama' => $login, 'prodi_id' => $prodi->id, 'user_id' => $user->id]);
        $service->setDosen($admin, $dosen, true);
        $dosens[] = $dosen->fresh();
    }
    $period = $service->savePeriod($admin, ['nama' => 'Uji PostgreSQL', 'mulai' => now()->subDay(), 'berakhir' => now()->addDay()]);
    $payload = ['periode_skripsi_id' => $period->id, 'dosen_id' => $dosens[0]->id, 'judul' => 'Sistem Informasi Akademik'];
    $submission = $service->submit($studentUser, $payload);
    check($submission->fresh()->mahasiswa_aktif === $student->id, 'Generated active-student column works');
    try {
        $submission->replicate()->save();
        throw new RuntimeException('Duplicate active submission was allowed');
    } catch (QueryException $e) {
        check($e->getCode() === '23505', 'Database rejects a second active submission');
    }
    $service->decide($dosens[0]->user, $submission, ['status' => 'Ditolak', 'alasan' => 'Topik belum sesuai']);
    check($submission->fresh()->mahasiswa_aktif === null, 'Rejection frees active-student slot');
    $second = $service->submit($studentUser, array_replace($payload, ['dosen_id' => $dosens[1]->id]));
    $third = $service->transfer($admin, $second, ['dosen_id' => $dosens[0]->id, 'alasan' => 'Penyesuaian bidang']);
    $service->decide($dosens[0]->user, $third, ['status' => 'Diterima']);
    check($student->pembimbingSkripsi()->sole()->id === $third->id, 'Accept, reject, resubmit and admin transfer preserve one supervisor');
    check(is_array(RiwayatSkripsi::where('tindakan', 'Pengalihan')->firstOrFail()->perubahan), 'Audit JSON is readable');
    check(Mahasiswa::whereLike('nama', '%MAHASISWA%')->count() === 1, 'Search ignores letter case');

    $request = Illuminate\Http\Request::create('/admin/skripsi', 'GET', ['q' => 'SISTEM']);
    $request->setUserResolver(fn () => $admin);
    $view = app(App\Http\Controllers\SkripsiController::class)->admin($request);
    check($view->getData()['summary']['Diterima'] === 1 && $view->getData()['submissions']->total() === 3, 'Admin history and latest-submission search work on PostgreSQL');
    $request = Illuminate\Http\Request::create('/dosen/skripsi');
    $request->setUserResolver(fn () => $dosens[0]->user);
    $view = app(App\Http\Controllers\SkripsiController::class)->index($request);
    check($view->getData()['accepted']->total() === 1, 'Dosen list includes the accepted student and title');

    foreach (['Sabtu', 'Rabu', 'Senin'] as $hari) {
        Jadwal::create(['hari' => $hari, 'jam_mulai' => '08:00', 'jam_selesai' => '09:00']);
    }
    // Execute the actual controller query (including CASE ordering) on PostgreSQL.
    $request = Illuminate\Http\Request::create('/admin/jadwal');
    $view = app(App\Http\Controllers\Admin\JadwalController::class)->index($request);
    check($view->getData()['jadwals']->pluck('hari')->all() === ['Senin', 'Rabu', 'Sabtu'], 'Admin schedule uses portable day ordering');
    $krs = Krs::create(['mahasiswa_id' => $student->id, 'jadwal_id' => Jadwal::first()->id, 'tahun_akademik' => '2026/2027', 'semester_akademik' => 'Ganjil']);
    check($krs->fresh()->status === 'Menunggu', 'KRS default and constraint accept Menunggu');
    $krs->update(['status' => 'Disetujui']);
    $krs->update(['status' => 'Ditolak']);
    check($krs->fresh()->status === 'Ditolak', 'KRS approval and rejection work');

    $period->update(['berakhir' => now()->subHour()]);
    try {
        $service->transfer($admin, $third, ['dosen_id' => $dosens[1]->id, 'alasan' => 'Terlambat']);
        throw new RuntimeException('Expired period accepted an edit');
    } catch (ValidationException $e) {
        check(str_contains($e->getMessage(), 'ditutup'), 'Deadline blocks admin changes');
    }

    // Empty only this disposable schema before testing rollback of all migrations.
    foreach (['riwayat_skripsis', 'pengajuan_skripsis', 'periode_skripsis', 'krs', 'jadwals', 'dosens', 'mahasiswas', 'prodis', 'users'] as $table) {
        DB::table($table)->delete();
    }
    check(Artisan::call('migrate:rollback', ['--force' => true]) === 0, 'All migrations roll back on the disposable schema');
} finally {
    DB::statement('DROP SCHEMA "'.$schema.'" CASCADE');
    DB::disconnect();
}
