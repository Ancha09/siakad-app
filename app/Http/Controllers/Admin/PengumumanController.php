<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Pengumuman;
use App\Models\PeriodeKrs;
use App\Models\Prodi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        return view('admin.pengumuman.form', $this->formData($pengumuman));
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
        return view('admin.pengumuman.form', $this->formData($pengumuman));
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
            'target_type' => ['nullable', Rule::in(['all', 'period', 'prodi', 'angkatan', 'student'])],
            'target_periode_krs_id' => ['required_if:target_type,period', 'nullable', 'integer', 'exists:periode_krs,id'],
            'target_prodi_id' => ['required_if:target_type,prodi', 'nullable', 'integer', 'exists:prodis,id'],
            'target_angkatan' => ['required_if:target_type,angkatan', 'nullable', 'integer', 'between:1900,2100'],
            'target_nim' => ['required_if:target_type,student', 'nullable', 'string', 'max:50', 'exists:mahasiswas,nim'],
            'judul' => ['required', 'string', 'max:180'],
            'isi' => ['required', 'string', 'max:20000'],
            'tautan' => ['nullable', 'url:http,https', 'max:2048'],
            'penting' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'terbit'])],
            'terbit_pada' => ['nullable', 'date'],
            'berakhir_pada' => ['nullable', 'date', 'after:'.($request->filled('terbit_pada') ? 'terbit_pada' : now('Asia/Jakarta')->format('Y-m-d H:i:s'))],
        ]);
        $data['penting'] = $request->boolean('penting');
        $data['target_type'] = $data['target_type'] ?? 'all';
        if ($data['penerima'] === 'dosen') {
            $data['target_type'] = 'all';
        }
        $data['target_user_id'] = $data['target_type'] === 'student'
            ? Mahasiswa::where('nim', $data['target_nim'])->value('user_id')
            : null;
        if ($data['target_type'] === 'student' && ! $data['target_user_id']) {
            throw ValidationException::withMessages(['target_nim' => 'Mahasiswa tersebut belum memiliki akun login.']);
        }
        $data['target_periode_krs_id'] = $data['target_type'] === 'period' ? ($data['target_periode_krs_id'] ?? null) : null;
        $data['target_prodi_id'] = $data['target_type'] === 'prodi' ? ($data['target_prodi_id'] ?? null) : null;
        $data['target_angkatan'] = $data['target_type'] === 'angkatan' ? ($data['target_angkatan'] ?? null) : null;
        unset($data['target_nim']);
        foreach (['terbit_pada', 'berakhir_pada'] as $field) {
            $data[$field] = empty($data[$field]) ? null
                : Carbon::parse($data[$field], 'Asia/Jakarta')->setTimezone(config('app.timezone'));
        }
        if ($data['status'] === 'terbit' && empty($data['terbit_pada'])) {
            $data['terbit_pada'] = now();
        }

        return $data;
    }

    private function formData(Pengumuman $pengumuman): array
    {
        return [
            'pengumuman' => $pengumuman,
            'periodes' => PeriodeKrs::orderByDesc('tanggal_mulai')->get(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'targetNim' => $pengumuman->target_user_id
                ? Mahasiswa::where('user_id', $pengumuman->target_user_id)->value('nim')
                : null,
        ];
    }
}
