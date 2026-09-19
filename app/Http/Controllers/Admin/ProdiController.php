<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Fakultas;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;

class ProdiController extends Controller
{
    // ===================== INDEX =====================

    public function index()
    {
        $prodis = Prodi::with('fakultas')
            ->latest()
            ->paginate(10)->withQueryString();

        return view(
            'admin.prodi.index',
            compact('prodis')
        );
    }


    // ===================== CREATE =====================

    public function create()
    {
        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        return view(
            'admin.prodi.create',
            compact('fakultas')
        );
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([

            'kode_prodi' => 'required|unique:prodis,kode_prodi',

            'nama_prodi' => 'required',

            'jenjang' => 'required',

            'fakultas_id' => 'required|exists:fakultas,id',

            'ketua_program_studi_nama' => 'nullable|string|max:150',

            'ketua_program_studi_nip' => 'nullable|string|max:50',

        ]);


        Prodi::create([

            'kode_prodi' => $request->kode_prodi,

            'nama_prodi' => $request->nama_prodi,

            'jenjang' => $request->jenjang,

            'fakultas_id' => $request->fakultas_id,

            'ketua_program_studi_nama' => $request->filled('ketua_program_studi_nama')
                ? trim((string) $request->ketua_program_studi_nama)
                : null,

            'ketua_program_studi_nip' => $request->filled('ketua_program_studi_nip')
                ? trim((string) $request->ketua_program_studi_nip)
                : null,

        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.prodi'))
            ->with(
                'success',
                'Data program studi berhasil ditambahkan.'
            );
    }


    // ===================== EDIT =====================

    public function edit(Prodi $prodi)
    {
        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        return view(
            'admin.prodi.edit',
            compact(
                'prodi',
                'fakultas'
            )
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Prodi $prodi
    ) {
        $request->validate([

            'kode_prodi' =>
                'required|unique:prodis,kode_prodi,' . $prodi->id,

            'nama_prodi' => 'required',

            'jenjang' => 'required',

            'fakultas_id' =>
                'required|exists:fakultas,id',

            'ketua_program_studi_nama' => 'nullable|string|max:150',

            'ketua_program_studi_nip' => 'nullable|string|max:50',

        ]);


        $prodi->update([

            'kode_prodi' => $request->kode_prodi,

            'nama_prodi' => $request->nama_prodi,

            'jenjang' => $request->jenjang,

            'fakultas_id' => $request->fakultas_id,

            'ketua_program_studi_nama' => $request->filled('ketua_program_studi_nama')
                ? trim((string) $request->ketua_program_studi_nama)
                : null,

            'ketua_program_studi_nip' => $request->filled('ketua_program_studi_nip')
                ? trim((string) $request->ketua_program_studi_nip)
                : null,

        ]);


        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.prodi'))
            ->with(
                'success',
                'Data program studi berhasil diperbarui.'
            );
    }


    // ===================== DELETE =====================

    public function destroy(Prodi $prodi)
    {
        $prodi->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.prodi'))
            ->with(
                'success',
                'Data program studi berhasil dihapus.'
            );
    }
}
