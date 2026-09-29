<?php

namespace App\Http\Controllers;

use App\Models\PembayaranMahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentAttachmentController extends Controller
{
    public function __invoke(Request $request, PembayaranMahasiswa $pembayaran)
    {
        $user = $request->user();
        $isOwner = $user->role === 'mahasiswa'
            && $user->mahasiswa
            && $pembayaran->tagihan()->where('mahasiswa_id', $user->mahasiswa->id)->exists();

        abort_unless($user->role === 'admin' || $isOwner, 403);
        abort_unless($pembayaran->bukti_path && Storage::disk('local')->exists($pembayaran->bukti_path), 404);

        $extension = pathinfo($pembayaran->bukti_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download(
            $pembayaran->bukti_path,
            'bukti-cash-'.$pembayaran->id.'.'.$extension,
        );
    }
}
