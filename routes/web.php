<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DosenController;
// ===================== ADMIN =====================

use App\Http\Controllers\Admin\FakultasController;
use App\Http\Controllers\Admin\JadwalController as AdminJadwalController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\KhsController;
use App\Http\Controllers\Admin\KrsController;
use App\Http\Controllers\Admin\KuesionerController as AdminKuesionerController;
use App\Http\Controllers\Admin\KurikulumController as AdminKurikulumController;
use App\Http\Controllers\Admin\LaporanAkademikController;
use App\Http\Controllers\Admin\MahasiswaController;
use App\Http\Controllers\Admin\MataKuliahController;
use App\Http\Controllers\Admin\NilaiManualController;
use App\Http\Controllers\Admin\PeriodeKrsController;
use App\Http\Controllers\Admin\PresensiController as AdminPresensiController;
use App\Http\Controllers\Admin\PresensiExportController;
use App\Http\Controllers\Admin\ProdiController;
use App\Http\Controllers\Admin\RuanganController;
use App\Http\Controllers\Dosen\DashboardController as DosenDashboardController;
use App\Http\Controllers\Dosen\EvaluasiController as DosenEvaluasiController;
// ===================== DOSEN =====================

use App\Http\Controllers\Dosen\JadwalController as DosenJadwalController;
use App\Http\Controllers\Dosen\KrsController as DosenKrsController;
use App\Http\Controllers\Dosen\MataKuliahController as DosenMataKuliahController;
use App\Http\Controllers\Dosen\NilaiController;
use App\Http\Controllers\Dosen\PenelitianController as DosenPenelitianController;
use App\Http\Controllers\Dosen\PresensiController;
use App\Http\Controllers\Dosen\ProfileController as DosenProfileController;
use App\Http\Controllers\KurikulumViewerController;
use App\Http\Controllers\Mahasiswa\DashboardController as MahasiswaDashboardController;
// ===================== MAHASISWA =====================

use App\Http\Controllers\Mahasiswa\KhsController as MahasiswaKhsController;
use App\Http\Controllers\Mahasiswa\KrsController as MahasiswaKrsController;
use App\Http\Controllers\Mahasiswa\KuesionerController as MahasiswaKuesionerController;
use App\Http\Controllers\Mahasiswa\PresensiController as MahasiswaPresensiController;
use App\Http\Controllers\Mahasiswa\ProfileController as MahasiswaProfileController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\SkripsiRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    $route = match (Auth::user()->role) {
        'admin' => 'admin.dashboard',
        'dosen' => 'dosen.dashboard',
        'mahasiswa' => 'mahasiswa.dashboard',
        default => null,
    };

    abort_unless($route, 403);

    return redirect()->route($route);
})->name('home');

// =========================================================
// DOSEN
// =========================================================

Route::middleware(['auth', SkripsiRole::class.':dosen'])->prefix('dosen')->name('dosen.')->group(function () {

    // ===================== DASHBOARD =====================

    Route::get('/dashboard', [DosenDashboardController::class, 'dashboard'])->name('dashboard');

    // ===================== JADWAL MENGAJAR =====================

    Route::get('/jadwal', [DosenJadwalController::class, 'index'])->name('jadwal');

    // ===================== MATA KULIAH AMPU =====================

    Route::get('/matakuliah', [DosenMataKuliahController::class, 'index'])->name('matakuliah');

    Route::middleware(SkripsiRole::class.':dosen')->group(function () {
        Route::get('/kurikulum', [KurikulumViewerController::class, 'dosen'])->name('kurikulum');
        Route::get('/kurikulum/silabus/{item}', [KurikulumViewerController::class, 'downloadDosen'])->name('kurikulum.silabus');
    });

    // ===================== NILAI =====================

    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai');

    Route::get('/nilai/rekap', [NilaiController::class, 'rekap'])->name('nilai.rekap');

    Route::get('/nilai/{jadwal}', [NilaiController::class, 'show'])->name('nilai.show');

    Route::post('/nilai', [NilaiController::class, 'store'])->name('nilai.store');

    // ===================== HASIL EVALUASI =====================

    Route::get('/evaluasi', [DosenEvaluasiController::class, 'index'])->name('evaluasi');
    Route::get('/evaluasi/pdf', [DosenEvaluasiController::class, 'pdf'])->name('evaluasi.pdf');

    // ===================== PRESENSI =====================

    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi');

    Route::get('/presensi/{jadwal}', [PresensiController::class, 'show'])->name('presensi.show');

    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');

    // ===================== ABSENSI MAHASISWA =====================

    Route::get('/absensi', [PresensiController::class, 'index'])->name('absensi');

    // ===================== KRS MAHASISWA =====================

    Route::get('/krs', [DosenKrsController::class, 'index'])->name('krs');

    Route::put('/krs/{id}/setujui', [DosenKrsController::class, 'setujui'])->name('krs.setujui');

    Route::put('/krs/{id}/tolak', [DosenKrsController::class, 'tolak'])->name('krs.tolak');

    // ===================== PENELITIAN =====================

    Route::middleware(SkripsiRole::class.':dosen')->group(function () {
        Route::get('/penelitian', [DosenPenelitianController::class, 'index'])->name('penelitian');
        Route::post('/penelitian', [DosenPenelitianController::class, 'store'])->name('penelitian.store');
        Route::put('/penelitian/{penelitian}', [DosenPenelitianController::class, 'update'])->name('penelitian.update');
        Route::delete('/penelitian/{penelitian}', [DosenPenelitianController::class, 'destroy'])->name('penelitian.destroy');
        Route::get('/penelitian/{penelitian}/dokumen/{dokumen}', [DosenPenelitianController::class, 'download'])->name('penelitian.download');
    });

    // ===================== PROFIL DOSEN =====================

    Route::get('/profil', [DosenProfileController::class, 'index'])->name('profil');

    Route::put('/profil', [DosenProfileController::class, 'update'])->name('profil.update');

    Route::put('/profil/password', [DosenProfileController::class, 'updatePassword'])->name('profil.password');

});

// =========================================================
// MAHASISWA
// =========================================================

Route::middleware(['auth', SkripsiRole::class.':mahasiswa'])->prefix('mahasiswa')->group(function () {

    // ===================== DASHBOARD =====================

    Route::get('/dashboard', [MahasiswaDashboardController::class, 'index'])->name('mahasiswa.dashboard');

    // ===================== KRS =====================

    Route::get('/krs', [MahasiswaKrsController::class, 'index'])->name('mahasiswa.krs');

    Route::get('/krs/kartu/pdf', [MahasiswaKrsController::class, 'cardPdf'])->name('mahasiswa.krs.pdf');

    Route::post('/krs', [MahasiswaKrsController::class, 'store'])->name('mahasiswa.krs.store');

    Route::post('/krs/{id}/ajukan-kembali', [MahasiswaKrsController::class, 'ajukanKembali'])->name('mahasiswa.krs.ajukan-kembali');

    // ===================== JADWAL =====================

    Route::get('/jadwal', [MahasiswaDashboardController::class, 'jadwal'])->name('mahasiswa.jadwal');

    // ===================== KHS =====================

    Route::get('/khs', [MahasiswaKhsController::class, 'index'])->name('mahasiswa.khs');

    Route::get('/khs/transkrip/pdf', [MahasiswaKhsController::class, 'transkripPdf'])->name('mahasiswa.transkrip.pdf');

    // ===================== KUESIONER EVALUASI DOSEN =====================

    Route::get('/kuesioner', [MahasiswaKuesionerController::class, 'index'])->name('mahasiswa.kuesioner');

    Route::get('/kuesioner/{krs}', [MahasiswaKuesionerController::class, 'create'])->name('mahasiswa.kuesioner.create');

    Route::post('/kuesioner/{krs}', [MahasiswaKuesionerController::class, 'store'])->name('mahasiswa.kuesioner.store');

    // ===================== KURIKULUM =====================

    Route::middleware(SkripsiRole::class.':mahasiswa')->group(function () {
        Route::get('/kurikulum', [KurikulumViewerController::class, 'mahasiswa'])->name('mahasiswa.kurikulum');
        Route::get('/kurikulum/silabus/{item}', [KurikulumViewerController::class, 'downloadMahasiswa'])->name('mahasiswa.kurikulum.silabus');
    });

    // ===================== KEUANGAN =====================

    Route::get('/keuangan', [MahasiswaDashboardController::class, 'keuangan'])->name('mahasiswa.keuangan');

    // ===================== PRESENSI =====================

    Route::get('/presensi', [MahasiswaPresensiController::class, 'index'])
        ->middleware(SkripsiRole::class.':mahasiswa')
        ->name('mahasiswa.presensi');

    // ===================== PROFIL =====================

    Route::get('/profil', [MahasiswaProfileController::class, 'index'])
        ->middleware(SkripsiRole::class.':mahasiswa')
        ->name('mahasiswa.profil');

});

// =========================================================
// ADMIN
// =========================================================

Route::middleware(['auth', SkripsiRole::class.':admin'])->prefix('admin')->group(function () {

    // ===================== DASHBOARD =====================

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware(SkripsiRole::class.':admin')->name('admin.dashboard');

    Route::middleware(SkripsiRole::class.':admin')->prefix('kurikulum')->name('admin.kurikulum.')->group(function () {
        Route::get('/', [AdminKurikulumController::class, 'index'])->name('index');
        Route::post('/', [AdminKurikulumController::class, 'store'])->name('store');
        Route::put('/{kurikulum}', [AdminKurikulumController::class, 'update'])->name('update');
        Route::delete('/{kurikulum}', [AdminKurikulumController::class, 'destroy'])->name('destroy');
        Route::post('/{kurikulum}/mata-kuliah', [AdminKurikulumController::class, 'storeMataKuliah'])->name('mata-kuliah.store');
        Route::put('/mata-kuliah/{item}', [AdminKurikulumController::class, 'updateMataKuliah'])->name('mata-kuliah.update');
        Route::delete('/mata-kuliah/{item}', [AdminKurikulumController::class, 'destroyMataKuliah'])->name('mata-kuliah.destroy');
        Route::get('/silabus/{item}', [AdminKurikulumController::class, 'downloadSilabus'])->name('silabus');
    });

    // ===================== DOSEN =====================

    Route::get('/dosen', [DosenController::class, 'index'])->name('admin.dosen');

    Route::get('/dosen/create', [DosenController::class, 'create'])->name('admin.dosen.create');

    Route::post('/dosen', [DosenController::class, 'store'])->name('admin.dosen.store');

    Route::get('/dosen/{dosen}/edit', [DosenController::class, 'edit'])->name('admin.dosen.edit');

    Route::put('/dosen/{dosen}', [DosenController::class, 'update'])->name('admin.dosen.update');

    Route::delete('/dosen/{dosen}', [DosenController::class, 'destroy'])->name('admin.dosen.destroy');

    // ===================== MAHASISWA =====================

    Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('admin.mahasiswa');

    Route::get('/mahasiswa/create', [MahasiswaController::class, 'create'])->name('admin.mahasiswa.create');

    Route::post('/mahasiswa', [MahasiswaController::class, 'store'])->name('admin.mahasiswa.store');

    Route::get('/mahasiswa/{mahasiswa}/edit', [MahasiswaController::class, 'edit'])->name('admin.mahasiswa.edit');

    Route::put('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('admin.mahasiswa.update');

    Route::delete('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('admin.mahasiswa.destroy');

    // ===================== MATA KULIAH =====================

    Route::get('/matakuliah', [MataKuliahController::class, 'index'])->name('admin.matakuliah');

    Route::get('/matakuliah/create', [MataKuliahController::class, 'create'])->name('admin.matakuliah.create');

    Route::post('/matakuliah', [MataKuliahController::class, 'store'])->name('admin.matakuliah.store');

    Route::get('/matakuliah/{matakuliah}/edit', [MataKuliahController::class, 'edit'])->name('admin.matakuliah.edit');

    Route::put('/matakuliah/{matakuliah}', [MataKuliahController::class, 'update'])->name('admin.matakuliah.update');

    Route::delete('/matakuliah/{matakuliah}', [MataKuliahController::class, 'destroy'])->name('admin.matakuliah.destroy');

    // ===================== PRODI =====================

    Route::get('/prodi', [ProdiController::class, 'index'])->name('admin.prodi');

    Route::get('/prodi/create', [ProdiController::class, 'create'])->name('admin.prodi.create');

    Route::post('/prodi', [ProdiController::class, 'store'])->name('admin.prodi.store');

    Route::get('/prodi/{prodi}/edit', [ProdiController::class, 'edit'])->name('admin.prodi.edit');

    Route::put('/prodi/{prodi}', [ProdiController::class, 'update'])->name('admin.prodi.update');

    Route::delete('/prodi/{prodi}', [ProdiController::class, 'destroy'])->name('admin.prodi.destroy');

    // ===================== RUANGAN =====================

    Route::get('/ruangan', [RuanganController::class, 'index'])->name('admin.ruangan');

    Route::get('/ruangan/create', [RuanganController::class, 'create'])->name('admin.ruangan.create');

    Route::post('/ruangan', [RuanganController::class, 'store'])->name('admin.ruangan.store');

    Route::get('/ruangan/{ruangan}/edit', [RuanganController::class, 'edit'])->name('admin.ruangan.edit');

    Route::put('/ruangan/{ruangan}', [RuanganController::class, 'update'])->name('admin.ruangan.update');

    Route::delete('/ruangan/{ruangan}', [RuanganController::class, 'destroy'])->name('admin.ruangan.destroy');

    // ===================== JADWAL ADMIN =====================

    Route::get('/jadwal', [AdminJadwalController::class, 'index'])->name('admin.jadwal');

    Route::get('/jadwal/create', [AdminJadwalController::class, 'create'])->name('admin.jadwal.create');

    Route::post('/jadwal', [AdminJadwalController::class, 'store'])->name('admin.jadwal.store');

    Route::get('/jadwal/{jadwal}/edit', [AdminJadwalController::class, 'edit'])->name('admin.jadwal.edit');

    Route::put('/jadwal/{jadwal}', [AdminJadwalController::class, 'update'])->name('admin.jadwal.update');

    Route::delete('/jadwal/{jadwal}', [AdminJadwalController::class, 'destroy'])->name('admin.jadwal.destroy');

    // ===================== FAKULTAS =====================

    Route::get('/fakultas', [FakultasController::class, 'index'])->name('admin.fakultas');

    Route::get('/fakultas/create', [FakultasController::class, 'create'])->name('admin.fakultas.create');

    Route::post('/fakultas', [FakultasController::class, 'store'])->name('admin.fakultas.store');

    Route::get('/fakultas/{fakulta}/edit', [FakultasController::class, 'edit'])->name('admin.fakultas.edit');

    Route::put('/fakultas/{fakulta}', [FakultasController::class, 'update'])->name('admin.fakultas.update');

    Route::delete('/fakultas/{fakulta}', [FakultasController::class, 'destroy'])->name('admin.fakultas.destroy');

    // ===================== KRS =====================

    Route::get('/krs', [KrsController::class, 'index'])->name('admin.krs');

    Route::get('/krs-mahasiswa', [KrsController::class, 'studentIndex'])->name('admin.krs-mahasiswa.index');

    Route::get('/krs-mahasiswa/{mahasiswa}', [KrsController::class, 'studentShow'])->name('admin.krs-mahasiswa.show');

    Route::get('/krs-mahasiswa/{mahasiswa}/pdf', [KrsController::class, 'studentCardPdf'])->name('admin.krs-mahasiswa.pdf');

    Route::get('/krs/create', [KrsController::class, 'create'])->name('admin.krs.create');

    Route::post('/krs', [KrsController::class, 'store'])->name('admin.krs.store');

    Route::get('/krs/{kr}/edit', [KrsController::class, 'edit'])->name('admin.krs.edit');

    Route::put('/krs/{kr}', [KrsController::class, 'update'])->name('admin.krs.update');

    Route::delete('/krs/{kr}', [KrsController::class, 'destroy'])->name('admin.krs.destroy');

    // ===================== PERIODE KRS =====================

    Route::get('/periode-krs', [PeriodeKrsController::class, 'index'])->name('admin.periode-krs');

    Route::get('/periode-krs/create', [PeriodeKrsController::class, 'create'])->name('admin.periode-krs.create');

    Route::post('/periode-krs', [PeriodeKrsController::class, 'store'])->name('admin.periode-krs.store');

    Route::get('/periode-krs/{periodeKrs}/mahasiswa', [PeriodeKrsController::class, 'students'])->name('admin.periode-krs.students');

    Route::patch('/periode-krs/{periodeKrs}/mahasiswa/{mahasiswa}/akses', [PeriodeKrsController::class, 'updateStudentAccess'])->name('admin.periode-krs.students.access');

    Route::patch('/periode-krs/{periodeKrs}/mahasiswa/akses-bulk', [PeriodeKrsController::class, 'updateBulkStudentAccess'])->name('admin.periode-krs.students.access-bulk');

    Route::get('/periode-krs/{periodeKrs}/edit', [PeriodeKrsController::class, 'edit'])->name('admin.periode-krs.edit');

    Route::put('/periode-krs/{periodeKrs}', [PeriodeKrsController::class, 'update'])->name('admin.periode-krs.update');

    Route::patch('/periode-krs/{periodeKrs}/toggle', [PeriodeKrsController::class, 'toggleStatus'])->name('admin.periode-krs.toggle');

    Route::delete('/periode-krs/{periodeKrs}', [PeriodeKrsController::class, 'destroy'])->name('admin.periode-krs.destroy');

    // ===================== KHS =====================

    Route::get('/khs', [KhsController::class, 'index'])->name('admin.khs');

    Route::get('/khs/create', [KhsController::class, 'create'])->name('admin.khs.create');

    Route::post('/khs', [KhsController::class, 'store'])->name('admin.khs.store');

    Route::get('/khs/{kh}/edit', [KhsController::class, 'edit'])->name('admin.khs.edit');

    Route::put('/khs/{kh}', [KhsController::class, 'update'])->name('admin.khs.update');

    Route::delete('/khs/{kh}', [KhsController::class, 'destroy'])->name('admin.khs.destroy');

    // ===================== NILAI LAMA / MANUAL =====================

    Route::get('/nilai-manual', [NilaiManualController::class, 'index'])->name('admin.nilai-manual.index');
    Route::get('/nilai-manual/create', [NilaiManualController::class, 'create'])->name('admin.nilai-manual.create');
    Route::post('/nilai-manual', [NilaiManualController::class, 'store'])->name('admin.nilai-manual.store');
    Route::get('/nilai-manual/{khs}/edit', [NilaiManualController::class, 'edit'])->name('admin.nilai-manual.edit');
    Route::put('/nilai-manual/{khs}', [NilaiManualController::class, 'update'])->name('admin.nilai-manual.update');
    Route::delete('/nilai-manual/{khs}', [NilaiManualController::class, 'destroy'])->name('admin.nilai-manual.destroy');

    // ===================== KELAS =====================

    Route::get('/kelas', [KelasController::class, 'index'])->name('admin.kelas');

    Route::get('/kelas/create', [KelasController::class, 'create'])->name('admin.kelas.create');

    Route::post('/kelas', [KelasController::class, 'store'])->name('admin.kelas.store');

    Route::get('/kelas/{kelas}/edit', [KelasController::class, 'edit'])->name('admin.kelas.edit');

    Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('admin.kelas.update');

    Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('admin.kelas.destroy');

    // ===================== REKAP PRESENSI =====================

    Route::get('/presensi', [AdminPresensiController::class, 'index'])->name('admin.presensi');

    Route::get('/presensi/download/excel', [PresensiExportController::class, 'excel'])->name('admin.presensi.excel');

    Route::get('/presensi/download/pdf', [PresensiExportController::class, 'pdf'])->name('admin.presensi.pdf');

    // ===================== REKAP KUESIONER =====================

    Route::get('/kuesioner', [AdminKuesionerController::class, 'index'])->name('admin.kuesioner');
    Route::get('/kuesioner/pdf', [AdminKuesionerController::class, 'pdf'])->name('admin.kuesioner.pdf');
    Route::get('/kuesioner/dosen/{dosen}', [AdminKuesionerController::class, 'show'])->name('admin.kuesioner.dosen');
    Route::get('/kuesioner/dosen/{dosen}/pdf', [AdminKuesionerController::class, 'pdfDetail'])->name('admin.kuesioner.dosen.pdf');

    // ===================== LAPORAN AKADEMIK =====================

    Route::get('/laporan-akademik', [LaporanAkademikController::class, 'index'])->name('admin.laporan.index');

    Route::get('/laporan-akademik/excel', [LaporanAkademikController::class, 'excel'])->name('admin.laporan.excel');

    Route::get('/laporan-akademik/pdf', [LaporanAkademikController::class, 'pdf'])->name('admin.laporan.pdf');

});

// =========================================================
// PROFILE
// =========================================================

Route::middleware(['auth', SkripsiRole::class.':admin'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

// =========================================================
// AUTH
// =========================================================

Route::middleware(['auth', SkripsiRole::class.':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('pengumuman', App\Http\Controllers\Admin\PengumumanController::class)->except('show');
});

foreach (['dosen', 'mahasiswa', 'admin'] as $role) {
    Route::middleware(['auth', SkripsiRole::class.':'.$role])->prefix($role)->name($role.'.')->group(function () {
        Route::get('/pemberitahuan', [PengumumanController::class, 'index'])->name('pemberitahuan');
        Route::post('/pemberitahuan/{pengumuman}/baca', [PengumumanController::class, 'read'])->name('pemberitahuan.baca');
    });
}

require __DIR__.'/auth.php';

require __DIR__.'/skripsi.php';
