<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Khs;
use App\Models\Mahasiswa;
use App\Services\MahasiswaNilaiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class KhsController extends Controller
{
    // =========================================================
    // HALAMAN KHS MAHASISWA
    // =========================================================

    public function index(MahasiswaNilaiService $nilaiService)
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

                $query->where('mahasiswa_id', $mahasiswa->id)
                    ->where('status', 'Disetujui');

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

        // IPK hanya dapat dilihat setelah seluruh kuesioner nilai selesai.
        $ringkasanNilai = $nilaiService->ringkasan($khs);
        $ipk = $ringkasanNilai['ipk_terlihat'];
        $jumlahKuesionerTertunda = $ringkasanNilai['kuesioner_tertunda'];

        // ===================== IPS PER SEMESTER =====================

        $ipsPerSemester = [];

        foreach ($khsPerSemester as $semester => $data) {

            $semesterTerkunci = $data->contains(function ($item) {
                return $item->krs?->kuesioner === null;
            });

            if ($semesterTerkunci) {
                $ipsPerSemester[$semester] = null;

                continue;
            }

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

        // Jangan teruskan nilai yang terkunci ke lapisan tampilan.
        $khs->each(function ($item) {
            if ($item->krs?->kuesioner === null) {
                $item->setAttribute('nilai_angka', null);
                $item->setAttribute('nilai_huruf', null);
                $item->setAttribute('bobot', null);
            }
        });

        // ===================== RETURN VIEW =====================

        return view(
            'mahasiswa.khs.index',
            compact(
                'mahasiswa',
                'khs',
                'khsPerSemester',
                'ipsPerSemester',
                'totalSks',
                'ipk',
                'jumlahKuesionerTertunda'
            )
        );
    }

    public function transkripPdf(MahasiswaNilaiService $nilaiService)
    {
        $mahasiswa = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $khs = Khs::with([
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.kuesioner',
        ])
            ->whereHas('krs', function ($query) use ($mahasiswa) {
                $query->where('mahasiswa_id', $mahasiswa->id)
                    ->where('status', 'Disetujui');
            })
            ->orderBy('tahun_akademik')
            ->orderBy('semester_akademik')
            ->get();

        $ringkasanNilai = $nilaiService->ringkasan($khs);

        if ($ringkasanNilai['kuesioner_tertunda'] > 0) {
            return redirect()
                ->route('mahasiswa.kuesioner')
                ->with('info', 'Lengkapi seluruh kuesioner wajib sebelum mengunduh transkrip.');
        }

        $totalSks = $khs->sum(
            fn (Khs $item) => (int) ($item->krs?->jadwal?->mataKuliah?->sks ?? 0)
        );

        return Pdf::loadView('mahasiswa.khs.transkrip-pdf', [
            'mahasiswa' => $mahasiswa,
            'khs' => $khs,
            'totalSks' => $totalSks,
            'ipk' => $ringkasanNilai['ipk_terlihat'],
        ])
            ->setPaper('a4', 'portrait')
            ->download('transkrip-'.$mahasiswa->nim.'.pdf');
    }
}
