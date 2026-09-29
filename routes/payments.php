<?php

use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentImportController;
use App\Http\Controllers\Mahasiswa\PaymentController as StudentPaymentController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\PaymentAttachmentController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Middleware\SkripsiRole;
use Illuminate\Support\Facades\Route;

Route::post('/midtrans/callback', MidtransCallbackController::class)->name('midtrans.callback');

Route::middleware(['auth', SkripsiRole::class.':admin'])->prefix('admin/pembayaran')->name('admin.pembayaran.')->group(function () {
    Route::get('/', [AdminPaymentController::class, 'index'])->name('index');
    Route::get('/create', [AdminPaymentController::class, 'create'])->name('create');
    Route::post('/', [AdminPaymentController::class, 'store'])->name('store');
    Route::get('/early-access', [AdminPaymentController::class, 'access'])->name('access');
    Route::patch('/early-access/{mahasiswa}', [AdminPaymentController::class, 'updateAccess'])->name('access.update');
    Route::get('/export/{format}', [AdminPaymentController::class, 'export'])->name('export');
    Route::get('/import', [PaymentImportController::class, 'index'])->name('import');
    Route::get('/import/template', [PaymentImportController::class, 'template'])->name('import.template');
    Route::post('/import/preview', [PaymentImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/confirm', [PaymentImportController::class, 'confirm'])->name('import.confirm');
    Route::get('/transaksi/{pembayaran}/bukti', PaymentReceiptController::class)->name('receipt');
    Route::get('/transaksi/{pembayaran}/lampiran', PaymentAttachmentController::class)->name('attachment');
    Route::patch('/transaksi/{pembayaran}/koreksi', [AdminPaymentController::class, 'void'])->name('void');
    Route::get('/{tagihan}', [AdminPaymentController::class, 'show'])->name('show');
    Route::patch('/{tagihan}', [AdminPaymentController::class, 'update'])->name('update');
    Route::patch('/{tagihan}/batalkan', [AdminPaymentController::class, 'cancel'])->name('cancel');
    Route::post('/{tagihan}/manual', [AdminPaymentController::class, 'manual'])->name('manual');
});

Route::middleware(['auth', SkripsiRole::class.':mahasiswa'])->prefix('mahasiswa/pembayaran')->name('mahasiswa.pembayaran.')->group(function () {
    Route::get('/', [StudentPaymentController::class, 'index'])->name('index');
    Route::get('/transaksi/{pembayaran}/bukti', PaymentReceiptController::class)->name('receipt');
    Route::get('/transaksi/{pembayaran}/lampiran', PaymentAttachmentController::class)->name('attachment');
    Route::get('/{tagihan}', [StudentPaymentController::class, 'show'])->name('show');
    Route::post('/{tagihan}/bayar', [StudentPaymentController::class, 'pay'])->middleware('throttle:10,1')->name('pay');
});
