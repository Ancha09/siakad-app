# Build Wasmer

Panduan lengkap: [docs/deploy-wasmer-supabase.md](docs/deploy-wasmer-supabase.md).

Gunakan deteksi framework Laravel, document root `public`, PHP 8.3+ dengan
`pdo_pgsql`, dan Node 22.12+ atau 24. Verifikasi extension pada runtime Wasmer,
bukan hanya PHP komputer lokal atau builder.

Build command untuk builder Linux yang menyediakan PHP, Composer dan Node:

```sh
sh scripts/build-wasmer.sh
```

`BUILD.md` ini dokumentasi, bukan jaminan Wasmer mengeksekusinya otomatis.
Masukkan command di pengaturan build jika tersedia. Jika builder Laravel
tidak menjalankan npm, panduan menjelaskan cara menyertakan `public/build`
dalam commit GitHub untuk uji coba.

Simpan APP_KEY dan kredensial DB sebagai environment/secrets Wasmer.
Jangan menjalankan migration, seeder, `composer run setup`, atau `config:cache`
saat build. Migration dijalankan dari Laragon dengan `.env.wasmer` terpisah.
