<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Presensi;
use App\Models\Prodi;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class AttendanceAnalyticsService
{
    /**
     * Mengambil seluruh analitik absensi berdasarkan rentang tanggal
     */
    public function getAnalytics(
        string $range = '30_days',
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $tahunAkademik = null
    ): array {
        [$start, $end, $rangeKey, $rangeLabel] = $this->resolveDateRange($range, $startDate, $endDate);

        // Ambil data hari ini
        $todayStats = $this->getTodaySummary($tahunAkademik);

        // Ambil data tren harian (Line/Area Chart)
        $trend = $this->getDailyTrend($start, $end, $tahunAkademik);

        // Ambil distribusi status untuk Donut/Pie Chart
        $distribution = $this->getStatusDistribution($start, $end, $todayStats, $tahunAkademik);

        // Ambil perbandingan antar Program Studi / Departemen (Bar Chart)
        $prodiComparison = $this->getProdiComparison($start, $end, $tahunAkademik);

        // Cek apakah ada data nyata di database
        $hasRealData = $trend['total_records'] > 0 || $todayStats['today_total'] > 0;

        // Jika tidak ada data sama sekali di rentang ini, buat data simulasi yang realistis
        if (! $hasRealData) {
            return $this->generateSimulatedAnalytics($start, $end, $rangeKey, $rangeLabel, $todayStats);
        }

        return [
            'range' => $rangeKey,
            'range_label' => $rangeLabel,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'has_real_data' => true,
            'stats' => $todayStats,
            'trend' => $trend,
            'distribution' => $distribution,
            'prodi_comparison' => $prodiComparison,
        ];
    }

    /**
     * Memetakan rentang tanggal dari input filter
     */
    public function resolveDateRange(string $range, ?string $startDate, ?string $endDate): array
    {
        $today = Carbon::today();

        switch ($range) {
            case 'today':
                $start = $today->copy()->startOfDay();
                $end = $today->copy()->endOfDay();
                $rangeLabel = 'Hari Ini (' . $today->translatedFormat('d M Y') . ')';
                $rangeKey = 'today';
                break;

            case '7_days':
                $start = $today->copy()->subDays(6)->startOfDay();
                $end = $today->copy()->endOfDay();
                $rangeLabel = '7 Hari Terakhir (' . $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M Y') . ')';
                $rangeKey = '7_days';
                break;

            case 'this_month':
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfDay();
                $rangeLabel = 'Bulan Ini (' . $today->translatedFormat('F Y') . ')';
                $rangeKey = 'this_month';
                break;

            case 'custom':
                if ($startDate && $endDate) {
                    try {
                        $start = Carbon::parse($startDate)->startOfDay();
                        $end = Carbon::parse($endDate)->endOfDay();
                        if ($start->greaterThan($end)) {
                            [$start, $end] = [$end, $start];
                        }
                        $rangeLabel = $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y');
                        $rangeKey = 'custom';
                        break;
                    } catch (\Exception $e) {
                        // fallback jika format tanggal tidak valid
                    }
                }
                // Fallback default
                $start = $today->copy()->subDays(29)->startOfDay();
                $end = $today->copy()->endOfDay();
                $rangeLabel = '30 Hari Terakhir';
                $rangeKey = '30_days';
                break;

            case '30_days':
            default:
                $start = $today->copy()->subDays(29)->startOfDay();
                $end = $today->copy()->endOfDay();
                $rangeLabel = '30 Hari Terakhir (' . $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M Y') . ')';
                $rangeKey = '30_days';
                break;
        }

        return [$start, $end, $rangeKey, $rangeLabel];
    }

    /**
     * Ringkasan absensi hari ini untuk Kartu Statistik (Stat Cards)
     */
    public function getTodaySummary(?string $tahunAkademik = null): array
    {
        $today = Carbon::today()->toDateString();
        $totalStudents = Mahasiswa::where('is_active', true)->count() ?: Mahasiswa::count();

        $query = Presensi::whereDate('tanggal', $today);

        if ($tahunAkademik) {
            $query->whereHas('krs', fn ($q) => $q->where('tahun_akademik', $tahunAkademik));
        }

        $records = (clone $query)->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray();

        $hadir = (int) ($records['Hadir'] ?? 0);
        $izin = (int) ($records['Izin'] ?? 0);
        $sakit = (int) ($records['Sakit'] ?? 0);
        $alpa = (int) (($records['Alpha'] ?? 0) + ($records['Alpa'] ?? 0));
        $totalToday = $hadir + $izin + $sakit + $alpa;

        // Cari catatan keterlambatan jika ada kata 'terlambat' di keterangan
        $keterlambatan = (clone $query)
            ->where(function ($q) {
                $q->where('keterangan', 'LIKE', '%terlambat%')
                  ->orWhere('keterangan', 'LIKE', '%telat%');
            })->count();

        $todayRate = $totalToday > 0 ? round(($hadir / $totalToday) * 100, 1) : 0;

        return [
            'total_students' => $totalStudents,
            'today_total' => $totalToday,
            'today_hadir' => $hadir,
            'today_izin' => $izin,
            'today_sakit' => $sakit,
            'today_izin_sakit' => $izin + $sakit,
            'today_alpa' => $alpa,
            'today_terlambat' => $keterlambatan,
            'today_rate' => $todayRate,
            'date_formatted' => Carbon::today()->translatedFormat('l, d F Y'),
        ];
    }

    /**
     * Mengambil tren harian (Line Chart) dalam rentang tanggal
     */
    public function getDailyTrend(Carbon $start, Carbon $end, ?string $tahunAkademik = null): array
    {
        $query = Presensi::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);

        if ($tahunAkademik) {
            $query->whereHas('krs', fn ($q) => $q->where('tahun_akademik', $tahunAkademik));
        }

        $rawGrouped = $query->select(
            'tanggal',
            'status',
            DB::raw('count(*) as count')
        )->groupBy('tanggal', 'status')
        ->get();

        $byDateStatus = [];
        $totalRecords = 0;

        foreach ($rawGrouped as $row) {
            $dateKey = Carbon::parse($row->tanggal)->toDateString();
            $status = $row->status;
            if ($status === 'Alpha') {
                $status = 'Alpa';
            }
            $byDateStatus[$dateKey][$status] = (int) $row->count;
            $totalRecords += (int) $row->count;
        }

        // Susun setiap tanggal dari start hingga end tanpa jeda
        $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());
        $labels = [];
        $fullDates = [];
        $hadirSeries = [];
        $izinSeries = [];
        $sakitSeries = [];
        $alpaSeries = [];
        $totalSeries = [];
        $rateSeries = [];

        foreach ($period as $date) {
            $dStr = $date->toDateString();
            $dCarbon = Carbon::parse($dStr);

            $h = $byDateStatus[$dStr]['Hadir'] ?? 0;
            $i = $byDateStatus[$dStr]['Izin'] ?? 0;
            $s = $byDateStatus[$dStr]['Sakit'] ?? 0;
            $a = ($byDateStatus[$dStr]['Alpa'] ?? 0) + ($byDateStatus[$dStr]['Alpha'] ?? 0);
            $tot = $h + $i + $s + $a;
            $rate = $tot > 0 ? round(($h / $tot) * 100, 1) : 0;

            // Format label: jika rentang > 14 hari gunakan d/m, jika <= 14 hari gunakan d M
            $format = $start->diffInDays($end) > 14 ? 'd/m' : 'd M';
            $labels[] = $dCarbon->translatedFormat($format);
            $fullDates[] = $dStr;

            $hadirSeries[] = $h;
            $izinSeries[] = $i;
            $sakitSeries[] = $s;
            $alpaSeries[] = $a;
            $totalSeries[] = $tot;
            $rateSeries[] = $rate;
        }

        return [
            'labels' => $labels,
            'full_dates' => $fullDates,
            'hadir' => $hadirSeries,
            'izin' => $izinSeries,
            'sakit' => $sakitSeries,
            'alpa' => $alpaSeries,
            'total' => $totalSeries,
            'rates' => $rateSeries,
            'total_records' => $totalRecords,
            'total_hadir' => array_sum($hadirSeries),
            'total_izin' => array_sum($izinSeries),
            'total_sakit' => array_sum($sakitSeries),
            'total_alpa' => array_sum($alpaSeries),
        ];
    }

    /**
     * Mengambil distribusi status untuk Donut/Pie Chart
     */
    public function getStatusDistribution(
        Carbon $start,
        Carbon $end,
        array $todayStats,
        ?string $tahunAkademik = null
    ): array {
        // Prioritaskan status hari ini jika ada log
        if ($todayStats['today_total'] > 0) {
            $total = $todayStats['today_total'];
            $hadir = $todayStats['today_hadir'];
            $izin = $todayStats['today_izin'];
            $sakit = $todayStats['today_sakit'];
            $alpa = $todayStats['today_alpa'];
            $title = 'Hari Ini (' . Carbon::today()->translatedFormat('d M Y') . ')';
        } else {
            // Jika hari ini belum ada perkuliahan/absensi, hitung dari rentang tanggal aktif
            $query = Presensi::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);
            if ($tahunAkademik) {
                $query->whereHas('krs', fn ($q) => $q->where('tahun_akademik', $tahunAkademik));
            }
            $records = $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $hadir = (int) ($records['Hadir'] ?? 0);
            $izin = (int) ($records['Izin'] ?? 0);
            $sakit = (int) ($records['Sakit'] ?? 0);
            $alpa = (int) (($records['Alpha'] ?? 0) + ($records['Alpa'] ?? 0));
            $total = $hadir + $izin + $sakit + $alpa;
            $title = 'Periode Terpilih';
        }

        $rates = [
            'hadir' => $total > 0 ? round(($hadir / $total) * 100, 1) : 0,
            'izin' => $total > 0 ? round(($izin / $total) * 100, 1) : 0,
            'sakit' => $total > 0 ? round(($sakit / $total) * 100, 1) : 0,
            'alpa' => $total > 0 ? round(($alpa / $total) * 100, 1) : 0,
        ];

        return [
            'title' => $title,
            'labels' => ['Hadir', 'Izin', 'Sakit', 'Alpa'],
            'counts' => [$hadir, $izin, $sakit, $alpa],
            'rates' => [$rates['hadir'], $rates['izin'], $rates['sakit'], $rates['alpa']],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            'total' => $total,
            'rate_hadir' => $rates['hadir'],
        ];
    }

    /**
     * Mengambil perbandingan kehadiran per Program Studi (Bar Chart)
     */
    public function getProdiComparison(Carbon $start, Carbon $end, ?string $tahunAkademik = null): array
    {
        $prodis = Prodi::orderBy('nama_prodi')->get();

        $labels = [];
        $hadirList = [];
        $izinList = [];
        $sakitList = [];
        $alpaList = [];
        $percentageList = [];
        $totalList = [];

        foreach ($prodis as $prodi) {
            $query = Presensi::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
                ->whereHas('krs', function ($q) use ($prodi, $tahunAkademik) {
                    $q->where(function ($sub) use ($prodi) {
                        $sub->where('prodi_id', $prodi->id)
                            ->orWhereHas('mahasiswa', fn ($m) => $m->where('prodi_id', $prodi->id));
                    });
                    if ($tahunAkademik) {
                        $q->where('tahun_akademik', $tahunAkademik);
                    }
                });

            $records = $query->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $h = (int) ($records['Hadir'] ?? 0);
            $i = (int) ($records['Izin'] ?? 0);
            $s = (int) ($records['Sakit'] ?? 0);
            $a = (int) (($records['Alpha'] ?? 0) + ($records['Alpa'] ?? 0));
            $tot = $h + $i + $s + $a;
            $pct = $tot > 0 ? round(($h / $tot) * 100, 1) : 0;

            // Singkat nama prodi jika panjang untuk tampilan label chart yang rapi
            $shortName = $this->shortenProdiName($prodi->nama_prodi);
            $labels[] = $shortName;
            $hadirList[] = $h;
            $izinList[] = $i;
            $sakitList[] = $s;
            $alpaList[] = $a;
            $percentageList[] = $pct;
            $totalList[] = $tot;
        }

        return [
            'labels' => $labels,
            'hadir' => $hadirList,
            'izin' => $izinList,
            'sakit' => $sakitList,
            'alpa' => $alpaList,
            'percentages' => $percentageList,
            'totals' => $totalList,
        ];
    }

    /**
     * Menghasilkan data simulasi yang realistis jika database belum memiliki catatan presensi
     */
    protected function generateSimulatedAnalytics(
        Carbon $start,
        Carbon $end,
        string $rangeKey,
        string $rangeLabel,
        array $todayStats
    ): array {
        $totalStudents = $todayStats['total_students'] ?: 150;
        $period = CarbonPeriod::create($start->toDateString(), $end->toDateString());

        $labels = [];
        $fullDates = [];
        $hadirSeries = [];
        $izinSeries = [];
        $sakitSeries = [];
        $alpaSeries = [];
        $totalSeries = [];
        $rateSeries = [];

        $diffDays = $start->diffInDays($end);
        $format = $diffDays > 14 ? 'd/m' : 'd M';

        // Base mahasiswa aktif per hari (misal 65% - 85% dari total mahasiswa memiliki jadwal di hari kerja)
        $baseActive = max(10, (int) round($totalStudents * 0.7));

        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();
            $dCarbon = Carbon::parse($date);
            $labels[] = $dCarbon->translatedFormat($format);
            $fullDates[] = $dCarbon->toDateString();

            if ($isWeekend) {
                // Akhir pekan biasanya tidak ada jadwal perkuliahan
                $hadirSeries[] = 0;
                $izinSeries[] = 0;
                $sakitSeries[] = 0;
                $alpaSeries[] = 0;
                $totalSeries[] = 0;
                $rateSeries[] = 0;
                continue;
            }

            // Variasi harian yang natural
            $seedModifier = ($date->day * 7 + $date->month * 13) % 15;
            $dailyTotal = max(10, $baseActive + $seedModifier - 7);

            // Rata-rata 86-93% Hadir, 3-6% Izin, 2-4% Sakit, 2-5% Alpa
            $h = (int) round($dailyTotal * (0.87 + (($seedModifier % 6) * 0.01)));
            $i = (int) round($dailyTotal * (0.04 + (($seedModifier % 3) * 0.01)));
            $s = (int) round($dailyTotal * (0.03 + (($seedModifier % 2) * 0.01)));
            $a = max(0, $dailyTotal - $h - $i - $s);
            $dailyTotal = $h + $i + $s + $a;
            $r = $dailyTotal > 0 ? round(($h / $dailyTotal) * 100, 1) : 0;

            $hadirSeries[] = $h;
            $izinSeries[] = $i;
            $sakitSeries[] = $s;
            $alpaSeries[] = $a;
            $totalSeries[] = $dailyTotal;
            $rateSeries[] = $r;
        }

        // Buat stat hari ini
        $todayIndex = count($fullDates) - 1;
        $tHadir = $hadirSeries[$todayIndex] ?? (int) round($baseActive * 0.88);
        $tIzin = $izinSeries[$todayIndex] ?? 3;
        $tSakit = $sakitSeries[$todayIndex] ?? 2;
        $tAlpa = $alpaSeries[$todayIndex] ?? 2;
        $tTotal = $tHadir + $tIzin + $tSakit + $tAlpa;
        $tRate = $tTotal > 0 ? round(($tHadir / $tTotal) * 100, 1) : 89.5;

        $simulatedToday = [
            'total_students' => $totalStudents,
            'today_total' => $tTotal,
            'today_hadir' => $tHadir,
            'today_izin' => $tIzin,
            'today_sakit' => $tSakit,
            'today_izin_sakit' => $tIzin + $tSakit,
            'today_alpa' => $tAlpa,
            'today_terlambat' => max(1, (int) round($tHadir * 0.04)),
            'today_rate' => $tRate,
            'date_formatted' => Carbon::today()->translatedFormat('l, d F Y'),
        ];

        // Distribusi Donut Chart
        $distribution = [
            'title' => 'Hari Ini (' . Carbon::today()->translatedFormat('d M Y') . ')',
            'labels' => ['Hadir', 'Izin', 'Sakit', 'Alpa'],
            'counts' => [$tHadir, $tIzin, $tSakit, $tAlpa],
            'rates' => [
                round(($tHadir / $tTotal) * 100, 1),
                round(($tIzin / $tTotal) * 100, 1),
                round(($tSakit / $tTotal) * 100, 1),
                round(($tAlpa / $tTotal) * 100, 1),
            ],
            'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
            'total' => $tTotal,
            'rate_hadir' => $tRate,
        ];

        // Perbandingan Program Studi
        $prodis = Prodi::orderBy('nama_prodi')->get();
        if ($prodis->isEmpty()) {
            $mockProdis = ['Teknik Pertambangan', 'Teknik Informatika', 'Sistem Informasi', 'Manajemen Rekayasa'];
        } else {
            $mockProdis = $prodis->pluck('nama_prodi')->toArray();
            if (count($mockProdis) === 1) {
                $mockProdis[] = 'Teknik Industri';
                $mockProdis[] = 'Sistem Informasi';
            }
        }

        $pLabels = [];
        $pHadir = [];
        $pIzin = [];
        $pSakit = [];
        $pAlpa = [];
        $pPct = [];
        $pTotals = [];

        foreach ($mockProdis as $idx => $pName) {
            $pLabels[] = $this->shortenProdiName($pName);
            $base = 25 + ($idx * 12);
            $h = (int) round($base * (0.85 + ($idx % 3) * 0.03));
            $i = (int) round($base * 0.05);
            $s = (int) round($base * 0.04);
            $a = max(1, $base - $h - $i - $s);
            $tot = $h + $i + $s + $a;
            $pct = round(($h / $tot) * 100, 1);

            $pHadir[] = $h;
            $pIzin[] = $i;
            $pSakit[] = $s;
            $pAlpa[] = $a;
            $pPct[] = $pct;
            $pTotals[] = $tot;
        }

        return [
            'range' => $rangeKey,
            'range_label' => $rangeLabel,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'has_real_data' => false,
            'is_mock' => true,
            'stats' => $simulatedToday,
            'trend' => [
                'labels' => $labels,
                'full_dates' => $fullDates,
                'hadir' => $hadirSeries,
                'izin' => $izinSeries,
                'sakit' => $sakitSeries,
                'alpa' => $alpaSeries,
                'total' => $totalSeries,
                'rates' => $rateSeries,
                'total_records' => array_sum($totalSeries),
                'total_hadir' => array_sum($hadirSeries),
                'total_izin' => array_sum($izinSeries),
                'total_sakit' => array_sum($sakitSeries),
                'total_alpa' => array_sum($alpaSeries),
            ],
            'distribution' => $distribution,
            'prodi_comparison' => [
                'labels' => $pLabels,
                'hadir' => $pHadir,
                'izin' => $pIzin,
                'sakit' => $pSakit,
                'alpa' => $pAlpa,
                'percentages' => $pPct,
                'totals' => $pTotals,
            ],
        ];
    }

    /**
     * Memperpendek nama Program Studi untuk label chart
     */
    protected function shortenProdiName(string $name): string
    {
        $replacements = [
            'Teknik Pertambangan' => 'T. Pertambangan',
            'Teknik Informatika' => 'T. Informatika',
            'Teknik Mesin' => 'T. Mesin',
            'Teknik Sipil' => 'T. Sipil',
            'Teknik Industri' => 'T. Industri',
            'Sistem Informasi' => 'Sist. Informasi',
            'Manajemen Informatika' => 'Manaj. Informatika',
        ];

        return $replacements[$name] ?? $name;
    }
}
