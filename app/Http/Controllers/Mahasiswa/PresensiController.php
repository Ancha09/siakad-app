<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
{
    public function index(Request $request)
    {
        $mahasiswa = Mahasiswa::with(['prodi', 'kelas'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $tahunAkademik = Krs::where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'Disetujui')
            ->whereNotNull('tahun_akademik')
            ->distinct()
            ->orderByDesc('tahun_akademik')
            ->pluck('tahun_akademik');

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'mataKuliahManual',
            'dosenManual',
            'jadwal.dosen',
            'jadwal.ruangan',
            'jadwal.kelas',
            'jadwal.presensiPertemuans' => fn ($query) => $query
                ->orderBy('pertemuan')
                ->orderBy('tanggal'),
            'presensis' => fn ($query) => $query->with('dosenManual')
                ->orderBy('pertemuan')
                ->orderBy('tanggal'),
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'Disetujui')
            ->when($request->filled('tahun_akademik'), fn ($query) => $query
                ->where('tahun_akademik', (string) $request->input('tahun_akademik')))
            ->orderByDesc('tahun_akademik')
            ->orderBy('semester_akademik')
            ->get();

        $mataKuliahs = $krs->map(function (Krs $item) {
            $presensis = $item->presensis;
            $hadir = $presensis->where('status', 'Hadir')->count();
            $izin = $presensis->where('status', 'Izin')->count();
            $sakit = $presensis->where('status', 'Sakit')->count();
            $alpha = $presensis->where('status', 'Alpha')->count();
            $total = $presensis->count();
            $persentase = $total > 0 ? round(($hadir / $total) * 100, 1) : null;

            $presensiPerPertemuan = $presensis->keyBy('pertemuan');
            $riwayat = $item->jadwal?->presensiPertemuans
                ?->map(function ($pertemuan) use ($presensiPerPertemuan) {
                    return (object) [
                        'pertemuan' => $pertemuan->pertemuan,
                        'tanggal' => $pertemuan->tanggal,
                        'materi_kuliah' => $pertemuan->materi_kuliah,
                        'keterangan' => $pertemuan->keterangan,
                        'foto' => $pertemuan->foto,
                        'materi' => $pertemuan->materi,
                        'presensi' => $presensiPerPertemuan->get($pertemuan->pertemuan),
                    ];
                }) ?? collect();

            $pertemuanTercatat = $riwayat->pluck('pertemuan');
            $presensiTanpaSesi = $presensis
                ->whereNotIn('pertemuan', $pertemuanTercatat)
                ->map(fn ($presensi) => (object) [
                    'pertemuan' => $presensi->pertemuan,
                    'tanggal' => $presensi->tanggal,
                    'materi_kuliah' => null,
                    'keterangan' => $presensi->keterangan,
                    'foto' => null,
                    'materi' => null,
                    'presensi' => $presensi,
                ]);

            return (object) [
                'krs' => $item,
                'dosen_pengampu' => ($presensis->isEmpty() ? $item->dosen_efektif?->nama : $presensis->map(fn ($record) => $record->dosen_efektif?->nama)->filter()->unique()->implode(', ')) ?: 'Dosen belum ditentukan',
                'jadwal' => $item->jadwal,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'total' => $total,
                'persentase' => $persentase,
                'riwayat' => $riwayat->concat($presensiTanpaSesi)->sortBy('pertemuan')->values(),
            ];
        })->filter(fn ($item) => $item->krs->mata_kuliah_efektif !== null)->values();

        $totalHadir = $mataKuliahs->sum('hadir');
        $totalIzin = $mataKuliahs->sum('izin');
        $totalSakit = $mataKuliahs->sum('sakit');
        $totalAlpha = $mataKuliahs->sum('alpha');
        $totalTercatat = $mataKuliahs->sum('total');
        $rataKehadiran = $totalTercatat > 0
            ? round(($totalHadir / $totalTercatat) * 100, 1)
            : null;

        return view('mahasiswa.presensi', compact(
            'mahasiswa',
            'tahunAkademik',
            'mataKuliahs',
            'totalHadir',
            'totalIzin',
            'totalSakit',
            'totalAlpha',
            'totalTercatat',
            'rataKehadiran'
        ));
    }
}
