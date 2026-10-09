<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\MahasiswaNilaiKomponen;
use App\Models\RpsPenilaianKomponen;
use App\Models\RpsPenilaianSkema;
use App\Services\ObeAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NilaiController extends Controller
{
    // ===================== DAFTAR JADWAL DOSEN =====================
    public function index()
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $jadwals = Jadwal::with([
                'mataKuliah',
                'ruangan',
                'dosen',
                'dosens',
                'skemaPenilaian.komponens',
            ])
            ->untukDosen($dosen->id)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view('dosen.nilai.index', compact('jadwals'));
    }

    // ===================== REKAP NILAI PER KELAS =====================
    public function rekap()
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $jadwals = Jadwal::with(['mataKuliah', 'ruangan', 'dosen', 'dosens'])
            ->untukDosen($dosen->id)
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        foreach ($jadwals as $jadwal) {
            $khs = Khs::whereHas('krs', function ($q) use ($jadwal) {
                $q->where('jadwal_id', $jadwal->id)->where('is_manual', false);
            })->get();

            $jadwal->jumlah = $khs->count();
            $jadwal->rata = $khs->count() ? round($khs->avg('nilai_angka'), 2) : 0;
            $jadwal->tertinggi = $khs->count() ? $khs->max('nilai_angka') : 0;
            $jadwal->terendah = $khs->count() ? $khs->min('nilai_angka') : 0;

            // Lulus jika nilai >= 60
            $jadwal->lulus = $khs->where('nilai_angka', '>=', 60)->count();
            $jadwal->tidak_lulus = $khs->where('nilai_angka', '<', 60)->count();
        }

        return view('dosen.nilai.rekap', compact('jadwals'));
    }

    // ===================== HALAMAN PENILAIAN OBE (3 TAB) =====================
    public function show(Jadwal $jadwal, Request $request, ObeAssessmentService $obeService)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        // Cegah dosen membuka jadwal milik dosen lain
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $jadwal->load([
            'mataKuliah.prodi',
            'mataKuliah.cpmks.subCpmks.cpl',
            'kelasRelasi',
            'ruangan',
            'dosen',
            'dosens',
        ]);

        $rps = $jadwal->mataKuliah->rpsAktif ?? $jadwal->mataKuliah->rpsList()->latest()->first();
        $skema = $obeService->getOrCreateSkema($jadwal, $dosen->id);
        $skema->load(['komponens.subCpmk.cpl', 'komponens.subCpmk.cpmk']);

        $krsList = Krs::with(['mahasiswa', 'nilaiKomponens', 'khs'])
            ->where('is_manual', false)
            ->where('jadwal_id', $jadwal->id)
            ->where('status', 'Disetujui')
            ->get();

        // Data matriks alokasi bobot instrumen ke Sub-CPMK
        $matrixData = $skema->matrix_alokasi;
        if (empty($matrixData) || empty($matrixData['rows'])) {
            $matrixData = $obeService->getDefaultMatrixForJadwal($jadwal);
            if ($skema->komponens()->count() === 0 && ! empty($matrixData['rows'])) {
                $obeService->syncKomponensFromMatrix($skema, $matrixData);
            }
        }

        // Hitung nilai mahasiswa dan status CPL
        $studentAssessments = [];
        foreach ($krsList as $krs) {
            $studentAssessments[$krs->id] = $obeService->hitungNilaiMahasiswa($krs, $skema, $rps);
        }

        // Hitung analitik agregat kelas untuk Tab 3
        $capaianKelas = $obeService->hitungCapaianKelas($jadwal, $skema, $rps);

        // Tentukan tab aktif
        $defaultTab = $skema->komponens->count() > 0 ? 'input' : 'pengaturan';
        $tab = $request->query('tab', $defaultTab);

        return view('dosen.nilai.obe', compact(
            'jadwal',
            'rps',
            'skema',
            'matrixData',
            'krsList',
            'studentAssessments',
            'capaianKelas',
            'tab'
        ));
    }

    // ===================== SIMPAN PENGATURAN SKEMA INSTRUMEN (TAB 1) =====================
    public function saveSkema(Request $request, Jadwal $jadwal, ObeAssessmentService $obeService)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $skema = RpsPenilaianSkema::firstOrCreate(
            ['jadwal_id' => $jadwal->id],
            ['dosen_id' => $dosen->id]
        );

        if ($skema->is_finalized) {
            return back()->with('error', 'Skema penilaian telah difinalisasi dan tidak dapat diubah tanpa izin Admin/Kaprodi.');
        }

        if ($request->input('action') === 'reset_rps') {
            $obeService->seedDefaultKomponenFromRps($skema, $jadwal, true);
            return redirect()
                ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'pengaturan'])
                ->with('success', 'Matriks alokasi instrumen penilaian berhasil dimuat ulang dari template dokumen RPS baku.');
        }

        if ($request->input('action') === 'return_rps') {
            $catatan = $request->input('catatan_revisi', 'Dosen meminta tinjauan ulang terkait temuan ketidaksesuaian tabel komponen dan pembagian soal pada RPS.');
            // Rekam log atau kirim notifikasi jika diperlukan
            return redirect()
                ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'pengaturan'])
                ->with('success', 'Temuan audit RPS dan permohonan revisi berhasil dikembalikan kepada Tim Penyusun Kurikulum / Kaprodi.');
        }

        // Cek jika request berasal dari Matriks 2D Alokasi
        if ($request->has('matrix_rows')) {
            $rawRows = $request->input('matrix_rows', []);
            $rows = [];
            $totalBobot = 0.0;

            foreach ($rawRows as $r) {
                $nama = trim($r['nama'] ?? '');
                if (empty($nama)) {
                    continue;
                }

                $keterangan = trim($r['keterangan'] ?? '');
                $allocations = [];

                if (! empty($r['allocations']) && is_array($r['allocations'])) {
                    foreach ($r['allocations'] as $subId => $val) {
                        $v = (float) $val;
                        if ($v > 0) {
                            $allocations[(string) $subId] = $v;
                            $totalBobot += $v;
                        }
                    }
                }

                $rows[] = [
                    'nama' => $nama,
                    'keterangan' => $keterangan,
                    'allocations' => $allocations,
                ];
            }

            $isDraft = $request->input('action') === 'draft';

            if (! $isDraft && round($totalBobot, 1) != 100.0) {
                return back()->withInput()->with('error', "Total bobot Sub-CPMK harus tepat 100%. Saat ini terhitung: " . round($totalBobot, 1) . "%. Anda dapat memilih 'Simpan Draf' jika pengaturan belum selesai.");
            }

            $defaultPreset = $obeService->getDefaultMatrixForJadwal($jadwal);
            $matrixData = [
                'rows' => $rows,
                'komponen_rps' => $skema->matrix_alokasi['komponen_rps'] ?? $defaultPreset['komponen_rps'],
                'temuan' => $skema->matrix_alokasi['temuan'] ?? $defaultPreset['temuan'],
            ];

            $obeService->syncKomponensFromMatrix($skema, $matrixData);

            $msg = $isDraft
                ? 'Draf matriks alokasi bobot RPS berhasil disimpan.'
                : 'Matriks alokasi bobot RPS berhasil disimpan dan disinkronkan ke daftar nilai mahasiswa.';

            $targetTab = $isDraft ? 'pengaturan' : 'input';

            return redirect()
                ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => $targetTab])
                ->with('success', $msg);
        }

        // Fallback untuk format linear lama jika ada
        $validated = $request->validate([
            'nama_instrumen' => ['required', 'array', 'min:1'],
            'nama_instrumen.*' => ['required', 'string', 'max:100'],
            'sub_cpmk_id' => ['required', 'array', 'min:1'],
            'sub_cpmk_id.*' => ['nullable', 'exists:sub_cpmks,id'],
            'bobot' => ['required', 'array', 'min:1'],
            'bobot.*' => ['required', 'numeric', 'between:0,100'],
        ]);

        $totalBobot = array_sum(array_map('floatval', $validated['bobot']));
        $isDraft = $request->input('action') === 'draft';

        if (! $isDraft && round($totalBobot, 1) != 100.0) {
            return back()->withInput()->with('error', "Total bobot instrumen harus tepat 100%. Saat ini: {$totalBobot}%. Anda dapat memilih 'Simpan Draf' jika belum selesai mengatur.");
        }

        DB::transaction(function () use ($skema, $validated) {
            $skema->komponens()->delete();

            foreach ($validated['nama_instrumen'] as $i => $nama) {
                RpsPenilaianKomponen::create([
                    'skema_id' => $skema->id,
                    'nama_instrumen' => trim($nama),
                    'sub_cpmk_id' => ! empty($validated['sub_cpmk_id'][$i]) ? $validated['sub_cpmk_id'][$i] : null,
                    'bobot' => (float) $validated['bobot'][$i],
                    'urutan' => $i + 1,
                ]);
            }
        });

        $msg = $isDraft
            ? 'Draf pengaturan penilaian RPS berhasil disimpan.'
            : 'Pengaturan RPS Penilaian berhasil disimpan.';

        return redirect()
            ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input'])
            ->with('success', $msg);
    }

    // ===================== SIMPAN NILAI KOMPONEN MAHASISWA (TAB 2) =====================
    public function saveNilai(Request $request, Jadwal $jadwal, ObeAssessmentService $obeService)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $skema = RpsPenilaianSkema::where('jadwal_id', $jadwal->id)->firstOrFail();
        if ($skema->is_finalized) {
            return back()->with('error', 'Nilai kelas telah difinalisasi dan terkunci.');
        }

        $validated = $request->validate([
            'nilai' => ['nullable', 'array'],
        ]);

        $scores = $validated['nilai'] ?? [];

        DB::transaction(function () use ($scores) {
            foreach ($scores as $krsId => $komponenScores) {
                if (! is_array($komponenScores)) {
                    continue;
                }
                foreach ($komponenScores as $komponenId => $val) {
                    if ($val === '' || $val === null) {
                        $val = 0.0;
                    }
                    MahasiswaNilaiKomponen::updateOrCreate(
                        [
                            'krs_id' => (int) $krsId,
                            'komponen_id' => (int) $komponenId,
                        ],
                        [
                            'nilai_angka' => (float) $val,
                        ]
                    );
                }
            }
        });

        // Update draf KHS
        $rps = $jadwal->mataKuliah->rpsAktif ?? $jadwal->mataKuliah->rpsList()->latest()->first();
        $obeService->sinkronisasiKeKhs($jadwal, $skema, $rps);

        return redirect()
            ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input'])
            ->with('success', 'Nilai mahasiswa berhasil disimpan.');
    }

    // ===================== FINALISASI NILAI (TAB 2) =====================
    public function finalize(Request $request, Jadwal $jadwal, ObeAssessmentService $obeService)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $skema = RpsPenilaianSkema::where('jadwal_id', $jadwal->id)->firstOrFail();
        if ($skema->is_finalized) {
            return back()->with('info', 'Nilai kelas sudah dalam status finalisasi.');
        }

        if (round($skema->total_bobot, 1) != 100.0) {
            return back()->with('error', 'Finalisasi gagal: Total bobot instrumen penilaian belum mencapai 100%.');
        }

        $rps = $jadwal->mataKuliah->rpsAktif ?? $jadwal->mataKuliah->rpsList()->latest()->first();

        DB::transaction(function () use ($skema, $jadwal, $obeService, $rps) {
            $skema->update([
                'is_finalized' => true,
                'finalized_at' => now(),
            ]);

            // Sinkronisasi nilai akhir ke tabel khs
            $obeService->sinkronisasiKeKhs($jadwal, $skema, $rps);
        });

        return redirect()
            ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input'])
            ->with('success', 'Nilai kelas berhasil difinalisasi! Data nilai akhir mahasiswa telah disinkronkan ke KHS, IPK, dan transkrip akademik.');
    }

    // ===================== UNDUH TEMPLATE EXCEL / CSV =====================
    public function exportTemplate(Jadwal $jadwal)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $skema = RpsPenilaianSkema::with('komponens')->where('jadwal_id', $jadwal->id)->firstOrFail();
        $komponens = $skema->komponens;

        if ($komponens->isEmpty()) {
            return back()->with('error', 'Silakan atur instrumen penilaian terlebih dahulu di Tab Pengaturan sebelum mengunduh template.');
        }

        $krsList = Krs::with(['mahasiswa', 'nilaiKomponens'])
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get();

        $filename = 'Template_Nilai_' . str_replace(' ', '_', $jadwal->mataKuliah->kode_mk) . '_Kelas_' . ($jadwal->kelas ?? 'A') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($komponens, $krsList) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens cleanly
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            $headerRow = ['NIM', 'Nama Mahasiswa'];
            foreach ($komponens as $k) {
                $headerRow[] = "{$k->nama_instrumen} [{$k->bobot}%]";
            }
            fputcsv($handle, $headerRow, ';');

            // Data rows
            foreach ($krsList as $krs) {
                $nilaiMap = $krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');
                $row = [
                    $krs->mahasiswa?->nim ?? '',
                    $krs->mahasiswa?->nama ?? '',
                ];
                foreach ($komponens as $k) {
                    $row[] = $nilaiMap->get($k->id) ?? '';
                }
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, 200, $headers);
    }

    // ===================== IMPOR DARI EXCEL / CSV =====================
    public function importExcel(Request $request, Jadwal $jadwal, ObeAssessmentService $obeService)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();
        if (! $jadwal->isDosenPengampu($dosen)) {
            abort(403);
        }

        $skema = RpsPenilaianSkema::with('komponens')->where('jadwal_id', $jadwal->id)->firstOrFail();
        if ($skema->is_finalized) {
            return back()->with('error', 'Nilai kelas telah difinalisasi dan terkunci.');
        }

        $request->validate([
            'file_excel' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file_excel');
        $filePath = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Check BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Determine delimiter (; or ,)
            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }
            $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';

            while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (count($rows) < 2) {
            return back()->with('error', 'Format file CSV tidak valid atau data kosong.');
        }

        $headerRow = array_map('trim', $rows[0]);
        $komponens = $skema->komponens;
        $krsList = Krs::with('mahasiswa')
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get()
            ->keyBy(fn ($k) => trim($k->mahasiswa?->nim));

        $importedCount = 0;

        DB::transaction(function () use ($rows, $headerRow, $komponens, $krsList, &$importedCount) {
            for ($r = 1; $r < count($rows); $r++) {
                $row = $rows[$r];
                if (empty($row[0])) {
                    continue;
                }
                $nim = trim($row[0]);
                $krs = $krsList->get($nim);
                if (! $krs) {
                    continue;
                }

                foreach ($komponens as $idx => $komp) {
                    $colIndex = 2 + $idx;
                    if (isset($row[$colIndex]) && is_numeric(str_replace(',', '.', trim($row[$colIndex])))) {
                        $val = (float) str_replace(',', '.', trim($row[$colIndex]));
                        $val = min(100.0, max(0.0, $val));

                        MahasiswaNilaiKomponen::updateOrCreate(
                            ['krs_id' => $krs->id, 'komponen_id' => $komp->id],
                            ['nilai_angka' => $val]
                        );
                    }
                }
                $importedCount++;
            }
        });

        $rps = $jadwal->mataKuliah->rpsAktif ?? $jadwal->mataKuliah->rpsList()->latest()->first();
        $obeService->sinkronisasiKeKhs($jadwal, $skema, $rps);

        return redirect()
            ->route('dosen.nilai.show', ['jadwal' => $jadwal->id, 'tab' => 'input'])
            ->with('success', "Berhasil mengimpor nilai untuk {$importedCount} mahasiswa.");
    }

    // ===================== STORE LEGACY =====================
    public function store(Request $request)
    {
        $dosen = Dosen::where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'krs_id' => ['required', 'array', 'min:1'],
            'krs_id.*' => ['required', 'integer', 'distinct', 'exists:krs,id'],
            'nilai_angka' => ['required', 'array'],
            'nilai_angka.*' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        if (count($validated['krs_id']) !== count($validated['nilai_angka'])) {
            throw ValidationException::withMessages([
                'nilai_angka' => 'Jumlah nilai tidak sesuai dengan jumlah mahasiswa.',
            ]);
        }

        $krsIds = collect(array_values($validated['krs_id']))->map(fn ($id) => (int) $id);
        $nilaiAngka = array_values($validated['nilai_angka']);
        $krsById = Krs::with(['jadwal', 'khs'])
            ->where('is_manual', false)
            ->whereIn('id', $krsIds)
            ->where('status', 'Disetujui')
            ->whereHas('jadwal', fn ($query) => $query->untukDosen($dosen->id))
            ->get()
            ->keyBy('id');

        abort_unless($krsById->count() === $krsIds->count(), 403);
        abort_if($krsById->contains(fn (Krs $krs) => $krs->khs?->is_manual), 403);

        $obeService = app(ObeAssessmentService::class);

        DB::transaction(function () use ($krsIds, $nilaiAngka, $krsById, $obeService) {
            foreach ($krsIds as $i => $krsId) {
                $nilai = $nilaiAngka[$i];
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                $konversi = $obeService->konversiNilai((float) $nilai);
                $krs = $krsById->get((int) $krsId);

                Khs::updateOrCreate(
                    ['krs_id' => $krs->id],
                    [
                        'nilai_angka' => (float) $nilai,
                        'nilai_huruf' => $konversi['huruf'],
                        'bobot' => $konversi['bobot'],
                        'tahun_akademik' => $krs->tahun_akademik,
                        'semester_akademik' => $krs->semester_akademik,
                    ]
                );
            }
        });

        return redirect()
            ->route('dosen.nilai')
            ->with('success', 'Nilai berhasil disimpan.');
    }
}
