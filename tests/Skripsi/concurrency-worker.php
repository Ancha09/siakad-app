<?php

// Only invoked by SkripsiTest against its generated SQLite snapshot.
use App\Models\PengajuanSkripsi;
use App\Models\User;
use App\Services\SkripsiService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$file = realpath($argv[1]);
if (! $file || dirname($file) !== realpath(storage_path('framework/testing')) || ! str_starts_with(basename($file), 'skripsi-')) {
    throw new RuntimeException('Only generated skripsi test databases are allowed.');
}
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $file, 'database.connections.sqlite.url' => null, 'database.connections.sqlite.busy_timeout' => 10000]);
DB::purge('sqlite');
$actor = User::findOrFail((int) $argv[2]);
$action = $argv[3];
$data = json_decode($argv[4], true, flags: JSON_THROW_ON_ERROR);
echo "ready\n";
flush();
try {
    $service = app(SkripsiService::class);
    if ($action === 'submit') {
        $service->submit($actor, $data);
    } elseif ($action === 'decide') {
        $service->decide($actor, PengajuanSkripsi::findOrFail($data['id']), ['status' => 'Diterima']);
    } elseif ($action === 'transfer') {
        $service->transfer($actor, PengajuanSkripsi::findOrFail($data['id']), $data);
    }
    echo "success\n";
} catch (ValidationException $e) {
    echo "rejected\n";
}
