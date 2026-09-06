<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['penerima' => ['nullable', Rule::in(['dosen', 'mahasiswa'])]]);
        $penerima = $request->input('penerima') ?: 'mahasiswa';
        $pengumumans = Pengumuman::with('penulis')->withCount('pembaca')->where('penerima', $penerima)
            ->latest()->paginate(10)->withQueryString();

        return view('admin.pengumuman.index', compact('pengumumans', 'penerima'));
    }

    public function create(Request $request)
    {
        $request->validate(['penerima' => ['nullable', Rule::in(['dosen', 'mahasiswa'])]]);
        $pengumuman = new Pengumuman(['penerima' => $request->input('penerima') ?: 'mahasiswa', 'status' => 'draft']);

        return view('admin.pengumuman.form', compact('pengumuman'));
    }

    public function store(Request $request)
    {
        $pengumuman = new Pengumuman($this->validated($request));
        $pengumuman->penulis()->associate($request->user());
        $pengumuman->save();

        return redirect()->route('admin.pengumuman.index', ['penerima' => $pengumuman->penerima])
            ->with('success', 'Pengumuman berhasil disimpan. Status: '.$pengumuman->label_status.'.');
    }

    public function edit(Pengumuman $pengumuman)
    {
        return view('admin.pengumuman.form', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($pengumuman, $data) {
            $pengumuman->update($data);
            // Perubahan isi/penerima perlu dibaca kembali oleh masing-masing pengguna.
            $pengumuman->pembaca()->detach();
        });

        return redirect()->route('admin.pengumuman.index', ['penerima' => $pengumuman->penerima])
            ->with('success', 'Pengumuman diperbarui. Penerima dapat membaca versi terbaru.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $penerima = $pengumuman->penerima;
        $pengumuman->delete();

        return redirect()->route('admin.pengumuman.index', compact('penerima'))->with('success', 'Pengumuman dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'penerima' => ['required', Rule::in(['dosen', 'mahasiswa'])],
            'judul' => ['required', 'string', 'max:180'],
            'isi' => ['required', 'string', 'max:20000'],
            'tautan' => ['nullable', 'url:http,https', 'max:2048'],
            'penting' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'terbit'])],
            'terbit_pada' => ['nullable', 'date'],
            'berakhir_pada' => ['nullable', 'date', 'after:'.($request->filled('terbit_pada') ? 'terbit_pada' : now('Asia/Jakarta')->format('Y-m-d H:i:s'))],
        ]);
        $data['penting'] = $request->boolean('penting');
        foreach (['terbit_pada', 'berakhir_pada'] as $field) {
            $data[$field] = empty($data[$field]) ? null
                : \Carbon\Carbon::parse($data[$field], 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        }
        if ($data['status'] === 'terbit' && empty($data['terbit_pada'])) $data['terbit_pada'] = now();

        return $data;
    }
}
