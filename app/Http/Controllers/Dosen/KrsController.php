<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Dosen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KrsController extends Controller
{
    // ===================== DAFTAR KRS MAHASISWA =====================

    public function index()
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();

        $krs = Krs::with([
            'mahasiswa.prodi',
            'mahasiswa.dosenWali',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
        ->where('is_manual', false)
        ->whereHas('mahasiswa', function ($query) use ($dosen) {
            $query->where('dosen_wali_id', $dosen->id);
        })
        ->orderByDesc('created_at')
        ->get();


        // ===================== STATISTIK =====================

        $menunggu = $krs
            ->where('status', 'Menunggu')
            ->count();

        $disetujui = $krs
            ->where('status', 'Disetujui')
            ->count();

        $ditolak = $krs
            ->where('status', 'Ditolak')
            ->count();


        return view(
            'dosen.krs.index',
            compact(
                'dosen',
                'krs',
                'menunggu',
                'disetujui',
                'ditolak'
            )
        );
    }


    // ===================== SETUJUI KRS =====================

    public function setujui(int $id)
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();


        // ===================== AMBIL KRS =====================

        $krs = Krs::where('id', $id)
            ->where('is_manual', false)
            ->whereHas('mahasiswa', function ($query) use ($dosen) {
                $query->where('dosen_wali_id', $dosen->id);
            })
            ->firstOrFail();


        // ===================== CEK STATUS =====================

        if ($krs->status !== 'Menunggu') {

            return redirect()
                ->route('dosen.krs')
                ->with(
                    'error',
                    'KRS ini tidak dapat disetujui karena statusnya sudah diproses.'
                );
        }


        // ===================== SETUJUI =====================

        $krs->update([

            'status' => 'Disetujui',

            // Jika sebelumnya pernah ditolak,
            // alasan penolakan dihapus ketika disetujui kembali.
            'alasan_penolakan' => null,

        ]);


        return redirect()
            ->route('dosen.krs')
            ->with(
                'success',
                'KRS mahasiswa berhasil disetujui.'
            );
    }


    // ===================== TOLAK KRS =====================

    public function tolak(
        Request $request,
        int $id
    ) {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();


        // ===================== VALIDASI ALASAN =====================

        $request->validate([
            'alasan_penolakan' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'alasan_penolakan.required' =>
                'Alasan penolakan wajib diisi.',

            'alasan_penolakan.min' =>
                'Alasan penolakan minimal 5 karakter.',

            'alasan_penolakan.max' =>
                'Alasan penolakan maksimal 1000 karakter.',
        ]);


        // ===================== AMBIL KRS =====================

        $krs = Krs::where('id', $id)
            ->where('is_manual', false)
            ->whereHas('mahasiswa', function ($query) use ($dosen) {
                $query->where('dosen_wali_id', $dosen->id);
            })
            ->firstOrFail();


        // ===================== CEK STATUS =====================

        if ($krs->status !== 'Menunggu') {

            return redirect()
                ->route('dosen.krs')
                ->with(
                    'error',
                    'KRS ini tidak dapat ditolak karena statusnya sudah diproses.'
                );
        }


        // ===================== TOLAK KRS =====================

        $krs->update([

            'status' => 'Ditolak',

            'alasan_penolakan' =>
                $request->alasan_penolakan,

        ]);


        return redirect()
            ->route('dosen.krs')
            ->with(
                'success',
                'KRS mahasiswa berhasil ditolak dan alasan penolakan telah disimpan.'
            );
    }
}
