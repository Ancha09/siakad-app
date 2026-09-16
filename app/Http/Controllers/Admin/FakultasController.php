<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;

class FakultasController extends Controller
{
    public function index()
    {
        $fakultas = Fakultas::latest()->paginate(10)->withQueryString();

        return view('admin.fakultas.index', compact('fakultas'));
    }

    public function create()
    {
        return view('admin.fakultas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_fakultas' => 'required|unique:fakultas,kode_fakultas',
            'nama_fakultas' => 'required',
        ]);

        Fakultas::create($request->only([
            'kode_fakultas',
            'nama_fakultas',
        ]));

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.fakultas'))
            ->with('success', 'Data fakultas berhasil ditambahkan.');
    }

    public function edit(Fakultas $fakulta)
    {
        return view('admin.fakultas.edit', ['fakultas' => $fakulta]);
    }

    public function update(Request $request, Fakultas $fakulta)
    {
        $request->validate([
            'kode_fakultas' => 'required|unique:fakultas,kode_fakultas,' . $fakulta->id,
            'nama_fakultas' => 'required',
        ]);

        $fakulta->update($request->only([
            'kode_fakultas',
            'nama_fakultas',
        ]));

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.fakultas'))
            ->with('success', 'Data fakultas berhasil diperbarui.');
    }

    public function destroy(Fakultas $fakulta)
    {
        $fakulta->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.fakultas'))
            ->with('success', 'Data fakultas berhasil dihapus.');
    }
}
