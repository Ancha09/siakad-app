# Pembayaran mahasiswa — Midtrans Sandbox

Fitur pembayaran menggunakan Midtrans Snap sebagai gateway utama dan hanya berjalan di Sandbox. Semua tagihan/transaksi dibuat dengan `is_test=true`; PDF dan Excel diberi label simulasi. Pembayaran tidak memengaruhi KRS, nilai, atau data akademik.

## Komponen

- `routes/payments.php`: route admin, mahasiswa, bukti PDF, dan callback publik.
- `config/payments.php`: konfigurasi Midtrans tanpa secret hardcoded.
- `database/migrations/2026_09_29_000000_create_student_billing_tables.php`: tabel tagihan, transaksi, early access, audit, dan log callback.
- `app/Services/MidtransPaymentService.php`: Snap checkout, pembatasan Sandbox, signature callback, status, dan idempotensi.
- `app/Contracts/PaymentGateway.php` dan binding di `AppServiceProvider`: kontrak provider agar gateway dapat diganti tanpa mengubah controller tagihan.
- `app/Services/StudentBillingService.php`: ledger, cicilan, biaya layanan, koreksi manual, dan audit.
- `app/Services/PaymentSpreadsheetService.php`: preview/import dan export XLSX yang aman.
- `app/Http/Controllers/Admin/PaymentController.php`, `Admin/PaymentImportController.php`, `Mahasiswa/PaymentController.php`, `PaymentReceiptController.php`, dan `MidtransCallbackController.php`.
- `resources/views/admin/pembayaran`, `resources/views/mahasiswa/pembayaran`, serta `resources/views/payments`.

Admin mengelola tagihan melalui menu **Tagihan & Pembayaran**. Mahasiswa melihat tagihannya melalui **Pembayaran (Uji Coba)**. Dosen tidak memiliki akses. Gateway hanya tampil bagi mahasiswa aktif yang dibuka dari halaman **Mahasiswa Early Access**.

## Biaya dan cicilan

Default biaya layanan VA adalah Rp4.000 per transaksi dan dapat diubah admin pada setiap tagihan. Mahasiswa memasukkan nominal pokok cicilan; total yang dikirim ke Midtrans adalah pokok cicilan ditambah biaya layanan. Karena tiap cicilan merupakan transaksi Snap terpisah, setiap cicilan memperoleh biaya layanan tersendiri. Rincian `nominal_pokok`, `biaya_layanan`, dan `amount` disimpan terpisah.

Checkout mahasiswa dibatasi ke Virtual Account BNI, Mandiri, BRI, BCA, atau Permata. BNI menjadi pilihan awal. Payload Snap hanya berisi satu kode VA yang dipilih dan tidak membuka QRIS, kartu, e-wallet, retail outlet, atau paylater. Nominal pokok tagihan dan setiap pembayaran VA minimal Rp600.000.

Admin dapat mencatat pembayaran cash/manual dari detail tagihan. Nominal, tanggal, nomor kwitansi, dan catatan wajib diisi; bukti JPG/JPEG/PNG/PDF maksimal 2 MB bersifat opsional dan disimpan pada disk `local` (privat). Mahasiswa hanya dapat mengunduh bukti miliknya melalui route terautentikasi. Koreksi cash mengubah status transaksi menjadi `void` dan mempertahankan ledger serta audit.

Contoh tagihan pokok Rp100.000, biaya Rp4.000, dua cicilan pokok Rp50.000:

- transaksi pertama: Rp50.000 + Rp4.000 = Rp54.000;
- transaksi kedua: Rp50.000 + Rp4.000 = Rp54.000;
- total dibayar setelah lunas: Rp108.000.

## Konfigurasi Sandbox

Ambil Server Key, Client Key, dan Merchant ID dari dashboard Midtrans dalam mode Sandbox. Isi hanya pada `.env` lokal/server; jangan commit key.

```dotenv
MIDTRANS_ENABLED=false
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-ISI_KEY_SANDBOX
MIDTRANS_CLIENT_KEY=SB-Mid-client-ISI_KEY_SANDBOX
MIDTRANS_MERCHANT_ID=ISI_MERCHANT_ID_SANDBOX
MIDTRANS_DEFAULT_SERVICE_FEE=4000
```

`MIDTRANS_ENABLED` sengaja default `false`. Aktifkan menjadi `true` setelah semua key Sandbox terisi. Implementasi ini sengaja menolak production dan hanya memanggil endpoint `app.sandbox.midtrans.com`.

Di dashboard Midtrans Sandbox, pasang Payment Notification URL:

```text
https://domain-aplikasi/midtrans/callback
```

Untuk localhost, gunakan tunnel HTTPS seperti ngrok atau Cloudflare Tunnel, lalu pasang URL tunnel ditambah `/midtrans/callback`. Gunakan URL tunnel sebagai `APP_URL` agar finish redirect kembali ke aplikasi yang sama.

Setelah mengubah konfigurasi:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:list --path=midtrans -v
```

Referensi resmi:

- https://docs.midtrans.com/reference/create-checkout-token-api
- https://docs.midtrans.com/docs/https-notification-webhooks
- https://docs.midtrans.com/reference/handle-notifications
- https://docs.midtrans.com/docs/snap-advanced-feature

## Keamanan callback

- Hanya `POST /midtrans/callback` yang dikecualikan dari CSRF. Route tidak memakai auth karena dipanggil server Midtrans.
- Signature dihitung dengan `SHA512(order_id + status_code + gross_amount + ServerKey)` dan dibandingkan secara konstan.
- Callback dicocokkan dengan order ID, nominal bruto, mata uang, merchant ID jika dikonfigurasi, status transaksi, dan fraud status.
- `settlement` atau `capture` dengan fraud status `accept` menandai pembayaran berhasil. `pending` tetap menunggu. `expire` kedaluwarsa. `cancel`, `deny`, dan `failure` gagal.
- Callback berulang idempotent dan tidak menambah pembayaran dua kali.
- Redirect browser tidak pernah mengubah status pembayaran.
- Log callback hanya menyimpan allowlist field untuk audit. Signature, key, email, nomor kartu, PIN, CVV, password bank, nomor VA, dan payload bebas tidak disimpan.
- Bila pembuatan transaksi timeout, order menjadi `unknown` dan order baru diblokir. Periksa order ID pada dashboard Midtrans; POST tidak diulang secara buta.

## Import dan export

Import memakai XLSX, sheet pertama, maksimal 500 baris dan 5 MB. Kolom:

`nim, nama, jenis_tagihan, deskripsi, nominal_pokok, biaya_layanan, jatuh_tempo, boleh_cicil`

Nama dan jatuh tempo boleh kosong. Biaya layanan kosong memakai default Rp4.000. Formula ditolak. Preview harus bebas error sebelum disimpan dan tagihan identik memerlukan konfirmasi. PDF/Excel laporan memisahkan pokok, biaya, total, dibayar, dan sisa.

## Uji lokal

```powershell
php artisan migrate --path=database/migrations/2026_09_29_000000_create_student_billing_tables.php
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/StudentBillingTest.php --compact
php artisan route:list --path=pembayaran -v
php artisan route:list --path=midtrans -v
```

Uji satu mahasiswa early access: buat tagihan, buat transaksi penuh/cicilan, bayar VA melalui simulator Sandbox Midtrans, pastikan callback mengubah status, kirim ulang callback dan pastikan nominal tidak bertambah, lalu unduh bukti PDF dan laporan.

## Deploy Hostinger untuk UAT Sandbox

Backup database lebih dahulu. Setelah kode direview dan key Sandbox dimasukkan langsung ke `.env` Hostinger:

```bash
cd /home/u258688689/domains/sttmi.my.id/public_html/siakad
git pull origin main
php artisan migrate:status
php artisan migrate --path=database/migrations/2026_09_29_000000_create_student_billing_tables.php --force
php artisan optimize:clear
php artisan config:cache
```

Mulai dari satu akun internal. Jangan memakai Live Server Key atau mengubah `MIDTRANS_IS_PRODUCTION=true` pada tahap UAT ini.
