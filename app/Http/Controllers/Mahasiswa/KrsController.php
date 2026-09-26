<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\KrsPdfRequest;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Services\AvailableKrsScheduleService;
use App\Services\KrsCardService;
use App\Services\KrsSksLimit;
use App\Services\MahasiswaNilaiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KrsController extends Controller
{
    // =========================================================
    // HALAMAN KRS
    // =========================================================

    public function index(
        MahasiswaNilaiService $nilaiService,
        AvailableKrsScheduleService $scheduleService,
        KrsCardService $cards,
        KrsSksLimit $sksLimit
    )
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
        $ipkTerlihat = $ringkasanNilai['ipk_terlihat'];
        $jumlahKuesionerTertunda = $ringkasanNilai['kuesioner_tertunda'];
        $jumlahNilai = $ringkasanNilai['jumlah_nilai'];

        // =========================================================
        // BATAS SKS SEMENTARA: 22 ATAU BATAS PERIODE JIKA LEBIH KECIL
        // =========================================================

        $batasSks = $sksLimit->forPeriod($periodeKrs);

        // ===================== KRS MAHASISWA =====================

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'mataKuliahManual',
            'dosenManual',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where(fn ($query) => $query
                ->where('is_manual', false)
                ->orWhereNull('is_manual'))
            ->get();

        $periodeKartuKrs = $krs
            ->filter(fn (Krs $item) => filled($item->tahun_akademik)
                && in_array($item->semester_akademik, ['Ganjil', 'Genap'], true))
            ->groupBy(fn (Krs $item) => $item->tahun_akademik.'|'.$item->semester_akademik)
            ->map(function ($items) use ($cards) {
                $first = $items->first();

                return [
                    'tahun_akademik' => $first->tahun_akademik,
                    'semester_akademik' => $first->semester_akademik,
                    'download_disetujui' => $cards->isApprovedForDownload($items),
                ];
            })
            ->sortByDesc('tahun_akademik')
            ->values();

        // ===================== KRS PADA PERIODE AKTIF =====================

        $krsPeriodeAktif = $periodeKrs
            ? $krs->filter(
                fn (Krs $item) => $scheduleService->matchesPeriod($item, $periodeKrs)
            )
            : collect();

        // ===================== JADWAL YANG SUDAH DIAMBIL =====================

        $jadwalDiambil = $krsPeriodeAktif
            ->pluck('jadwal_id')
            ->filter()
            ->values()
            ->toArray();
        $mataKuliahDiambil = $krsPeriodeAktif
            ->map(fn (Krs $item) => $item->mata_kuliah_efektif?->id)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // ===================== TOTAL SKS =====================

        $totalSks = $krsPeriodeAktif
            ->where('status', '!=', 'Ditolak')
            ->sum(function ($item) {

                return $item->mata_kuliah_efektif?->sks ?? 0;

            });

        // ===================== SISA SKS =====================

        $sisaSks = max(
            0,
            $batasSks - $totalSks
        );

        // ===================== JADWAL TERSEDIA =====================

        $jadwals = collect();
        $jadwalsBySemester = collect();

        if ($periodeKrs && $aksesKrsDibuka) {

            $jadwals = $scheduleService->forStudent(
                $mahasiswa,
                $periodeKrs,
                $jadwalDiambil,
                $mataKuliahDiambil
            );

            $requiredParity = $scheduleService->requiredParity($periodeKrs->semester);
            $jadwalsBySemester = $jadwals
                ->groupBy(fn (Jadwal $jadwal) => $scheduleService->semesterNumberForStudent(
                    $jadwal,
                    $mahasiswa,
                    $requiredParity
                ))
                ->filter(fn ($items, $semester) => is_numeric($semester))
                ->sortKeys(SORT_NUMERIC)
                ->map(fn ($items) => $items
                    ->sortBy(fn (Jadwal $jadwal) => strtolower(
                        ($jadwal->mataKuliah?->kode_mk ?? '').'|'.
                        ($jadwal->mataKuliah?->nama_mk ?? '').'|'.
                        str_pad((string) $jadwal->id, 10, '0', STR_PAD_LEFT)
                    ))
                    ->values());

            Log::debug('KRS available schedule lookup', [
                'periode_krs_id' => $periodeKrs->id,
                'mahasiswa_id' => $mahasiswa->id,
                'prodi_id' => $mahasiswa->prodi_id,
                'semester_mahasiswa' => $mahasiswa->semester,
                'tahun_akademik' => $periodeKrs->tahun_akademik,
                'semester_akademik' => $periodeKrs->semester,
                'jumlah_jadwal' => $jadwals->count(),
                'audit' => $scheduleService->lastAudit(),
            ]);
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
                'jadwalsBySemester',
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

    public function cardPdf(KrsPdfRequest $request, KrsCardService $cards)
    {
        $period = $request->validated();
        $mahasiswa = Mahasiswa::where('user_id', Auth::id())->firstOrFail();
        $records = $cards->records(
            $mahasiswa,
            $period['tahun_akademik'],
            $period['semester_akademik']
        );

        if ($records->isEmpty()) {
            return redirect()
                ->route('mahasiswa.krs')
                ->with('error', 'Data KRS tidak ditemukan.');
        }

        if (! $cards->isApprovedForDownload($records)) {
            return redirect()
                ->route('mahasiswa.krs')
                ->with('error', 'KRS belum disetujui dosen wali. Download KRS tersedia setelah disetujui.');
        }

        $data = $cards->data(
            $mahasiswa,
            $period['tahun_akademik'],
            $period['semester_akademik'],
            true
        );
        abort_if($data['printableRecords']->isEmpty(), 404, 'Tidak ada KRS yang dapat dicetak pada periode tersebut.');

        return Pdf::loadView('krs.card-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($cards->filename($mahasiswa, $period['tahun_akademik'], $period['semester_akademik']));
    }

    // =========================================================
    // AMBIL MATA KULIAH
    // =========================================================

    public function store(
        Request $request,
        AvailableKrsScheduleService $scheduleService,
        KrsSksLimit $sksLimit
    )
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
        // BATAS SKS SAMA DENGAN YANG DITAMPILKAN DI HALAMAN KRS
        // =========================================================

        $batasSks = $sksLimit->forPeriod($periodeKrs);

        // ===================== AMBIL JADWAL =====================

        $jadwal = Jadwal::with('mataKuliah')
            ->findOrFail(
                $request->jadwal_id
            );

        $krsPeriodeAktif = Krs::with(['jadwal.mataKuliah', 'mataKuliahManual'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where(fn ($query) => $query
                ->where('is_manual', false)
                ->orWhereNull('is_manual'))
            ->get()
            ->filter(
                fn (Krs $item) => $scheduleService->matchesPeriod($item, $periodeKrs)
            );

        // Duplikasi hanya diperiksa pada periode aktif, bukan seluruh riwayat KRS.
        $sudahAda = $krsPeriodeAktif->contains(
            fn (Krs $item) => (int) $item->jadwal_id === (int) $jadwal->id
                || (int) $item->mata_kuliah_efektif?->id === (int) $jadwal->mata_kuliah_id
        );

        if ($sudahAda) {

            return back()->with(
                'error',
                'Mata kuliah tersebut sudah ada di KRS.'
            );
        }

        // Gunakan aturan yang sama dengan daftar di halaman agar request buatan
        // tidak dapat memilih jadwal di luar prodi/semester/periode mahasiswa.
        $jadwalBolehDiambil = $scheduleService
            ->forStudent($mahasiswa, $periodeKrs)
            ->contains(fn (Jadwal $item) => (int) $item->id === (int) $jadwal->id);

        if (! $jadwalBolehDiambil) {
            return back()->with(
                'error',
                'Jadwal mata kuliah tidak tersedia untuk prodi, semester, dan periode KRS Anda.'
            );
        }

        // Bentrok antarprodi boleh disimpan oleh admin, tetapi seorang mahasiswa
        // tetap tidak boleh mengambil dua perkuliahan dengan rentang waktu tumpang tindih.
        $jadwalBentrok = $krsPeriodeAktif
            ->where('status', '!=', 'Ditolak')
            ->map(fn (Krs $item) => $item->jadwal)
            ->filter()
            ->first(fn (Jadwal $existing) => $this->schedulesOverlap($existing, $jadwal));

        if ($jadwalBentrok) {
            $mataKuliahBentrok = $jadwalBentrok->mataKuliah?->nama_mk ?? 'mata kuliah sebelumnya';
            $mataKuliahBaru = $jadwal->mataKuliah?->nama_mk ?? 'mata kuliah yang dipilih';

            return back()->with(
                'error',
                "Terdapat jadwal mata kuliah yang bentrok: {$mataKuliahBentrok} dan {$mataKuliahBaru}."
            );
        }

        // ===================== HITUNG TOTAL SKS =====================

        $totalSks = $krsPeriodeAktif
            ->where('status', '!=', 'Ditolak')
            ->sum(function ($item) {

                return $item->mata_kuliah_efektif?->sks ?? 0;

            });

        $sksMataKuliah =
            $jadwal->mataKuliah->sks ?? 0;

        $totalSetelahAmbil =
            $totalSks + $sksMataKuliah;

        // =========================================================
        // CEK MAKSIMAL SKS PERIODE
        // =========================================================

        if (
            $totalSetelahAmbil >
            $batasSks
        ) {

            return back()->with(
                'error',
                'Mata kuliah tidak dapat diambil karena total SKS melebihi batas maksimal Anda, yaitu '.
                $batasSks.
                ' SKS.'
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

    private function schedulesOverlap(Jadwal $left, Jadwal $right): bool
    {
        if (strcasecmp(trim((string) $left->hari), trim((string) $right->hari)) !== 0) {
            return false;
        }

        $leftStart = $this->timeToSeconds($left->jam_mulai);
        $leftEnd = $this->timeToSeconds($left->jam_selesai);
        $rightStart = $this->timeToSeconds($right->jam_mulai);
        $rightEnd = $this->timeToSeconds($right->jam_selesai);

        if (in_array(null, [$leftStart, $leftEnd, $rightStart, $rightEnd], true)) {
            return false;
        }

        return $leftStart < $rightEnd && $leftEnd > $rightStart;
    }

    private function timeToSeconds(mixed $time): ?int
    {
        if (! is_string($time) || ! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($time), $parts)) {
            return null;
        }

        return ((int) $parts[1] * 3600) + ((int) $parts[2] * 60) + (int) ($parts[3] ?? 0);
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
