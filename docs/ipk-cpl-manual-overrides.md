# Perbaikan manual override IPK CPL TP

Tanggal validasi: 23 September 2026.

## Akar masalah

Importer dan laporan sebelumnya sama-sama mensyaratkan pencocokan otomatis. KU 302 ditolak karena nama berbeda, TA 106 ditolak karena prefix berbeda, dan TA 601 belum mempunyai master ekuivalen. Menghubungkan ID saja tidak cukup karena service laporan kembali memeriksa kecocokan otomatis.

Sekarang keputusan manual diperiksa terlebih dahulu berdasarkan prodi, kode/nama sumber yang dinormalisasi, CPL, serta kode/nama target. Pencocokan otomatis lainnya tetap dipertahankan. TA 601 juga wajib mempunyai kesesuaian nama agar kode yang sama tidak dianggap sebagai Tugas Akhir.

## Keputusan yang diterapkan oleh kode

| Sumber | CPL | Target | Alasan |
|---|---|---|---|
| KU 302 - Matriks Ruang Vektor | CPL 1 | KU 302 (TP) - Dasar Komputasi | Ekuivalensi perubahan kurikulum untuk akreditasi |
| TA 106 - Pengantar Ilmu Kebumian & Pertambangan | CPL 1 | KU 106 - Pengantar Ilmu Kebumian dan Pertambangan | Nama ekuivalen, prefix berbeda |
| TA 601 - MK Pilihan 2 | CPL 2, CPL 7 | TA 601 - MK Pilihan 2 | Master mata kuliah pilihan yang sama |

Master TP diprioritaskan; master umum tanpa prodi dapat dipakai untuk KU 302/KU 106 jika targetnya sesuai dan tidak ambigu. Master TG ditolak. TA 601 wajib mempunyai prodi TP. Jika belum tersedia, SKS dan semester diambil dari mapping, tanpa menebak nilai wajib. Benturan kode atau target ambigu dilaporkan tanpa mengubah master lain.

`ipk-cpl:apply-overrides` hanya memperbarui ID master pada mapping yang sesuai keputusan dan dapat membuat satu master TA 601. Lock program studi, transaksi, pemeriksaan kode ternormalisasi, serta unique constraint digunakan untuk mencegah duplikasi. Menjalankan ulang command tidak mengubah timestamp mapping yang sudah benar. Import Excel juga mengenali override agar import berikutnya tidak membatalkannya.

Log perubahan memuat keputusan, ID mapping/target, dan waktu. PDF menampilkan catatan ekuivalensi; Excel mempunyai sheet Manual Override. Web, PDF, dan Excel tetap memakai `IpkCplReportService::cplOverview` yang sama. Grafik kelengkapan dan Status CPL tetap tidak tersedia.

## File perubahan

- `app/Services/CplManualOverrides.php`: definisi, resolusi, validasi, catatan, dan penerapan override.
- `app/Console/Commands/ApplyIpkCplOverrides.php`: command perbaikan terarah.
- `app/Services/CplMappingImporter.php`: pertahankan override saat import dan log perubahan.
- `app/Services/IpkCplReportService.php`: penerimaan mapping yang konsisten dan ringkasan otomatis/manual/bermasalah.
- `app/Console/Commands/AuditIpkCplMapping.php`: jumlah dan daftar override serta alasan mapping bermasalah.
- `app/Console/Commands/ImportIpkCplMapping.php`: jumlah override dan alasan sumber belum terhubung.
- `app/Http/Controllers/Admin/IpkCplController.php`: sheet Excel dan validasi target pada form mapping.
- `resources/views/admin/ipk-cpl/program.blade.php`: card ringkasan mapping.
- `resources/views/admin/ipk-cpl/pdf.blade.php`: ringkasan mapping dan catatan ekuivalensi.
- `tests/Feature/IpkCplReportTest.php`: regresi perhitungan, import, ekspor, integritas data, dan override.
- `docs/ipk-cpl-manual-overrides.md`: laporan ini.

Model CPL/CplMataKuliah/MataKuliah, route, serta schema tidak perlu diubah. Tidak ada migration baru.

## Validasi yang dijalankan

Runtime PHP portable 8.3.35 dan Composer 2.10.3 disiapkan di `.codex/cpl-runtime` (diabaikan Git). Dependensi dipasang dari lockfile menggunakan `composer install --no-interaction --prefer-dist --no-scripts --no-progress`; composer.json/composer.lock tidak diubah. Tidak ada perubahan `.env`.

Perintah lokal yang dijalankan (PHP memakai `.codex/cpl-runtime/php.exe`):

```text
php -l <file PHP yang diubah>
php vendor/bin/pint <file PHP yang diubah>
php vendor/bin/pest tests/Feature/IpkCplReportTest.php --compact
php artisan route:list --path=ipk-cpl --except-vendor
git diff --check
```

Hasil akhir Pest: **18 tes lulus, 221 assertion**. Database tes adalah SQLite `:memory:` dengan APP_KEY khusus proses tes; bukan database aplikasi atau produksi.

Audit sebelum/sesudah pada fixture workbook `database/data/ipkcpl.xlsx` dijalankan melalui tes Artisan:

| Ukuran | Sebelum apply | Sesudah apply |
|---|---:|---:|
| Total mapping | 146 | 146 |
| Mapping aman otomatis | 142 | 142 |
| Mapping manual override | 0 | 4 |
| Mapping bermasalah | 4 | 0 |
| Sumber unik bermasalah | 3 | 0 |

Fixture menyiapkan master untuk seluruh sumber workbook. Angka ini membuktikan alur perbaikan pada data tes, bukan klaim audit database produksi.

Tes memverifikasi pembuatan TA 601 ketika belum ada, pemakaian TA-601 (TP) ketika sudah ada, command/import berulang tanpa duplikasi, penolakan master TG/Tugas Akhir, penolakan form TA 601 ke TA 301, serta pelaporan SKS wajib yang belum tersedia. Snapshot seluruh KHS/KRS fixture, master lama, dan mapping di luar keputusan tetap identik setelah command dijalankan dua kali.

Request HTTP tes halaman dan detail menghasilkan 200; PDF/Excel berhasil diunduh. Angka PDF sama dengan halaman web pada filter yang sama. Chart payload hanya label dan IPK. Route admin tetap terlindungi.

Smoke test Edge headless memakai aset chart production yang sudah ada dan script grafik dari Blade, pada HTML terisolasi. Kasus berisi nilai dan kosong sama-sama lulus. Diperiksa: satu canvas, satu Chart instance, CPL 1-9, Y 0-4, event DOMContentLoaded ulang tidak membuat chart baru, dan pesan kosong tampil. Smoke test ini bukan login interaktif ke situs produksi.

## Status database aplikasi

Workspace memakai konfigurasi SQLite, tetapi file database aplikasi belum tersedia. Database produksi/Hostinger tidak terhubung dalam sesi ini. Karena itu belum dapat dipastikan apakah master TA 601 produksi sudah ada; master baru hanya dibuat pada fixture pengujian. Jalankan command di bawah pada deployment untuk menerapkan keputusan ke data aktual dan memperoleh audit aktual.

## Deploy Hostinger

Setelah perubahan ini di-commit dan tersedia pada branch main:

```sh
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan ipk-cpl:audit-mapping
php artisan ipk-cpl:apply-overrides
php artisan ipk-cpl:audit-mapping
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Tidak perlu `migrate` untuk perubahan ini. Tidak perlu import ulang workbook apabila 146 mapping sudah tersedia. Command khusus di atas membatasi perubahan pada keputusan manual yang diminta. Jangan menjalankan reset database, drop table, seeder, atau migrasi destructive.

Jika target/master/data wajib tidak tersedia, command menampilkan alasan dan exit code gagal; mapping lain yang berhasil diperbaiki tetap dilaporkan. Baca audit aktual, lalu verifikasi halaman dan unduhan PDF dengan filter tahun akademik yang sama. Keberadaan nilai pada master target menentukan IPK; membuat master TA 601 tidak menciptakan nilai mahasiswa.
