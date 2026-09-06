<?php

// CLI only: never expose diagnostics as a public web route.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$errors = [];
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    $errors[] = 'PHP 8.3+ diperlukan.';
}
foreach (['pdo_pgsql', 'openssl', 'mbstring', 'fileinfo', 'dom', 'session', 'tokenizer', 'filter'] as $extension) {
    if (! extension_loaded($extension)) {
        $errors[] = "Extension $extension belum aktif pada PHP ini.";
    }
}
$root = dirname(__DIR__);
if (! is_file($root.'/vendor/autoload.php')) {
    $errors[] = 'Jalankan composer install terlebih dahulu.';
} else {
    require $root.'/vendor/autoload.php';
}
if (is_file($root.'/public/hot')) {
    $errors[] = 'public/hot masih ada; hentikan Vite dev dan jangan sertakan file ini dalam deploy.';
}
try {
    if (! is_file($root.'/public/build/manifest.json')) {
        throw new RuntimeException('Manifest missing');
    }
    $manifest = json_decode(file_get_contents($root.'/public/build/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
        if (! isset($manifest[$entry]['file']) || ! is_file($root.'/public/build/'.$manifest[$entry]['file'])) {
            $errors[] = "Aset $entry tidak ditemukan. Jalankan npm ci lalu npm run build.";
        }
    }
} catch (Throwable $exception) {
    $errors[] = 'Manifest Vite belum tersedia atau tidak valid. Jalankan npm run build.';
}
foreach (['storage', 'bootstrap/cache'] as $directory) {
    if (! is_writable($root.'/'.$directory)) {
        $errors[] = "$directory harus dapat ditulis runtime PHP.";
    }
}
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}
echo 'PHP, driver PostgreSQL, folder writable dan manifest Vite siap. Koneksi Supabase belum diuji oleh skrip ini.'.PHP_EOL;
