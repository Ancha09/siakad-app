<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Services\AvailableKrsScheduleService;
use App\Services\MahasiswaNilaiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // ===================== DASHBOARD =====================
    public function index(MahasiswaNilaiService $nilaiService)
    {
        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Semua KRS mahasiswa
        $krs = Krs::with([
            'jadwal.mataKuliah',
            'mataKuliahManual',
            'jadwal.dosen',
            'jadwal.ruangan',
            'khs',
            'kuesioner',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->get();

        // Total SKS yang sudah disetujui
        $totalSks = $krs
            ->where('status', 'Disetujui')
            ->sum(function ($item) {
                return $item->khs?->sks_efektif ?? (int) ($item->mata_kuliah_efektif?->sks ?? 0);
            });

        // Jadwal dari KRS yang sudah disetujui
        $jadwals = $krs
            ->where('status', 'Disetujui')
            ->map(function ($item) {
                return $item->jadwal;
            })
            ->filter()
            ->values();

        // Jumlah KRS
        $jumlahKrs = $krs->count();

        // Jumlah KRS yang masih menunggu
        $krsMenunggu = $krs
            ->where('status', 'Menunggu')
            ->count();

        $ringkasanNilai = $nilaiService->ringkasanMahasiswa($mahasiswa->id);
        $kuesionerTertunda = $ringkasanNilai['kuesioner_tertunda'];
        $ipkTerlihat = $ringkasanNilai['ipk_terlihat'];
        $jumlahNilai = $ringkasanNilai['jumlah_nilai'];
        $nilaiService->sembunyikanNilaiTerkunci($krs->pluck('khs')->filter());

        return view('mahasiswa.dashboard', compact(
            'mahasiswa',
            'krs',
            'jadwals',
            'totalSks',
            'jumlahKrs',
            'krsMenunggu',
            'kuesionerTertunda',
            'ipkTerlihat',
            'jumlahNilai'
        ));
    }

    // ===================== KRS =====================
    public function krs()
    {
        return view('mahasiswa.krs');
    }

    // ===================== JADWAL =====================
    public function jadwal(Request $request, AvailableKrsScheduleService $scheduleService)
    {
        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 1. Ambil seluruh KRS mahasiswa yang disetujui untuk mendeteksi riwayat periode
        $allApprovedKrs = Krs::with([
            'jadwal.mataKuliah.rpsAktif',
            'jadwal.mataKuliah.prodi',
            'jadwal.dosen',
            'jadwal.dosens',
            'jadwal.ruangan',
            'jadwal.kelasRelasi',
            'mataKuliahManual.rpsAktif',
            'dosenManual',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'Disetujui')
            ->get();

        // Riwayat periode untuk filter dropdown jika mahasiswa memiliki KRS di beberapa semester
        $periodeList = $allApprovedKrs
            ->filter(fn (Krs $item) => filled($item->tahun_akademik ?? $item->jadwal?->tahun_akademik))
            ->groupBy(function (Krs $item) use ($scheduleService) {
                $thn = $scheduleService->normalizeAcademicYear($item->tahun_akademik ?? $item->jadwal?->tahun_akademik);
                $sem = $scheduleService->normalizeAcademicSemester($item->semester_akademik ?? $item->jadwal?->semester_akademik) ?? 'Ganjil';

                return $thn.'|'.$sem;
            })
            ->map(function ($items, $key) {
                [$thn, $sem] = explode('|', $key);

                return [
                    'key' => $key,
                    'tahun_akademik' => $thn,
                    'semester_akademik' => $sem,
                    'label' => "T.A {$thn} - Semester {$sem}",
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('tahun_akademik')
            ->values();

        // 2. Tentukan periode aktif saat ini dari PeriodeKrs
        $periodeKrsAktif = PeriodeKrs::query()
            ->where('status', 'Dibuka')
            ->where('tanggal_mulai', '<=', now())
            ->where('tanggal_selesai', '>=', now())
            ->latest('tanggal_mulai')
            ->latest('id')
            ->first();

        if (! $periodeKrsAktif) {
            $periodeKrsAktif = PeriodeKrs::query()
                ->latest('tanggal_mulai')
                ->latest('id')
                ->first();
        }

        // 3. Tentukan periode target (dari request atau otomatis dari periode aktif)
        $selectedPeriodKey = null;
        $targetTahun = null;
        $targetSemester = null;

        if ($request->filled('periode')) {
            $parts = explode('|', (string) $request->input('periode'));
            if (count($parts) === 2) {
                $targetTahun = $scheduleService->normalizeAcademicYear($parts[0]);
                $targetSemester = $scheduleService->normalizeAcademicSemester($parts[1]);
                $selectedPeriodKey = $targetTahun.'|'.$targetSemester;
            }
        } elseif ($request->filled('tahun_akademik')) {
            $targetTahun = $scheduleService->normalizeAcademicYear($request->input('tahun_akademik'));
            $targetSemester = $scheduleService->normalizeAcademicSemester($request->input('semester_akademik'));
            $selectedPeriodKey = $targetTahun.($targetSemester ? '|'.$targetSemester : '');
        }

        // Jika tidak ada parameter filter dari request, utamakan periode aktif
        if (! $targetTahun && $periodeKrsAktif) {
            $targetTahun = $scheduleService->normalizeAcademicYear($periodeKrsAktif->tahun_akademik);
            $targetSemester = $scheduleService->normalizeAcademicSemester($periodeKrsAktif->semester);
            $selectedPeriodKey = $targetTahun.'|'.$targetSemester;
        }

        // Fallback: Jika tabel PeriodeKrs memiliki data, tapi mahasiswa tidak punya KRS di periode aktif,
        // atau jika PeriodeKrs tidak ada sama sekali di sistem tapi ada riwayat KRS di beberapa periode
        if (! $targetTahun && $periodeList->isNotEmpty() && PeriodeKrs::query()->exists()) {
            $firstPeriod = $periodeList->first();
            $targetTahun = $firstPeriod['tahun_akademik'];
            $targetSemester = $firstPeriod['semester_akademik'];
            $selectedPeriodKey = $targetTahun.'|'.$targetSemester;
        }

        // 4. Filter krsItems sesuai periode aktif / target yang ditentukan
        if ($targetTahun) {
            $normalizedTargetYear = $scheduleService->normalizeAcademicYear($targetTahun);
            $normalizedTargetSemester = $targetSemester ? $scheduleService->normalizeAcademicSemester($targetSemester) : null;

            $krsItems = $allApprovedKrs->filter(function (Krs $item) use ($scheduleService, $normalizedTargetYear, $normalizedTargetSemester) {
                $itemYear = $scheduleService->normalizeAcademicYear($item->tahun_akademik ?? $item->jadwal?->tahun_akademik);
                $itemSem = $scheduleService->normalizeAcademicSemester($item->semester_akademik ?? $item->jadwal?->semester_akademik);

                $yearMatches = $itemYear === $normalizedTargetYear;
                $semMatches = ! $normalizedTargetSemester || $itemSem === $normalizedTargetSemester;

                return $yearMatches && $semMatches;
            })->values();
        } else {
            $krsItems = $allApprovedKrs;
        }

        // Menghindari duplikasi jadwal dan urutkan berdasarkan hari & jam perkuliahan
        $dayOrder = [
            'senin' => 1,
            'selasa' => 2,
            'rabu' => 3,
            'kamis' => 4,
            'jumat' => 5,
            'sabtu' => 6,
            'minggu' => 7,
        ];

        $krsItems = $krsItems
            ->unique(fn (Krs $item) => $item->jadwal_id ? 'jadwal_'.$item->jadwal_id : 'krs_'.$item->id)
            ->sort(function (Krs $a, Krs $b) use ($dayOrder) {
                $hariA = strtolower(trim((string) ($a->jadwal?->hari ?? '')));
                $hariB = strtolower(trim((string) ($b->jadwal?->hari ?? '')));

                $orderA = $dayOrder[$hariA] ?? 99;
                $orderB = $dayOrder[$hariB] ?? 99;

                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }

                $jamA = $a->jadwal?->jam_mulai ?? '99:99';
                $jamB = $b->jadwal?->jam_mulai ?? '99:99';

                if ($jamA !== $jamB) {
                    return strcmp($jamA, $jamB);
                }

                return ($a->id ?? 0) <=> ($b->id ?? 0);
            })
            ->values();

        $totalSks = $krsItems->sum(function (Krs $item) {
            return (int) ($item->mata_kuliah_efektif?->sks ?? 0);
        });

        return view(
            'mahasiswa.jadwal.index',
            compact(
                'mahasiswa',
                'krsItems',
                'targetTahun',
                'targetSemester',
                'periodeList',
                'selectedPeriodKey',
                'totalSks'
            )
        );
    }

    // ===================== KHS =====================
    public function khs()
    {
        return view('mahasiswa.khs');
    }

    // ===================== KEUANGAN =====================
    public function keuangan()
    {
        return view('mahasiswa.keuangan');
    }
}
