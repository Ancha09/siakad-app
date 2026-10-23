<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Kurikulum;
use App\Models\KurikulumMataKuliah;
use App\Models\Mahasiswa;
use App\Models\MataKuliahRps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KurikulumViewerController extends Controller
{
    public function mahasiswa(Request $request)
    {
        $mahasiswa = Mahasiswa::with(['prodi', 'kelas'])->where('user_id', Auth::id())->firstOrFail();
        $prodiId = $mahasiswa->prodi_id;
        $activeTab = $request->query('tab', 'rps-saya');

        // 1. Ambil seluruh KRS mahasiswa yang disetujui (Semester Berjalan & Riwayat)
        $krsItems = Krs::with([
            'jadwal.mataKuliah.prodi',
            'jadwal.mataKuliah.rpsAktif',
            'jadwal.mataKuliah.rpsList',
            'jadwal.mataKuliah.cpmks.subCpmks.cpl',
            'jadwal.dosen',
            'jadwal.dosens',
            'jadwal.ruangan',
            'jadwal.skemaPenilaian',
            'mataKuliahManual.prodi',
            'mataKuliahManual.rpsAktif',
            'mataKuliahManual.rpsList',
            'mataKuliahManual.cpmks.subCpmks.cpl',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'Disetujui')
            ->get()
            ->sortByDesc(function (Krs $krs) {
                $tahun = (string) ($krs->tahun_akademik ?: $krs->jadwal?->tahun_akademik ?: '0000/0000');
                $sem = strtolower((string) ($krs->semester_akademik ?: $krs->jadwal?->semester_akademik ?: ''));
                $semOrder = $sem === 'genap' ? 2 : 1;

                return $tahun . '-' . $semOrder . '-' . str_pad((string) $krs->id, 8, '0', STR_PAD_LEFT);
            })
            ->values();

        // Kelompokkan KRS berdasarkan Periode (Tahun Akademik & Semester Akademik)
        $krsByPeriod = $krsItems->groupBy(function (Krs $krs) {
            $tahun = $krs->tahun_akademik ?: ($krs->jadwal?->tahun_akademik ?: 'Reguler');
            $sem = $krs->semester_akademik ?: ($krs->jadwal?->semester_akademik ?: '');

            return trim($tahun . ' ' . $sem);
        });

        $activePeriodKey = $krsByPeriod->keys()->first();

        // 2. Ambil seluruh RPS Aktif yang telah di-ACC Admin untuk Prodi mahasiswa (atau MK Umum)
        $rpsProdiList = MataKuliahRps::with([
            'mataKuliah.prodi',
            'mataKuliah.cpmks.subCpmks.cpl',
        ])
            ->where('is_active', true)
            ->when($prodiId, function ($q) use ($prodiId) {
                $q->whereHas('mataKuliah', function ($m) use ($prodiId) {
                    $m->where('prodi_id', $prodiId)
                        ->orWhereNull('prodi_id')
                        ->orWhereHas('kurikulums', fn ($k) => $k->where('prodi_id', $prodiId))
                        ->orWhereHas('cpmks.subCpmks.cpl', function ($c) use ($prodiId) {
                            $c->where('prodi_id', $prodiId)->orWhere('program_studi_id', $prodiId);
                        });
                });
            })
            ->get()
            ->sortBy([
                fn (MataKuliahRps $rps) => (int) ($rps->mataKuliah?->semester ?? 99),
                fn (MataKuliahRps $rps) => (string) ($rps->mataKuliah?->kode_mk ?? ''),
            ])
            ->values();

        // 3. Ambil katalog kurikulum prodi
        $kurikulums = Kurikulum::with([
            'prodi',
            'mataKuliahKurikulum.mataKuliah.rpsAktif',
            'mataKuliahKurikulum.mataKuliah.cpmks.subCpmks.cpl',
        ])
            ->where('prodi_id', $prodiId)
            ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tahun_mulai')
            ->get();

        $silabusRoute = 'mahasiswa.kurikulum.silabus';

        return view('mahasiswa.kurikulum', compact(
            'mahasiswa',
            'activeTab',
            'krsItems',
            'krsByPeriod',
            'activePeriodKey',
            'rpsProdiList',
            'kurikulums',
            'silabusRoute'
        ));
    }

    public function dosen()
    {
        $pemilik = Dosen::with('prodi')->where('user_id', Auth::id())->firstOrFail();

        return $this->catalog($pemilik->prodi_id, 'dosen.kurikulum', 'dosen.kurikulum.silabus');
    }

    public function downloadMahasiswa(KurikulumMataKuliah $item)
    {
        return $this->downloadForProdi(
            $item,
            Mahasiswa::where('user_id', Auth::id())->value('prodi_id')
        );
    }

    public function downloadDosen(KurikulumMataKuliah $item)
    {
        return $this->downloadForProdi(
            $item,
            Dosen::where('user_id', Auth::id())->value('prodi_id')
        );
    }

    private function catalog(int $prodiId, string $view, string $silabusRoute)
    {
        $kurikulums = Kurikulum::with(['prodi', 'mataKuliahKurikulum.mataKuliah.rpsAktif'])
            ->where('prodi_id', $prodiId)
            ->orderByRaw("CASE WHEN status = 'Aktif' THEN 0 ELSE 1 END")
            ->orderByDesc('tahun_mulai')
            ->get();

        return view($view, compact('kurikulums', 'silabusRoute'));
    }

    private function downloadForProdi(KurikulumMataKuliah $item, ?int $prodiId)
    {
        $item->loadMissing(['kurikulum', 'mataKuliah']);
        abort_unless($prodiId && $item->kurikulum->prodi_id === $prodiId, 403);
        abort_unless($item->silabus_path && Storage::disk('local')->exists($item->silabus_path), 404);

        return response()->download(
            storage_path('app/private/' . ltrim($item->silabus_path, '/')),
            'Silabus-' . $item->mataKuliah->kode_mk . '.pdf'
        );
    }
}
