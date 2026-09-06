<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Auth;

class MataKuliahController extends Controller
{
    public function index()
    {
        $dosen = Dosen::where('user_id', Auth::id())
            ->firstOrFail();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'ruangan',
        ])
        ->where('dosen_id', $dosen->id)
        ->orderBy('tahun_akademik', 'desc')
        ->orderBy('hari')
        ->orderBy('jam_mulai')
        ->get();

        return view('dosen.matakuliah.index', compact('jadwals'));
    }
}