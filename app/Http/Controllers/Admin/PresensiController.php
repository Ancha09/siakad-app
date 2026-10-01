<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\MataKuliah;
use App\Models\Presensi;
use App\Models\Prodi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    // =========================================================
    // REKAP PRESENSI ADMIN
    // =========================================================

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester_akademik' => ['nullable', 'string', 'max:30'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
            'pertemuan' => ['nullable', 'integer', 'between:1,16'],
            'status' => ['nullable', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alpha'])],
        ]);

        // ===================== DATA FILTER =====================

        $prodis = Prodi::orderBy('nama_prodi')->get();

        $kelas = Kelas::orderBy('nama_kelas')->get();

        $dosens = Dosen::orderBy('nama')->get();

        $mataKuliahs = MataKuliah::orderBy('nama_mk')->get();

        $tahunAkademiks = DB::table('jadwals')
            ->whereNotNull('tahun_akademik')
            ->distinct()
            ->pluck('tahun_akademik')
            ->merge(DB::table('krs')->whereNotNull('tahun_akademik')->distinct()->pluck('tahun_akademik'))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $semesterAkademiks = DB::table('jadwals')
            ->whereNotNull('semester_akademik')
            ->distinct()
            ->pluck('semester_akademik')
            ->merge(DB::table('krs')->whereNotNull('semester_akademik')->distinct()->pluck('semester_akademik'))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        // =====================================================
        // DATA PRESENSI
        // =====================================================

        $query = Presensi::with([
            'krs.mahasiswa.prodi',
            'krs.mahasiswa.kelas',
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.mataKuliahManual',
            'dosenManual', 'krs.dosenManual',
            'krs.prodiManual',
            'krs.kelasManual',
            'krs.jadwal.ruangan',
        ]);

        if ($request->filled('tahun_akademik')) {
            $query->whereHas('krs', fn ($q) => $q
                ->where('tahun_akademik', $filters['tahun_akademik'])
                ->orWhereHas('jadwal', fn ($jadwal) => $jadwal->where('tahun_akademik', $filters['tahun_akademik'])));
        }

        if ($request->filled('semester_akademik')) {
            $query->whereHas('krs', fn ($q) => $q
                ->where('semester_akademik', $filters['semester_akademik'])
                ->orWhereHas('jadwal', fn ($jadwal) => $jadwal->where('semester_akademik', $filters['semester_akademik'])));
        }

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

            $query->forDosen((int) $request->dosen_id);

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

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $filters['tanggal']);
        }

        if ($request->filled('status')) {
            $query->where('status', $filters['status']);
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
                    'dosen_pengampu' => $data->map(fn ($record) => $record->dosen_efektif?->nama)->filter()->unique()->implode(', ') ?: '-',

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
            ->leftJoin('mata_kuliahs', 'jadwals.mata_kuliah_id', '=', 'mata_kuliahs.id')
            ->leftJoin('dosens', 'jadwals.dosen_id', '=', 'dosens.id')
            ->select(
                'presensi_pertemuans.*',
                'jadwals.mata_kuliah_id',
                'jadwals.dosen_id',
                'mata_kuliahs.kode_mk',
                'mata_kuliahs.nama_mk',
                'dosens.nama as nama_dosen'
            );

        if ($request->filled('tahun_akademik')) {
            $pertemuanQuery->where('jadwals.tahun_akademik', $filters['tahun_akademik']);
        }

        if ($request->filled('semester_akademik')) {
            $pertemuanQuery->where('jadwals.semester_akademik', $filters['semester_akademik']);
        }

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

        if ($request->filled('tanggal')) {
            $pertemuanQuery->whereDate('presensi_pertemuans.tanggal', $filters['tanggal']);
        }

        if ($request->filled('prodi_id') || $request->filled('kelas_id') || $request->filled('status')) {
            $pertemuanQuery->whereExists(function ($query) use ($filters, $request) {
                $query->selectRaw('1')
                    ->from('presensis')
                    ->join('krs', 'presensis.krs_id', '=', 'krs.id')
                    ->join('mahasiswas', 'krs.mahasiswa_id', '=', 'mahasiswas.id')
                    ->whereColumn('krs.jadwal_id', 'jadwals.id')
                    ->whereColumn('presensis.pertemuan', 'presensi_pertemuans.pertemuan');

                if ($request->filled('prodi_id')) {
                    $query->where(fn ($prodi) => $prodi
                        ->where('krs.prodi_id', $filters['prodi_id'])
                        ->orWhere('mahasiswas.prodi_id', $filters['prodi_id']));
                }

                if ($request->filled('kelas_id')) {
                    $query->where(fn ($kelasFilter) => $kelasFilter
                        ->where('krs.kelas_id', $filters['kelas_id'])
                        ->orWhere('mahasiswas.kelas_id', $filters['kelas_id']));
                }

                if ($request->filled('status')) {
                    $query->where('presensis.status', $filters['status']);
                }
            });
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

        // =====================================================
        // PROGRES PERTEMUAN MATA KULIAH (SEMESTER AKTIF)
        // =====================================================

        $progresMataKuliah = \App\Models\Jadwal::with([
            'mataKuliah',
            'dosen',
            'kelas',
            'ruangan',
        ])
        ->withCount('presensiPertemuans as total_pertemuan')
        ->when($request->filled('tahun_akademik'), fn ($q) => $q->where('tahun_akademik', $filters['tahun_akademik']))
        ->when($request->filled('semester_akademik'), fn ($q) => $q->where('semester_akademik', $filters['semester_akademik']))
        ->when($request->filled('prodi_id'), fn ($q) => $q->whereHas('mataKuliah', fn ($m) => $m->where('prodi_id', $filters['prodi_id'])))
        ->when($request->filled('mata_kuliah_id'), fn ($q) => $q->where('mata_kuliah_id', $filters['mata_kuliah_id']))
        ->when($request->filled('dosen_id'), fn ($q) => $q->where('dosen_id', $filters['dosen_id']))
        ->orderBy('hari')
        ->get();

        return view(
            'admin.presensi.index',
            compact(
                'prodis',
                'kelas',
                'dosens',
                'mataKuliahs',
                'tahunAkademiks',
                'semesterAkademiks',
                'presensis',
                'rekap',
                'pertemuans',
                'progresMataKuliah',
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

                    $item->dosen_efektif?->nama ?? '-',

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

    // =========================================================
    // DETAIL PRESENSI & BAP MATA KULIAH (ADMIN)
    // =========================================================

    public function detail(Jadwal $jadwal)
    {
        $jadwal->load([
            'mataKuliah.prodi.fakultas',
            'dosen.prodi',
            'kelas',
            'ruangan',
            'presensiPertemuans' => fn ($q) => $q->orderBy('pertemuan'),
        ]);

        $krs = Krs::with([
            'mahasiswa.prodi',
            'mahasiswa.kelas',
            'presensis',
        ])
        ->where('is_manual', false)
        ->where('jadwal_id', $jadwal->id)
        ->get();

        foreach ($krs as $item) {
            $item->hadir = $item->presensis->where('status', 'Hadir')->count();
            $item->izin = $item->presensis->where('status', 'Izin')->count();
            $item->sakit = $item->presensis->where('status', 'Sakit')->count();
            $item->alpha = $item->presensis->where('status', 'Alpha')->count();
            $total = $item->hadir + $item->izin + $item->sakit + $item->alpha;
            $item->total_pertemuan_mhs = $total;
            $item->persentase = $total > 0 ? round(($item->hadir / $total) * 100, 1) : 0;
        }

        $pertemuans = $jadwal->presensiPertemuans;
        $krsIds = $krs->pluck('id')->toArray();
        $totalPeserta = $krs->count();

        foreach ($pertemuans as $p) {
            $p->total_hadir = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Hadir')
                ->count();
            $p->total_izin = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Izin')
                ->count();
            $p->total_sakit = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Sakit')
                ->count();
            $p->total_alpha = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Alpha')
                ->count();
        }

        $rataRataKehadiranKelas = $krs->count() > 0
            ? round($krs->avg('persentase'), 1)
            : 0;

        return view('admin.presensi.detail', compact(
            'jadwal',
            'krs',
            'pertemuans',
            'totalPeserta',
            'rataRataKehadiranKelas'
        ));
    }

    // =========================================================
    // DOWNLOAD BAP PDF (ADMIN)
    // =========================================================

    public function downloadBapPdf(Jadwal $jadwal)
    {
        $jadwal->load([
            'mataKuliah.prodi.fakultas',
            'dosen.prodi',
            'kelas',
            'ruangan',
            'presensiPertemuans' => fn ($q) => $q->orderBy('pertemuan'),
        ]);

        $krs = Krs::with([
            'mahasiswa.prodi',
            'mahasiswa.kelas',
            'presensis',
        ])
        ->where('is_manual', false)
        ->where('jadwal_id', $jadwal->id)
        ->get();

        $totalPeserta = $krs->count();
        $pertemuans = $jadwal->presensiPertemuans;
        $krsIds = $krs->pluck('id')->toArray();

        foreach ($pertemuans as $p) {
            $p->total_hadir = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Hadir')
                ->count();
            $p->total_izin = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Izin')
                ->count();
            $p->total_sakit = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Sakit')
                ->count();
            $p->total_alpha = Presensi::whereIn('krs_id', $krsIds)
                ->where('pertemuan', $p->pertemuan)
                ->where('status', 'Alpha')
                ->count();
        }

        $prodi = $jadwal->mataKuliah?->prodi ?? $jadwal->dosen?->prodi;

        $pdf = Pdf::loadView('admin.presensi.bap-pdf', [
            'jadwal' => $jadwal,
            'pertemuans' => $pertemuans,
            'krs' => $krs,
            'totalPeserta' => $totalPeserta,
            'prodi' => $prodi,
        ])->setPaper('a4', 'portrait');

        $kodeMk = $jadwal->mataKuliah?->kode_mk ?? 'MK';
        $namaMk = Str::slug($jadwal->mataKuliah?->nama_mk ?? 'Matkul');
        $namaKelas = Str::slug($jadwal->kelas?->nama_kelas ?? $jadwal->kelas ?? 'Kelas');
        $filename = 'BAP_' . $kodeMk . '_' . $namaMk . '_' . $namaKelas . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }
}
