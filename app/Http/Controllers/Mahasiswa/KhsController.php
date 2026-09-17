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
            'dosenManual',
            'krs.jadwal.mataKuliah',
            'krs.mataKuliahManual',
            'krs.dosenManual',
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

            return $item->sks_efektif;

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

            $ipsPerSemester[$semester] = $nilaiService->hitungIndeks($dataTerlihat);
        }

        // Jangan teruskan nilai yang terkunci ke lapisan tampilan.
        $nilaiService->sembunyikanNilaiTerkunci($khs);

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
            'dosenManual',
            'krs.jadwal.mataKuliah',
            'krs.mataKuliahManual',
            'krs.dosenManual',
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
            fn (Khs $item) => $item->sks_efektif
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
