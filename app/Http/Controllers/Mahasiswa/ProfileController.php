<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $mahasiswa = Mahasiswa::with([
            'user',
            'prodi.fakultas',
            'kelas.dosenWali',
            'dosenWali',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $dosenWali = $mahasiswa->kelas?->dosenWali ?? $mahasiswa->dosenWali;

        return view('mahasiswa.profil', compact('mahasiswa', 'dosenWali'));
    }
}
