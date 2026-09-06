# Deployment SIAKAD STTMI

Untuk Wasmer + Supabase PostgreSQL, gunakan [panduan khusus](deploy-wasmer-supabase.md)
dan `.env.wasmer.example`. Panduan di bawah ini tetap untuk hosting MySQL/MariaDB.

## Kebutuhan server

- PHP 8.3 atau lebih baru dengan ekstensi PDO MySQL, Mbstring, OpenSSL, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo, GD, Intl, Curl, dan Zip.
- MySQL atau MariaDB yang mendukung foreign key dan generated column.
- Folder `storage` dan `bootstrap/cache` harus dapat ditulis oleh PHP.
- Document root domain harus mengarah ke folder `public`.

## Persiapan

1. Jalankan `composer install --no-dev --optimize-autoloader`.
2. Jalankan `npm ci` lalu `npm run build`.
3. Salin `.env.production.example` menjadi `.env` di server.
4. Isi `APP_URL`, data koneksi database, dan `APP_KEY` untuk server tersebut.
5. Jangan mengunggah file `public/hot`. File itu hanya untuk Vite development.

Jika hosting tidak menyediakan Composer atau Node.js, jalankan langkah 1 dan 2 di komputer lokal lalu sertakan folder `vendor` dan `public/build` dalam paket unggahan.

## Struktur web

Simpan aplikasi di luar folder publik dan arahkan domain ke `<folder-aplikasi>/public`. Jika hosting hanya menyediakan `public_html`, letakkan isi folder `public` di `public_html`, simpan bagian aplikasi lainnya di luar `public_html`, lalu sesuaikan dua path pada `public_html/index.php` menuju `vendor/autoload.php` dan `bootstrap/app.php` yang sebenarnya. Jangan menaruh `.env` di folder yang dapat diakses melalui web.

## Menyiapkan aplikasi

Jalankan perintah berikut dari folder aplikasi:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Jika hosting tidak mengizinkan symbolic link, unggahan presensi tidak dapat disajikan melalui disk publik. Gunakan hosting yang mendukung symbolic link atau pindahkan penyimpanan publik ke layanan object storage sebelum aplikasi dipakai.

## Pemeriksaan setelah unggah

1. Pastikan halaman login memakai HTTPS dan tidak menampilkan detail error.
2. Masuk dengan masing-masing akun admin, dosen, dan mahasiswa.
3. Pastikan setiap akun mendapat respons 403 ketika membuka URL milik role lain.
4. Uji unggah dan buka kembali foto serta materi presensi.
5. Uji periode KRS, pengajuan mahasiswa, persetujuan dosen, input nilai, KHS, pengumuman, dan laporan.
6. Biarkan `PASSWORD_RESET_ENABLED=false` sampai SMTP sudah dikonfigurasi dan berhasil diuji.
