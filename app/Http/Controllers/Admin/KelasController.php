<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Prodi;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    // ================= INDEX =================

    public function index(Request $request)
    {
        // Data fakultas untuk filter
        $fakultas = Fakultas::orderBy('nama_fakultas')->get();

        // Data prodi untuk filter
        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        // Query kelas
        $query = Kelas::with([
            'prodi.fakultas',
            'dosenWali',
        ])
        ->withCount('mahasiswas');


        // ================= SEARCH =================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->whereLike('nama_kelas', '%' . $search . '%'
                );

            });
        }


        // ================= FILTER FAKULTAS =================

        if ($request->filled('fakultas_id')) {

            $query->whereHas('prodi', function ($q) use ($request) {

                $q->where(
                    'fakultas_id',
                    $request->fakultas_id
                );

            });
        }


        // ================= FILTER PRODI =================

        if ($request->filled('prodi_id')) {

            $query->where(
                'prodi_id',
                $request->prodi_id
            );
        }


        // ================= FILTER ANGKATAN =================

        if ($request->filled('angkatan')) {

            $query->where(
                'angkatan',
                $request->angkatan
            );
        }


        // ================= FILTER SEMESTER =================

        if ($request->filled('semester')) {

            $query->where(
                'semester',
                $request->semester
            );
        }


        // ================= HASIL =================

        $kelases = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();


        // ================= ANGKATAN =================

        $angkatans = Kelas::select('angkatan')
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');


        return view(
            'admin.kelas.index',
            compact(
                'kelases',
                'fakultas',
                'prodis',
                'angkatans'
            )
        );
    }


    // ================= CREATE =================

    public function create()
    {
        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        $dosens = Dosen::orderBy('nama')->get();

        return view(
            'admin.kelas.create',
            compact(
                'fakultas',
                'prodis',
                'dosens'
            )
        );
    }


    // ================= STORE =================

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'    => 'required|max:100',
            'prodi_id'      => 'required|exists:prodis,id',
            'angkatan'      => 'required|digits:4',
            'semester'      => 'nullable|integer|min:1|max:14',
            'dosen_wali_id' => 'nullable|exists:dosens,id',
        ]);


        Kelas::create([
            'nama_kelas'    => $request->nama_kelas,
            'prodi_id'      => $request->prodi_id,
            'angkatan'      => $request->angkatan,
            'semester'      => $request->semester,
            'dosen_wali_id' => $request->dosen_wali_id,
        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.kelas'))
            ->with(
                'success',
                'Kelas berhasil ditambahkan.'
            );
    }


    // ================= EDIT =================

    public function edit(Kelas $kelas)
    {
        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        $dosens = Dosen::orderBy('nama')->get();

        return view(
            'admin.kelas.edit',
            compact(
                'kelas',
                'fakultas',
                'prodis',
                'dosens'
            )
        );
    }


    // ================= UPDATE =================

    public function update(
        Request $request,
        Kelas $kelas
    ) {
        $request->validate([
            'nama_kelas'    => 'required|max:100',
            'prodi_id'      => 'required|exists:prodis,id',
            'angkatan'      => 'required|digits:4',
            'semester'      => 'nullable|integer|min:1|max:14',
            'dosen_wali_id' => 'nullable|exists:dosens,id',
        ]);


        $kelas->update([
            'nama_kelas'    => $request->nama_kelas,
            'prodi_id'      => $request->prodi_id,
            'angkatan'      => $request->angkatan,
            'semester'      => $request->semester,
            'dosen_wali_id' => $request->dosen_wali_id,
        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.kelas'))
            ->with(
                'success',
                'Data kelas berhasil diperbarui.'
            );
    }


    // ================= DELETE =================

    public function destroy(Kelas $kelas)
    {
        // Lepaskan kelas dari mahasiswa
        $kelas->mahasiswas()->update([
            'kelas_id' => null,
        ]);


        $kelas->delete();


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.kelas'))
            ->with(
                'success',
                'Kelas berhasil dihapus.'
            );
    }
}
