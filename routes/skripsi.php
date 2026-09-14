<?php

use App\Http\Controllers\SkripsiController;
use App\Http\Middleware\SkripsiRole;
use Illuminate\Support\Facades\Route;

foreach (['mahasiswa', 'dosen', 'admin'] as $role) {
    Route::middleware(['auth', SkripsiRole::class.':'.$role])->prefix($role.'/skripsi')->name($role.'.skripsi')->group(function () use ($role) {
        Route::get('/', [SkripsiController::class, $role === 'admin' ? 'admin' : 'index']);
        if ($role === 'mahasiswa') {
            Route::post('/', [SkripsiController::class, 'store'])->name('.store');
            Route::put('/pengajuan/{pengajuan}/judul', [SkripsiController::class, 'updateTitle'])->name('.title.update');
            Route::get('/template-kartu-bimbingan', [SkripsiController::class, 'downloadTemplate'])->name('.template.download');
        } elseif ($role === 'dosen') {
            Route::put('/pengajuan/{pengajuan}/keputusan', [SkripsiController::class, 'decide'])->name('.decide');
        } else {
            Route::get('/laporan/excel', [SkripsiController::class, 'adminExcel'])->name('.excel');
            Route::get('/laporan/pdf', [SkripsiController::class, 'adminPdf'])->name('.pdf');
            Route::post('/periode', [SkripsiController::class, 'period'])->name('.periode.store');
            Route::put('/periode/{periode}', [SkripsiController::class, 'period'])->name('.periode.update');
            Route::put('/dosen/{dosen}', [SkripsiController::class, 'dosen'])->name('.dosen');
            Route::post('/pengajuan/{pengajuan}/alihkan', [SkripsiController::class, 'transfer'])->name('.transfer');
            Route::post('/template-kartu-bimbingan', [SkripsiController::class, 'uploadTemplate'])->name('.template.store');
            Route::get('/template-kartu-bimbingan', [SkripsiController::class, 'downloadTemplate'])->name('.template.download');
        }
        Route::get('/pengajuan/{pengajuan}', [SkripsiController::class, 'show'])->name('.show');
    });
}
