<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\Cpmk;
use App\Models\Jadwal;
use App\Models\MataKuliah;
use App\Models\MataKuliahRps;
use App\Models\Prodi;
use App\Models\RpsPenilaianSkema;
use App\Models\SubCpmk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ObeCurriculumController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'cpl');
        $prodis = Prodi::orderBy('nama_prodi')->get();

        $selectedProdiId = $request->query('prodi_id') ?: $prodis->first()?->id;
        $selectedProdi = $prodis->firstWhere('id', $selectedProdiId);

        $mataKuliahQuery = MataKuliah::query()->orderBy('kode_mk');
        if ($selectedProdiId) {
            $mataKuliahQuery->where('prodi_id', $selectedProdiId);
        }
        $mataKuliahs = $mataKuliahQuery->get();

        $selectedMkId = $request->query('mata_kuliah_id') ?: $mataKuliahs->first()?->id;
        $selectedMk = $mataKuliahs->firstWhere('id', $selectedMkId);

        // Data Tab 1: CPL
        $cpls = Cpl::query()
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->where(function ($sub) use ($selectedProdiId) {
                    $sub->where('prodi_id', $selectedProdiId)
                        ->orWhere('program_studi_id', $selectedProdiId);
                });
            })
            ->withCount('subCpmks')
            ->orderBy('sort_order')
            ->orderBy('kode_cpl')
            ->get();

        // Data Tab 2: CPMK & Sub-CPMK
        $cpmks = Cpmk::query()
            ->when($selectedMkId, fn ($q) => $q->where('mata_kuliah_id', $selectedMkId))
            ->with(['subCpmks.cpl'])
            ->orderBy('kode_cpmk')
            ->get();

        // CPL list for dropdown in Sub-CPMK form
        $availableCpls = Cpl::query()
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->where(function ($sub) use ($selectedProdiId) {
                    $sub->where('prodi_id', $selectedProdiId)
                        ->orWhere('program_studi_id', $selectedProdiId);
                });
            })
            ->orderBy('kode_cpl')
            ->get();

        // Data Tab 3: RPS
        $rpsList = MataKuliahRps::with('mataKuliah.prodi')
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->whereHas('mataKuliah', fn ($m) => $m->where('prodi_id', $selectedProdiId));
            })
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Data Tab 4: Monitoring & Unlock
        $monitoringJadwals = Jadwal::with(['mataKuliah.prodi', 'dosen', 'skemaPenilaian'])
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->whereHas('mataKuliah', fn ($m) => $m->where('prodi_id', $selectedProdiId));
            })
            ->orderByDesc('tahun_akademik')
            ->orderBy('mata_kuliah_id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.obe.index', compact(
            'tab',
            'prodis',
            'selectedProdiId',
            'selectedProdi',
            'mataKuliahs',
            'selectedMkId',
            'selectedMk',
            'cpls',
            'cpmks',
            'availableCpls',
            'rpsList',
            'monitoringJadwals'
        ));
    }

    // ==========================================
    // CPL ACTIONS
    // ==========================================

    public function storeCpl(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'kode_cpl' => ['required', 'string', 'max:50'],
            'nama_cpl' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        Cpl::create([
            'prodi_id' => $validated['prodi_id'],
            'program_studi_id' => $validated['prodi_id'],
            'kode_cpl' => trim($validated['kode_cpl']),
            'nama_cpl' => $validated['nama_cpl'] ? trim($validated['nama_cpl']) : null,
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
        ]);

        return redirect()
            ->route('admin.obe.index', ['tab' => 'cpl', 'prodi_id' => $validated['prodi_id']])
            ->with('success', "CPL {$validated['kode_cpl']} berhasil ditambahkan.");
    }

    public function updateCpl(Request $request, Cpl $cpl)
    {
        $validated = $request->validate([
            'prodi_id' => ['required', 'exists:prodis,id'],
            'kode_cpl' => ['required', 'string', 'max:50'],
            'nama_cpl' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $cpl->update([
            'prodi_id' => $validated['prodi_id'],
            'program_studi_id' => $validated['prodi_id'],
            'kode_cpl' => trim($validated['kode_cpl']),
            'nama_cpl' => $validated['nama_cpl'] ? trim($validated['nama_cpl']) : null,
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
        ]);

        return redirect()
            ->route('admin.obe.index', ['tab' => 'cpl', 'prodi_id' => $validated['prodi_id']])
            ->with('success', "CPL {$cpl->kode_cpl} berhasil diperbarui.");
    }

    public function destroyCpl(Cpl $cpl)
    {
        $prodiId = $cpl->prodi_id ?: $cpl->program_studi_id;
        $kode = $cpl->kode_cpl;
        $cpl->delete();

        return redirect()
            ->route('admin.obe.index', ['tab' => 'cpl', 'prodi_id' => $prodiId])
            ->with('success', "CPL {$kode} berhasil dihapus.");
    }

    // ==========================================
    // CPMK ACTIONS
    // ==========================================

    public function storeCpmk(Request $request)
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'kode_cpmk' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $mk = MataKuliah::findOrFail($validated['mata_kuliah_id']);

        Cpmk::create([
            'mata_kuliah_id' => $mk->id,
            'kode_cpmk' => trim($validated['kode_cpmk']),
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
        ]);

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $mk->prodi_id,
                'mata_kuliah_id' => $mk->id,
            ])
            ->with('success', "CPMK {$validated['kode_cpmk']} berhasil ditambahkan.");
    }

    public function updateCpmk(Request $request, Cpmk $cpmk)
    {
        $validated = $request->validate([
            'kode_cpmk' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $cpmk->update([
            'kode_cpmk' => trim($validated['kode_cpmk']),
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
        ]);

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $cpmk->mataKuliah?->prodi_id,
                'mata_kuliah_id' => $cpmk->mata_kuliah_id,
            ])
            ->with('success', "CPMK {$cpmk->kode_cpmk} berhasil diperbarui.");
    }

    public function destroyCpmk(Cpmk $cpmk)
    {
        $mkId = $cpmk->mata_kuliah_id;
        $prodiId = $cpmk->mataKuliah?->prodi_id;
        $kode = $cpmk->kode_cpmk;
        $cpmk->delete();

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $prodiId,
                'mata_kuliah_id' => $mkId,
            ])
            ->with('success', "CPMK {$kode} berhasil dihapus.");
    }

    // ==========================================
    // SUB-CPMK ACTIONS
    // ==========================================

    public function storeSubCpmk(Request $request)
    {
        $validated = $request->validate([
            'cpmk_id' => ['required', 'exists:cpmks,id'],
            'cpl_id' => ['nullable', 'exists:cpls,id'],
            'kode_sub_cpmk' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'bobot_default' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $cpmk = Cpmk::with('mataKuliah')->findOrFail($validated['cpmk_id']);

        SubCpmk::create([
            'cpmk_id' => $cpmk->id,
            'cpl_id' => $validated['cpl_id'] ?: null,
            'kode_sub_cpmk' => trim($validated['kode_sub_cpmk']),
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
            'bobot_default' => $validated['bobot_default'] !== null ? (float) $validated['bobot_default'] : null,
        ]);

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $cpmk->mataKuliah?->prodi_id,
                'mata_kuliah_id' => $cpmk->mata_kuliah_id,
            ])
            ->with('success', "Sub-CPMK {$validated['kode_sub_cpmk']} berhasil ditambahkan.");
    }

    public function updateSubCpmk(Request $request, SubCpmk $subCpmk)
    {
        $validated = $request->validate([
            'cpl_id' => ['nullable', 'exists:cpls,id'],
            'kode_sub_cpmk' => ['required', 'string', 'max:50'],
            'deskripsi' => ['nullable', 'string'],
            'bobot_default' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $subCpmk->update([
            'cpl_id' => $validated['cpl_id'] ?: null,
            'kode_sub_cpmk' => trim($validated['kode_sub_cpmk']),
            'deskripsi' => $validated['deskripsi'] ? trim($validated['deskripsi']) : null,
            'bobot_default' => $validated['bobot_default'] !== null ? (float) $validated['bobot_default'] : null,
        ]);

        $mk = $subCpmk->cpmk?->mataKuliah;

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $mk?->prodi_id,
                'mata_kuliah_id' => $mk?->id,
            ])
            ->with('success', "Sub-CPMK {$subCpmk->kode_sub_cpmk} berhasil diperbarui.");
    }

    public function destroySubCpmk(SubCpmk $subCpmk)
    {
        $mk = $subCpmk->cpmk?->mataKuliah;
        $kode = $subCpmk->kode_sub_cpmk;
        $subCpmk->delete();

        return redirect()
            ->route('admin.obe.index', [
                'tab' => 'cpmk',
                'prodi_id' => $mk?->prodi_id,
                'mata_kuliah_id' => $mk?->id,
            ])
            ->with('success', "Sub-CPMK {$kode} berhasil dihapus.");
    }

    // ==========================================
    // RPS ACTIONS
    // ==========================================

    public function storeRps(Request $request)
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'file_rps' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'target_passing_grade' => ['nullable', 'numeric', 'between:0,100'],
            'porsi_cpl_keys' => ['nullable', 'array'],
            'porsi_cpl_values' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $filePath = null;
        if ($request->hasFile('file_rps')) {
            $filePath = $request->file('file_rps')->store('rps', 'public');
        }

        // Susun porsi CPL (json: {"CPL 1": 60, "CPL 2": 40})
        $porsiCpl = [];
        if (! empty($validated['porsi_cpl_keys']) && ! empty($validated['porsi_cpl_values'])) {
            foreach ($validated['porsi_cpl_keys'] as $idx => $key) {
                $val = (float) ($validated['porsi_cpl_values'][$idx] ?? 0);
                if ($key !== '' && $val > 0) {
                    $porsiCpl[trim($key)] = $val;
                }
            }
        }

        $isActive = $request->boolean('is_active', true);

        if ($isActive) {
            MataKuliahRps::where('mata_kuliah_id', $validated['mata_kuliah_id'])->update(['is_active' => false]);
        }

        $rps = MataKuliahRps::create([
            'mata_kuliah_id' => $validated['mata_kuliah_id'],
            'tahun_akademik' => $validated['tahun_akademik'] ?: null,
            'file_rps' => $filePath,
            'target_passing_grade' => $validated['target_passing_grade'] !== null ? (float) $validated['target_passing_grade'] : 60.00,
            'porsi_cpl' => ! empty($porsiCpl) ? $porsiCpl : null,
            'is_active' => $isActive,
        ]);

        $mk = MataKuliah::find($validated['mata_kuliah_id']);

        return redirect()
            ->route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $mk?->prodi_id])
            ->with('success', "RPS Mata Kuliah {$mk?->nama_mk} berhasil disimpan.");
    }

    public function updateRps(Request $request, MataKuliahRps $rps)
    {
        $validated = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'file_rps' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'target_passing_grade' => ['nullable', 'numeric', 'between:0,100'],
            'porsi_cpl_keys' => ['nullable', 'array'],
            'porsi_cpl_values' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('file_rps')) {
            if ($rps->file_rps && Storage::disk('public')->exists($rps->file_rps)) {
                Storage::disk('public')->delete($rps->file_rps);
            }
            $rps->file_rps = $request->file('file_rps')->store('rps', 'public');
        }

        $porsiCpl = [];
        if (! empty($validated['porsi_cpl_keys']) && ! empty($validated['porsi_cpl_values'])) {
            foreach ($validated['porsi_cpl_keys'] as $idx => $key) {
                $val = (float) ($validated['porsi_cpl_values'][$idx] ?? 0);
                if ($key !== '' && $val > 0) {
                    $porsiCpl[trim($key)] = $val;
                }
            }
        }

        $isActive = $request->boolean('is_active', true);
        if ($isActive) {
            MataKuliahRps::where('mata_kuliah_id', $rps->mata_kuliah_id)
                ->where('id', '!=', $rps->id)
                ->update(['is_active' => false]);
        }

        $rps->update([
            'tahun_akademik' => $validated['tahun_akademik'] ?: null,
            'target_passing_grade' => $validated['target_passing_grade'] !== null ? (float) $validated['target_passing_grade'] : 60.00,
            'porsi_cpl' => ! empty($porsiCpl) ? $porsiCpl : null,
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $rps->mataKuliah?->prodi_id])
            ->with('success', "RPS Mata Kuliah {$rps->mataKuliah?->nama_mk} berhasil diperbarui.");
    }

    public function destroyRps(MataKuliahRps $rps)
    {
        $prodiId = $rps->mataKuliah?->prodi_id;
        $mkName = $rps->mataKuliah?->nama_mk;

        if ($rps->file_rps && Storage::disk('public')->exists($rps->file_rps)) {
            Storage::disk('public')->delete($rps->file_rps);
        }

        $rps->delete();

        return redirect()
            ->route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $prodiId])
            ->with('success', "Dokumen RPS {$mkName} berhasil dihapus.");
    }

    public function downloadRps(MataKuliahRps $rps)
    {
        if (! $rps->file_rps || ! Storage::disk('public')->exists($rps->file_rps)) {
            return back()->with('error', 'Dokumen file PDF RPS tidak ditemukan di server.');
        }

        $filename = 'RPS_' . ($rps->mataKuliah?->kode_mk ?? 'MK') . '_' . ($rps->tahun_akademik ? str_replace('/', '-', $rps->tahun_akademik) : 'Aktif') . '.pdf';

        return Storage::disk('public')->download($rps->file_rps, $filename);
    }

    // ==========================================
    // UNLOCK FINALISASI NILAI (ADMIN/KAPRODI)
    // ==========================================

    public function unlockNilai(RpsPenilaianSkema $skema)
    {
        $skema->update([
            'is_finalized' => false,
            'finalized_at' => null,
        ]);

        $mkNama = $skema->jadwal?->mataKuliah?->nama_mk ?? 'Mata Kuliah';
        $kelas = $skema->jadwal?->kelas ?? '-';

        return back()->with('success', "Kunci finalisasi nilai untuk {$mkNama} (Kelas {$kelas}) berhasil dibuka. Dosen pengampu kini dapat merevisi kembali instrumen dan input nilai.");
    }

    public function downloadRpsByCourse(MataKuliah $mataKuliah)
    {
        $rps = $mataKuliah->rpsAktif ?? $mataKuliah->rpsList()->latest()->first();
        if (! $rps || ! $rps->file_rps || ! Storage::disk('public')->exists($rps->file_rps)) {
            return back()->with('error', 'Dokumen RPS belum tersedia untuk mata kuliah ini.');
        }

        $filename = 'RPS_' . ($mataKuliah->kode_mk ?? 'MK') . '.pdf';

        return Storage::disk('public')->download($rps->file_rps, $filename);
    }
}
