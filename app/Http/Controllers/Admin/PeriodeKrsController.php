<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeKrs;
use Illuminate\Http\Request;

class PeriodeKrsController extends Controller
{
    // ===================== INDEX =====================

    public function index()
    {
        $periodeKrs = PeriodeKrs::latest()
            ->paginate(10);

        return view(
            'admin.periode_krs.index',
            compact('periodeKrs')
        );
    }


    // ===================== CREATE =====================

    public function create()
    {
        return view('admin.periode_krs.create');
    }


    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'tahun_akademik'  => 'required|max:20',
            'semester'        => 'required|in:Ganjil,Genap',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks'     => 'required|integer|min:0|max:30',
            'maksimal_sks'    => 'required|integer|min:1|max:30',
            'status'          => 'required|in:Dibuka,Ditutup',
            'keterangan'      => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' =>
                        'Minimal SKS tidak boleh lebih besar dari maksimal SKS.'
                ]);
        }

        PeriodeKrs::create([
            'tahun_akademik'  => $request->tahun_akademik,
            'semester'        => $request->semester,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks'     => $request->minimal_sks,
            'maksimal_sks'    => $request->maksimal_sks,
            'status'          => $request->status,
            'keterangan'      => $request->keterangan,
        ]);

        return redirect()
            ->route('admin.periode-krs')
            ->with(
                'success',
                'Periode KRS berhasil ditambahkan.'
            );
    }


    // ===================== EDIT =====================

    public function edit(PeriodeKrs $periodeKrs)
    {
        return view(
            'admin.periode_krs.edit',
            compact('periodeKrs')
        );
    }


    // ===================== UPDATE =====================

    public function update(
        Request $request,
        PeriodeKrs $periodeKrs
    ) {
        $request->validate([
            'tahun_akademik'  => 'required|max:20',
            'semester'        => 'required|in:Ganjil,Genap',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks'     => 'required|integer|min:0|max:30',
            'maksimal_sks'    => 'required|integer|min:1|max:30',
            'status'          => 'required|in:Dibuka,Ditutup',
            'keterangan'      => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' =>
                        'Minimal SKS tidak boleh lebih besar dari maksimal SKS.'
                ]);
        }

        $periodeKrs->update([
            'tahun_akademik'  => $request->tahun_akademik,
            'semester'        => $request->semester,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks'     => $request->minimal_sks,
            'maksimal_sks'    => $request->maksimal_sks,
            'status'          => $request->status,
            'keterangan'      => $request->keterangan,
        ]);

        return redirect()
            ->route('admin.periode-krs')
            ->with(
                'success',
                'Periode KRS berhasil diperbarui.'
            );
    }


    // ===================== TOGGLE STATUS =====================

    public function toggleStatus(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->update([
            'status' => $periodeKrs->status === 'Dibuka'
                ? 'Ditutup'
                : 'Dibuka'
        ]);

        return back()->with(
            'success',
            'Status periode KRS berhasil diubah.'
        );
    }


    // ===================== DELETE =====================

    public function destroy(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->delete();

        return redirect()
            ->route('admin.periode-krs')
            ->with(
                'success',
                'Periode KRS berhasil dihapus.'
            );
    }
}