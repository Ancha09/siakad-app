<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Services\ObeCplAnalyticsService;
use App\Services\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnalisisCplController extends Controller
{
    public function index(Request $request, ObeCplAnalyticsService $analyticsService)
    {
        $state = $this->resolveAnalyticsState($request, $analyticsService);

        return view('admin.analisis_cpl.index', $state);
    }

    public function excel(
        Request $request,
        ObeCplAnalyticsService $analyticsService,
        ReportExporter $exporter
    ) {
        $state = $this->resolveAnalyticsState($request, $analyticsService);
        /** @var Prodi|null $prodi */
        $prodi = $state['selectedProdi'];
        $analytics = $state['analytics'];
        $ambang = (float) $state['ambang'];
        $target = (float) $state['target'];
        $cpls = $analytics['cpls'] ?? [];

        $prodiName = $prodi ? ($prodi->jenjang . ' ' . $prodi->nama_prodi) : 'Semua Program Studi';
        $filterDesc = $this->buildFilterDescription($prodiName, $state['selectedAngkatan'], $ambang, $target);

        $summaryRows = [];
        $courseRows = [];
        $studentRows = [];

        foreach ($cpls as $cpl) {
            $kode = $cpl['kode_cpl'];
            $avg = $cpl['weighted_average'] !== null ? number_format((float) $cpl['weighted_average'], 2, ',', '.') : '-';
            $pct = $cpl['percent_achieved'] !== null ? number_format((float) $cpl['percent_achieved'], 1, ',', '.') . '%' : '-';
            $statusTarget = $cpl['percent_achieved'] === null
                ? 'Belum Ada Nilai'
                : ($cpl['is_target_met'] ? 'Tercapai' : 'Belum Tercapai');

            $summaryRows[] = [
                $kode,
                $cpl['deskripsi'] ?: $cpl['nama_cpl'],
                $cpl['course_count'],
                number_format((float) $cpl['total_ws'], 2, ',', '.'),
                $avg,
                $cpl['evaluated_students_count'],
                $cpl['passed_students_count'],
                $pct,
                $statusTarget,
            ];

            foreach ($cpl['courses'] ?? [] as $m) {
                $avgMk = $m['avg_score'] !== null ? number_format((float) $m['avg_score'], 2, ',', '.') : '-';
                $kontribMk = $m['kontribusi_avg'] !== null ? number_format((float) $m['kontribusi_avg'], 2, ',', '.') : '-';
                $courseRows[] = [
                    $kode,
                    $m['kode_mk'],
                    $m['nama_mk'],
                    $m['sks'],
                    number_format((float) $m['w_percent'], 2, ',', '.') . '%',
                    number_format((float) $m['w'], 4, ',', '.'),
                    number_format((float) $m['ws'], 2, ',', '.'),
                    number_format((float) $m['jatah_percent'], 1, ',', '.') . '%',
                    $avgMk,
                    $kontribMk,
                ];
            }

            foreach ($cpl['students'] ?? [] as $st) {
                $studentRows[] = [
                    $kode,
                    $st['nim'],
                    $st['nama'],
                    $st['angkatan'] ?: '-',
                    number_format((float) $st['sum_ws'], 2, ',', '.'),
                    number_format((float) $st['sum_kontribusi'], 2, ',', '.'),
                    number_format((float) $st['score'], 2, ',', '.'),
                    $st['passed'] ? 'Ya (Lolos)' : 'Tidak (Belum Lolos)',
                ];
            }
        }

        $sheets = [
            [
                'title' => 'Ringkasan CPL',
                'headings' => [
                    'Kode CPL',
                    'Deskripsi Capaian Pembelajaran',
                    'Jumlah MK',
                    'Total Bobot (w x SKS)',
                    'Weighted Average (Rata-rata)',
                    'Mahasiswa Dinilai',
                    'Mahasiswa Lolos (>= ' . (int) $ambang . ')',
                    '% Ketercapaian',
                    'Status Target (' . (int) $target . '%)',
                ],
                'rows' => $summaryRows,
            ],
            [
                'title' => 'Kontribusi Mata Kuliah',
                'headings' => [
                    'Kode CPL',
                    'Kode MK',
                    'Nama Mata Kuliah',
                    'SKS',
                    'Porsi CPL (%)',
                    'Bobot (w)',
                    'w x SKS',
                    'Jatah Bobot (%)',
                    'Rata-rata Skor CPL di MK',
                    'Kontribusi ((w x SKS) x Skor)',
                ],
                'rows' => $courseRows,
            ],
            [
                'title' => 'Capaian Kohort Mahasiswa',
                'headings' => [
                    'Kode CPL',
                    'NIM',
                    'Nama Mahasiswa',
                    'Angkatan',
                    'Total (w x SKS)',
                    'Total Kontribusi',
                    'Nilai Capaian CPL',
                    'Lolos (>= ' . (int) $ambang . ')?',
                ],
                'rows' => $studentRows,
            ],
        ];

        $slug = Str::slug($prodiName ?: 'prodi');
        $filename = 'analisis-cpl-' . $slug . '-' . now()->format('Ymd-His') . '.xlsx';

        return $exporter->excel(
            $filename,
            'Laporan Analisis Ketercapaian CPL — ' . $prodiName,
            $filterDesc,
            $sheets
        );
    }

    public function pdf(Request $request, ObeCplAnalyticsService $analyticsService)
    {
        $state = $this->resolveAnalyticsState($request, $analyticsService);
        /** @var Prodi|null $prodi */
        $prodi = $state['selectedProdi'];
        $prodiName = $prodi ? ($prodi->jenjang . ' ' . $prodi->nama_prodi) : 'Semua Program Studi';
        $filterDesc = $this->buildFilterDescription(
            $prodiName,
            $state['selectedAngkatan'],
            (float) $state['ambang'],
            (float) $state['target']
        );

        $slug = Str::slug($prodiName ?: 'prodi');
        $filename = 'analisis-cpl-' . $slug . '-' . now()->format('Ymd-His') . '.pdf';

        return Pdf::loadView('admin.analisis_cpl.pdf', $state + [
            'prodiName' => $prodiName,
            'filterDescription' => $filterDesc,
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    private function resolveAnalyticsState(Request $request, ObeCplAnalyticsService $analyticsService): array
    {
        $prodis = $analyticsService->getProdisWithCplStatus();

        $defaultProdi = $prodis->firstWhere('has_cpl_data', true) ?? $prodis->first();
        $selectedProdiId = (int) ($request->query('prodi_id') ?: ($defaultProdi?->id ?? 0));
        /** @var Prodi|null $selectedProdi */
        $selectedProdi = $prodis->firstWhere('id', $selectedProdiId) ?? $defaultProdi;

        $cohorts = $selectedProdi
            ? $analyticsService->getCohortsForProdi($selectedProdi->id)
            : collect();

        $rawAngkatan = $request->query('angkatan');
        if ($rawAngkatan === 'all') {
            $selectedAngkatan = null;
        } elseif ($rawAngkatan !== null && $rawAngkatan !== '') {
            $selectedAngkatan = max(ObeCplAnalyticsService::MIN_RPS_ANGKATAN, (int) $rawAngkatan);
        } else {
            $selectedAngkatan = (int) ($cohorts->first() ?? ObeCplAnalyticsService::MIN_RPS_ANGKATAN);
        }

        $mode = in_array($request->query('mode'), ['weighted_average', 'persen_ketercapaian'], true)
            ? $request->query('mode')
            : 'weighted_average';

        $ambang = $request->filled('ambang')
            ? max(0.0, min(100.0, (float) $request->query('ambang')))
            : 60.0;

        $target = $request->filled('target')
            ? max(0.0, min(100.0, (float) $request->query('target')))
            : 75.0;

        $analytics = $selectedProdi
            ? $analyticsService->buildAnalytics($selectedProdi, $selectedAngkatan, $ambang, $target)
            : [
                'prodi' => null,
                'angkatan' => null,
                'ambang' => $ambang,
                'target_pct' => $target,
                'total_students_in_cohort' => 0,
                'cpls' => [],
            ];

        return compact(
            'prodis',
            'selectedProdi',
            'cohorts',
            'selectedAngkatan',
            'mode',
            'ambang',
            'target',
            'analytics'
        );
    }

    private function buildFilterDescription(string $prodiName, ?int $angkatan, float $ambang, float $target): string
    {
        $kohortText = $angkatan ? "Angkatan {$angkatan}" : 'Semua Angkatan';

        return "Program Studi: {$prodiName} | Kohort: {$kohortText} | Ambang Lulus CPL: >= " . (int) $ambang . " | Target Kelulusan: " . (int) $target . "%";
    }
}
