<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\Krs;
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
     * Template matriks alokasi bobot instrumen ke Sub-CPMK standar OBE
     */
    public function getDefaultMatrixForJadwal(Jadwal $jadwal): array
    {
        $mk = $jadwal->mataKuliah;
        if (! $mk) {
            return ['rows' => [], 'komponen_rps' => [], 'temuan' => []];
        }

        $allSubCpmks = SubCpmk::whereHas('cpmk', function ($q) use ($mk) {
            $q->where('mata_kuliah_id', $mk->id);
        })->orderBy('id')->get();

        $subCount = $allSubCpmks->count();
        $subIds = $allSubCpmks->pluck('id')->values()->toArray();

        // 1. Template jika tersedia 13 atau lebih Sub-CPMK (persis sesuai standar RPS baku pada mockup)
        if ($subCount >= 13) {
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
                    'keterangan' => 'P15 · presentasi',
                    'allocations' => [
                        (string) $subIds[12] => 4,
                    ],
                ],
                [
                    'nama' => 'UTS',
                    'keterangan' => '6 bagian soal (asumsi)',
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
                    'keterangan' => '7 bagian soal (asumsi)',
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
            // 2. Pembagian proporsional otomatis untuk jumlah Sub-CPMK dinamis (misal 4, 6, 8, 10, dll)
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
                ['nama' => 'UTS', 'keterangan' => "Bagian soal materi paruh 1", 'allocations' => $utsAlloc],
                ['nama' => 'UAS', 'keterangan' => "Bagian soal materi paruh 2", 'allocations' => $uasAlloc],
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
            'Belum ada pemetaan CPMK – CPL dan porsi CPL. Pemetaan bertanda * di sini masih asumsi.',
            'Rancangan tugas proyek membebankan Sub-CPMK, tetapi bobotnya tercatat tunggal di pertemuan akhir.',
            'UTS dan UAS belum dipecah per Sub-CPMK. Pembagian per bagian soal di atas masih asumsi.',
        ];

        return [
            'rows' => $rows,
            'komponen_rps' => $komponenRps,
            'temuan' => $temuan,
        ];
    }

    /**
     * Sinkronisasikan tabel rps_penilaian_komponen dari struktur Matriks Alokasi
     */
    public function syncKomponensFromMatrix(RpsPenilaianSkema $skema, array $matrixData): void
    {
        DB::transaction(function () use ($skema, $matrixData) {
            $skema->update(['matrix_alokasi' => $matrixData]);

            // Hapus komponen instrumen lama dan buat ulang dari matriks
            $skema->komponens()->delete();

            $urutan = 1;
            $rows = $matrixData['rows'] ?? [];

            foreach ($rows as $rIdx => $row) {
                $nama = trim($row['nama'] ?? 'Komponen ' . ($rIdx + 1));
                $allocations = $row['allocations'] ?? [];

                foreach ($allocations as $subCpmkId => $bobotVal) {
                    $bobot = (float) $bobotVal;
                    if ($bobot <= 0) {
                        continue;
                    }

                    $sub = SubCpmk::find($subCpmkId);
                    $subKode = $sub ? $sub->kode_sub_cpmk : "S{$subCpmkId}";

                    // Beri label instrumen yang jelas, misal: Kuis (Sub-CPMK 1) atau UTS (Sub-CPMK 1)
                    $instrumenName = "{$nama} ({$subKode})";

                    RpsPenilaianKomponen::create([
                        'skema_id' => $skema->id,
                        'nama_instrumen' => $instrumenName,
                        'sub_cpmk_id' => (int) $subCpmkId,
                        'bobot' => $bobot,
                        'urutan' => $urutan++,
                    ]);
                }
            }
        });
    }

    /**
     * Hitung Nilai Akhir (NA) dan status ketercapaian CPL per mahasiswa
     */
    public function hitungNilaiMahasiswa(Krs $krs, RpsPenilaianSkema $skema, ?MataKuliahRps $rps = null): array
    {
        $komponens = $skema->komponens()->with(['subCpmk.cpl', 'subCpmk.cpmk'])->get();
        $nilaiMap = $krs->nilaiKomponens->pluck('nilai_angka', 'komponen_id');

        $totalBobot = (float) $komponens->sum('bobot');
        $na = 0.0;
        $cplContributions = [];
        $passingGrade = (float) ($rps?->target_passing_grade ?? 60.0);

        foreach ($komponens as $komp) {
            $score = (float) ($nilaiMap->get($komp->id) ?? 0.0);
            $weight = (float) $komp->bobot;
            $na += ($score * $weight) / 100.0;

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
        ];
    }

    /**
     * Hitung analitik agregat ketercapaian CPL & CPMK kelas untuk Tab 3
     */
    public function hitungCapaianKelas(Jadwal $jadwal, RpsPenilaianSkema $skema, ?MataKuliahRps $rps = null): array
    {
        $krsList = Krs::with(['mahasiswa', 'nilaiKomponens'])
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get();

        $komponens = $skema->komponens()->with(['subCpmk.cpl', 'subCpmk.cpmk'])->get();
        $passingGrade = (float) ($rps?->target_passing_grade ?? 60.0);
        $totalMahasiswa = $krsList->count();

        $gradeCounts = [
            'A' => 0, 'A-' => 0, 'B+' => 0, 'B' => 0,
            'B-' => 0, 'C+' => 0, 'C' => 0, 'D' => 0, 'E' => 0,
        ];

        $cplStats = [];
        $cpmkStats = [];

        foreach ($krsList as $krs) {
            $hasil = $this->hitungNilaiMahasiswa($krs, $skema, $rps);
            $gradeCounts[$hasil['nilai_huruf']] = ($gradeCounts[$hasil['nilai_huruf']] ?? 0) + 1;

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

        // Hitung persentase ketercapaian CPL
        $cplReport = [];
        foreach ($cplStats as $code => $st) {
            $avg = $totalMahasiswa > 0 ? round($st['total_score'] / $totalMahasiswa, 2) : 0.0;
            $passRate = $totalMahasiswa > 0 ? round(($st['lulus_count'] / $totalMahasiswa) * 100, 1) : 0.0;
            $cplReport[$code] = [
                'kode_cpl' => $code,
                'avg_score' => $avg,
                'pass_count' => $st['lulus_count'],
                'pass_rate' => $passRate,
                'target_passing_grade' => $passingGrade,
            ];
        }

        // Hitung persentase ketercapaian CPMK
        $cpmkReport = [];
        foreach ($cpmkStats as $code => $st) {
            $avg = $totalMahasiswa > 0 ? round($st['total_score'] / $totalMahasiswa, 2) : 0.0;
            $passRate = $totalMahasiswa > 0 ? round(($st['lulus_count'] / $totalMahasiswa) * 100, 1) : 0.0;
            $cpmkReport[$code] = [
                'kode_cpmk' => $code,
                'avg_score' => $avg,
                'pass_count' => $st['lulus_count'],
                'pass_rate' => $passRate,
            ];
        }

        return [
            'total_mahasiswa' => $totalMahasiswa,
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
        $krsList = Krs::with('nilaiKomponens')
            ->where('jadwal_id', $jadwal->id)
            ->where('is_manual', false)
            ->where('status', 'Disetujui')
            ->get();

        DB::transaction(function () use ($krsList, $skema, $rps) {
            foreach ($krsList as $krs) {
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
        });
    }
}

