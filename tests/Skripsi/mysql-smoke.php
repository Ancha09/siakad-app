<?php

// Optional MySQL integration check. Requires a disposable instance whose datadir
// matches storage/framework/testing/mysql-skripsi-path.txt. Never uses .env DB.
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanSkripsi;
use App\Models\Prodi;
use App\Models\RiwayatSkripsi;
use App\Models\User;
use App\Services\SkripsiService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$expected = realpath(trim(file_get_contents(storage_path('framework/testing/mysql-skripsi-path.txt'))));
if (! $expected || ! str_starts_with($expected, realpath(storage_path('framework/testing')).DIRECTORY_SEPARATOR.'mysql-skripsi-')) {
    throw new RuntimeException('A disposable MySQL datadir is required.');
}
$pdo = null;
for ($attempt = 0; $attempt < 50; $attempt++) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=33316', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (PDOException $e) {
        if ($attempt === 49) {
            throw $e;
        }
        usleep(100000);
    }
}
$actual = realpath($pdo->query('SELECT @@datadir')->fetchColumn());
if ($actual !== $expected) {
    throw new RuntimeException('Refusing to use a non-test MySQL instance.');
}
$pdo->exec('CREATE DATABASE siakad_skripsi_isolated CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
config(['database.default' => 'mysql', 'database.connections.mysql' => [
    'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 33316, 'database' => 'siakad_skripsi_isolated',
    'username' => 'root', 'password' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '', 'strict' => true, 'engine' => 'InnoDB',
]]);
DB::purge('mysql');
foreach ([
    '0001_01_01_000000_create_users_table.php', '2026_07_05_041846_add_role_to_users_table.php',
    '2026_07_05_030858_create_prodis_table.php', '2026_07_05_030905_create_dosens_table.php',
    '2026_07_05_030913_create_mahasiswas_table.php', '2026_09_05_000000_create_skripsi_tables.php',
] as $migration) {
    (require database_path('migrations/'.$migration))->up();
}
$check = function (bool $condition, string $message) {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$users = [];
foreach (['admin', 'mahasiswa', 'dosen1', 'dosen2'] as $key) {
    $users[$key] = User::create(['name' => $key, 'login' => $key, 'email' => $key.'@example.test', 'password' => 'password', 'role' => str_starts_with($key, 'dosen') ? 'dosen' : $key]);
}
$prodi = Prodi::create(['kode_prodi' => 'TI', 'nama_prodi' => 'Informatika']);
$student = Mahasiswa::create(['nim' => 'M001', 'nama' => 'Mahasiswa Uji', 'semester' => 7, 'user_id' => $users['mahasiswa']->id, 'prodi_id' => $prodi->id]);
$dosens = [];
$service = app(SkripsiService::class);
foreach (['dosen1', 'dosen2'] as $key) {
    $dosens[$key] = Dosen::create(['nidn' => $key, 'nama' => $key, 'user_id' => $users[$key]->id, 'prodi_id' => $prodi->id]);
    $service->setDosen($users['admin'], $dosens[$key], true);
}
$period = $service->savePeriod($users['admin'], ['nama' => 'Uji MySQL', 'mulai' => now()->subDay(), 'berakhir' => now()->addDay()]);
$submission = $service->submit($users['mahasiswa'], ['periode_skripsi_id' => $period->id, 'dosen_id' => $dosens['dosen1']->id, 'judul' => 'Judul asli']);
$check($student->pembimbingSkripsi()->count() === 0, 'Pending must not assign a supervisor.');
$service->decide($users['dosen1'], $submission, ['status' => 'Ditolak', 'alasan' => 'Alasan asli']);
$new = $service->transfer($users['admin'], $submission, ['dosen_id' => $dosens['dosen2']->id, 'alasan' => 'Beban dosen']);
$check($submission->fresh()->alasan_keputusan === 'Alasan asli' && $new->status === 'Menunggu', 'Transfer must preserve decision and require approval.');
$service->decide($users['dosen2'], $new, ['status' => 'Diterima']);
$check($student->pembimbingSkripsi()->sole()->dosen_id === $dosens['dosen2']->id, 'Accepted supervisor must match.');
try {
    PengajuanSkripsi::create(['periode_skripsi_id' => $period->id, 'mahasiswa_id' => $student->id, 'dosen_id' => $dosens['dosen1']->id, 'judul' => 'Duplikat', 'status' => 'Diterima', 'dibuat_oleh' => $users['admin']->id, 'jenis_pembuat' => 'admin']);
    throw new RuntimeException('Unique constraint did not reject duplicate.');
} catch (QueryException $e) {
    $check(($e->errorInfo[1] ?? null) === 1062, 'Expected MySQL duplicate key error.');
}
$check(RiwayatSkripsi::where('tindakan', 'Diterima')->count() === 1, 'Acceptance audit missing.');
(require database_path('migrations/2026_09_05_000000_create_skripsi_tables.php'))->down();
$check(Mahasiswa::count() === 1 && Dosen::count() === 2, 'Rollback must preserve existing academic data.');
echo "MySQL 8 integration passed: migration up/down, eligibility, period, submit, reject, transfer, accept, unique constraint, audit.\n";
