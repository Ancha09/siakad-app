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
     * Inisialisasi komponen instrumen penilaian dari dokumen RPS baku
     */
    public function seedDefaultKomponenFromRps(RpsPenilaianSkema $skema, Jadwal $jadwal, bool $force = false): void
    {
        if ($force) {
            $skema->komponens()->delete();
        }

        $mk = $jadwal->mataKuliah;
        if (! $mk) {
            return;
        }

        $rps = $mk->rpsAktif ?? MataKuliahRps::where('mata_kuliah_id', $mk->id)->where('is_active', true)->first();
        if (! $rps || empty($rps->komponen_bobot_default)) {
            return;
        }

        $subCpmks = SubCpmk::whereHas('cpmk', function ($q) use ($mk) {
            $q->where('mata_kuliah_id', $mk->id);
        })->orderBy('id')->get();

        $subCount = $subCpmks->count();
        $urutan = 1;
        $subIndex = 0;

        foreach ($rps->komponen_bobot_default as $nama => $bobot) {
            $assignedSubId = $subCount > 0 ? $subCpmks[$subIndex % $subCount]->id : null;

            RpsPenilaianKomponen::create([
                'skema_id' => $skema->id,
                'nama_instrumen' => trim($nama),
                'sub_cpmk_id' => $assignedSubId,
                'bobot' => (float) $bobot,
                'urutan' => $urutan++,
            ]);

            $subIndex++;
        }
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

