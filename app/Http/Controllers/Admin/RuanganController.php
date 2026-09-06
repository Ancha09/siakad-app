<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ruangan;
use Illuminate\Http\Request;

class RuanganController extends Controller
{
    // ===================== INDEX =====================
    public function index()
    {
        $ruangans = Ruangan::latest()->paginate(10);

        return view('admin.ruangan.index', compact('ruangans'));
    }

    // ===================== CREATE =====================
    public function create()
    {
        return view('admin.ruangan.create');
    }

    // ===================== STORE =====================
    public function store(Request $request)
    {
        $request->validate([
            'kode_ruangan' => 'required|unique:ruangans,kode_ruangan',
            'nama_ruangan' => 'required',
            'gedung'       => 'nullable',
            'kapasitas'    => 'nullable|integer|min:1',
        ]);

        Ruangan::create($request->only([
            'kode_ruangan',
            'nama_ruangan',
            'gedung',
            'kapasitas',
        ]));

        return redirect()
            ->route('admin.ruangan')
            ->with('success', 'Data ruangan berhasil ditambahkan.');
    }

    // ===================== EDIT =====================
    public function edit(Ruangan $ruangan)
    {
        return view('admin.ruangan.edit', compact('ruangan'));
    }

    // ===================== UPDATE =====================
    public function update(Request $request, Ruangan $ruangan)
    {
        $request->validate([
            'kode_ruangan' => 'required|unique:ruangans,kode_ruangan,' . $ruangan->id,
            'nama_ruangan' => 'required',
            'gedung'       => 'nullable',
            'kapasitas'    => 'nullable|integer|min:1',
        ]);

        $ruangan->update($request->only([
            'kode_ruangan',
            'nama_ruangan',
            'gedung',
            'kapasitas',
        ]));

        return redirect()
            ->route('admin.ruangan')
            ->with('success', 'Data ruangan berhasil diperbarui.');
    }

    // ===================== DELETE =====================
    public function destroy(Ruangan $ruangan)
    {
        $ruangan->delete();

        return redirect()
            ->route('admin.ruangan')
            ->with('success', 'Data ruangan berhasil dihapus.');
    }
}