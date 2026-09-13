<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Krs;
use App\Models\TemplateBimbingan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // ===================== DASHBOARD =====================
    public function index()
    {
        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Semua KRS mahasiswa
        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
        ->where('mahasiswa_id', $mahasiswa->id)
        ->get();

        // Total SKS yang sudah disetujui
        $totalSks = $krs
            ->where('status', 'Disetujui')
            ->sum(function ($item) {
                return $item->jadwal->mataKuliah->sks ?? 0;
            });

        // Jadwal dari KRS yang sudah disetujui
        $jadwals = $krs
            ->where('status', 'Disetujui')
            ->map(function ($item) {
                return $item->jadwal;
            })
            ->filter()
            ->values();

        // Jumlah KRS
        $jumlahKrs = $krs->count();

        // Jumlah KRS yang masih menunggu
        $krsMenunggu = $krs
            ->where('status', 'Menunggu')
            ->count();

        $templateBimbingan = TemplateBimbingan::aktif();

        return view('mahasiswa.dashboard', compact(
            'mahasiswa',
            'krs',
            'jadwals',
            'totalSks',
            'jumlahKrs',
            'krsMenunggu',
            'templateBimbingan'
        ));
    }


    // ===================== KRS =====================
    public function krs()
    {
        return view('mahasiswa.krs');
    }


    // ===================== JADWAL =====================
    public function jadwal()
    {
        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $jadwals = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
        ->where('mahasiswa_id', $mahasiswa->id)
        ->where('status', 'Disetujui')
        ->get()
        ->map(function ($krs) {
            return $krs->jadwal;
        })
        ->filter()
        ->values();

        return view(
            'mahasiswa.jadwal.index',
            compact(
                'mahasiswa',
                'jadwals'
            )
        );
    }


    // ===================== KHS =====================
    public function khs()
    {
        return view('mahasiswa.khs');
    }


    // ===================== KEUANGAN =====================
    public function keuangan()
    {
        return view('mahasiswa.keuangan');
    }

}
