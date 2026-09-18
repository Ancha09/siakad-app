<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Services\KrsCardService;
use App\Services\MahasiswaNilaiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KrsController extends Controller
{
    // =========================================================
    // HALAMAN KRS
    // =========================================================

    public function index(MahasiswaNilaiService $nilaiService)
    {
        // ===================== DATA MAHASISWA =====================

        $mahasiswa = Mahasiswa::with('prodi')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // ===================== PERIODE KRS AKTIF =====================

        $periodeKrs = $this->periodeKrsTerbaru();
        $pesanAksesKrs = $this->pesanPenolakanKrs($periodeKrs, $mahasiswa);
        $aksesKrsDibuka = $pesanAksesKrs === null;

        // =========================================================
        // HITUNG IPK MAHASISWA
        // =========================================================

        $ringkasanNilai = $nilaiService->ringkasanMahasiswa($mahasiswa->id);
        $ipk = $ringkasanNilai['ipk_aktual'];
        $ipkTerlihat = $ringkasanNilai['ipk_terlihat'];
        $jumlahKuesionerTertunda = $ringkasanNilai['kuesioner_tertunda'];
        $jumlahNilai = $ringkasanNilai['jumlah_nilai'];

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
            ->where('is_manual', false)
            ->get();

        $periodeKartuKrs = $krs
            ->where('status', '!=', 'Ditolak')
            ->groupBy(fn (Krs $item) => $item->tahun_akademik.'|'.$item->semester_akademik)
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'tahun_akademik' => $first->tahun_akademik,
                    'semester_akademik' => $first->semester_akademik,
                ];
            })
            ->sortByDesc('tahun_akademik')
            ->values();

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

        if ($periodeKrs && $aksesKrsDibuka) {

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
                'ipkTerlihat',
                'jumlahKuesionerTertunda',
                'jumlahNilai',
                'batasSks',
                'periodeKartuKrs',
                'aksesKrsDibuka',
                'pesanAksesKrs'
            )
        );
    }

    public function cardPdf(Request $request, KrsCardService $cards)
    {
        $period = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);
        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();
        $data = $cards->data($mahasiswa, $period['tahun_akademik'], $period['semester_akademik']);
        abort_if($data['printableRecords']->isEmpty(), 404, 'Tidak ada KRS yang dapat dicetak pada periode tersebut.');

        return Pdf::loadView('krs.card-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($cards->filename($mahasiswa, $data['semesterStudi']));
    }

    // =========================================================
    // AMBIL MATA KULIAH
    // =========================================================

    public function store(Request $request, MahasiswaNilaiService $nilaiService)
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

        $periodeKrs = $this->periodeKrsTerbaru();
        $pesanPenolakan = $this->pesanPenolakanKrs($periodeKrs, $mahasiswa);

        if ($pesanPenolakan !== null) {

            return back()->with(
                'error',
                $pesanPenolakan
            );
        }

        // =========================================================
        // HITUNG IPK
        // =========================================================

        $ipk = $nilaiService->ringkasanMahasiswa($mahasiswa->id)['ipk_aktual'];

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
            'kelas.prodi',
        ])
            ->findOrFail(
                $request->jadwal_id
            );

        // ===================== CEK PRODI =====================

        if (
            ! $jadwal->mataKuliah ||
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
                'Mata kuliah tidak dapat diambil karena total SKS melebihi batas maksimal Anda, yaitu '.
                $batasSks.
                ' SKS berdasarkan ketentuan akademik dan IPK yang tersimpan.'
            );
        }

        // ===================== SIMPAN KRS =====================

        Krs::create([

            'mahasiswa_id' => $mahasiswa->id,

            'jadwal_id' => $jadwal->id,

            // ==============================
            // MENUNGGU PERSETUJUAN DOSEN
            // ==============================

            'status' => 'Menunggu',

            'alasan_penolakan' => null,

            'tahun_akademik' => $periodeKrs->tahun_akademik,

            'semester_akademik' => $periodeKrs->semester,

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

        $periodeKrs = $this->periodeKrsTerbaru();
        $pesanPenolakan = $this->pesanPenolakanKrs($periodeKrs, $mahasiswa);

        if ($pesanPenolakan !== null) {

            return redirect()
                ->route('mahasiswa.krs')
                ->with(
                    'error',
                    $pesanPenolakan
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
            ->where('is_manual', false)
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

            'status' => 'Menunggu',

            'alasan_penolakan' => null,

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

    private function aksesKrsDibuka(PeriodeKrs $periodeKrs, Mahasiswa $mahasiswa): bool
    {
        return $periodeKrs->allowsMahasiswa($mahasiswa);
    }

    private function periodeKrsTerbaru(): ?PeriodeKrs
    {
        $periodeAktif = PeriodeKrs::query()
            ->where('status', 'Dibuka')
            ->where('tanggal_mulai', '<=', now())
            ->where('tanggal_selesai', '>=', now())
            ->latest('tanggal_mulai')
            ->latest('id')
            ->first();

        return $periodeAktif
            ?? PeriodeKrs::query()->latest('tanggal_mulai')->latest('id')->first();
    }

    private function pesanPenolakanKrs(?PeriodeKrs $periodeKrs, Mahasiswa $mahasiswa): ?string
    {
        if (! $periodeKrs || $periodeKrs->status !== 'Dibuka') {
            return 'Pengisian KRS sedang ditutup.';
        }

        if (now()->lt($periodeKrs->tanggal_mulai)) {
            return 'Periode KRS belum dimulai.';
        }

        if (now()->gt($periodeKrs->tanggal_selesai)) {
            return 'Periode KRS sudah berakhir.';
        }

        if (! $this->aksesKrsDibuka($periodeKrs, $mahasiswa)) {
            return 'Akses KRS Anda belum dibuka oleh admin.';
        }

        return null;
    }
}
