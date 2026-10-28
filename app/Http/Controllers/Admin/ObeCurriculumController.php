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
use App\Services\ObeAssessmentService;
use App\Services\ReportExporter;
use App\Services\RpsPdfParserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            $mataKuliahQuery->where(function ($q) use ($selectedProdiId) {
                $q->where('prodi_id', $selectedProdiId)
                    ->orWhereNull('prodi_id')
                    ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $selectedProdiId));
            });
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
                $q->whereHas('mataKuliah', function ($m) use ($selectedProdiId) {
                    $m->where('prodi_id', $selectedProdiId)
                        ->orWhereNull('prodi_id')
                        ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $selectedProdiId))
                        ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($selectedProdiId) {
                            $c->where('prodi_id', $selectedProdiId)
                                ->orWhere('program_studi_id', $selectedProdiId);
                        });
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Data Tab 4: Monitoring & Unlock
        $monitoringJadwals = Jadwal::with(['mataKuliah.prodi', 'dosen', 'skemaPenilaian'])
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->whereHas('mataKuliah', function ($m) use ($selectedProdiId) {
                    $m->where('prodi_id', $selectedProdiId)
                        ->orWhereNull('prodi_id')
                        ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $selectedProdiId));
                });
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

        $kodeCpl = trim($validated['kode_cpl']);
        $deskripsiCpl = ! empty($validated['deskripsi']) ? trim($validated['deskripsi']) : null;
        $namaCpl = ! empty($validated['nama_cpl']) ? trim($validated['nama_cpl']) : ($deskripsiCpl ?: $kodeCpl);

        Cpl::create([
            'prodi_id' => $validated['prodi_id'],
            'program_studi_id' => $validated['prodi_id'],
            'kode_cpl' => $kodeCpl,
            'nama_cpl' => $namaCpl,
            'deskripsi' => $deskripsiCpl,
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

        $kodeCpl = trim($validated['kode_cpl']);
        $deskripsiCpl = ! empty($validated['deskripsi']) ? trim($validated['deskripsi']) : null;
        $namaCpl = ! empty($validated['nama_cpl']) ? trim($validated['nama_cpl']) : ($deskripsiCpl ?: $kodeCpl);

        $cpl->update([
            'prodi_id' => $validated['prodi_id'],
            'program_studi_id' => $validated['prodi_id'],
            'kode_cpl' => $kodeCpl,
            'nama_cpl' => $namaCpl,
            'deskripsi' => $deskripsiCpl,
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
    // RPS ACTIONS & AUTO-EXTRACT
    // ==========================================

    public function previewRps(Request $request, RpsPdfParserService $parserService)
    {
        if ($request->isMethod('get')) {
            $sessionPreview = session('obe_rps_preview');
            $tempPath = $sessionPreview['temp_path'] ?? null;
            if (! $tempPath || ! Storage::disk('public')->exists($tempPath)) {
                // Fallback ke file terbaru di temp_rps jika baru saja di-upload sebelum sesi aktif
                $tempFiles = Storage::disk('public')->files('temp_rps');
                if (! empty($tempFiles)) {
                    usort($tempFiles, fn ($a, $b) => Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a));
                    $tempPath = $tempFiles[0];
                }
            }
            if (! $tempPath || ! Storage::disk('public')->exists($tempPath)) {
                return redirect()
                    ->route('admin.obe.index', ['tab' => 'rps'])
                    ->with('info', 'Silakan pilih dan unggah kembali dokumen PDF RPS untuk melakukan pratinjau.');
            }
            $selectedMkId = $sessionPreview['mata_kuliah_id'] ?? null;
            $selectedProdiId = $sessionPreview['prodi_id'] ?? null;
        } else {
            $validated = $request->validate([
                'file_rps' => ['required', 'file', 'mimes:pdf', 'max:10240'],
                'mata_kuliah_id' => ['nullable', 'exists:mata_kuliahs,id'],
                'prodi_id' => ['nullable', 'exists:prodis,id'],
            ]);

            $file = $request->file('file_rps');
            $tempPath = $file->store('temp_rps', 'public');
            $selectedMkId = $validated['mata_kuliah_id'] ?? null;
            $selectedProdiId = $validated['prodi_id'] ?? null;

            session([
                'obe_rps_preview' => [
                    'temp_path' => $tempPath,
                    'mata_kuliah_id' => $selectedMkId,
                    'prodi_id' => $selectedProdiId,
                ],
            ]);
        }

        try {
            $fullTempPath = storage_path('app/public/' . ltrim($tempPath, '/'));

            $extracted = $parserService->extract($fullTempPath);

            $matchedMk = null;

            if ($selectedMkId) {
                $matchedMk = MataKuliah::with('prodi')->find($selectedMkId);
            } else {
                if (! empty($extracted['metadata']['kode_mk'])) {
                    $matchedMk = MataKuliah::with('prodi')
                        ->where('kode_mk', $extracted['metadata']['kode_mk'])
                        ->first();
                }
                if (! $matchedMk && ! empty($extracted['metadata']['nama_mk'])) {
                    $matchedMk = MataKuliah::with('prodi')
                        ->where('nama_mk', 'LIKE', '%' . $extracted['metadata']['nama_mk'] . '%')
                        ->first();
                }
            }

            if (! $selectedProdiId && $matchedMk) {
                $selectedProdiId = $matchedMk->prodi_id;
            }
            if (! $selectedProdiId && ! empty($extracted['metadata']['prodi'])) {
                $matchedProdi = Prodi::where('nama_prodi', 'LIKE', '%' . $extracted['metadata']['prodi'] . '%')->first();
                if ($matchedProdi) {
                    $selectedProdiId = $matchedProdi->id;
                }
            }

            $allProdis = Prodi::orderBy('nama_prodi')->get();
            $allMataKuliahs = MataKuliah::with('prodi')->orderBy('nama_mk')->get();

            return view('admin.obe.preview-rps', [
                'extracted' => $extracted,
                'tempPath' => $tempPath,
                'matchedMk' => $matchedMk,
                'selectedMkId' => $matchedMk?->id ?? $selectedMkId,
                'selectedProdiId' => $selectedProdiId,
                'allProdis' => $allProdis,
                'allMataKuliahs' => $allMataKuliahs,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal memproses dokumen RPS: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $selectedProdiId])
                ->with('error', 'Gagal memproses dokumen RPS: ' . $e->getMessage());
        }
    }

    public function applyRps(Request $request, RpsPdfParserService $parserService, \App\Services\ObeAssessmentService $obeService)
    {
        $validated = $request->validate([
            'mata_kuliah_id' => ['required', 'string'],
            'prodi_id' => ['required', 'exists:prodis,id'],
            'temp_path' => ['required', 'string'],
            'meta_kode_mk' => ['nullable', 'string', 'max:50'],
            'meta_nama_mk' => ['nullable', 'string', 'max:255'],
            'meta_sks' => ['nullable', 'numeric'],
            'meta_semester' => ['nullable', 'string', 'max:50'],
            'rubrik_lab_cpl_json' => ['nullable', 'string'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'target_passing_grade' => ['nullable', 'numeric', 'between:0,100'],
            'is_active' => ['nullable', 'boolean'],
            'cpls' => ['nullable', 'array'],
            'cpmks' => ['nullable', 'array'],
            'sub_cpmks' => ['nullable', 'array'],
            'komponen_nama' => ['nullable', 'array'],
            'komponen_bobot' => ['nullable', 'array'],
            'porsi_cpl_keys' => ['nullable', 'array'],
            'porsi_cpl_values' => ['nullable', 'array'],
        ]);

        $prodiId = (int) $validated['prodi_id'];

        if ($validated['mata_kuliah_id'] === 'new') {
            $kodeMk = trim($validated['meta_kode_mk'] ?? '') ?: ('MK-' . strtoupper(substr(uniqid(), -5)));
            $namaMk = trim($validated['meta_nama_mk'] ?? '') ?: 'Mata Kuliah RPS Baru';
            $sks = ! empty($validated['meta_sks']) ? (int) $validated['meta_sks'] : 3;
            $semester = $parserService->parseSemesterInt($validated['meta_semester'] ?? '1');

            $mataKuliah = MataKuliah::firstOrCreate(
                ['kode_mk' => $kodeMk],
                [
                    'nama_mk' => $namaMk,
                    'sks' => $sks,
                    'semester' => $semester,
                    'prodi_id' => $prodiId,
                ]
            );
        } else {
            $mataKuliah = MataKuliah::findOrFail($validated['mata_kuliah_id']);
        }

        if (! $mataKuliah->prodi_id) {
            $mataKuliah->update(['prodi_id' => $prodiId]);
        }

        $tempPath = $validated['temp_path'];

        DB::beginTransaction();
        try {
            $permanentPath = null;
            $cleanCode = preg_replace('/[^A-Za-z0-9_\-]/', '_', $mataKuliah->kode_mk ?: 'MK');
            if (Storage::disk('public')->exists($tempPath)) {
                $extension = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'pdf';
                $cleanTahun = $validated['tahun_akademik'] ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $validated['tahun_akademik']) : time();
                $permanentPath = "rps/RPS_{$cleanCode}_{$cleanTahun}_" . uniqid() . ".{$extension}";

                Storage::disk('public')->copy($tempPath, $permanentPath);
            } else {
                // Fallback jika file temp_rps sudah terpindah ke folder rps pada percobaan sebelumnya
                $rpsFiles = array_values(array_filter(
                    Storage::disk('public')->files('rps'),
                    fn ($f) => str_starts_with(basename($f), "RPS_{$cleanCode}_")
                ));
                if (! empty($rpsFiles)) {
                    usort($rpsFiles, fn ($a, $b) => Storage::disk('public')->lastModified($b) <=> Storage::disk('public')->lastModified($a));
                    $permanentPath = $rpsFiles[0];
                }
            }

            // 1. Format Komponen Bobot Default
            $komponenBobotDefault = [];
            if (! empty($validated['komponen_nama']) && ! empty($validated['komponen_bobot'])) {
                foreach ($validated['komponen_nama'] as $i => $nama) {
                    $namaClean = trim($nama);
                    $bobot = (float) ($validated['komponen_bobot'][$i] ?? 0);
                    if ($namaClean !== '' && $bobot > 0) {
                        $komponenBobotDefault[$namaClean] = $bobot;
                    }
                }
            }

            // 2. Format Porsi CPL
            $porsiCpl = [];
            if (! empty($validated['porsi_cpl_keys']) && ! empty($validated['porsi_cpl_values'])) {
                foreach ($validated['porsi_cpl_keys'] as $i => $cplKey) {
                    $val = (float) ($validated['porsi_cpl_values'][$i] ?? 0);
                    if ($cplKey !== '' && $val > 0) {
                        $porsiCpl[trim($cplKey)] = $val;
                    }
                }
            }

            $isActive = $request->boolean('is_active', true);
            if ($isActive) {
                MataKuliahRps::where('mata_kuliah_id', $mataKuliah->id)->update(['is_active' => false]);
            }

            // 3. Simpan MataKuliahRps
            $rps = MataKuliahRps::create([
                'mata_kuliah_id' => $mataKuliah->id,
                'tahun_akademik' => $validated['tahun_akademik'] ?: null,
                'file_rps' => $permanentPath,
                'file_rps_path' => $permanentPath,
                'target_passing_grade' => $validated['target_passing_grade'] !== null ? (float) $validated['target_passing_grade'] : 60.00,
                'porsi_cpl' => ! empty($porsiCpl) ? $porsiCpl : null,
                'komponen_bobot_default' => ! empty($komponenBobotDefault) ? $komponenBobotDefault : null,
                'is_active' => $isActive,
            ]);

            // 4. Sinkronisasi CPL
            $cplMap = [];
            if (! empty($validated['cpls'])) {
                foreach ($validated['cpls'] as $cplData) {
                    $kode = preg_replace('/\s+/', ' ', strtoupper(trim($cplData['kode_cpl'] ?? '')));
                    if (! $kode) {
                        continue;
                    }

                    $cpl = Cpl::where(function ($q) use ($prodiId) {
                        $q->where('prodi_id', $prodiId)->orWhere('program_studi_id', $prodiId);
                    })->where('kode_cpl', $kode)->first();

                    $deskripsiCpl = ! empty($cplData['deskripsi']) ? trim($cplData['deskripsi']) : null;
                    $namaCpl = ! empty($cplData['nama_cpl']) ? trim($cplData['nama_cpl']) : ($deskripsiCpl ?: $kode);

                    if ($cpl) {
                        $updatesCpl = [];
                        if (empty($cpl->deskripsi) && $deskripsiCpl) {
                            $updatesCpl['deskripsi'] = $deskripsiCpl;
                        }
                        if (empty($cpl->nama_cpl)) {
                            $updatesCpl['nama_cpl'] = $namaCpl;
                        }
                        if (! empty($updatesCpl)) {
                            $cpl->update($updatesCpl);
                        }
                    } else {
                        $cpl = Cpl::create([
                            'prodi_id' => $prodiId,
                            'program_studi_id' => $prodiId,
                            'kode_cpl' => $kode,
                            'nama_cpl' => $namaCpl,
                            'deskripsi' => $deskripsiCpl,
                        ]);
                    }
                    $cplMap[$kode] = $cpl;
                }
            }

            // 5. Sinkronisasi CPMK
            $cpmkMap = [];
            $keepCpmkIds = [];
            if (! empty($validated['cpmks'])) {
                foreach ($validated['cpmks'] as $cpmkData) {
                    $kode = preg_replace('/\s+/', ' ', strtoupper(trim($cpmkData['kode_cpmk'] ?? '')));
                    if (! $kode) {
                        continue;
                    }

                    $cpmk = Cpmk::firstOrCreate(
                        [
                            'mata_kuliah_id' => $mataKuliah->id,
                            'kode_cpmk' => $kode,
                        ],
                        [
                            'deskripsi' => ! empty($cpmkData['deskripsi']) ? trim($cpmkData['deskripsi']) : null,
                        ]
                    );

                    if (! empty($cpmkData['deskripsi'])) {
                        $cpmk->update(['deskripsi' => trim($cpmkData['deskripsi'])]);
                    }

                    $cpmkMap[$kode] = $cpmk;
                    $keepCpmkIds[] = $cpmk->id;
                }
            }

            // 6. Sinkronisasi Sub-CPMK (unik per mata_kuliah_id + kode_sub_cpmk agar tidak duplikat saat pindah CPMK)
            $keepSubIds = [];
            if (! empty($validated['sub_cpmks'])) {
                foreach ($validated['sub_cpmks'] as $subData) {
                    $kodeSub = trim($subData['kode_sub_cpmk'] ?? '');
                    if (! $kodeSub) {
                        continue;
                    }

                    $cpmkKode = preg_replace('/\s+/', ' ', strtoupper(trim($subData['cpmk_kode'] ?? ($subData['cpmk_terkait'] ?? ''))));
                    $cpmk = $cpmkMap[$cpmkKode] ?? (isset($cpmkMap) && count($cpmkMap) ? reset($cpmkMap) : null);
                    if (! $cpmk) {
                        $cpmk = Cpmk::where('mata_kuliah_id', $mataKuliah->id)->first();
                    }

                    if (! $cpmk) {
                        $cpmk = Cpmk::create([
                            'mata_kuliah_id' => $mataKuliah->id,
                            'kode_cpmk' => 'CPMK 1',
                            'deskripsi' => 'Capaian Pembelajaran Mata Kuliah ' . $mataKuliah->nama_mk,
                        ]);
                        $cpmkMap['CPMK 1'] = $cpmk;
                        $keepCpmkIds[] = $cpmk->id;
                    }

                    $rawCplKode = trim($subData['cpl_kode'] ?? ($subData['cpl_terkait'] ?? ''));
                    // Ambil kode CPL pertama jika berisi daftar dipisah koma
                    $firstCplPart = trim(explode(',', $rawCplKode)[0] ?? '');
                    $cplKode = preg_replace('/\s+/', ' ', strtoupper($firstCplPart));
                    $cplId = null;
                    if ($cplKode && isset($cplMap[$cplKode])) {
                        $cplId = $cplMap[$cplKode]->id;
                    } elseif ($cplKode) {
                        $existingCpl = Cpl::where(function ($q) use ($prodiId) {
                            $q->where('prodi_id', $prodiId)->orWhere('program_studi_id', $prodiId);
                        })->where('kode_cpl', $cplKode)->first();
                        $cplId = $existingCpl?->id;
                    }

                    $existingSub = SubCpmk::whereHas('cpmk', function ($q) use ($mataKuliah) {
                        $q->where('mata_kuliah_id', $mataKuliah->id);
                    })->where('kode_sub_cpmk', $kodeSub)->first();

                    $subPayload = [
                        'cpmk_id' => $cpmk->id,
                        'cpl_id' => $cplId,
                        'kode_sub_cpmk' => $kodeSub,
                        'deskripsi' => ! empty($subData['deskripsi']) ? trim($subData['deskripsi']) : null,
                        'bobot_default' => isset($subData['bobot_default']) && $subData['bobot_default'] !== '' ? (float) $subData['bobot_default'] : null,
                    ];

                    if ($existingSub) {
                        $existingSub->update($subPayload);
                        $keepSubIds[] = $existingSub->id;
                    } else {
                        $newSub = SubCpmk::create($subPayload);
                        $keepSubIds[] = $newSub->id;
                    }
                }

                // Bersihkan Sub-CPMK lama pada mata kuliah ini yang tidak ada lagi di RPS baru
                if (! empty($keepSubIds)) {
                    SubCpmk::whereHas('cpmk', function ($q) use ($mataKuliah) {
                        $q->where('mata_kuliah_id', $mataKuliah->id);
                    })->whereNotIn('id', $keepSubIds)->delete();
                }

                // Bersihkan CPMK lama yang sudah tidak memiliki Sub-CPMK dan tidak ada di RPS baru
                if (! empty($keepCpmkIds)) {
                    Cpmk::where('mata_kuliah_id', $mataKuliah->id)
                        ->whereNotIn('id', $keepCpmkIds)
                        ->doesntHave('subCpmks')
                        ->delete();
                }
            }

            // 7. Sinkronisasi otomatis matriks penilaian untuk jadwal mata kuliah ini yang belum difinalisasi
            $jadwals = Jadwal::with('mataKuliah')->where('mata_kuliah_id', $mataKuliah->id)->get();
            foreach ($jadwals as $j) {
                $skema = RpsPenilaianSkema::where('jadwal_id', $j->id)->where('is_finalized', false)->first();
                if ($skema) {
                    $obeService->seedDefaultKomponenFromRps($skema, $j, true);
                }
            }

            DB::commit();

            if ($tempPath && str_starts_with($tempPath, 'temp_rps/') && Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
            session()->forget('obe_rps_preview');

            return redirect()
                ->route('admin.obe.index', [
                    'tab' => 'rps',
                    'prodi_id' => $prodiId,
                    'mata_kuliah_id' => $mataKuliah->id,
                ])
                ->with('success', "RPS Mata Kuliah {$mataKuliah->nama_mk} ({$mataKuliah->kode_mk}) beserta CPL, CPMK, Sub-CPMK, dan bobot instrumen berhasil diterapkan ke kurikulum!");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Gagal menerapkan kurikulum RPS: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('admin.obe.rps.preview')
                ->withInput()
                ->with('error', 'Gagal menerapkan kurikulum RPS: ' . $e->getMessage());
        }
    }

    public function storeRps(Request $request)
    {
        $validated = $request->validate([
            'prodi_id' => ['nullable', 'exists:prodis,id'],
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
            'file_rps_path' => $filePath,
            'target_passing_grade' => $validated['target_passing_grade'] !== null ? (float) $validated['target_passing_grade'] : 60.00,
            'porsi_cpl' => ! empty($porsiCpl) ? $porsiCpl : null,
            'is_active' => $isActive,
        ]);

        $mk = MataKuliah::find($validated['mata_kuliah_id']);
        $redirectProdiId = $validated['prodi_id'] ?? $mk?->prodi_id;

        if ($mk && ! $mk->prodi_id && $redirectProdiId) {
            $mk->update(['prodi_id' => $redirectProdiId]);
        }

        return redirect()
            ->route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $redirectProdiId])
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
        $filePath = $rps->effective_file_path;
        if (! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return back()->with('error', 'Dokumen file PDF RPS tidak ditemukan di server.');
        }

        $filename = 'RPS_' . ($rps->mataKuliah?->kode_mk ?? 'MK') . '_' . ($rps->tahun_akademik ? str_replace('/', '-', $rps->tahun_akademik) : 'Aktif') . '.pdf';

        return response()->download(storage_path('app/public/' . ltrim($filePath, '/')), $filename);
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

    public function updateKomponenStandar(Request $request, Jadwal $jadwal, ObeAssessmentService $obeService)
    {
        $rawStandar = $request->input('komponen_standar', []);
        $komponenStandar = [];
        $totalStandar = 0.0;

        foreach ($rawStandar as $ks) {
            $nama = trim($ks['nama'] ?? '');
            $bobot = (float) ($ks['bobot'] ?? 0);
            if ($nama !== '' && $bobot > 0) {
                $komponenStandar[] = [
                    'nama' => $nama,
                    'bobot' => $bobot,
                ];
                $totalStandar += $bobot;
            }
        }

        if (empty($komponenStandar) || round($totalStandar, 1) != 100.0) {
            return back()->with('error', 'Total bobot komponen penilaian standar harus tepat 100%. Saat ini: ' . round($totalStandar, 1) . '%.');
        }

        $skema = RpsPenilaianSkema::firstOrCreate(
            ['jadwal_id' => $jadwal->id],
            ['dosen_id' => $jadwal->dosen_id]
        );

        $skema->update(['komponen_standar' => $komponenStandar]);
        $obeService->recalculateStandardGradesForJadwal($jadwal, $skema->fresh());

        $mkNama = $jadwal->mataKuliah?->nama_mk ?? 'Mata Kuliah';

        return back()->with('success', "Komponen & bobot penilaian standar untuk {$mkNama} (Kelas {$jadwal->kelas}) berhasil diperbarui oleh Admin dan nilai mahasiswa terkait telah disinkronkan.");
    }

    public function downloadRpsByCourse(MataKuliah $mataKuliah)
    {
        $rps = $mataKuliah->rpsAktif ?? $mataKuliah->rpsList()->latest()->first();
        $filePath = $rps?->effective_file_path;
        if (! $rps || ! $filePath || ! Storage::disk('public')->exists($filePath)) {
            return back()->with('error', 'Dokumen RPS belum tersedia untuk mata kuliah ini.');
        }

        $filename = 'RPS_' . ($mataKuliah->kode_mk ?? 'MK') . '.pdf';

        return response()->download(storage_path('app/public/' . ltrim($filePath, '/')), $filename);
    }

    // ==========================================
    // TOGGLE STATUS AKTIVASI OBE PROGRAM STUDI
    // ==========================================

    public function toggleProdi(Request $request, Prodi $prodi)
    {
        $status = $request->has('is_obe_active')
            ? (bool) $request->input('is_obe_active')
            : ! $prodi->is_obe_active;

        $prodi->update(['is_obe_active' => $status]);

        $statusText = $status ? 'diaktifkan (ON)' : 'dinonaktifkan (OFF)';
        $msg = "Status Kurikulum OBE untuk Program Studi {$prodi->nama_prodi} berhasil {$statusText}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_obe_active' => $status,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    // ==========================================
    // EXPORT EXCEL & PDF KURIKULUM OBE & RPS
    // ==========================================

    public function exportExcel(Request $request, ReportExporter $exporter)
    {
        $data = $this->collectObeExportData($request);
        $prodiName = $data['selectedProdi']
            ? ($data['selectedProdi']->jenjang . ' ' . $data['selectedProdi']->nama_prodi)
            : 'Semua Program Studi';

        $cplRows = $data['cpls']->map(fn (Cpl $cpl) => [
            $cpl->kode_cpl,
            $cpl->nama_cpl ?: '-',
            $cpl->deskripsi ?: '-',
            $cpl->sub_cpmks_count ?? 0,
        ]);

        $cpmkRows = [];
        foreach ($data['cpmks'] as $cpmk) {
            if ($cpmk->subCpmks->isEmpty()) {
                $cpmkRows[] = [
                    $cpmk->mataKuliah?->kode_mk ?? '-',
                    $cpmk->mataKuliah?->nama_mk ?? '-',
                    $cpmk->kode_cpmk,
                    $cpmk->deskripsi ?: '-',
                    '-',
                    '-',
                    '-',
                    '-',
                ];
                continue;
            }
            foreach ($cpmk->subCpmks as $sub) {
                $cpmkRows[] = [
                    $cpmk->mataKuliah?->kode_mk ?? '-',
                    $cpmk->mataKuliah?->nama_mk ?? '-',
                    $cpmk->kode_cpmk,
                    $cpmk->deskripsi ?: '-',
                    $sub->kode_sub_cpmk,
                    $sub->deskripsi ?: '-',
                    $sub->cpl?->kode_cpl ?? 'Belum dipetakan',
                    $sub->bobot_default !== null ? $sub->bobot_default . '%' : '-',
                ];
            }
        }

        $rpsRows = $data['rpsItems']->map(function (MataKuliahRps $rps) {
            $porsiText = '-';
            if (! empty($rps->porsi_cpl) && is_array($rps->porsi_cpl)) {
                $parts = [];
                foreach ($rps->porsi_cpl as $k => $v) {
                    $parts[] = "{$k}: {$v}%";
                }
                $porsiText = implode(', ', $parts);
            }

            return [
                $rps->mataKuliah?->kode_mk ?? '-',
                $rps->mataKuliah?->nama_mk ?? '-',
                $rps->mataKuliah?->sks ?? '-',
                $rps->mataKuliah?->semester ?? '-',
                $rps->tahun_akademik ?: 'Reguler',
                $rps->target_passing_grade,
                $porsiText,
                $rps->is_active ? 'Aktif' : 'Arsip',
            ];
        });

        $sheets = [
            [
                'title' => 'Daftar CPL Prodi',
                'headings' => ['Kode CPL', 'Nama Singkat CPL', 'Deskripsi Capaian Pembelajaran', 'Jumlah Sub-CPMK Terkait'],
                'rows' => $cplRows,
            ],
            [
                'title' => 'Dokumen & Target RPS',
                'headings' => ['Kode MK', 'Nama Mata Kuliah', 'SKS', 'Semester', 'Tahun Ajaran', 'Passing Grade', 'Target Porsi CPL', 'Status'],
                'rows' => $rpsRows,
            ],
            [
                'title' => 'Matriks CPMK & Sub-CPMK',
                'headings' => ['Kode MK', 'Nama Mata Kuliah', 'Kode CPMK', 'Deskripsi CPMK', 'Kode Sub-CPMK', 'Deskripsi Sub-CPMK', 'CPL Pemetaan', 'Bobot Default'],
                'rows' => $cpmkRows,
            ],
        ];

        $slug = Str::slug($prodiName ?: 'prodi');
        $filename = 'kurikulum-obe-rps-' . $slug . '-' . now()->format('Ymd-His') . '.xlsx';

        return $exporter->excel(
            $filename,
            'Laporan Kurikulum OBE & RPS — ' . $prodiName,
            'Program Studi: ' . $prodiName,
            $sheets
        );
    }

    public function exportPdf(Request $request)
    {
        $data = $this->collectObeExportData($request);
        $prodiName = $data['selectedProdi']
            ? ($data['selectedProdi']->jenjang . ' ' . $data['selectedProdi']->nama_prodi)
            : 'Semua Program Studi';

        $slug = Str::slug($prodiName ?: 'prodi');
        $filename = 'kurikulum-obe-rps-' . $slug . '-' . now()->format('Ymd-His') . '.pdf';

        return Pdf::loadView('admin.obe.pdf', $data + [
            'prodiName' => $prodiName,
            'filterDescription' => 'Program Studi: ' . $prodiName,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function collectObeExportData(Request $request): array
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();
        $selectedProdiId = $request->query('prodi_id') ?: $prodis->first()?->id;
        $selectedProdi = $prodis->firstWhere('id', $selectedProdiId);

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

        $rpsItems = MataKuliahRps::with('mataKuliah.prodi')
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->whereHas('mataKuliah', function ($m) use ($selectedProdiId) {
                    $m->where('prodi_id', $selectedProdiId)
                        ->orWhereNull('prodi_id')
                        ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $selectedProdiId))
                        ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($selectedProdiId) {
                            $c->where('prodi_id', $selectedProdiId)
                                ->orWhere('program_studi_id', $selectedProdiId);
                        });
                });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $cpmks = Cpmk::with(['mataKuliah', 'subCpmks.cpl'])
            ->when($selectedProdiId, function ($q) use ($selectedProdiId) {
                $q->whereHas('mataKuliah', function ($m) use ($selectedProdiId) {
                    $m->where('prodi_id', $selectedProdiId)
                        ->orWhereNull('prodi_id')
                        ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $selectedProdiId))
                        ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($selectedProdiId) {
                            $c->where('prodi_id', $selectedProdiId)
                                ->orWhere('program_studi_id', $selectedProdiId);
                        });
                });
            })
            ->orderBy('mata_kuliah_id')
            ->orderBy('kode_cpmk')
            ->get();

        return compact('selectedProdi', 'selectedProdiId', 'cpls', 'rpsItems', 'cpmks');
    }
}
