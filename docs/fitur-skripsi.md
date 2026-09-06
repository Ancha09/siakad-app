# Pengajuan judul dan pembimbing skripsi

Implementasi menggunakan Laravel 13.18.1, autentikasi yang sudah ada, role `admin` / `dosen` / `mahasiswa`, dan relasi ke `users`, `mahasiswas`, `dosens`, serta `prodis`. Dosen wali dan halaman bimbingan akademik lama tetap terpisah dari pembimbing skripsi.

## Pemasangan

Jalankan MySQL project melalui Laragon, lalu dari root project:

```powershell
php artisan migrate --path=database/migrations/2026_09_05_000000_create_skripsi_tables.php
```

Migration ini menambah `dosens.skripsi_aktif` serta tabel `periode_skripsis`, `pengajuan_skripsis`, dan `riwayat_skripsis`. Tidak ada seeder, penghapusan data, perubahan `.env`, atau build aset yang diperlukan. Jika status migration sudah `Ran`, tidak perlu menjalankannya kembali. Perubahan syarat minimal semester 7 menggunakan kolom `mahasiswas.semester` yang sudah ada dan tidak memerlukan migration tambahan.

Perintah dibatasi ke migration baru karena riwayat migration lama memiliki duplikasi kolom mahasiswa. Pada database aplikasi yang sudah digunakan, tabel akademik prasyarat harus sudah tersedia. Instalasi database dari nol melalui seluruh migration lama masih membutuhkan perbaikan tersendiri.

## Mencoba alur

1. Login **admin**, buka menu **Pembimbing Skripsi** (`/admin/skripsi`). Tambahkan nama periode, waktu mulai, dan waktu berakhir. Aktifkan dosen pembimbing yang berhak pada tabel **Beban dan kelayakan dosen**.
2. Login **mahasiswa**, buka **Pengajuan Skripsi** (`/mahasiswa/skripsi`), pilih periode, isi judul, pilih dosen, lalu kirim. Status awal **Menunggu**, sehingga belum ada pembimbing resmi.
3. Login **dosen** tujuan, buka **Pengajuan Bimbingan** (`/dosen/skripsi`). Buka tindakan **Berikan keputusan** dan terima atau tolak dengan alasan. Penerimaan langsung menampilkan mahasiswa dalam daftar **Mahasiswa bimbingan resmi**.
4. Setelah ditolak, mahasiswa dapat mengirim pengajuan baru kepada dosen lain. Judul baru boleh berbeda; judul dan alasan pada pengajuan lama tetap tersimpan.
5. Login admin untuk mencari atau memfilter mahasiswa. Filter **Belum mendapat pembimbing** mencakup mahasiswa yang belum mengajukan, masih menunggu, dan ditolak. Dari pengajuan terakhir yang menunggu/ditolak, pilih **Alihkan ke dosen lain**, isi alasan, dan konfirmasi.
6. Dosen pengganti harus menerima pengajuan baru. Pengajuan menunggu yang lama menjadi **Dialihkan**; penolakan lama dipertahankan. Dosen lama tidak bisa memproses pengajuan tersebut lagi.
7. Gunakan **Lihat riwayat** untuk audit pengajuan. Perubahan periode dan kelayakan dosen tersedia di bagian bawah halaman admin. Periode lama dapat dipilih kembali untuk membaca riwayatnya.

Semua tindakan pengajuan, keputusan, dan pengalihan dibatasi oleh rentang waktu yang sama di backend. Di luar rentang itu, halaman tetap dapat dibaca. Status menunggu tidak diubah otomatis ketika periode berakhir. Admin dapat memperpanjang periode, dan perubahan tenggat dicatat.

## Keputusan desain

- **Timezone:** mengikuti `config('app.timezone')`. Konfigurasi project saat implementasi adalah `UTC`; label waktu menampilkan timezone tersebut. Tidak ada konversi diam-diam ke WIB dan tidak ada perubahan timezone global.
- **Kelayakan dosen:** belum ada status aktif/aturan pembimbing di skema lama. Flag baru `skripsi_aktif` bernilai **false** pada awalnya; admin mengaktifkan dosen yang berhak. Dosen harus mempunyai prodi dan akun dengan role `dosen`. Tidak diterapkan pembatasan satu prodi yang tidak ada di aturan project.
- **Kelayakan mahasiswa:** harus mempunyai profil yang terhubung ke akun role `mahasiswa`, prodi, dan semester **minimal 7**. Semester 7, 8, dan seterusnya diperbolehkan. Semester kosong atau di bawah 7 tidak dapat mengajukan, mengajukan ulang, diproses dosen, atau dialihkan admin. Pemeriksaan memakai semester yang tersimpan pada profil mahasiswa, bukan nilai yang dikirim melalui form. Daftar/ringkasan mahasiswa skripsi admin dibatasi semester 7 ke atas; riwayat pengajuan lama tetap dapat dibaca. Data semester yang keliru dapat diperbaiki melalui menu data mahasiswa. Tidak ditambahkan syarat SKS lain.
- **Satu pembimbing per periode:** pengajuan **Diterima** menjadi sumber resmi pembimbing. Contoh akses: `$mahasiswa->pembimbingSkripsi()->where('periode_skripsi_id', $periodeId)->first()?->dosen`. Tidak ada penetapan pembimbing dari pilihan mahasiswa sebelum persetujuan.
- **Request bersamaan:** transaksi memperoleh lock periode melalui penambahan `lock_version` sebelum membaca status dan tenggat; profil/pengajuan juga dikunci. Unique index `(periode_skripsi_id, mahasiswa_aktif)` memakai kolom yang dihitung database dari status **Menunggu/Diterima**, sehingga satu mahasiswa tidak dapat mempunyai dua pengajuan aktif pada periode yang sama. Lima percobaan transaksi tersedia untuk benturan lock/deadlock.
- **Riwayat:** setiap pengajuan menyimpan judul, pembuat dan jenis pembuat, periode, tujuan, status, keputusan, dan tautan ke pengajuan sebelumnya. Audit menyimpan identitas, nama dan role pelaku saat kejadian, waktu, serta perubahan. Foreign key membatasi penghapusan data yang telah menjadi bagian riwayat.
- **Pengalihan:** hanya pengajuan terakhir yang belum diterima. Mahasiswa yang belum mengajukan tetap terlihat oleh admin, tetapi harus mengirim judul dahulu agar ada pengajuan yang dapat dialihkan. Pergantian pembimbing yang sudah diterima tidak disediakan.
- **Beban dosen:** menampilkan jumlah diterima dan menunggu pada periode terpilih. Tidak ada kuota atau pencocokan keahlian otomatis. Menonaktifkan dosen tidak membatalkan pembimbing yang sudah disetujui; pengajuan menunggu bisa dialihkan admin.
- **Beberapa periode:** masing-masing dipilih secara eksplisit dan mempunyai batas satu pembimbing sendiri. Saat membuka halaman, periode yang sedang terbuka diprioritaskan. Riwayat tetap terpisah per periode.

## Pengujian

Hasil setelah penambahan syarat semester: **35 test / 354 assertion lulus** pada suite skripsi, termasuk batas semester kosong/1/6/7/8/9/14, filter admin, pencegahan pelanggaran melalui pengajuan ulang/keputusan/pengalihan, dan empat skenario dua proses bersamaan. Pengujian integrasi MySQL **8.0.30** sebelumnya juga lulus untuk migration naik/turun dan alur utama. Pint lulus untuk file PHP yang diperiksa. Tampilan ketiga role diuji melalui respons HTTP Laravel; belum ada pemeriksaan visual melalui browser.

Suite fitur khusus menggunakan database SQLite di memori, ditambah snapshot SQLite sementara untuk pengujian dua proses bersamaan. Pengujian memuat migration prasyarat asli yang diperlukan dan migration baru; tidak memakai database `.env` atau menjalankan `migrate:fresh`.

PHP Laragon pada lingkungan implementasi memiliki DLL SQLite tetapi belum mengaktifkannya secara default. Jalankan:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit -c phpunit.skripsi.xml
```

Jika ekstensi SQLite sudah aktif di PHP, flag `-d extension=...` tidak diperlukan pada perintah utama. Worker concurrency mengaktifkan `pdo_sqlite` untuk prosesnya sendiri.

Suite menguji pengajuan, validasi kelayakan, judul/alasan wajib, isolasi akun dan role, penerimaan, penolakan, pengajuan ulang, pencarian/filter/ringkasan admin, pengalihan menunggu maupun ditolak, penolakan tindakan lama, tenggat sebelum/sesudah periode, perpanjangan, timezone, batas unik database, HTML escaping, halaman kosong, pemisahan periode, serta snapshot pelaku. Empat skenario concurrency menjalankan dua proses PHP yang bersaing: pengajuan, penerimaan, pengalihan, dan penerimaan melawan pengalihan.

Pengujian integrasi MySQL opsional pada Windows/Laragon:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File tests/Skripsi/run-mysql-smoke.ps1 -MysqlBin 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin'
```

Sesuaikan `MysqlBin` dengan folder `bin` MySQL. Script membuat server sementara pada `127.0.0.1:33316`, memverifikasi datadir berada pada folder pengujian yang dibuatnya, lalu menguji migration naik/turun, pengaturan kelayakan/periode, pengajuan, penolakan, pengalihan, penerimaan, unique constraint, dan audit. Server serta datanya dibersihkan setelah pengujian. Script tidak menggunakan database MySQL aplikasi. Concurrency diuji di SQLite; pengujian MySQL ini adalah pengujian integrasi berurutan.

Test bawaan yang dicoba dengan `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/pestphp/pest/bin/pest --filter 'returns a successful response'` gagal sebelum menjalankan assertion karena migration `2026_08_07_170017_add_fields_to_mahasiswas_table.php` menduplikasi kolom `nim`. Masalah itu tidak diubah dalam fitur ini; suite bawaan tidak dinyatakan lulus.

## Daftar file

File baru:

- `database/migrations/2026_09_05_000000_create_skripsi_tables.php`
- `app/Models/PeriodeSkripsi.php`
- `app/Models/PengajuanSkripsi.php`
- `app/Models/RiwayatSkripsi.php`
- `app/Services/SkripsiService.php`
- `app/Policies/PengajuanSkripsiPolicy.php`
- `app/Http/Middleware/SkripsiRole.php`
- `app/Http/Controllers/SkripsiController.php`
- `routes/skripsi.php`
- `resources/views/skripsi/admin.blade.php`
- `resources/views/skripsi/mahasiswa.blade.php`
- `resources/views/skripsi/dosen.blade.php`
- `resources/views/skripsi/show.blade.php`
- `resources/views/skripsi/partials/style.blade.php`
- `resources/views/skripsi/partials/period.blade.php`
- `resources/views/skripsi/partials/submissions.blade.php`
- `resources/views/skripsi/partials/audits.blade.php`
- `resources/views/skripsi/partials/pagination.blade.php`
- `phpunit.skripsi.xml`
- `tests/Skripsi/SkripsiTest.php`
- `tests/Skripsi/concurrency-worker.php`
- `tests/Skripsi/mysql-smoke.php`
- `tests/Skripsi/run-mysql-smoke.ps1`
- `docs/fitur-skripsi.md`

File yang diubah:

- `app/Models/Mahasiswa.php`: relasi pengajuan dan pembimbing resmi per periode.
- `app/Models/Dosen.php`: relasi pengajuan dan scope pembimbing aktif.
- `routes/web.php`: memuat route fitur skripsi.
- `resources/views/layouts/admin.blade.php`: menu Pembimbing Skripsi.
- `resources/views/layouts/mahasiswa.blade.php`: menu Pengajuan Skripsi.
- `resources/views/layouts/dosen.blade.php`: menu Pengajuan Bimbingan.

File laporan akademik yang sedang dibuka tidak diubah.
