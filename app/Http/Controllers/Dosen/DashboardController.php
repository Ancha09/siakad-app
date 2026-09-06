<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    // ===================== DASHBOARD =====================

    public function dashboard()
    {
        return view('dosen.dashboard');
    }


    // ===================== JADWAL MENGAJAR =====================

    public function jadwal()
    {
        return view('dosen.jadwal');
    }


    // ===================== MATA KULIAH =====================
    // Sekarang ditangani oleh MataKuliahController

    public function matakuliah()
    {
        return redirect()->route('dosen.matakuliah');
    }


    // ===================== NILAI =====================
    // Sekarang ditangani oleh NilaiController

    public function nilai()
    {
        return redirect()->route('dosen.nilai');
    }


    // ===================== PRESENSI =====================
    // Sekarang ditangani oleh PresensiController

    public function absensi()
    {
        return redirect()->route('dosen.presensi');
    }


    // ===================== PROFIL =====================

    public function profil()
    {
        return view('dosen.profil');
    }
}
