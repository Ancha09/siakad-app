<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahRps;
use App\Models\Prodi;
use Illuminate\Support\Collection;

class ObeCplAnalyticsService
{
    public function __construct(
        private readonly ObeAssessmentService $obeService
    ) {}

    /**
     * Normalisasi kode CPL agar seragam (misal "CPL-01", "cpl 1" -> "CPL 1")
     */
    public function normalizeCplCode(string $raw): string
    {
        $clean = strtoupper(trim($raw));
        if (preg_match('/^CPL[\s\-_]*0*(\d+[A-Z]?)$/i', $clean, $m)) {
            return 'CPL ' . strtoupper($m[1]);
        }

        return preg_replace('/\s+/', ' ', $clean) ?: $clean;
    }

    /**
     * Ambil daftar Program Studi beserta indikator apakah prodi tersebut memiliki data CPL / RPS OBE
     */
    public function getProdisWithCplStatus(): Collection
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        return $prodis->map(function (Prodi $prodi) {
            $cplCount = Cpl::where(function ($q) use ($prodi) {
                $q->where('prodi_id', $prodi->id)
                    ->orWhere('program_studi_id', $prodi->id);
            })->count();

            $rpsCount = MataKuliahRps::whereHas('mataKuliah', function ($q) use ($prodi, $cplCount) {
                $q->where('prodi_id', $prodi->id)
                    ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $prodi->id))
                    ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($prodi) {
                        $c->where('prodi_id', $prodi->id)
                            ->orWhere('program_studi_id', $prodi->id);
                    });
                if ($cplCount > 0 || $prodi->is_obe_active) {
                    $q->orWhereNull('prodi_id');
                }
            })->count();

            $prodi->setAttribute('cpl_count', $cplCount);
            $prodi->setAttribute('rps_count', $rpsCount);
            $prodi->setAttribute('has_cpl_data', $cplCount > 0 || $rpsCount > 0 || (bool) $prodi->is_obe_active);

            return $prodi;
        });
    }

    /**
     * Ambil daftar angkatan (kohort) mahasiswa pada prodi terpilih
     */
    public function getCohortsForProdi(int $prodiId): Collection
    {
        return Mahasiswa::query()
            ->where('prodi_id', $prodiId)
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan')
            ->map(fn ($yr) => (int) $yr)
            ->filter(fn (int $yr) => $yr > 1990)
            ->values();
    }

    /**
     * Bangun data lengkap Analisis CPL untuk Prodi & Filter Kohort terpilih
     */
    public function buildAnalytics(Prodi $prodi, ?int $angkatan = null, float $ambang = 60.0, float $targetPct = 75.0): array
    {
        // 1. Kumpulkan seluruh CPL milik Prodi ini
        $dbCpls = Cpl::query()
            ->where(function ($q) use ($prodi) {
                $q->where('prodi_id', $prodi->id)
                    ->orWhere('program_studi_id', $prodi->id);
            })
            ->orderBy('sort_order')
            ->orderBy('kode_cpl')
            ->get();

        $cplCatalog = [];
        foreach ($dbCpls as $cpl) {
            $code = $this->normalizeCplCode($cpl->kode_cpl);
            if (! isset($cplCatalog[$code])) {
                $cplCatalog[$code] = [
                    'id' => $cpl->id,
                    'kode_cpl' => $code,
                    'nama_cpl' => $cpl->nama_cpl ?: ($cpl->deskripsi ?: $code),
                    'deskripsi' => $cpl->deskripsi ?: ($cpl->nama_cpl ?: ''),
                ];
            }
        }

        // 2. Kumpulkan seluruh Mata Kuliah yang memiliki RPS (porsi_cpl) atau Sub-CPMK terpetakan ke CPL Prodi ini
        $hasProdiCpl = ! empty($cplCatalog) || (bool) $prodi->is_obe_active;
        $courses = MataKuliah::with(['rpsAktif', 'rpsList', 'cpmks.subCpmks.cpl'])
            ->where(function ($q) use ($prodi, $hasProdiCpl) {
                $q->where('prodi_id', $prodi->id)
                    ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $prodi->id))
                    ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($prodi) {
                        $c->where('prodi_id', $prodi->id)
                            ->orWhere('program_studi_id', $prodi->id);
                    });
                if ($hasProdiCpl) {
                    $q->orWhere(function ($u) {
                        $u->whereNull('prodi_id')->whereHas('rpsList');
                    });
                }
            })
            ->orderBy('semester')
            ->orderBy('kode_mk')
            ->get();

        // Map: courseId => ['mk' => MataKuliah, 'sks' => int, 'rps' => ?MataKuliahRps, 'weights' => [cplCode => float (0..1)]]
        $courseWeightsMap = [];

        foreach ($courses as $mk) {
            $rps = $mk->rpsAktif ?? $mk->rpsList->sortByDesc('is_active')->sortByDesc('id')->first();
            $weights = $this->extractCourseCplWeights($mk, $rps);

            if (empty($weights)) {
                continue;
            }

            $courseWeightsMap[$mk->id] = [
                'mk' => $mk,
                'sks' => max(1, (int) $mk->sks),
                'rps' => $rps,
                'weights' => $weights, // e.g. ['CPL 1' => 0.66, 'CPL 6' => 0.34]
            ];

            // Pastikan setiap kode CPL dari RPS terdaftar di katalog CPL
            foreach (array_keys($weights) as $code) {
                if (! isset($cplCatalog[$code])) {
                    $cplCatalog[$code] = [
                        'id' => null,
                        'kode_cpl' => $code,
                        'nama_cpl' => $code,
                        'deskripsi' => '',
                    ];
                }
            }
        }

        // Urutkan kode CPL secara natural (CPL 1, CPL 2, ..., CPL 9)
        uksort($cplCatalog, fn ($a, $b) => strnatcasecmp($a, $b));

        // 3. Ambil daftar mahasiswa di Prodi (dan angkatan jika difilter)
        $studentsQuery = Mahasiswa::query()
            ->where('prodi_id', $prodi->id)
            ->when($angkatan !== null, fn ($q) => $q->where('angkatan', $angkatan))
            ->orderBy('nim')
            ->orderBy('nama');

        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id')->all();

        // 4. Ambil seluruh KRS Disetujui milik mahasiswa tersebut pada mata kuliah yang relevan
        $relevantCourseIds = array_keys($courseWeightsMap);
        $krsRecords = collect();

        if (! empty($studentIds) && ! empty($relevantCourseIds)) {
            $krsRecords = Krs::with([
                'khs',
                'nilaiKomponens',
                'jadwal.mataKuliah.rpsAktif',
                'jadwal.mataKuliah.rpsList',
                'jadwal.mataKuliah.cpmks.subCpmks.cpl',
                'jadwal.skemaPenilaian.komponens.subCpmk.cpl',
                'jadwal.skemaPenilaian.komponens.subCpmk.cpmk',
                'mataKuliahManual',
                'mahasiswa.prodi',
            ])
                ->whereIn('mahasiswa_id', $studentIds)
                ->where('status', 'Disetujui')
                ->where(function ($q) use ($relevantCourseIds) {
                    $q->whereIn('mata_kuliah_id', $relevantCourseIds)
                        ->orWhereHas('jadwal', fn ($j) => $j->whereIn('mata_kuliah_id', $relevantCourseIds));
                })
                ->orderBy('id')
                ->get();
        }

        // Cache skema per jadwal agar tidak query berulang
        $jadwalSkemaCache = [];

        // Map skor mahasiswa per mata kuliah & per CPL:
        // $studentCourseCplScores[$studentId][$courseId][$cplCode] = float (0..100)
        $studentCourseCplScores = [];

        foreach ($krsRecords as $krs) {
            $mk = $krs->mata_kuliah_efektif;
            if (! $mk || ! isset($courseWeightsMap[$mk->id])) {
                continue;
            }

            $courseInfo = $courseWeightsMap[$mk->id];
            $courseCplWeights = $courseInfo['weights'];
            $rps = $courseInfo['rps'];

            $cplScoresInCourse = $this->resolveStudentCplScoresInCourse(
                $krs,
                $courseCplWeights,
                $rps,
                $jadwalSkemaCache
            );

            if (! empty($cplScoresInCourse)) {
                $studentCourseCplScores[$krs->mahasiswa_id][$mk->id] = $cplScoresInCourse;
            }
        }

        // 5. Bangun struktur analitik per CPL
        $cplAnalytics = [];

        foreach ($cplCatalog as $cplCode => $cplMeta) {
            // Daftar MK yang berkontribusi pada CPL ini
            $contributingCourses = [];
            $totalTheoreticalWs = 0.0;

            foreach ($courseWeightsMap as $mkId => $cInfo) {
                if (! isset($cInfo['weights'][$cplCode]) || $cInfo['weights'][$cplCode] <= 0) {
                    continue;
                }
                $w = (float) $cInfo['weights'][$cplCode];
                $sks = (int) $cInfo['sks'];
                $ws = round($w * $sks, 4);
                $totalTheoreticalWs += $ws;

                $contributingCourses[$mkId] = [
                    'mata_kuliah_id' => $mkId,
                    'kode_mk' => $cInfo['mk']->kode_mk,
                    'nama_mk' => $cInfo['mk']->nama_mk,
                    'semester' => $cInfo['mk']->semester,
                    'sks' => $sks,
                    'w' => round($w, 4),
                    'w_percent' => round($w * 100, 2),
                    'ws' => $ws,
                    'jatah_percent' => 0.0, // diisi setelah totalTheoreticalWs diketahui
                    'scores' => [],
                    'avg_score' => null,
                    'kontribusi_avg' => null,
                    'student_count' => 0,
                ];
            }

            foreach ($contributingCourses as $mkId => &$cRow) {
                $cRow['jatah_percent'] = $totalTheoreticalWs > 0
                    ? round(($cRow['ws'] / $totalTheoreticalWs) * 100, 1)
                    : 0.0;
            }
            unset($cRow);

            // Hitung nilai akhir CPL untuk setiap mahasiswa
            $studentResults = [];

            foreach ($students as $student) {
                $stuCourses = $studentCourseCplScores[$student->id] ?? [];
                $sumWs = 0.0;
                $sumKontribusi = 0.0;
                $mkBreakdown = [];

                foreach ($contributingCourses as $mkId => $cRow) {
                    if (! isset($stuCourses[$mkId][$cplCode])) {
                        continue;
                    }

                    $skor = (float) $stuCourses[$mkId][$cplCode];
                    $ws = (float) $cRow['ws'];
                    $kontribusi = $ws * $skor;

                    $sumWs += $ws;
                    $sumKontribusi += $kontribusi;

                    $contributingCourses[$mkId]['scores'][] = $skor;

                    $mkBreakdown[] = [
                        'kode_mk' => $cRow['kode_mk'],
                        'nama_mk' => $cRow['nama_mk'],
                        'sks' => $cRow['sks'],
                        'w' => $cRow['w'],
                        'w_percent' => $cRow['w_percent'],
                        'ws' => round($ws, 2),
                        'skor' => round($skor, 2),
                        'kontribusi' => round($kontribusi, 2),
                    ];
                }

                if ($sumWs > 0) {
                    $finalCplScore = round($sumKontribusi / $sumWs, 2);
                    $studentResults[] = [
                        'mahasiswa_id' => $student->id,
                        'nim' => $student->nim,
                        'nama' => $student->nama,
                        'angkatan' => $student->angkatan,
                        'sum_ws' => round($sumWs, 2),
                        'sum_kontribusi' => round($sumKontribusi, 2),
                        'score' => $finalCplScore,
                        'passed' => $finalCplScore >= $ambang,
                        'mk_breakdown' => $mkBreakdown,
                    ];
                }
            }

            // Hitung rata-rata skor per MK untuk CPL ini
            $sumCourseWsWithData = 0.0;
            $sumCourseKontribusiAvg = 0.0;

            foreach ($contributingCourses as $mkId => &$cRow) {
                $count = count($cRow['scores']);
                $cRow['student_count'] = $count;
                if ($count > 0) {
                    $avgMk = round(array_sum($cRow['scores']) / $count, 2);
                    $kontribAvg = round($cRow['ws'] * $avgMk, 2);
                    $cRow['avg_score'] = $avgMk;
                    $cRow['kontribusi_avg'] = $kontribAvg;
                    $sumCourseWsWithData += $cRow['ws'];
                    $sumCourseKontribusiAvg += ($cRow['ws'] * $avgMk);
                }
                unset($cRow['scores']);
            }
            unset($cRow);

            $evaluatedCount = count($studentResults);
            $passedCount = collect($studentResults)->where('passed', true)->count();
            $avgCplScore = $evaluatedCount > 0
                ? round(collect($studentResults)->avg('score'), 2)
                : ($sumCourseWsWithData > 0 ? round($sumCourseKontribusiAvg / $sumCourseWsWithData, 2) : null);

            $pctAchieved = $evaluatedCount > 0
                ? round(($passedCount / $evaluatedCount) * 100, 1)
                : null;

            $cplAnalytics[] = [
                'kode_cpl' => $cplCode,
                'nama_cpl' => $cplMeta['nama_cpl'],
                'deskripsi' => $cplMeta['deskripsi'],
                'total_ws' => round($totalTheoreticalWs, 2),
                'course_count' => count($contributingCourses),
                'evaluated_students_count' => $evaluatedCount,
                'passed_students_count' => $passedCount,
                'weighted_average' => $avgCplScore,
                'percent_achieved' => $pctAchieved,
                'is_target_met' => $pctAchieved !== null && $pctAchieved >= $targetPct,
                'courses' => array_values($contributingCourses),
                'students' => $studentResults,
            ];
        }

        return [
            'prodi' => [
                'id' => $prodi->id,
                'nama_prodi' => $prodi->nama_prodi,
                'jenjang' => $prodi->jenjang,
            ],
            'angkatan' => $angkatan,
            'ambang' => $ambang,
            'target_pct' => $targetPct,
            'total_students_in_cohort' => $students->count(),
            'cpls' => $cplAnalytics,
        ];
    }

    /**
     * Ekstrak bobot proporsi CPL (0..1) untuk satu Mata Kuliah dari RPS Aktif atau Sub-CPMK
     *
     * @return array<string, float>
     */
    public function extractCourseCplWeights(MataKuliah $mk, ?MataKuliahRps $rps = null): array
    {
        $weights = [];

        // 1. Prioritas utama: kolom porsi_cpl pada dokumen RPS
        if ($rps && ! empty($rps->porsi_cpl) && is_array($rps->porsi_cpl)) {
            $rawTotal = 0.0;
            $normalized = [];

            foreach ($rps->porsi_cpl as $rawCode => $val) {
                $num = (float) $val;
                if ($num <= 0) {
                    continue;
                }
                $code = $this->normalizeCplCode((string) $rawCode);
                $normalized[$code] = ($normalized[$code] ?? 0.0) + $num;
                $rawTotal += $num;
            }

            if ($rawTotal > 0) {
                foreach ($normalized as $code => $num) {
                    // Jika disimpan dalam persen (misal 66 untuk 66%), konversi ke desimal 0.66
                    $fraction = $rawTotal > 1.05 ? ($num / 100.0) : $num;
                    $weights[$code] = round($fraction, 6);
                }

                return $weights;
            }
        }

        // 2. Fallback: hitung dari bobot_default Sub-CPMK yang terhubung ke CPL
        $subCpmks = $mk->cpmks->flatMap(fn ($cpmk) => $cpmk->subCpmks);
        if ($subCpmks->isEmpty()) {
            return [];
        }

        $totalBobot = 0.0;
        $byCpl = [];

        foreach ($subCpmks as $sub) {
            if (! $sub->cpl) {
                continue;
            }
            $code = $this->normalizeCplCode($sub->cpl->kode_cpl);
            $bobot = (float) ($sub->bobot_default ?? 0.0);
            if ($bobot <= 0) {
                $bobot = 1.0; // jika belum ada bobot_default, bagi rata
            }
            $byCpl[$code] = ($byCpl[$code] ?? 0.0) + $bobot;
            $totalBobot += $bobot;
        }

        if ($totalBobot > 0) {
            foreach ($byCpl as $code => $b) {
                $weights[$code] = round($b / $totalBobot, 6);
            }
        }

        return $weights;
    }

    /**
     * Hitung skor CPL mahasiswa (skala 0..100) pada suatu KRS Mata Kuliah untuk setiap CPL yang dibebankan
     *
     * @param  array<string, float>  $courseCplWeights
     * @param  array<int, mixed>  $jadwalSkemaCache
     * @return array<string, float>
     */
    private function resolveStudentCplScoresInCourse(
        Krs $krs,
        array $courseCplWeights,
        ?MataKuliahRps $rps,
        array &$jadwalSkemaCache
    ): array {
        $result = [];

        // Cek apakah mahasiswa punya rincian nilai komponen OBE pada jadwal ini
        $hasKomponenScores = $krs->nilaiKomponens && $krs->nilaiKomponens->isNotEmpty();
        $jadwal = $krs->jadwal;

        if ($hasKomponenScores && $jadwal) {
            if (! isset($jadwalSkemaCache[$jadwal->id])) {
                $jadwalSkemaCache[$jadwal->id] = $jadwal->skemaPenilaian
                    ?: $this->obeService->getOrCreateSkema($jadwal);
            }
            $skema = $jadwalSkemaCache[$jadwal->id];

            if ($skema) {
                $calc = $this->obeService->hitungNilaiMahasiswa($krs, $skema, $rps);
                $cplResults = $calc['cpl_results'] ?? [];
                $normalizedCalcCpl = [];

                foreach ($cplResults as $rawCode => $cData) {
                    $normCode = $this->normalizeCplCode((string) $rawCode);
                    $normalizedCalcCpl[$normCode] = (float) ($cData['score'] ?? 0.0);
                }

                $fallbackNa = (float) ($calc['na'] ?? 0.0);
                if ($fallbackNa <= 0 && $krs->khs && $krs->khs->nilai_angka !== null) {
                    $fallbackNa = (float) $krs->khs->nilai_angka;
                }

                foreach (array_keys($courseCplWeights) as $cplCode) {
                    if (isset($normalizedCalcCpl[$cplCode])) {
                        $result[$cplCode] = round($normalizedCalcCpl[$cplCode], 2);
                    } elseif ($fallbackNa > 0) {
                        $result[$cplCode] = round($fallbackNa, 2);
                    }
                }

                if (! empty($result)) {
                    return $result;
                }
            }
        }

        // Fallback ke nilai KHS (nilai_angka skala 0..100, atau bobot * 25 jika hanya ada bobot)
        $khs = $krs->khs;
        if (! $khs) {
            return [];
        }

        $score100 = null;
        if ($khs->nilai_angka !== null && (float) $khs->nilai_angka > 0) {
            $score100 = (float) $khs->nilai_angka;
        } elseif ($khs->bobot !== null && (float) $khs->bobot > 0) {
            $score100 = (float) $khs->bobot * 25.0;
        }

        if ($score100 === null) {
            return [];
        }

        $score100 = round(min(100.0, max(0.0, $score100)), 2);
        foreach (array_keys($courseCplWeights) as $cplCode) {
            $result[$cplCode] = $score100;
        }

        return $result;
    }
}
