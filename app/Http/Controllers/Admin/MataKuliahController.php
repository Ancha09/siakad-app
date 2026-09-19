<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;

class MataKuliahController extends Controller
{
    // ===================== INDEX =====================

    public function index(Request $request)
    {
        // ===================== DATA PRODI =====================

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();


        // ===================== QUERY MATA KULIAH =====================

        $query = MataKuliah::with([
            'prodi.fakultas'
        ]);


        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->whereLike('kode_mk', '%' . $search . '%'
                )
                ->orWhereLike('nama_mk', '%' . $search . '%'
                );

            });
        }


        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->where(
                'prodi_id',
                $request->prodi_id
            );
        }


        // ===================== FILTER SEMESTER =====================

        if ($request->filled('semester')) {

            $query->where(
                'semester',
                $request->semester
            );
        }


        // ===================== HASIL =====================

        $matakuliahs = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();


        return view(
            'admin.matakuliah.index',
            compact(
                'matakuliahs',
                'prodis'
            )
        );
    }


    // ===================== CREATE =====================

    public function create()
    {
        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        return view(
            'admin.matakuliah.create',
            compact('prodis')
        );
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'kode_mk'  => 'required|unique:mata_kuliahs,kode_mk',
            'nama_mk'  => 'required',
            'sks'      => 'required|integer|min:1|max:6',
            'semester' => 'nullable|integer|min:1|max:14',
            'prodi_id' => 'nullable|exists:prodis,id',
        ]);


        MataKuliah::create([
            'kode_mk'  => $request->kode_mk,
            'nama_mk'  => $request->nama_mk,
            'sks'      => $request->sks,
            'semester' => $request->semester,
            'prodi_id' => $request->filled('prodi_id') ? $request->integer('prodi_id') : null,
        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.matakuliah'))
            ->with(
                'success',
                'Data mata kuliah berhasil ditambahkan.'
            );
    }


    // ===================== EDIT =====================

    public function edit(MataKuliah $matakuliah)
    {
        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        return view(
            'admin.matakuliah.edit',
            compact(
                'matakuliah',
                'prodis'
            )
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        MataKuliah $matakuliah
    ) {
        $request->validate([
            'kode_mk' => 'required|unique:mata_kuliahs,kode_mk,' . $matakuliah->id,
            'nama_mk' => 'required',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'nullable|integer|min:1|max:14',
            'prodi_id' => 'nullable|exists:prodis,id',
        ]);


        $matakuliah->update([
            'kode_mk' => $request->kode_mk,
            'nama_mk' => $request->nama_mk,
            'sks' => $request->sks,
            'semester' => $request->semester,
            'prodi_id' => $request->filled('prodi_id') ? $request->integer('prodi_id') : null,
        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.matakuliah'))
            ->with(
                'success',
                'Data mata kuliah berhasil diperbarui.'
            );
    }


    // ===================== DELETE =====================

    public function destroy(MataKuliah $matakuliah)
    {
        $matakuliah->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.matakuliah'))
            ->with(
                'success',
                'Data mata kuliah berhasil dihapus.'
            );
    }
}
