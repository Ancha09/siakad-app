<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Khs;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Auth;

class KhsController extends Controller
{
    // =========================================================
    // HALAMAN KHS MAHASISWA
    // =========================================================

    public function index()
    {
        // ===================== DATA MAHASISWA =====================

        $mahasiswa = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // ===================== DATA KHS =====================

        $khs = Khs::with([
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.jadwal.ruangan',
            'krs.kuesioner',
        ])
            ->whereHas('krs', function ($query) use ($mahasiswa) {

                $query->where(
                    'mahasiswa_id',
                    $mahasiswa->id
                );

            })
            ->orderByDesc('tahun_akademik')
            ->get();

        // Nilai hanya ikut ditampilkan dan dihitung setelah kuesioner diisi.
        $khsTerlihat = $khs->filter(function ($item) {
            return $item->krs?->kuesioner !== null;
        });

        // ===================== KELOMPOK PER SEMESTER =====================

        $khsPerSemester = $khs->groupBy(function ($item) {

            return $item->tahun_akademik
                .' - '
                .$item->semester_akademik;

        });

        // ===================== TOTAL SKS =====================

        $totalSks = $khsTerlihat->sum(function ($item) {

            return $item->krs
                ->jadwal
                ->mataKuliah
                ->sks ?? 0;

        });

        // ===================== TOTAL MUTU =====================

        $totalMutu = $khsTerlihat->sum(function ($item) {

            $sks = $item->krs
                ->jadwal
                ->mataKuliah
                ->sks ?? 0;

            $bobot = $item->bobot ?? 0;

            return $sks * $bobot;

        });

        // ===================== IPK =====================

        $ipk = $totalSks > 0
            ? round($totalMutu / $totalSks, 2)
            : 0;

        // ===================== IPS PER SEMESTER =====================

        $ipsPerSemester = [];

        foreach ($khsPerSemester as $semester => $data) {

            $dataTerlihat = $data->filter(function ($item) {
                return $item->krs?->kuesioner !== null;
            });

            $sksSemester = $dataTerlihat->sum(function ($item) {

                return $item->krs
                    ->jadwal
                    ->mataKuliah
                    ->sks ?? 0;

            });

            $mutuSemester = $dataTerlihat->sum(function ($item) {

                $sks = $item->krs
                    ->jadwal
                    ->mataKuliah
                    ->sks ?? 0;

                $bobot = $item->bobot ?? 0;

                return $sks * $bobot;

            });

            $ipsPerSemester[$semester] = $sksSemester > 0
                ? round($mutuSemester / $sksSemester, 2)
                : 0;
        }

        // ===================== RETURN VIEW =====================

        return view(
            'mahasiswa.khs.index',
            compact(
                'mahasiswa',
                'khs',
                'khsPerSemester',
                'ipsPerSemester',
                'totalSks',
                'ipk'
            )
        );
    }
}
