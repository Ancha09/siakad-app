<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\MahasiswaNilaiKomponen;
use App\Models\MataKuliahRps;
use App\Models\RpsPenilaianKomponen;
use App\Models\RpsPenilaianSkema;
use App\Models\SubCpmk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObeAssessmentService
{
    /**
     * Konversi nilai angka ke huruf dan mutu/bobot standar STTMI
     */
    public function konversiNilai(float $angka): array
    {
        if ($angka >= 85) {
            return ['huruf' => 'A', 'bobot' => 4.00];
        }
        if ($angka >= 80) {
            return ['huruf' => 'A-', 'bobot' => 3.75];
        }
        if ($angka >= 75) {
            return ['huruf' => 'B+', 'bobot' => 3.50];
        }
        if ($angka >= 70) {
            return ['huruf' => 'B', 'bobot' => 3.00];
        }
        if ($angka >= 65) {
            return ['huruf' => 'B-', 'bobot' => 2.75];
        }
        if ($angka >= 60) {
            return ['huruf' => 'C+', 'bobot' => 2.50];
        }
        if ($angka >= 55) {
            return ['huruf' => 'C', 'bobot' => 2.00];
        }
        if ($angka >= 40) {
            return ['huruf' => 'D', 'bobot' => 1.00];
        }

        return ['huruf' => 'E', 'bobot' => 0.00];
    }

    /**
     * Ambil atau inisialisasi skema penilaian untuk jadwal perkuliahan
     */
    public function getOrCreateSkema(Jadwal $jadwal, ?int $dosenId = null): RpsPenilaianSkema
    {
        $skema = RpsPenilaianSkema::firstOrCreate(
            ['jadwal_id' => $jadwal->id],
            [
                'dosen_id' => $dosenId ?: $jadwal->dosen_id,
                'is_finalized' => false,
            ]
        );

        if ($skema->komponens()->count() === 0) {
            $this->seedDefaultKomponenFromRps($skema, $jadwal);
        }

        return $skema;
    }

    /**
     * Inisialisasi komponen instrumen penilaian dan matriks alokasi dari dokumen RPS / kurikulum
     */
    public function seedDefaultKomponenFromRps(RpsPenilaianSkema $skema, Jadwal $jadwal, bool $force = false): void
    {
        if ($force) {
            $skema->komponens()->delete();
            $skema->update(['matrix_alokasi' => null]);
        }

        $defaultMatrix = $this->getDefaultMatrixForJadwal($jadwal);
        if (! empty($defaultMatrix['rows'])) {
            $this->syncKomponensFromMatrix($skema, $defaultMatrix);
        }
    }

    /**
     * Memastikan matriks alokasi lengkap mencakup seluruh Sub-CPMK (termasuk CPMK 11 / Sub-CPMK 14)
     * dan seluruh sel teralokasi tersinkronisasi ke tabel rps_penilaian_komponen.
     */
    public function ensureCompleteMatrixForJadwal(RpsPenilaianSkema $skema, Jadwal $jadwal): array
    {
        $mk = $jadwal->mataKuliah;
        if (! $mk) {
            return ['rows' => [], 'komponen_rps' => [], 'temuan' => []];
        }

        $rps = $mk->rpsAktif ?? $mk->rpsList()->latest()->first();

        // Auto-heal: jika ada file PDF RPS aktif tetapi Sub-CPMK belum memiliki bobot_default atau Sub-CPMK Lab belum terekstrak
        if ($rps && $rps->effective_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($rps->effective_file_path)) {
            $currentSubs = SubCpmk::whereHas('cpmk', fn ($q) => $q->where('mata_kuliah_id', $mk->id))->get();
            $missingBobotCount = $currentSubs->whereNull('bobot_default')->count();
            if ($currentSubs->isEmpty() || $missingBobotCount > 0) {
                $this->autoHealCourseFromRpsPdf($mk, $rps);
                $mk->unsetRelation('cpmks');
                $mk->load('cpmks.subCpmks.cpl');
                $rps->refresh();
            }
        }

        $allSubCpmks = SubCpmk::with(['cpmk', 'cpl'])->whereHas('cpmk', function ($q) use ($mk) {
            $q->where('mata_kuliah_id', $mk->id);
        })->orderBy('id')->get();

        $subIds = $allSubCpmks->pluck('id')->values()->toArray();

        $matrixData = $skema->matrix_alokasi;
        $needsReset = false;

        if (empty($matrixData) || empty($matrixData['rows'])) {
            $needsReset = true;
        } else {
            $allocatedSubIds = [];
            $hasUas = false;
            $totalBobot = 0;

            foreach ($matrixData['rows'] as $row) {
                $nama = strtolower(trim($row['nama'] ?? ''));
                if (str_contains($nama, 'uas')) {
                    $hasUas = true;
                }
                foreach ($row['allocations'] ?? [] as $subId => $w) {
                    if ((float) $w > 0) {
                        $allocatedSubIds[(string) $subId] = true;
                        $totalBobot += (float) $w;
                    }
                }
            }

            foreach ($subIds as $sId) {
                if (! isset($allocatedSubIds[(string) $sId])) {
                    $needsReset = true;
                    break;
                }
            }

            // Cek apakah ada Sub-CPMK Lab tetapi baris Praktikum belum ada di matriks lama
            $hasLabSub = $allSubCpmks->contains(fn ($s) => stripos($s->kode_sub_cpmk, 'lab') !== false || stripos($s->kode_sub_cpmk, 'praktikum') !== false);
            $hasLabRow = collect($matrixData['rows'])->contains(fn ($r) => stripos($r['nama'] ?? '', 'praktikum') !== false || stripos($r['nama'] ?? '', 'lab') !== false);
            if ($hasLabSub && ! $hasLabRow) {
                $needsReset = true;
            }

            if (! $hasUas || round($totalBobot) < 95) {
                $needsReset = true;
            }
        }

        if ($needsReset) {
            $matrixData = $this->getDefaultMatrixForJadwal($jadwal);
            $this->syncKomponensFromMatrix($skema, $matrixData);
        } else {
            // Pastikan cpl_split dan komponen_rps selalu terbarui dari RPS aktif
            if (! isset($matrixData['cpl_split']) || empty($matrixData['komponen_rps'])) {
                $freshDefault = $this->getDefaultMatrixForJadwal($jadwal);
                $matrixData['cpl_split'] = $freshDefault['cpl_split'] ?? [];
                $matrixData['komponen_rps'] = $freshDefault['komponen_rps'] ?? ($matrixData['komponen_rps'] ?? []);
                $matrixData['temuan'] = $freshDefault['temuan'] ?? ($matrixData['temuan'] ?? []);
                $skema->update(['matrix_alokasi' => $matrixData]);
            }
            $this->ensureKomponensMatchMatrix($skema, $matrixData);
        }

        return $matrixData;
    }

    /**
     * Auto-heal data CPMK, Sub-CPMK, dan Porsi CPL dari file PDF RPS jika sebelumnya ter-upload dengan format lama
     */
    protected function autoHealCourseFromRpsPdf($mk, MataKuliahRps $rps): void
    {
        try {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($rps->effective_file_path);
            if (! file_exists($fullPath)) {
                return;
            }

            $parser = new RpsPdfParserService();
            $extracted = $parser->extract($fullPath);
            if (empty($extracted['sub_cpmks'])) {
                return;
            }

            $prodiId = $mk->prodi_id ?: 1;

            DB::transaction(function () use ($mk, $rps, $extracted, $prodiId) {
                if (! empty($extracted['porsi_cpl'])) {
                    $rps->porsi_cpl = $extracted['porsi_cpl'];
                }
                if (! empty($extracted['komponen_bobot'])) {
                    $rps->komponen_bobot_default = $extracted['komponen_bobot'];
                }
                $rps->save();

                $cplMap = [];
                foreach ($extracted['cpls'] ?? [] as $cplData) {
                    $kode = preg_replace('/\s+/', ' ', strtoupper(trim($cplData['kode_cpl'] ?? '')));
                    if (! $kode) {
                        continue;
                    }
                    $cpl = \App\Models\Cpl::where(function ($q) use ($prodiId) {
                        $q->where('prodi_id', $prodiId)->orWhere('program_studi_id', $prodiId);
                    })->where('kode_cpl', $kode)->first();

                    if (! $cpl) {
                        $deskripsiCpl = ! empty($cplData['deskripsi']) ? trim($cplData['deskripsi']) : null;
                        $namaCpl = ! empty($cplData['nama_cpl']) ? trim($cplData['nama_cpl']) : ($deskripsiCpl ?: $kode);
                        $cpl = \App\Models\Cpl::create([
                            'prodi_id' => $prodiId,
                            'program_studi_id' => $prodiId,
                            'kode_cpl' => $kode,
                            'nama_cpl' => $namaCpl,
                            'deskripsi' => $deskripsiCpl,
                        ]);
                    }
                    $cplMap[$kode] = $cpl;
                }

                $cpmkMap = [];
                $keepCpmkIds = [];
                foreach ($extracted['cpmks'] ?? [] as $cpmkData) {
                    $kode = preg_replace('/\s+/', ' ', strtoupper(trim($cpmkData['kode_cpmk'] ?? '')));
                    if (! $kode) {
                        continue;
                    }
                    $cpmk = \App\Models\Cpmk::updateOrCreate(
                        ['mata_kuliah_id' => $mk->id, 'kode_cpmk' => $kode],
                        ['deskripsi' => $cpmkData['deskripsi'] ?? null]
                    );
                    $cpmkMap[$kode] = $cpmk;
                    $keepCpmkIds[] = $cpmk->id;
                }

                $keepSubIds = [];
                foreach ($extracted['sub_cpmks'] as $subData) {
                    $kodeSub = trim($subData['kode_sub_cpmk'] ?? '');
                    $cpmkKode = preg_replace('/\s+/', ' ', strtoupper(trim($subData['cpmk_kode'] ?? 'CPMK 1')));
                    $cpmk = $cpmkMap[$cpmkKode] ?? reset($cpmkMap);
                    if (! $cpmk) {
                        continue;
                    }

                    $cplKode = preg_replace('/\s+/', ' ', strtoupper(trim($subData['cpl_kode'] ?? '')));
                    $cplId = $cplMap[$cplKode]->id ?? null;

                    $existingSub = SubCpmk::whereHas('cpmk', fn ($q) => $q->where('mata_kuliah_id', $mk->id))
                        ->where('kode_sub_cpmk', $kodeSub)
                        ->first();

                    $payload = [
                        'cpmk_id' => $cpmk->id,
                        'cpl_id' => $cplId,
                        'kode_sub_cpmk' => $kodeSub,
                        'deskripsi' => $subData['deskripsi'] ?? null,
                        'bobot_default' => isset($subData['bobot_default']) ? (float) $subData['bobot_default'] : null,
                    ];

                    if ($existingSub) {
                        $existingSub->update($payload);
                        $keepSubIds[] = $existingSub->id;
                    } else {
                        $newSub = SubCpmk::create($payload);
                        $keepSubIds[] = $newSub->id;
                    }
                }

                if (! empty($keepSubIds)) {
                    SubCpmk::whereHas('cpmk', fn ($q) => $q->where('mata_kuliah_id', $mk->id))
                        ->whereNotIn('id', $keepSubIds)
                        ->delete();
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto-heal RPS PDF gagal: ' . $e->getMessage());
        }
    }

    /**
     * Memastikan setiap sel berbobot di matriks terhubung ke RpsPenilaianKomponen
     */
    public function ensureKomponensMatchMatrix(RpsPenilaianSkema $skema, array $matrixData): void
    {
        $existing = $skema->komponens()->get();
        $urutan = $existing->count() + 1;

        foreach ($matrixData['rows'] ?? [] as $row) {
            $nama = trim($row['nama'] ?? '');
            foreach ($row['allocations'] ?? [] as $subId => $bobotVal) {
                if ((float) $bobotVal <= 0) {
                    continue;
                }
                $sId = (int) $subId;
                $matched = $existing->first(function ($k) use ($sId, $nama) {
                    return (int) $k->sub_cpmk_id === $sId && str_starts_with($k->nama_instrumen, $nama);
                });

                if (! $matched) {
                    $sub = SubCpmk::find($sId);
                    $subKode = $sub ? $sub->kode_sub_cpmk : "S{$sId}";
                    $newKomp = RpsPenilaianKomponen::create([
                        'skema_id' => $skema->id,
                        'nama_instrumen' => "{$nama} ({$subKode})",
                        'sub_cpmk_id' => $sId,
                        'bobot' => (float) $bobotVal,
                        'urutan' => $urutan++,
                    ]);
                    $existing->push($newKomp);
                }
            }
        }
    }

    /**
     * Template matriks alokasi bobot instrumen ke Sub-CPMK secara dinamis mengikuti dokumen RPS aktif
     */
    public function getDefaultMatrixForJadwal(Jadwal $jadwal): array
    {
        $mk = $jadwal->mataKuliah;
        if (! $mk) {
            return ['rows' => [], 'komponen_rps' => [], 'temuan' => [], 'cpl_split' => []];
        }

        $rps = $mk->rpsAktif ?? $mk->rpsList()->latest()->first();

        $allSubCpmks = SubCpmk::with(['cpmk', 'cpl'])->whereHas('cpmk', function ($q) use ($mk) {
            $q->where('mata_kuliah_id', $mk->id);
        })->orderBy('id')->get();

        $subCount = $allSubCpmks->count();
        $subIds = $allSubCpmks->pluck('id')->values()->toArray();

        $hasCustomWeights = $allSubCpmks->whereNotNull('bobot_default')->isNotEmpty();
        $hasLabSub = $allSubCpmks->contains(fn ($s) => stripos($s->kode_sub_cpmk, 'lab') !== false || stripos($s->kode_sub_cpmk, 'praktikum') !== false);
        $hasPdfFile = $rps && $rps->effective_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($rps->effective_file_path);

        // Gunakan generator matriks dinamis berbasis dokumen RPS apabila mata kuliah memiliki bobot Sub-CPMK / Sub-CPMK Lab / file PDF RPS
        if ($subCount > 0 && ($hasCustomWeights || $hasLabSub || $hasPdfFile)) {
            return $this->buildDynamicMatrixFromRps($mk, $rps, $allSubCpmks);
        }

        // Fallback untuk mata kuliah demo / legacy tanpa bobot_default di Sub-CPMK
        if ($subCount >= 14) {
            $rows = [
                [
                    'nama' => 'Kuis',
                    'keterangan' => 'P1, 2, 5, 9, 10, 12',
                    'allocations' => [
                        (string) $subIds[0] => 3,
                        (string) $subIds[1] => 3,
                        (string) $subIds[4] => 4,
                        (string) $subIds[6] => 3,
                        (string) $subIds[7] => 3,
                        (string) $subIds[9] => 4,
                    ],
                ],
                [
                    'nama' => 'Tugas terstruktur',
                    'keterangan' => 'P3, 4, 6, 11',
                    'allocations' => [
                        (string) $subIds[2] => 3,
                        (string) $subIds[3] => 4,
                        (string) $subIds[5] => 4,
                        (string) $subIds[8] => 3,
                    ],
                ],
                [
                    'nama' => 'Studi kasus',
                    'keterangan' => 'P13, 14',
                    'allocations' => [
                        (string) $subIds[10] => 4,
                        (string) $subIds[11] => 3,
                    ],
                ],
                [
                    'nama' => 'Proyek kelompok',
                    'keterangan' => 'P15 · presentasi (CPMK 11)',
                    'allocations' => [
                        (string) $subIds[13] => 4,
                    ],
                ],
                [
                    'nama' => 'UTS',
                    'keterangan' => '6 bagian soal materi paruh 1',
                    'allocations' => [
                        (string) $subIds[0] => 4,
                        (string) $subIds[1] => 4,
                        (string) $subIds[2] => 4,
                        (string) $subIds[3] => 4,
                        (string) $subIds[4] => 4,
                        (string) $subIds[5] => 5,
                    ],
                ],
                [
                    'nama' => 'UAS',
                    'keterangan' => '8 bagian soal materi paruh 2',
                    'allocations' => [
                        (string) $subIds[6] => 4,
                        (string) $subIds[7] => 4,
                        (string) $subIds[8] => 4,
                        (string) $subIds[9] => 4,
                        (string) $subIds[10] => 4,
                        (string) $subIds[11] => 4,
                        (string) $subIds[12] => 3,
                        (string) $subIds[13] => 3,
                    ],
                ],
            ];
        } elseif ($subCount === 13) {
            $rows = [
                [
                    'nama' => 'Kuis',
                    'keterangan' => 'P1, 2, 5, 9, 10, 12',
                    'allocations' => [
                        (string) $subIds[0] => 3,
                        (string) $subIds[1] => 3,
                        (string) $subIds[4] => 4,
                        (string) $subIds[6] => 3,
                        (string) $subIds[7] => 3,
                        (string) $subIds[9] => 4,
                    ],
                ],
                [
                    'nama' => 'Tugas terstruktur',
                    'keterangan' => 'P3, 4, 6, 11',
                    'allocations' => [
                        (string) $subIds[2] => 3,
                        (string) $subIds[3] => 4,
                        (string) $subIds[5] => 4,
                        (string) $subIds[8] => 3,
                    ],
                ],
                [
                    'nama' => 'Studi kasus',
                    'keterangan' => 'P13',
                    'allocations' => [
                        (string) $subIds[10] => 4,
                        (string) $subIds[11] => 3,
                    ],
                ],
                [
                    'nama' => 'Proyek kelompok',
                    'keterangan' => 'P14 · presentasi',
                    'allocations' => [
                        (string) $subIds[12] => 4,
                    ],
                ],
                [
                    'nama' => 'UTS',
                    'keterangan' => '6 bagian soal materi paruh 1',
                    'allocations' => [
                        (string) $subIds[0] => 4,
                        (string) $subIds[1] => 4,
                        (string) $subIds[2] => 4,
                        (string) $subIds[3] => 4,
                        (string) $subIds[4] => 4,
                        (string) $subIds[5] => 5,
                    ],
                ],
                [
                    'nama' => 'UAS',
                    'keterangan' => '7 bagian soal materi paruh 2',
                    'allocations' => [
                        (string) $subIds[6] => 4,
                        (string) $subIds[7] => 4,
                        (string) $subIds[8] => 4,
                        (string) $subIds[9] => 5,
                        (string) $subIds[10] => 5,
                        (string) $subIds[11] => 4,
                        (string) $subIds[12] => 4,
                    ],
                ],
            ];
        } elseif ($subCount > 0) {
            $half = max(1, (int) floor($subCount / 2));
            $utsAlloc = [];
            $utsWeight = round(25 / $half, 1);
            $utsAccum = 0;
            for ($i = 0; $i < $half; $i++) {
                $w = ($i === $half - 1) ? round(25 - $utsAccum, 1) : $utsWeight;
                $utsAlloc[(string) $subIds[$i]] = $w;
                $utsAccum += $w;
            }

            $uasAlloc = [];
            $uasCount = $subCount - $half;
            $uasWeight = round(30 / max(1, $uasCount), 1);
            $uasAccum = 0;
            for ($i = $half; $i < $subCount; $i++) {
                $w = ($i === $subCount - 1) ? round(30 - $uasAccum, 1) : $uasWeight;
                $uasAlloc[(string) $subIds[$i]] = $w;
                $uasAccum += $w;
            }

            $kuisAlloc = [];
            $kuisCount = min(4, $subCount);
            $kw = round(20 / $kuisCount, 1);
            $kAcc = 0;
            for ($i = 0; $i < $kuisCount; $i++) {
                $w = ($i === $kuisCount - 1) ? round(20 - $kAcc, 1) : $kw;
                $kuisAlloc[(string) $subIds[$i]] = $w;
                $kAcc += $w;
            }

            $tugasAlloc = [];
            $tCount = min(5, $subCount);
            $tw = round(25 / $tCount, 1);
            $tAcc = 0;
            for ($i = 0; $i < $tCount; $i++) {
                $idx = ($i + 1) % $subCount;
                $w = ($i === $tCount - 1) ? round(25 - $tAcc, 1) : $tw;
                $tugasAlloc[(string) $subIds[$idx]] = $w;
                $tAcc += $w;
            }

            $rows = [
                ['nama' => 'Kuis', 'keterangan' => 'Rincian pertemuan kuis', 'allocations' => $kuisAlloc],
                ['nama' => 'Tugas terstruktur', 'keterangan' => 'Tugas terstruktur mingguan', 'allocations' => $tugasAlloc],
                ['nama' => 'UTS', 'keterangan' => 'Bagian soal materi paruh 1', 'allocations' => $utsAlloc],
                ['nama' => 'UAS', 'keterangan' => 'Bagian soal materi paruh 2', 'allocations' => $uasAlloc],
            ];
        } else {
            $rows = [];
        }

        $komponenRps = [
            'Kuis, keaktifan, kerja sama tim' => ['weekly' => 20, 'summary' => 15],
            'Tugas (terstruktur, studi kasus, proyek)' => ['weekly' => 25, 'summary' => 30],
            'UTS' => ['weekly' => 25, 'summary' => 25],
            'UAS' => ['weekly' => 30, 'summary' => 30],
        ];

        $temuan = [
            'Bobot kuis di rincian mingguan 20%, tetapi di tabel komponen 15%. Tugas 25% vs 30%.',
            'Sub-CPMK 14 (CPMK 11) dialokasikan pada presentasi proyek akhir (4%) dan soal bagian UAS (3%).',
            'Seluruh materi paruh 1 diuji pada UTS dan materi paruh 2 diuji pada UAS.',
        ];

        return [
            'rows' => $rows,
            'komponen_rps' => $komponenRps,
            'temuan' => $temuan,
            'cpl_split' => [],
        ];
    }

    /**
     * Bangun matriks alokasi secara dinamis dari struktur RPS & Sub-CPMK aktual
     */
    protected function buildDynamicMatrixFromRps($mk, ?MataKuliahRps $rps, Collection $allSubCpmks): array
    {
        $parser = new RpsPdfParserService();
        $pdfSubMeta = [];
        $rubrikLabCpl = [];

        if ($rps && $rps->effective_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($rps->effective_file_path)) {
            try {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($rps->effective_file_path);
                $extracted = $parser->extract($fullPath);
                foreach ($extracted['sub_cpmks'] ?? [] as $es) {
                    $pdfSubMeta[$es['kode_sub_cpmk']] = $es;
                }
                $rubrikLabCpl = $extracted['rubrik_lab_cpl'] ?? [];
            } catch (\Throwable $e) {
                $pdfSubMeta = [];
            }
        }

        $kompDefault = $rps?->komponen_bobot_default ?? [];
        $utsTarget = (float) ($kompDefault['UTS'] ?? 20.0);
        $uasTarget = (float) ($kompDefault['UAS'] ?? 25.0);

        $theorySubs = collect();
        $labSubs = collect();

        foreach ($allSubCpmks as $sub) {
            $isLab = stripos($sub->kode_sub_cpmk, 'lab') !== false || stripos($sub->kode_sub_cpmk, 'praktikum') !== false;
            if ($isLab) {
                $labSubs->push($sub);
            } else {
                $theorySubs->push($sub);
            }
        }

        $praktikumTarget = $labSubs->isNotEmpty()
            ? (float) ($kompDefault['Praktikum'] ?? ($labSubs->sum('bobot_default') ?: 25.0))
            : 0.0;

        $weeklyTarget = max(0.0, 100.0 - $utsTarget - $uasTarget - $praktikumTarget);

        // Kelompokkan Sub-CPMK teori ke dalam kategori instrumen mingguan & paruh semester (1 = pra-UTS, 2 = pasca-UTS)
        $groupedAllocations = [
            'Kuis' => ['alloc' => [], 'pertemuan' => []],
            'Tugas terstruktur' => ['alloc' => [], 'pertemuan' => []],
            'Tugas studi kasus' => ['alloc' => [], 'pertemuan' => []],
            'Laporan praktikum' => ['alloc' => [], 'pertemuan' => []],
            'Proyek kelompok' => ['alloc' => [], 'pertemuan' => []],
        ];

        $paruh1Ids = [];
        $paruh2Ids = [];

        $theoryCount = $theorySubs->count();
        $defaultHalf = max(1, (int) floor($theoryCount / 2));

        // Jika Sub-CPMK belum punya bobot_default, bagi merata dari $weeklyTarget
        $fallbackWeeklyMap = $parser->distributeWeightClean(
            $theorySubs->pluck('id')->map(fn ($id) => (string) $id)->toArray(),
            $weeklyTarget
        );

        foreach ($theorySubs->values() as $idx => $sub) {
            $meta = $pdfSubMeta[$sub->kode_sub_cpmk] ?? [];
            $bobot = $sub->bobot_default !== null && (float) $sub->bobot_default > 0
                ? (float) $sub->bobot_default
                : (float) ($meta['bobot_default'] ?? ($fallbackWeeklyMap[(string) $sub->id] ?? 2.0));

            $jenis = $meta['jenis_penilaian'] ?? null;
            if (! $jenis) {
                $desc = strtolower($sub->deskripsi ?? '');
                if ($idx === 0) {
                    $jenis = 'Kuis';
                } elseif (str_contains($desc, 'mempresentasikan') || str_contains($desc, 'rancangan') || str_contains($desc, 'diagram fasa')) {
                    $jenis = 'Proyek kelompok';
                } elseif (str_contains($desc, 'redoks') || str_contains($desc, 'termodinamika') || str_contains($desc, 'mengevaluasi')) {
                    $jenis = 'Tugas studi kasus';
                } else {
                    $jenis = 'Tugas terstruktur';
                }
            }

            if (! isset($groupedAllocations[$jenis])) {
                $jenis = 'Tugas terstruktur';
            }

            $pertemuan = $meta['pertemuan'] ?? (string) ($idx < $defaultHalf ? ($idx + 1) : ($idx + 2));
            $paruh = $meta['paruh'] ?? ($idx < $defaultHalf ? 1 : 2);

            if ((int) $paruh === 1) {
                $paruh1Ids[] = (string) $sub->id;
            } else {
                $paruh2Ids[] = (string) $sub->id;
            }

            $groupedAllocations[$jenis]['alloc'][(string) $sub->id] = $bobot;
            $groupedAllocations[$jenis]['pertemuan'][] = $pertemuan;
        }

        // Pastikan total bobot mingguan teori pas dengan $weeklyTarget jika mendekati
        $currentWeeklySum = 0.0;
        foreach ($groupedAllocations as $g) {
            $currentWeeklySum += array_sum($g['alloc']);
        }

        $rows = [];
        foreach ($groupedAllocations as $namaJenis => $data) {
            if (empty($data['alloc'])) {
                continue;
            }
            $pList = array_values(array_unique($data['pertemuan']));
            $ket = ! empty($pList) ? ('P' . implode(', ', $pList)) : 'Rincian mingguan';
            if ($namaJenis === 'Proyek kelompok') {
                $ket .= ' · presentasi';
            }
            $rows[] = [
                'nama' => $namaJenis,
                'keterangan' => $ket,
                'allocations' => $data['alloc'],
            ];
        }

        // Baris UTS (Paruh 1)
        if ($utsTarget > 0 && ! empty($paruh1Ids)) {
            $utsAlloc = $parser->distributeWeightClean($paruh1Ids, $utsTarget);
            $rows[] = [
                'nama' => 'UTS',
                'keterangan' => count($paruh1Ids) . ' bagian soal materi paruh 1',
                'allocations' => $utsAlloc,
            ];
        }

        // Baris UAS (Paruh 2)
        if ($uasTarget > 0 && ! empty($paruh2Ids)) {
            $uasAlloc = $parser->distributeWeightClean($paruh2Ids, $uasTarget);
            $rows[] = [
                'nama' => 'UAS',
                'keterangan' => count($paruh2Ids) . ' bagian soal materi paruh 2',
                'allocations' => $uasAlloc,
            ];
        }

        // Baris Praktikum Laboratorium (Sub-CPMK Lab)
        $cplSplit = [];
        if ($labSubs->isNotEmpty() && $praktikumTarget > 0) {
            $labIds = $labSubs->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            $labAlloc = $parser->distributeWeightClean($labIds, $praktikumTarget);
            $labCpmkCodes = $labSubs->map(fn ($s) => $s->cpmk?->kode_cpmk)->filter()->unique()->implode(', ');

            $rows[] = [
                'nama' => 'Praktikum laboratorium',
                'keterangan' => 'Modul praktikum 1–4' . ($labCpmkCodes ? " ({$labCpmkCodes})" : ''),
                'allocations' => $labAlloc,
            ];

            // Hitung proporsi CPL untuk Sub-CPMK Lab agar 100% sinkron dengan target porsi_cpl & Rubrik Penilaian Laboratorium
            $targetPorsi = $rps?->porsi_cpl ?? [];
            if (! empty($rubrikLabCpl)) {
                foreach ($labSubs as $lSub) {
                    $cplSplit[(string) $lSub->id] = $rubrikLabCpl;
                }
            } elseif (count($targetPorsi) > 1) {
                // Hitung bobot CPL yang sudah terakomodasi oleh Sub-CPMK Teori
                $theoryCplSum = [];
                foreach ($theorySubs as $tSub) {
                    $cCode = $tSub->cpl?->kode_cpl;
                    if (! $cCode) {
                        continue;
                    }
                    $subTotal = 0.0;
                    foreach ($rows as $r) {
                        $subTotal += (float) ($r['allocations'][(string) $tSub->id] ?? 0.0);
                    }
                    $theoryCplSum[$cCode] = ($theoryCplSum[$cCode] ?? 0.0) + $subTotal;
                }

                $derivedRatios = [];
                $remTotal = 0.0;
                foreach ($targetPorsi as $cCode => $tVal) {
                    $needed = max(0.0, (float) $tVal - ($theoryCplSum[$cCode] ?? 0.0));
                    if ($needed > 0) {
                        $derivedRatios[$cCode] = $needed;
                        $remTotal += $needed;
                    }
                }

                if ($remTotal > 0) {
                    $normRatios = [];
                    foreach ($derivedRatios as $cCode => $v) {
                        $normRatios[$cCode] = round($v / $remTotal, 4);
                    }
                    foreach ($labSubs as $lSub) {
                        $cplSplit[(string) $lSub->id] = $normRatios;
                    }
                }
            }
        }

        // Susun tabel perbandingan Komponen RPS (Card 2)
        $komponenRps = [];
        if ($labSubs->isNotEmpty()) {
            $tugasSummary = (float) (($kompDefault['Tugas'] ?? 0) + ($kompDefault['Kuis'] ?? 0));
            if ($tugasSummary <= 0) {
                $tugasSummary = $weeklyTarget;
            }
            $komponenRps = [
                'Tugas terstruktur, kuis, keaktifan, dan kerja sama' => [
                    'weekly' => round($currentWeeklySum, 1),
                    'summary' => round($tugasSummary, 1),
                    'key' => 'tugas_kuis',
                ],
                'UTS' => [
                    'weekly' => round($utsTarget, 1),
                    'summary' => round($utsTarget, 1),
                    'key' => 'uts',
                ],
                'UAS' => [
                    'weekly' => round($uasTarget, 1),
                    'summary' => round($uasTarget, 1),
                    'key' => 'uas',
                ],
                'Praktikum laboratorium' => [
                    'weekly' => round($praktikumTarget, 1),
                    'summary' => round($praktikumTarget, 1),
                    'key' => 'praktikum',
                ],
            ];

            $temuan = [
                "Akumulasi bobot mingguan teori ({$currentWeeklySum}%), UTS ({$utsTarget}%), UAS ({$uasTarget}%), dan Praktikum Laboratorium ({$praktikumTarget}%) telah selaras 100% dengan tabel komponen RPS.",
                'Penilaian Praktikum Laboratorium (' . $praktikumTarget . '%) ditautkan ke Sub-CPMK Lab (SL) dan didistribusikan ke CPL sesuai Rubrik Penilaian Laboratorium.',
                'Seluruh Sub-CPMK paruh 1 diuji pada UTS dan Sub-CPMK paruh 2 diuji pada UAS.',
            ];
        } else {
            $kuisSummary = (float) ($kompDefault['Kuis'] ?? 10.0);
            $tugasSummary = (float) (($kompDefault['Tugas'] ?? 25.0) + ($kompDefault['Praktikum'] ?? 20.0));
            $kuisWeekly = array_sum($groupedAllocations['Kuis']['alloc'] ?? []);
            $tugasWeekly = $currentWeeklySum - $kuisWeekly;

            $komponenRps = [
                'Kuis, keaktifan, kerja sama tim' => [
                    'weekly' => round($kuisWeekly, 1),
                    'summary' => round($kuisSummary, 1),
                    'key' => 'kuis',
                ],
                'Tugas (terstruktur, studi kasus, proyek)' => [
                    'weekly' => round($tugasWeekly, 1),
                    'summary' => round($tugasSummary, 1),
                    'key' => 'tugas',
                ],
                'UTS' => [
                    'weekly' => round($utsTarget, 1),
                    'summary' => round($utsTarget, 1),
                    'key' => 'uts',
                ],
                'UAS' => [
                    'weekly' => round($uasTarget, 1),
                    'summary' => round($uasTarget, 1),
                    'key' => 'uas',
                ],
            ];

            $temuan = [
                "Akumulasi bobot penilaian mingguan ({$currentWeeklySum}%), UTS ({$utsTarget}%), dan UAS ({$uasTarget}%) mencapai total 100%.",
                'Seluruh Sub-CPMK paruh 1 diuji pada UTS dan Sub-CPMK paruh 2 diuji pada UAS.',
            ];
        }

        return [
            'rows' => $rows,
            'komponen_rps' => $komponenRps,
            'temuan' => $temuan,
            'cpl_split' => $cplSplit,
        ];
    }

    /**
     * Sinkronisasikan tabel rps_penilaian_komponen dari struktur Matriks Alokasi
     * (Aman & non-destruktif: tidak menghapus nilai mahasiswa yang sudah tersimpan)
     */
    public function syncKomponensFromMatrix(RpsPenilaianSkema $skema, array $matrixData): void
    {
        DB::transaction(function () use ($skema, $matrixData) {
            $skema->update(['matrix_alokasi' => $matrixData]);

            $existingKomponens = $skema->komponens()->get();
            $urutan = 1;
            $rows = $matrixData['rows'] ?? [];
            $keepIds = [];

            foreach ($rows as $rIdx => $row) {
                $nama = trim($row['nama'] ?? 'Komponen ' . ($rIdx + 1));
                $allocations = $row['allocations'] ?? [];

                foreach ($allocations as $subCpmkId => $bobotVal) {
                    $bobot = (float) $bobotVal;
                    if ($bobot <= 0) {
                        continue;
                    }

                    $subId = (int) $subCpmkId;
                    $sub = SubCpmk::find($subId);
                    $subKode = $sub ? $sub->kode_sub_cpmk : "S{$subId}";
                    $instrumenName = "{$nama} ({$subKode})";

                    // Cocokkan komponen yang sudah ada untuk sub_cpmk dan nama instrumen ini
                    $komp = $existingKomponens->first(function ($k) use ($subId, $nama) {
                        return (int) $k->sub_cpmk_id === $subId && str_starts_with($k->nama_instrumen, $nama);
                    });

                    if ($komp) {
                        $komp->update([
                            'nama_instrumen' => $instrumenName,
                            'bobot' => $bobot,
                            'urutan' => $urutan++,
                        ]);
                    } else {
                        $komp = RpsPenilaianKomponen::create([
                            'skema_id' => $skema->id,
                            'nama_instrumen' => $instrumenName,
                            'sub_cpmk_id' => $subId,
                            'bobot' => $bobot,
                            'urutan' => $urutan++,
                        ]);
                    }

                    $keepIds[] = $komp->id;
                }
            }

            // Hapus komponen yang tidak lagi ada di matriks HANYA jika belum memiliki nilai mahasiswa
            foreach ($existingKomponens as $old) {
                if (! in_array($old->id, $keepIds)) {
                    $hasScores = MahasiswaNilaiKomponen::where('komponen_id', $old->id)->where('nilai_angka', '>', 0)->exists();
                    if (! $hasScores) {
                        $old->delete();
                    }
                }
            }
        });
    }

    /**
     * Peta komponen matriks [rowIndex][subCpmkId] => RpsPenilaianKomponen
     */
    public function getKomponenMatrixMap(RpsPenilaianSkema $skema, array $matrixData): array
    {
        $komponens = $skema->komponens()->with('subCpmk')->get();
        $rows = $matrixData['rows'] ?? [];
        $map = [];

        foreach ($rows as $rIdx => $row) {
            $nama = trim($row['nama'] ?? '');
            $allocations = $row['allocations'] ?? [];

            foreach ($allocations as $subCpmkId => $bobotVal) {
                if ((float) $bobotVal <= 0) {
                    continue;
                }

                $subId = (int) $subCpmkId;
                $matched = $komponens->first(function ($k) use ($subId, $nama) {
                    return (int) $k->sub_cpmk_id === $subId && str_starts_with($k->nama_instrumen, $nama);
                });

                if (! $matched) {
                    $matched = $komponens->firstWhere('sub_cpmk_id', $subId);
                }

                // Jika komponen belum ada di database, buat otomatis on-the-fly
                // agar input nilai untuk sel ini tidak menjadi '-' (disabled)
                if (! $matched) {
                    $sub = SubCpmk::find($subId);
                    $subKode = $sub ? $sub->kode_sub_cpmk : "S{$subId}";
                    $matched = RpsPenilaianKomponen::create([
                        'skema_id' => $skema->id,
                        'nama_instrumen' => "{$nama} ({$subKode})",
                        'sub_cpmk_id' => $subId,
                        'bobot' => (float) $bobotVal,
                        'urutan' => $komponens->count() + 1,
                    ]);
                    $komponens->push($matched);
                }

                if ($matched) {
                    $map[$rIdx][$subId] = $matched;
                }
            }
        }

        return $map;
    }

    /**
     * Hitung Nilai Akhir (NA) dan status ketercapaian CPL per mahasiswa
     */
    public function hitungNilaiMahasiswa(Krs $krs, RpsPenilaianSkema $skema, ?MataKuliahRps $rps = null): array
    {
        // 1. Mahasiswa Non-OBE / Reguler (penilaian konvensional langsung ke KHS)
        if (! $krs->isObeApplicable()) {
            $khs = $krs->khs;
            $na = $khs ? (float) $khs->nilai_angka : 0.0;
            $na = round(min(100.0, max(0.0, $na)), 2);
            $konversi = $this->konversiNilai($na);

            return [
                'na' => $na,
                'nilai_huruf' => $khs?->nilai_huruf ?: $konversi['huruf'],
                'bobot' => $khs?->bobot !== null ? (float) $khs->bobot : (float) $konversi['bobot'],
                'cpl_results' => [],
                'unachieved_cpls' => [],
                'all_cpl_achieved' => true,
                'passing_grade' => (float) ($rps?->target_passing_grade ?? 60.0),
                'is_obe' => false,
                'detail_nilai' => $khs?->detail_nilai ?? [],
            ];
        }

        // 2. Mahasiswa OBE (berbasis instrumen matriks Sub-CPMK dan CPL)
        $komponens = $skema->komponens()->with(['subCpmk.cpl', 'subCpmk.cpmk'])->get();
        $nilaiMap = $krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');

        $totalBobot = (float) $komponens->sum('bobot');
        $na = 0.0;
        $cplContributions = [];
        $passingGrade = (float) ($rps?->target_passing_grade ?? 60.0);
        $cplSplitMap = $skema->matrix_alokasi['cpl_split'] ?? [];

        foreach ($komponens as $komp) {
            $score = (float) ($nilaiMap->get($komp->id) ?? 0.0);
            $weight = (float) $komp->bobot;
            $na += ($score * $weight) / 100.0;

            $subId = $komp->sub_cpmk_id;
            $subSplit = $cplSplitMap[$subId] ?? ($cplSplitMap[(string) $subId] ?? null);

            if (is_array($subSplit) && ! empty($subSplit)) {
                foreach ($subSplit as $splitCplCode => $ratio) {
                    $splitWeight = $weight * (float) $ratio;
                    if (! isset($cplContributions[$splitCplCode])) {
                        $cplContributions[$splitCplCode] = [
                            'cpl_id' => null,
                            'kode_cpl' => $splitCplCode,
                            'nama_cpl' => $splitCplCode,
                            'total_weight' => 0.0,
                            'weighted_score' => 0.0,
                        ];
                    }
                    $cplContributions[$splitCplCode]['total_weight'] += $splitWeight;
                    $cplContributions[$splitCplCode]['weighted_score'] += ($score * $splitWeight);
                }
            } else {
                $cpl = $komp->subCpmk?->cpl;
                if ($cpl) {
                    $cplCode = $cpl->kode_cpl;
                    if (! isset($cplContributions[$cplCode])) {
                        $cplContributions[$cplCode] = [
                            'cpl_id' => $cpl->id,
                            'kode_cpl' => $cplCode,
                            'nama_cpl' => $cpl->nama_cpl ?: $cpl->deskripsi,
                            'total_weight' => 0.0,
                            'weighted_score' => 0.0,
                        ];
                    }
                    $cplContributions[$cplCode]['total_weight'] += $weight;
                    $cplContributions[$cplCode]['weighted_score'] += ($score * $weight);
                }
            }
        }

        $cplResults = [];
        $unachievedCpls = [];

        foreach ($cplContributions as $code => $data) {
            $cplScore = $data['total_weight'] > 0
                ? round($data['weighted_score'] / $data['total_weight'], 2)
                : 0.0;
            $achieved = $cplScore >= $passingGrade;
            $cplResults[$code] = [
                'score' => $cplScore,
                'achieved' => $achieved,
                'total_weight' => $data['total_weight'],
            ];

            if (! $achieved) {
                $unachievedCpls[] = $code;
            }
        }

        $na = round(min(100.0, max(0.0, $na)), 2);
        $konversi = $this->konversiNilai($na);

        return [
            'na' => $na,
            'nilai_huruf' => $konversi['huruf'],
            'bobot' => $konversi['bobot'],
            'cpl_results' => $cplResults,
            'unachieved_cpls' => $unachievedCpls,
            'all_cpl_achieved' => empty($unachievedCpls),
            'passing_grade' => $passingGrade,
            'is_obe' => true,
        ];
    }

    /**
     * Hitung analitik agregat ketercapaian CPL & CPMK kelas untuk Tab 3
     */
    public function hitungCapaianKelas(Jadwal $jadwal, RpsPenilaianSkema $skema, ?MataKuliahRps $rps = null): array
    {
        $krsList = Krs::with(['mahasiswa.prodi', 'nilaiKomponens', 'khs'])
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get();

        $komponens = $skema->komponens()->with(['subCpmk.cpl', 'subCpmk.cpmk'])->get();
        $passingGrade = (float) ($rps?->target_passing_grade ?? 60.0);
        $totalMahasiswa = $krsList->count();
        $totalObeMahasiswa = $krsList->filter(fn ($k) => $k->isObeApplicable())->count();

        $gradeCounts = [
            'A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0,
            'B-' => 0, 'C+' => 0, 'C' => 0, 'D' => 0, 'E' => 0,
        ];

        $cplStats = [];
        $cpmkStats = [];

        foreach ($krsList as $krs) {
            $hasil = $this->hitungNilaiMahasiswa($krs, $skema, $rps);
            $gradeCounts[$hasil['nilai_huruf']] = ($gradeCounts[$hasil['nilai_huruf']] ?? 0) + 1;

            if (! $krs->isObeApplicable()) {
                continue;
            }

            foreach ($hasil['cpl_results'] as $code => $res) {
                if (! isset($cplStats[$code])) {
                    $cplStats[$code] = [
                        'kode_cpl' => $code,
                        'total_score' => 0.0,
                        'lulus_count' => 0,
                    ];
                }
                $cplStats[$code]['total_score'] += $res['score'];
                if ($res['achieved']) {
                    $cplStats[$code]['lulus_count'] += 1;
                }
            }

            // Hitung CPMK
            $nilaiMap = $krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');
            $cpmkContributions = [];
            foreach ($komponens as $komp) {
                $cpmk = $komp->subCpmk?->cpmk;
                if ($cpmk) {
                    $cpmkCode = $cpmk->kode_cpmk;
                    $score = (float) ($nilaiMap->get($komp->id) ?? 0.0);
                    $weight = (float) $komp->bobot;
                    if (! isset($cpmkContributions[$cpmkCode])) {
                        $cpmkContributions[$cpmkCode] = ['weighted' => 0.0, 'weight' => 0.0];
                    }
                    $cpmkContributions[$cpmkCode]['weighted'] += ($score * $weight);
                    $cpmkContributions[$cpmkCode]['weight'] += $weight;
                }
            }

            foreach ($cpmkContributions as $cpmkCode => $dt) {
                if (! isset($cpmkStats[$cpmkCode])) {
                    $cpmkStats[$cpmkCode] = ['kode_cpmk' => $cpmkCode, 'total_score' => 0.0, 'lulus_count' => 0];
                }
                $cpmkScore = $dt['weight'] > 0 ? ($dt['weighted'] / $dt['weight']) : 0.0;
                $cpmkStats[$cpmkCode]['total_score'] += $cpmkScore;
                if ($cpmkScore >= $passingGrade) {
                    $cpmkStats[$cpmkCode]['lulus_count'] += 1;
                }
            }
        }

        // Hitung persentase ketercapaian CPL (berdasarkan mahasiswa OBE)
        $cplReport = [];
        foreach ($cplStats as $code => $st) {
            $avg = $totalObeMahasiswa > 0 ? round($st['total_score'] / $totalObeMahasiswa, 2) : 0.0;
            $passRate = $totalObeMahasiswa > 0 ? round(($st['lulus_count'] / $totalObeMahasiswa) * 100, 1) : 0.0;
            $cplReport[$code] = [
                'kode_cpl' => $code,
                'avg_score' => $avg,
                'pass_count' => $st['lulus_count'],
                'pass_rate' => $passRate,
                'target_passing_grade' => $passingGrade,
            ];
        }

        // Hitung persentase ketercapaian CPMK (berdasarkan mahasiswa OBE)
        $cpmkReport = [];
        foreach ($cpmkStats as $code => $st) {
            $avg = $totalObeMahasiswa > 0 ? round($st['total_score'] / $totalObeMahasiswa, 2) : 0.0;
            $passRate = $totalObeMahasiswa > 0 ? round(($st['lulus_count'] / $totalObeMahasiswa) * 100, 1) : 0.0;
            $cpmkReport[$code] = [
                'kode_cpmk' => $code,
                'avg_score' => $avg,
                'pass_count' => $st['lulus_count'],
                'pass_rate' => $passRate,
            ];
        }

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_obe_mahasiswa' => $totalObeMahasiswa,
            'passing_grade' => $passingGrade,
            'grade_counts' => $gradeCounts,
            'cpl_report' => $cplReport,
            'cpmk_report' => $cpmkReport,
        ];
    }

    /**
     * Sinkronisasi nilai akhir seluruh mahasiswa di kelas ke tabel KHS
     */
    public function sinkronisasiKeKhs(Jadwal $jadwal, RpsPenilaianSkema $skema, ?MataKuliahRps $rps = null): void
    {
        $krsList = Krs::with(['nilaiKomponens', 'mahasiswa.prodi'])
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get();

        DB::transaction(function () use ($krsList, $skema, $rps) {
            foreach ($krsList as $krs) {
                // Hanya perbarui otomatis dari matriks jika mahasiswa memang kurikulum OBE
                if ($krs->isObeApplicable()) {
                    $hasil = $this->hitungNilaiMahasiswa($krs, $skema, $rps);

                    Khs::updateOrCreate(
                        ['krs_id' => $krs->id],
                        [
                            'nilai_angka' => $hasil['na'],
                            'nilai_huruf' => $hasil['nilai_huruf'],
                            'bobot' => $hasil['bobot'],
                            'tahun_akademik' => $krs->tahun_akademik,
                            'semester_akademik' => $krs->semester_akademik,
                        ]
                    );
                }
            }
        });
    }
}

