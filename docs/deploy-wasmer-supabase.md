# Deploy uji coba SIAKAD ke Wasmer + Supabase

Disiapkan 6 September 2026. `.env` dan database Laragon tidak diganti.
Belum ada deploy ke akun Wasmer maupun koneksi ke project Supabase nyata.

## Hasil pemeriksaan project

| Bagian | Hasil |
| --- | --- |
| Backend | Laravel 13, Blade, login Laravel dengan role admin/dosen/mahasiswa |
| PHP | Target Composer PHP 8.3; lockfile diselesaikan ulang agar tidak membutuhkan Symfony 8/PHP 8.4.1 |
| Frontend | Vite 8, Alpine, Tailwind 3 melalui PostCSS; `npm ci` dan `npm run build` berhasil |
| Manifest | `public/build/manifest.json`, entry CSS dan JS beserta berkas aset tersedia |
| Database lokal | `.env` mengandung DB_CONNECTION dua kali; nilai terakhir MySQL. File tidak diubah |
| PostgreSQL | Semua migration diuji pada PostgreSQL 14.5 sementara, termasuk rollback |
| Pengujian | Tes default 33/33 (termasuk integrasi dan command admin) serta skripsi 35/35 lolos; smoke PostgreSQL menguji alur layanan dan constraint |
| Git | Folder awal belum memiliki `.git`; inisialisasi dan push dilakukan dengan langkah di bawah |
| File unggahan | Presensi memakai disk `public`; silabus dan penelitian memakai disk `local` |

Perubahan Composer juga memperbarui sejumlah dependency yang diizinkan composer.json,
termasuk Laravel 13.18.1 ke 13.30.1. Gunakan lockfile yang disertakan, bukan
`composer update` setiap deploy.

## 1. Batas gratis dan pemeriksaan runtime

[Wasmer Hobby](https://wasmer.io/pricing) menyediakan paket gratis dengan kuota;
SSH/SFTP tercantum pada paket berbayar. Karena itu panduan ini menjalankan
migration dari Laragon. Periksa kuota di dashboard sebelum mengaktifkan layanan.
[Supabase Free](https://supabase.com/pricing) mencantumkan database 500 MB dan
project dapat dipause setelah satu minggu tidak aktif.

Wasmer mendeteksi Laravel dan memasang Composer. Supabase di sini merupakan
database eksternal. **Dukungan Laravel tidak otomatis membuktikan driver
PostgreSQL tersedia.** Lihat [runtime dan framework Wasmer](https://docs.wasmer.io/edge/learn/supported-frameworks-and-languages/).

Sebelum memakai data uji, periksa runtime PHP Wasmer yang dipilih:

```sh
# Setelah memasang Wasmer CLI sesuai dokumentasi resminya:
wasmer run php/php -- -v
wasmer run php/php -- -m
```

Perintah tersebut memeriksa paket `php/php`; pastikan paket/versinya sama dengan
runtime deployment. Jika dashboard menggunakan paket lain, periksa paket itu.
Harus tersedia PHP >=8.3, `pdo_pgsql`, OpenSSL, Mbstring, Fileinfo, DOM, Session,
Tokenizer, dan Filter. Pemeriksaan lokal dengan PHP Laragon tidak membuktikan
runtime Wasmer sudah mendukungnya. `scripts/check-deploy.php` dapat dijalankan
dengan PHP runtime saat tersedia akses CLI/remote session.

Jika `pdo_pgsql` tidak tersedia, kombinasi runtime tersebut dengan Supabase
belum bisa dipakai. Pilih runtime Wasmer yang menyertakannya atau minta dukungan
Wasmer memastikan paket yang sesuai. Mengisi `DB_CONNECTION=pgsql`, memasang
Composer package, atau menyalin DLL Windows tidak menambahkan driver ke PHP Wasmer.
Koneksi TLS keluar ke host pooler port 5432 juga harus berhasil.

## 2. Persiapan Laragon dan build

Jalankan dari PowerShell di root project:

```powershell
php --version
node --version
npm.cmd --version
composer.bat install
composer.bat validate --no-check-publish
composer.bat check-platform-reqs --no-dev
npm.cmd ci
npm.cmd run build
Test-Path public/build/manifest.json
php -d extension=pdo_pgsql scripts/check-deploy.php
```

Gunakan Node 22.12+ atau 24 (Vite juga menerima Node 20.19+). `.npmrc` tetap
memakai `ignore-scripts=true`; instalasi bersih dan build berhasil dengan setting
ini. `public/build` merupakan hasil build, sedangkan `public/hot` penanda dev
server dan harus tidak ada dalam deployment. Hentikan `npm run dev` sebelum
pengujian build production jika file `public/hot` masih ada.

PHP lokal saat audit memiliki DLL `pdo_pgsql` tetapi belum mengaktifkannya di
php.ini. Opsi `-d extension=pdo_pgsql` hanya mengaktifkan driver untuk satu
perintah, tanpa mengubah instalasi Laragon. Jika sudah aktif di php.ini, hilangkan
opsi `-d` agar tidak muncul peringatan extension dimuat dua kali.

## 3. Siapkan Supabase

1. Buat project Free untuk uji coba dan simpan database password.
2. Buka **Connect**, pilih **Session pooler**, port **5432**. Salin host dan
   username persis dari dashboard; biasanya username `postgres.PROJECT_REF`.
3. Buat schema aplikasi di SQL Editor Supabase:

```sql
CREATE SCHEMA IF NOT EXISTS siakad AUTHORIZATION postgres;
REVOKE ALL ON SCHEMA siakad FROM PUBLIC, anon, authenticated;
```

Jangan menambahkan `siakad` ke daftar exposed schemas Data API. Laravel mengakses
database melalui PDO. Login tetap memakai tabel `siakad.users` dan autentikasi
Laravel; tidak perlu memindahkannya ke Supabase Auth atau memakai anon/service-role
API key sebagai password database. Schema terpisah mengikuti
[panduan resmi Supabase untuk Laravel](https://supabase.com/docs/guides/getting-started/quickstarts/laravel).

Gunakan TLS `DB_SSLMODE=require`. Session pooler cocok untuk akses IPv4 dan
prepared statements Laravel. Hindari transaction pooler port 6543 untuk
konfigurasi ini. Direct connection dapat dipakai jika jaringan mendukung endpoint
IPv6-nya. Rujukan: [koneksi PostgreSQL Supabase](https://supabase.com/docs/guides/database/connecting-to-postgres).

## 4. Environment terpisah, migration, dan admin pertama

```powershell
if (!(Test-Path .env.wasmer)) { Copy-Item .env.wasmer.example .env.wasmer }
```

Edit `.env.wasmer`, bukan `.env`:

- Isi DB_HOST, DB_USERNAME, DB_PASSWORD sesuai Session pooler; DB_DATABASE=postgres.
- Pertahankan DB_CONNECTION=pgsql, DB_PORT=5432, DB_CHARSET=utf8,
  DB_SCHEMA=siakad, DB_SSLMODE=require. Jangan membawa utf8mb4/collation MySQL.
- DB_URL kosong jika memakai field terpisah. Jika menggunakan URL, encode karakter
  khusus password dan pastikan tidak ada URL lama yang menimpa field koneksi.
- Isi APP_URL dengan URL HTTPS Wasmer setelah diketahui; APP_DEBUG=false,
  APP_ENV=production, APP_FORCE_HTTPS=true.
- SESSION_DRIVER=database, CACHE_STORE=database, QUEUE_CONNECTION=sync,
  SESSION_SECURE_COOKIE=true, SESSION_ENCRYPT=true, LOG_CHANNEL=stderr.
- Jangan memakai `VITE_` untuk rahasia; nilainya bisa masuk JavaScript browser.

Pastikan file `.env.wasmer` benar-benar ada dan sudah diisi sebelum menjalankan
command berikut. Cache konfigurasi lokal dibersihkan supaya `--env` dibaca:

```powershell
php artisan config:clear
# Hanya sekali untuk deployment baru dengan APP_KEY kosong:
php artisan key:generate --env=wasmer --force

# Setelah schema siakad dibuat dan kredensial sudah benar:
php -d extension=pdo_pgsql artisan migrate --env=wasmer --force
php -d extension=pdo_pgsql artisan migrate:status --env=wasmer
php -d extension=pdo_pgsql artisan deploy:admin --env=wasmer
```

Command `deploy:admin` meminta login/email/password tanpa mencetak password,
menolak akun yang sudah ada, dan tidak mereset password admin lama. Seeder
AdminSeeder kini menolak environment production karena memiliki password demo
tetap. Jangan memakai `--seed` atau `composer run setup` untuk deployment ini.

Simpan APP_KEY dari `.env.wasmer` sebagai secret Wasmer dan gunakan nilai yang
sama setiap redeploy. Jangan menjalankan `key:generate` setiap build. Perubahan
key dapat membatalkan cookie/session terenkripsi dan memutus akses data terenkripsi.

`migrate` membuat struktur, **tidak menyalin data MySQL lokal**. Mulai uji coba
dengan data baru melalui admin. Migration bukan backup dan tidak menggantikan
proses transfer data jika data lama diperlukan. Jangan menjalankan `migrate:fresh`,
`db:wipe`, atau `migrate:refresh` terhadap database berisi data.

## 5. GitHub

Buat repository GitHub kosong (disarankan private untuk project kampus). Jangan
menyertakan data mahasiswa/dosen nyata, dump database, atau unggahan lokal.
`.gitignore` mengabaikan `.env.*` kecuali template `.example`, database SQLite,
dump di folder database, dependency, cache, dan file runtime.

```powershell
git init
git branch -M main
Copy-Item app.yaml.example app.yaml
# Edit owner dan name di app.yaml sesuai akun Wasmer Anda sebelum melanjutkan.
git add .
git status --short
git diff --cached --stat
git diff --cached
```

Periksa staged files. Pastikan `.env`, `.env.wasmer`, database nyata, `vendor`,
`node_modules`, `public/hot`, dan file unggahan tidak masuk. File build Vite
diabaikan secara default. Untuk uji coba pertama, sertakan hasil build secara
eksplisit agar tidak bergantung pada dukungan npm builder Laravel Wasmer:

```powershell
npm.cmd run build
git add -f public/build
git status --short
git commit -m "Prepare Wasmer and Supabase deployment"
git remote add origin https://github.com/USERNAME/REPOSITORY.git
git push -u origin main
```

Ganti USERNAME/REPOSITORY. Setelah aset pernah di-track, setiap perubahan frontend
harus dibuild ulang lalu `git add -A public/build` sebelum commit. Jika kelak
builder menjalankan npm secara konsisten, aset dapat dikelola sebagai hasil build
di pipeline; jangan sampai manifest hilang dari paket yang dilayani.

## 6. Import ke Wasmer

1. Pilih paket Hobby, import repository GitHub, dan beri Wasmer akses hanya ke
   repository yang diperlukan. Pilih branch `main` serta root project `/`.
2. Pastikan framework **Laravel/PHP**, document root **public** atau entry
   **public/index.php**. Jangan memilih preset Vite static site: aplikasi ini
   tetap membutuhkan PHP untuk login, dashboard, dan semua fitur skripsi.
3. Periksa runtime sesuai langkah 1. Jika tersedia pengaturan build Linux dengan
   PHP, Composer, Node, dan pdo_pgsql, gunakan `sh scripts/build-wasmer.sh`.
   Jika tidak, biarkan instalasi Composer Laravel otomatis dan pakai aset build
   yang sudah di-commit. Periksa log bahwa `vendor` dan manifest ikut dipaketkan.
4. Jika diminta start command, gunakan konfigurasi PHP Laravel bawaan. Bentuk
   server yang didokumentasikan starter Wasmer adalah
   `php -S 0.0.0.0:$PORT -t public`; gunakan port dari platform (umumnya 8080),
   jangan hardcode port Laragon. Pastikan route `/login` dan `/admin/skripsi`
   diteruskan ke front controller.
5. Masukkan nilai `.env.wasmer` ke environment/secrets Wasmer. APP_KEY dan
   DB_PASSWORD/DB_URL adalah secrets. Jangan mengunggah file `.env.wasmer`.
   Pastikan semua DB_* menunjuk Supabase dan tidak tertimpa konfigurasi database
   MySQL bawaan Wasmer. Jangan menambahkan capability database MySQL ke app.yaml.
6. Template app.yaml mengaktifkan HTTPS dan single_concurrency untuk PHP,
   serta volume `/app/storage/app` untuk unggahan. Periksa path aplikasi pada
   image Wasmer benar `/app` dan volume tersedia pada paket yang dipilih.
7. Deploy, ambil URL final, sesuaikan APP_URL jika perlu, lalu redeploy.

[Integrasi Git Wasmer](https://docs.wasmer.io/edge/git/) menjalankan deployment
dari branch terhubung dan menggabungkan konfigurasi app.yaml. Field app.yaml
mengikuti [referensi konfigurasi](https://docs.wasmer.io/edge/configuration/).
`app.yaml.example` adalah template yang harus diisi; tidak memuat identitas akun
atau secret. `BUILD.md` adalah petunjuk, bukan hook otomatis yang dijamin platform.

## 7. Penyimpanan, cache, dan email

- Session dan cache memakai PostgreSQL sehingga tidak bergantung pada filesystem
  sementara. Queue sync tidak membutuhkan worker permanen untuk uji coba ini.
- Semua berkas unggahan aplikasi saat ini memakai `storage/app` dengan disk
  `local`/`public` yang dipanggil eksplisit. Mengubah FILESYSTEM_DISK saja tidak
  memindahkannya ke Supabase Storage. Volume Wasmer mempertahankan berkas lintas
  restart; lihat [panduan volume Wasmer](https://docs.wasmer.io/edge/guides/volumes/).
- Presensi publik membutuhkan `public/storage` yang menunjuk `storage/app/public`.
  Build script membuat link relatif. Untuk jalur builder otomatis, pastikan link
  tersebut dibuat/dipaketkan oleh builder. Build script memakai `ln -s`
  pada Linux tanpa membutuhkan dependency Symfony Filesystem tambahan.
  Jangan membuat link Laragon absolut ke drive C:
  di paket Linux. Tes unggah, unduh, dan keberadaan file setelah redeploy.
- `storage/framework/views` dan `bootstrap/cache` harus writable. Jangan mengunggah
  cache konfigurasi/route hasil Laragon: dapat berisi kredensial dan path Windows.
  Untuk uji coba, gunakan tanpa `config:cache`. Cache konfigurasi hanya boleh
  dibuat di runtime final setelah semua secrets tersedia dan dibuat ulang ketika
  environment berubah.
- PASSWORD_RESET_ENABLED=false sampai pengiriman email benar-benar tersedia dan
  diuji. MAIL_MAILER=log tidak mengirim email; jangan mengandalkannya untuk reset.

## 8. Audit PostgreSQL dan data lama

| Temuan | Penanganan |
| --- | --- |
| `FIELD(hari, ...)` di jadwal admin dan KRS mahasiswa | Diganti CASE dengan urutan Senin–Sabtu |
| Migration ALTER/MODIFY ENUM khusus MySQL | Migration tambahan PostgreSQL memperbarui krs_status_check dan default Menunggu |
| LIKE PostgreSQL membedakan huruf besar/kecil | Pencarian controller memakai whereLike/orWhereLike Laravel |
| enum() Laravel | Menjadi varchar + CHECK pada PostgreSQL; semua migration forward diuji |
| Generated mahasiswa_aktif + unique per periode | Berhasil pada PostgreSQL 14.5; satu pengajuan aktif, penolakan historis tetap boleh banyak |
| unsigned/year/after | Diterjemahkan grammar Laravel; PostgreSQL tidak menerapkan unsigned atau urutan kolom after seperti MySQL |
| Transaksi, lockForUpdate, JSON riwayat | Alur layanan diuji pada PostgreSQL; tes proses konkuren yang ada tetap berbasis SQLite |
| Rollback status KRS | Menolak penyempitan constraint jika masih ada baris Menunggu, tanpa mengubah data diam-diam |

Jika perlu menyalin data lokal, backup terlebih dahulu dan lakukan transfer terpisah
ke project/schema uji kosong. Jangan langsung mengimpor dump SQL MySQL ke PostgreSQL.
Gunakan ekspor/impor CSV atau ETL per tabel dengan urutan parent sebelum child.
Pertahankan ID, foreign key, hash password, isi judul, status, dan JSON riwayat;
konversi boolean 0/1 ke bentuk PostgreSQL, serta tanggal tidak valid ke nilai yang
sesuai setelah ditinjau. Jangan impor kolom generated `mahasiswa_aktif`.
Tabel migrations berasal dari Laravel migrate; session/cache/jobs tidak diperlukan
untuk menyalin riwayat akademik. Jangan menimpa schema `auth`/`storage` Supabase.

Setelah memasukkan ID eksplisit, reset sequence untuk setiap tabel ber-ID. Contoh:

```sql
SELECT setval(
  pg_get_serial_sequence('siakad.users', 'id'),
  COALESCE((SELECT MAX(id) FROM siakad.users), 1),
  EXISTS (SELECT 1 FROM siakad.users)
);
```

Bandingkan jumlah record, relasi, login, riwayat penolakan/pengalihan, serta satu
pembimbing aktif per mahasiswa/periode sebelum menggunakan hasil transfer.
Audit ini belum memeriksa isi database MySQL lokal atau memindahkan data tersebut.

## 9. Checklist setelah deploy

- [ ] PHP Wasmer >=8.3 dan pdo_pgsql tersedia; koneksi TLS Supabase berhasil.
- [ ] APP_DEBUG=false, APP_URL HTTPS final, APP_KEY tetap dan rahasia.
- [ ] Semua migration berstatus Ran di schema siakad.
- [ ] `/up` merespons 200 (health Laravel ini tidak membuktikan koneksi database).
- [ ] `/login` memuat CSS/JS dari `/build/assets`, tanpa localhost:5173 atau mixed content.
- [ ] Login admin, dosen, mahasiswa berhasil; URL role lain menolak akses.
- [ ] Admin mengaktifkan dosen pembimbing dan mengatur periode mulai/berakhir.
- [ ] Mahasiswa semester >=7 memasukkan judul dan memilih dosen; pengajuan ganda ditolak.
- [ ] Dosen melihat nama mahasiswa/judul dan dapat menerima/menolak dengan alasan.
- [ ] Mahasiswa yang ditolak dapat memilih dosen lain; riwayat lama tetap terlihat admin.
- [ ] Admin dapat mengalihkan pengajuan terakhir berstatus Menunggu/Ditolak sebelum tenggat.
- [ ] Setelah tenggat, pengajuan/keputusan/pengalihan diblokir.
- [ ] KRS Menunggu, persetujuan/penolakan, urutan jadwal, dan pencarian nama diuji.
- [ ] Session tetap bekerja, dan unggahan tetap ada setelah restart/redeploy.
- [ ] Pantau kuota Wasmer/Supabase dan status pause project Supabase.

Catatan fitur: kode saat audit mencocokkan lewat **pengalihan pengajuan yang sudah
ada**, bukan penempatan langsung mahasiswa tanpa pengajuan. Admin juga tidak dapat
mengalihkan pengajuan yang sudah Diterima, walau periode masih terbuka. Alur ini
dipertahankan; bila kebutuhan adalah menempatkan mahasiswa tanpa pengajuan atau
mengedit pembimbing yang sudah diterima, itu membutuhkan perubahan fitur tersendiri.

## 10. Command pembaruan dan verifikasi

```powershell
# Lokal: ulangi setelah ada perubahan kode/frontend.
composer.bat install
npm.cmd ci
npm.cmd run build
php -d extension=pdo_pgsql scripts/check-deploy.php
php -d extension=pdo_sqlite vendor/bin/pest --configuration=phpunit.skripsi.xml
php -d extension=pdo_sqlite vendor/bin/pest --testsuite=Integration

# Hanya jika ada migration baru, gunakan kredensial Supabase di .env.wasmer.
php artisan config:clear
php -d extension=pdo_pgsql artisan migrate --env=wasmer --force

git add .
git add -A public/build
git diff --cached --stat
git commit -m "Update SIAKAD trial deployment"
git push origin main
```

Smoke PostgreSQL khusus pengembang: `php -d extension=pdo_pgsql
tests/Deployment/postgres-smoke.php`. Skrip hanya mengakses PostgreSQL lokal
127.0.0.1:55439, user siakad_test, database postgres; membuat dan menghapus schema
acak miliknya sendiri. Jangan mengubah targetnya menjadi Supabase/database kampus.
Server sementara saat audit dihentikan setelah pengujian.
