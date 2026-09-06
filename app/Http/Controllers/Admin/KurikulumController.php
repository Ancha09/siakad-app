<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KurikulumController extends Controller
{
    public function index(Request $request)
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();
        $kurikulums = Kurikulum::with('prodi')
            ->when($request->filled('prodi_id'), fn ($query) => $query->where('prodi_id', $request->integer('prodi_id')))
            ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tahun_mulai')
            ->get();

        $selectedKurikulum = null;
        if ($request->filled('kurikulum')) {
            $selectedKurikulum = Kurikulum::with(['prodi', 'mataKuliahKurikulum.mataKuliah'])
                ->findOrFail($request->integer('kurikulum'));
        } elseif ($kurikulums->isNotEmpty()) {
            $selectedKurikulum = Kurikulum::with(['prodi', 'mataKuliahKurikulum.mataKuliah'])
                ->find($kurikulums->first()->id);
        }

        $mataKuliahs = $selectedKurikulum
            ? MataKuliah::where('prodi_id', $selectedKurikulum->prodi_id)
                ->orderBy('semester')->orderBy('kode_mk')->get()
            : collect();

        return view('admin.kurikulum.index', compact(
            'prodis',
            'kurikulums',
            'selectedKurikulum',
            'mataKuliahs'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validateKurikulum($request);

        DB::transaction(function () use ($data) {
            if ($data['status'] === 'Aktif') {
                Kurikulum::where('prodi_id', $data['prodi_id'])->update(['status' => 'Tidak Aktif']);
            }

            Kurikulum::create($data);
        });

        return to_route('admin.kurikulum.index')->with('success', 'Kurikulum berhasil ditambahkan.');
    }

    public function update(Request $request, Kurikulum $kurikulum)
    {
        $data = $this->validateKurikulum($request);

        if ($kurikulum->prodi_id !== (int) $data['prodi_id'] && $kurikulum->mataKuliahKurikulum()->exists()) {
            return back()->withErrors([
                'prodi_id' => 'Program studi tidak dapat diubah setelah susunan mata kuliah diisi.',
            ])->withInput();
        }

        DB::transaction(function () use ($data, $kurikulum) {
            if ($data['status'] === 'Aktif') {
                Kurikulum::where('prodi_id', $data['prodi_id'])
                    ->where('id', '!=', $kurikulum->id)
                    ->update(['status' => 'Tidak Aktif']);
            }

            $kurikulum->update($data);
        });

        return to_route('admin.kurikulum.index', ['kurikulum' => $kurikulum->id])
            ->with('success', 'Kurikulum berhasil diperbarui.');
    }

    public function destroy(Kurikulum $kurikulum)
    {
        $paths = $kurikulum->mataKuliahKurikulum()->whereNotNull('silabus_path')->pluck('silabus_path');
        $kurikulum->delete();
        Storage::disk('local')->delete($paths->all());

        return to_route('admin.kurikulum.index')->with('success', 'Kurikulum berhasil dihapus.');
    }

    public function storeMataKuliah(Request $request, Kurikulum $kurikulum)
    {
        $data = $this->validateMataKuliah($request, $kurikulum);
        $data['kurikulum_id'] = $kurikulum->id;
        $data['silabus_path'] = $this->storeSilabus($request, $kurikulum);

        KurikulumMataKuliah::create($data);

        return $this->backToKurikulum($kurikulum, 'Mata kuliah berhasil ditambahkan ke kurikulum.');
    }

    public function updateMataKuliah(Request $request, KurikulumMataKuliah $item)
    {
        $item->loadMissing('kurikulum');
        $data = $this->validateMataKuliah($request, $item->kurikulum, $item);

        if ($request->hasFile('silabus')) {
            $oldPath = $item->silabus_path;
            $data['silabus_path'] = $this->storeSilabus($request, $item->kurikulum);
            $item->update($data);
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } else {
            $item->update($data);
        }

        return $this->backToKurikulum($item->kurikulum, 'Mata kuliah dan silabus berhasil diperbarui.');
    }

    public function destroyMataKuliah(KurikulumMataKuliah $item)
    {
        $item->loadMissing('kurikulum');
        $kurikulum = $item->kurikulum;
        $path = $item->silabus_path;
        $item->delete();
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return $this->backToKurikulum($kurikulum, 'Mata kuliah dihapus dari kurikulum.');
    }

    public function downloadSilabus(KurikulumMataKuliah $item)
    {
        $item->loadMissing('mataKuliah');
        abort_unless($item->silabus_path && Storage::disk('local')->exists($item->silabus_path), 404);

        return Storage::disk('local')->download(
            $item->silabus_path,
            'Silabus-'.$item->mataKuliah->kode_mk.'.pdf'
        );
    }

    private function validateKurikulum(Request $request): array
    {
        return $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'nama_kurikulum' => ['required', 'string', 'max:255'],
            'tahun_mulai' => ['required', 'integer', 'min:2000', 'max:'.(now()->year + 10)],
            'tahun_selesai' => ['nullable', 'integer', 'gte:tahun_mulai', 'max:'.(now()->year + 20)],
            'status' => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
        ]);
    }

    private function validateMataKuliah(
        Request $request,
        Kurikulum $kurikulum,
        ?KurikulumMataKuliah $item = null
    ): array {
        return $request->validate([
            'mata_kuliah_id' => [
                'required',
                Rule::exists('mata_kuliahs', 'id')->where('prodi_id', $kurikulum->prodi_id),
                Rule::unique('kurikulum_mata_kuliah', 'mata_kuliah_id')
                    ->where('kurikulum_id', $kurikulum->id)
                    ->where('semester', $request->integer('semester'))
                    ->ignore($item?->id),
            ],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'jenis' => ['required', Rule::in(['Wajib', 'Pilihan'])],
            'silabus' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'mata_kuliah_id.unique' => 'Mata kuliah tersebut sudah ada pada semester yang dipilih.',
            'silabus.mimes' => 'Silabus harus berupa file PDF.',
            'silabus.max' => 'Ukuran silabus maksimal 5 MB.',
        ]);
    }

    private function storeSilabus(Request $request, Kurikulum $kurikulum): ?string
    {
        return $request->file('silabus')?->store('silabus/'.$kurikulum->id, 'local');
    }

    private function backToKurikulum(Kurikulum $kurikulum, string $message)
    {
        return to_route('admin.kurikulum.index', ['kurikulum' => $kurikulum->id])
            ->with('success', $message);
    }
}
