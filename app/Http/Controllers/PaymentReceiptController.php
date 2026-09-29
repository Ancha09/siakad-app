<?php

namespace App\Http\Controllers;

use App\Models\PembayaranMahasiswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function __invoke(Request $request, PembayaranMahasiswa $pembayaran)
    {
        $pembayaran->load('tagihan.mahasiswa.prodi');
        $user = $request->user();
        abort_unless($user->role === 'admin' || ($user->role === 'mahasiswa' && $user->mahasiswa?->id === $pembayaran->tagihan->mahasiswa_id), 404);
        abort_unless($pembayaran->status === 'paid', 404);

        return Pdf::loadView('payments.receipt-pdf', ['payment' => $pembayaran, 'bill' => $pembayaran->tagihan])
            ->setOption('isRemoteEnabled', false)->setPaper('a4')->download('bukti-sandbox-'.$pembayaran->id.'.pdf');
    }
}
