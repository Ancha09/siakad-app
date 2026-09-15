<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    // =========================================================
    // REKAP PRESENSI ADMIN
    // =========================================================

    public function index(Request $request)
    {
        // ===================== DATA FILTER =====================

        $prodis = Prodi::orderBy('nama_prodi')->get();

        $kelas = Kelas::orderBy('nama_kelas')->get();

        $dosens = Dosen::orderBy('nama')->get();

        $mataKuliahs = MataKuliah::orderBy('nama_mk')->get();

        // =====================================================
        // DATA PRESENSI
        // =====================================================

        $query = Presensi::with([
            'krs.mahasiswa.prodi',
            'krs.mahasiswa.kelas',
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.mataKuliahManual',
            'krs.dosenManual',
            'krs.prodiManual',
            'krs.kelasManual',
            'krs.jadwal.ruangan',
        ]);

        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->whereHas('krs', fn ($q) => $q
                ->where('prodi_id', $request->prodi_id)
                ->orWhereHas('mahasiswa', fn ($mahasiswa) => $mahasiswa->where('prodi_id', $request->prodi_id)));

        }

        // ===================== FILTER KELAS =====================

        if ($request->filled('kelas_id')) {

            $query->whereHas('krs', fn ($q) => $q
                ->where('kelas_id', $request->kelas_id)
                ->orWhereHas('mahasiswa', fn ($mahasiswa) => $mahasiswa->where('kelas_id', $request->kelas_id)));

        }

        // ===================== FILTER DOSEN =====================

        if ($request->filled('dosen_id')) {

            $query->whereHas('krs', fn ($q) => $q
                ->where('dosen_id', $request->dosen_id)
                ->orWhereHas('jadwal', fn ($jadwal) => $jadwal->where('dosen_id', $request->dosen_id)));

        }

        // ===================== FILTER MATA KULIAH =====================

        if ($request->filled('mata_kuliah_id')) {

            $query->whereHas('krs', fn ($q) => $q
                ->where('mata_kuliah_id', $request->mata_kuliah_id)
                ->orWhereHas('jadwal', fn ($jadwal) => $jadwal->where('mata_kuliah_id', $request->mata_kuliah_id)));

        }

        // ===================== FILTER PERTEMUAN =====================

        if ($request->filled('pertemuan')) {

            $query->where(
                'pertemuan',
                $request->pertemuan
            );

        }

        // ===================== AMBIL DATA =====================

        $presensis = $query
            ->orderByDesc('tanggal')
            ->orderBy('pertemuan')
            ->get();

        // =====================================================
        // REKAP PER MAHASISWA
        // =====================================================

        $rekap = $presensis
            ->groupBy(function ($item) {

                return $item->krs_id;

            })
            ->map(function ($data) {

                $first = $data->first();

                $hadir = $data
                    ->where('status', 'Hadir')
                    ->count();

                $izin = $data
                    ->where('status', 'Izin')
                    ->count();

                $sakit = $data
                    ->where('status', 'Sakit')
                    ->count();

                $alpha = $data
                    ->where('status', 'Alpha')
                    ->count();

                $total = $hadir
                    + $izin
                    + $sakit
                    + $alpha;

                $persentase = $total > 0
                    ? round(
                        ($hadir / $total) * 100,
                        1
                    )
                    : 0;

                return (object) [

                    'krs' => $first->krs,

                    'hadir' => $hadir,

                    'izin' => $izin,

                    'sakit' => $sakit,

                    'alpha' => $alpha,

                    'total' => $total,

                    'persentase' => $persentase,

                ];

            })
            ->values();

        // =====================================================
        // DATA SESI PERTEMUAN
        // =====================================================

        $pertemuanQuery = DB::table('presensi_pertemuans')
            ->join(
                'jadwals',
                'presensi_pertemuans.jadwal_id',
                '=',
                'jadwals.id'
            )
            ->select(
                'presensi_pertemuans.*',
                'jadwals.mata_kuliah_id',
                'jadwals.dosen_id'
            );

        if ($request->filled('dosen_id')) {

            $pertemuanQuery->where(
                'jadwals.dosen_id',
                $request->dosen_id
            );

        }

        if ($request->filled('mata_kuliah_id')) {

            $pertemuanQuery->where(
                'jadwals.mata_kuliah_id',
                $request->mata_kuliah_id
            );

        }

        if ($request->filled('pertemuan')) {

            $pertemuanQuery->where(
                'presensi_pertemuans.pertemuan',
                $request->pertemuan
            );

        }

        $pertemuans = $pertemuanQuery
            ->orderByDesc('tanggal')
            ->orderBy('pertemuan')
            ->get();

        // =====================================================
        // STATISTIK
        // =====================================================

        $totalPresensi = $presensis->count();

        $totalHadir = $presensis
            ->where('status', 'Hadir')
            ->count();

        $totalIzin = $presensis
            ->where('status', 'Izin')
            ->count();

        $totalSakit = $presensis
            ->where('status', 'Sakit')
            ->count();

        $totalAlpha = $presensis
            ->where('status', 'Alpha')
            ->count();

        return view(
            'admin.presensi.index',
            compact(
                'prodis',
                'kelas',
                'dosens',
                'mataKuliahs',
                'presensis',
                'rekap',
                'pertemuans',
                'totalPresensi',
                'totalHadir',
                'totalIzin',
                'totalSakit',
                'totalAlpha'
            )
        );
    }

    // =========================================================
    // DOWNLOAD EXCEL
    // =========================================================

    public function downloadExcel(Request $request)
    {
        return response()->streamDownload(function () use ($request) {

            $handle = fopen('php://output', 'w');

            // BOM supaya Excel membaca UTF-8
            fprintf(
                $handle,
                chr(0xEF).chr(0xBB).chr(0xBF)
            );

            fputcsv($handle, [
                'NIM',
                'Nama Mahasiswa',
                'Program Studi',
                'Kelas',
                'Mata Kuliah',
                'Dosen',
                'Pertemuan',
                'Tanggal',
                'Status',
            ]);

            $query = Presensi::with([
                'krs.mahasiswa.prodi',
                'krs.mahasiswa.kelas',
                'krs.jadwal.mataKuliah',
                'krs.jadwal.dosen',
            ]);

            if ($request->filled('prodi_id')) {

                $query->whereHas(
                    'krs.mahasiswa',
                    fn ($q) => $q->where(
                        'prodi_id',
                        $request->prodi_id
                    )
                );

            }

            if ($request->filled('kelas_id')) {

                $query->whereHas(
                    'krs.mahasiswa',
                    fn ($q) => $q->where(
                        'kelas_id',
                        $request->kelas_id
                    )
                );

            }

            if ($request->filled('dosen_id')) {

                $query->whereHas(
                    'krs.jadwal',
                    fn ($q) => $q->where(
                        'dosen_id',
                        $request->dosen_id
                    )
                );

            }

            if ($request->filled('mata_kuliah_id')) {

                $query->whereHas(
                    'krs.jadwal',
                    fn ($q) => $q->where(
                        'mata_kuliah_id',
                        $request->mata_kuliah_id
                    )
                );

            }

            if ($request->filled('pertemuan')) {

                $query->where(
                    'pertemuan',
                    $request->pertemuan
                );

            }

            $data = $query
                ->orderBy('tanggal')
                ->orderBy('pertemuan')
                ->get();

            foreach ($data as $item) {

                fputcsv($handle, [

                    $item->krs->mahasiswa->nim ?? '-',

                    $item->krs->mahasiswa->nama ?? '-',

                    $item->krs->mahasiswa->prodi->nama_prodi ?? '-',

                    $item->krs->mahasiswa->kelas->nama_kelas ?? '-',

                    $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-',

                    $item->krs?->dosen_efektif?->nama ?? '-',

                    'Pertemuan '.$item->pertemuan,

                    $item->tanggal,

                    $item->status,

                ]);

            }

            fclose($handle);

        }, 'rekap-presensi.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // =========================================================
    // DOWNLOAD PDF
    // =========================================================

    public function downloadPdf(Request $request)
    {
        return redirect()
            ->route(
                'admin.presensi',
                $request->query()
            )
            ->with(
                'error',
                'Fitur PDF kita aktifkan setelah tampilan rekap selesai.'
            );
    }
}
