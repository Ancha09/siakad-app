<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index()
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $jadwals = Jadwal::with([
                'mataKuliah.prodi',
                'dosen',
                'ruangan',
            ])
            ->withCount([
                'krs as jumlah_mahasiswa' => fn ($query) => $query->where('status', '!=', 'Ditolak'),
            ])
            ->where('dosen_id', $dosen->id)
            ->orderByDesc('tahun_akademik')
            ->orderByDesc('semester_akademik')
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view('dosen.jadwal.index', compact('dosen', 'jadwals'));
    }
}
