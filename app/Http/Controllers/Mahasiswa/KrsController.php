<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
use App\Models\Khs;
use App\Models\PeriodeKrs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KrsController extends Controller
{
    // =========================================================
    // HALAMAN KRS
    // =========================================================

    public function index()
    {
        // ===================== DATA MAHASISWA =====================

        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();


        // ===================== PERIODE KRS AKTIF =====================

        $periodeKrs = PeriodeKrs::where('status', 'Dibuka')
            ->where('tanggal_mulai', '<=', now())
            ->where('tanggal_selesai', '>=', now())
            ->latest()
            ->first();


        // =========================================================
        // HITUNG IPK MAHASISWA
        // =========================================================

        $ipk = $this->hitungIpk($mahasiswa->id);


        // =========================================================
        // TENTUKAN BATAS SKS BERDASARKAN IPK
        // =========================================================

        $batasSksIpk = $this->tentukanBatasSks($ipk);


        // =========================================================
        // BATAS SKS FINAL
        //
        // Tidak boleh melebihi batas maksimal periode KRS.
        // =========================================================

        if ($periodeKrs) {

            $batasSks = min(
                $batasSksIpk,
                $periodeKrs->maksimal_sks
            );

        } else {

            $batasSks = $batasSksIpk;

        }


        // ===================== KRS MAHASISWA =====================

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'jadwal.kelas.prodi',
        ])
        ->where('mahasiswa_id', $mahasiswa->id)
        ->get();


        // ===================== KRS PADA PERIODE AKTIF =====================

        $krsPeriodeAktif = $periodeKrs
            ? $krs->where('tahun_akademik', $periodeKrs->tahun_akademik)
                ->where('semester_akademik', $periodeKrs->semester)
            : collect();


        // ===================== JADWAL YANG SUDAH DIAMBIL =====================

        $jadwalDiambil = $krsPeriodeAktif
            ->pluck('jadwal_id')
            ->toArray();


        // ===================== TOTAL SKS =====================

        $totalSks = $krsPeriodeAktif
            ->where('status', '!=', 'Ditolak')
            ->sum(function ($item) {

                return $item->jadwal->mataKuliah->sks ?? 0;

            });


        // ===================== SISA SKS =====================

        $sisaSks = max(
            0,
            $batasSks - $totalSks
        );


        // ===================== JADWAL TERSEDIA =====================

        $jadwals = collect();


        if ($periodeKrs) {

            $jadwals = Jadwal::with([
                'mataKuliah',
                'dosen',
                'ruangan',
                'kelas.prodi',
            ])

            // Jangan tampilkan jadwal yang sudah ada di KRS
            ->whereNotIn('id', $jadwalDiambil)

            // ===================== SESUAI PRODI =====================

            ->whereHas('mataKuliah', function ($query) use ($mahasiswa) {

                $query->where(
                    'prodi_id',
                    $mahasiswa->prodi_id
                );

            })

            // ===================== SESUAI TAHUN AKADEMIK =====================

            ->where(
                'tahun_akademik',
                $periodeKrs->tahun_akademik
            )

            // ===================== SESUAI SEMESTER AKADEMIK =====================

            ->where(
                'semester_akademik',
                $periodeKrs->semester
            )

            ->orderByRaw("
                CASE hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 0 END
            ")

            ->orderBy('jam_mulai')

            ->get();
        }


        // =========================================================
        // RETURN VIEW
        // =========================================================

        return view(
            'mahasiswa.krs.index',
            compact(
                'mahasiswa',
                'krs',
                'jadwals',
                'totalSks',
                'sisaSks',
                'periodeKrs',
                'ipk',
                'batasSks'
            )
        );
    }


    // =========================================================
    // AMBIL MATA KULIAH
    // =========================================================

    public function store(Request $request)
    {
        $mahasiswa = Mahasiswa::where(
            'user_id',
            Auth::id()
        )->firstOrFail();


        // ===================== VALIDASI =====================

        $request->validate([
            'jadwal_id' => 'required|exists:jadwals,id',
        ]);


        // ===================== CEK PERIODE KRS =====================

        $periodeKrs = PeriodeKrs::where(
            'status',
            'Dibuka'
        )
        ->where(
            'tanggal_mulai',
            '<=',
            now()
        )
        ->where(
            'tanggal_selesai',
            '>=',
            now()
        )
        ->latest()
        ->first();


        if (!$periodeKrs) {

            return back()->with(
                'error',
                'Pengisian KRS sedang ditutup atau periode KRS telah berakhir.'
            );
        }


        // =========================================================
        // HITUNG IPK
        // =========================================================

        $ipk = $this->hitungIpk(
            $mahasiswa->id
        );


        // =========================================================
        // TENTUKAN BATAS SKS BERDASARKAN IPK
        // =========================================================

        $batasSksIpk = $this->tentukanBatasSks(
            $ipk
        );


        // =========================================================
        // BATAS FINAL
        // Tidak boleh melebihi maksimal SKS periode.
        // =========================================================

        $batasSks = min(
            $batasSksIpk,
            $periodeKrs->maksimal_sks
        );


        // ===================== AMBIL JADWAL =====================

        $jadwal = Jadwal::with([
            'mataKuliah',
            'kelas.prodi'
        ])
        ->findOrFail(
            $request->jadwal_id
        );


        // ===================== CEK PRODI =====================

        if (
            !$jadwal->mataKuliah ||
            $jadwal->mataKuliah->prodi_id != $mahasiswa->prodi_id
        ) {

            return back()->with(
                'error',
                'Mata kuliah tidak sesuai dengan Program Studi Anda.'
            );
        }


        // ===================== CEK TAHUN AKADEMIK =====================

        if (
            $jadwal->tahun_akademik !=
            $periodeKrs->tahun_akademik
        ) {

            return back()->with(
                'error',
                'Jadwal tidak termasuk dalam periode akademik KRS saat ini.'
            );
        }


        // ===================== CEK SEMESTER AKADEMIK =====================

        if (
            $jadwal->semester_akademik !=
            $periodeKrs->semester
        ) {

            return back()->with(
                'error',
                'Jadwal tidak sesuai dengan semester akademik KRS saat ini.'
            );
        }


        // ===================== CEK DUPLIKAT =====================

        $sudahAda = Krs::where(
            'mahasiswa_id',
            $mahasiswa->id
        )
        ->where(
            'jadwal_id',
            $jadwal->id
        )
        ->exists();


        if ($sudahAda) {

            return back()->with(
                'error',
                'Mata kuliah tersebut sudah ada di KRS.'
            );
        }


        // ===================== HITUNG TOTAL SKS =====================

        $totalSks = Krs::where(
            'mahasiswa_id',
            $mahasiswa->id
        )
        ->where('tahun_akademik', $periodeKrs->tahun_akademik)
        ->where('semester_akademik', $periodeKrs->semester)
        ->where('status', '!=', 'Ditolak')
        ->with('jadwal.mataKuliah')
        ->get()
        ->sum(function ($item) {

            return $item->jadwal->mataKuliah->sks ?? 0;

        });


        $sksMataKuliah =
            $jadwal->mataKuliah->sks ?? 0;


        $totalSetelahAmbil =
            $totalSks + $sksMataKuliah;


        // =========================================================
        // CEK MAKSIMAL SKS BERDASARKAN IPK
        // =========================================================

        if (
            $totalSetelahAmbil >
            $batasSks
        ) {

            return back()->with(
                'error',
                'Mata kuliah tidak dapat diambil karena total SKS melebihi batas maksimal Anda, yaitu ' .
                $batasSks .
                ' SKS berdasarkan IPK ' .
                number_format($ipk, 2) .
                '.'
            );
        }


        // ===================== SIMPAN KRS =====================

        Krs::create([

            'mahasiswa_id' =>
                $mahasiswa->id,

            'jadwal_id' =>
                $jadwal->id,

            // ==============================
            // MENUNGGU PERSETUJUAN DOSEN
            // ==============================

            'status' =>
                'Menunggu',

            'alasan_penolakan' =>
                null,

            'tahun_akademik' =>
                $periodeKrs->tahun_akademik,

            'semester_akademik' =>
                $periodeKrs->semester,

        ]);


        return redirect()
            ->route('mahasiswa.krs')
            ->with(
                'success',
                'Mata kuliah berhasil diajukan. Menunggu persetujuan Dosen Wali.'
            );
    }


    // =========================================================
    // AJUKAN KEMBALI KRS YANG DITOLAK
    // =========================================================

    public function ajukanKembali(int $id)
    {
        // ===================== MAHASISWA LOGIN =====================

        $mahasiswa = Mahasiswa::where(
            'user_id',
            Auth::id()
        )->firstOrFail();


        // ===================== CEK PERIODE KRS =====================

        $periodeKrs = PeriodeKrs::where(
            'status',
            'Dibuka'
        )
        ->where(
            'tanggal_mulai',
            '<=',
            now()
        )
        ->where(
            'tanggal_selesai',
            '>=',
            now()
        )
        ->latest()
        ->first();


        if (!$periodeKrs) {

            return redirect()
                ->route('mahasiswa.krs')
                ->with(
                    'error',
                    'Pengajuan KRS kembali tidak dapat dilakukan karena periode KRS sedang ditutup.'
                );
        }


        // ===================== AMBIL KRS MILIK MAHASISWA =====================

        $krs = Krs::where(
            'id',
            $id
        )
        ->where(
            'mahasiswa_id',
            $mahasiswa->id
        )
        ->firstOrFail();


        // ===================== CEK STATUS =====================

        if ($krs->status !== 'Ditolak') {

            return redirect()
                ->route('mahasiswa.krs')
                ->with(
                    'error',
                    'KRS tersebut tidak dapat diajukan kembali karena statusnya bukan Ditolak.'
                );
        }


        // ===================== CEK TAHUN AKADEMIK =====================

        if (
            $krs->tahun_akademik !=
            $periodeKrs->tahun_akademik
        ) {

            return redirect()
                ->route('mahasiswa.krs')
                ->with(
                    'error',
                    'KRS tersebut bukan bagian dari periode akademik yang sedang dibuka.'
                );
        }


        // ===================== CEK SEMESTER AKADEMIK =====================

        if (
            $krs->semester_akademik !=
            $periodeKrs->semester
        ) {

            return redirect()
                ->route('mahasiswa.krs')
                ->with(
                    'error',
                    'KRS tersebut bukan bagian dari semester akademik yang sedang dibuka.'
                );
        }


        // ===================== AJUKAN KEMBALI =====================

        $krs->update([

            'status' =>
                'Menunggu',

            'alasan_penolakan' =>
                null,

        ]);


        // ===================== REDIRECT =====================

        return redirect()
            ->route('mahasiswa.krs')
            ->with(
                'success',
                'KRS berhasil diajukan kembali. Menunggu persetujuan Dosen Wali.'
            );
    }


    // =========================================================
    // HITUNG IPK MAHASISWA
    // =========================================================

    private function hitungIpk(int $mahasiswaId): float
    {
        $khs = Khs::with([
            'krs.jadwal.mataKuliah'
        ])
        ->whereHas('krs', function ($query) use ($mahasiswaId) {

            $query->where(
                'mahasiswa_id',
                $mahasiswaId
            );

        })
        ->get();


        if ($khs->isEmpty()) {

            return 0.00;
        }


        $totalSks = 0;
        $totalMutu = 0;


        foreach ($khs as $item) {

            $sks =
                $item->krs
                    ->jadwal
                    ->mataKuliah
                    ->sks ?? 0;

            $bobot =
                $item->bobot ?? 0;


            $totalSks += $sks;

            $totalMutu +=
                $sks * $bobot;
        }


        if ($totalSks <= 0) {

            return 0.00;
        }


        return round(
            $totalMutu / $totalSks,
            2
        );
    }


    // =========================================================
    // TENTUKAN BATAS SKS BERDASARKAN IPK
    // =========================================================

    private function tentukanBatasSks(float $ipk): int
    {
        // IPK >= 3.50
        if ($ipk >= 3.50) {

            return 24;
        }


        // IPK >= 3.25
        if ($ipk >= 3.25) {

            return 22;
        }


        // IPK < 3.25
        return 20;
    }
}
