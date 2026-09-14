<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LaporanAkademikController extends Controller
{
    private const NILAI_HURUF = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'D', 'E'];

    public function index(Request $request)
    {
        $this->pastikanAdmin($request);
        $this->validasiFilter($request);

        $laporan = $this->buatLaporan($request);
        $laporan['detailMahasiswa'] = $this->paginateCollection(
            $laporan['detailMahasiswa'],
            15,
            'mahasiswa_page'
        );
        $laporan['kehadiranRendah'] = $this->paginateCollection(
            $laporan['kehadiranRendah'],
            10,
            'kehadiran_page'
        );

        return view('admin.laporan.laporan', array_merge(
            $laporan,
            $this->dataFilter()
        ));
    }

    public function excel(Request $request, ReportExporter $exporter)
    {
        $this->pastikanAdmin($request);
        $this->validasiFilter($request);
        $laporan = $this->buatLaporan($request);

        return $exporter->excel(
            'laporan-akademik-'.now()->format('Ymd-His').'.xlsx',
            'Laporan Akademik STTMI',
            $this->deskripsiFilter($request),
            $this->excelSheets($laporan)
        );
    }

    public function pdf(Request $request)
    {
        $this->pastikanAdmin($request);
        $this->validasiFilter($request);

        $data = array_merge($this->buatLaporan($request), [
            'reportTitle' => 'Laporan Akademik',
            'deskripsiFilter' => $this->deskripsiFilter($request),
        ]);

        return Pdf::loadView('admin.laporan.print', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-akademik-'.now()->format('Ymd-His').'.pdf');
    }

    private function excelSheets(array $laporan): array
    {
        $ringkasan = $laporan['ringkasan'];

        return [
            [
                'title' => 'Ringkasan',
                'headings' => ['Indikator', 'Nilai'],
                'rows' => [
                    ['Jumlah Mahasiswa', $ringkasan['jumlah_mahasiswa']],
                    ['Jumlah Mata Kuliah', $ringkasan['jumlah_mata_kuliah']],
                    ['Rata-rata IP', $ringkasan['rata_ip']],
                    ['Rata-rata Kehadiran', $ringkasan['rata_kehadiran'].'%'],
                    ['Mahasiswa Kehadiran < 75%', $ringkasan['mahasiswa_kehadiran_rendah']],
                ],
            ],
            [
                'title' => 'Rekap Nilai',
                'headings' => ['Mata Kuliah', 'Dosen', 'Kelas', 'Mahasiswa', 'Rata Nilai', 'Rata Bobot', 'Tertinggi', 'Terendah'],
                'rows' => $laporan['rekapNilai']->map(fn ($item) => [
                    $item->mata_kuliah, $item->dosen, $item->kelas, $item->jumlah_mahasiswa,
                    $item->rata_nilai, $item->rata_bobot, $item->tertinggi, $item->terendah,
                ]),
            ],
            [
                'title' => 'Kehadiran Rendah',
                'headings' => ['NIM', 'Nama', 'Program Studi', 'Kelas', 'Mata Kuliah', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Kehadiran'],
                'rows' => $laporan['kehadiranRendah']->map(fn ($item) => [
                    $item->nim, $item->nama, $item->prodi, $item->kelas, $item->mata_kuliah,
                    $item->hadir, $item->izin, $item->sakit, $item->alpha, $item->persentase.'%',
                ]),
            ],
            [
                'title' => 'Mahasiswa',
                'headings' => ['NIM', 'Nama', 'Program Studi', 'Kelas', 'SKS Disetujui', 'IP Akademik', 'Kehadiran'],
                'rows' => $laporan['detailMahasiswa']->map(fn ($item) => [
                    $item->nim, $item->nama, $item->prodi, $item->kelas, $item->total_sks,
                    $item->ip ?? '-', $item->kehadiran === null ? '-' : $item->kehadiran.'%',
                ]),
            ],
            [
                'title' => 'Evaluasi Perkuliahan',
                'headings' => ['Dosen', 'Mata Kuliah', 'Kelas', 'Responden', 'Rata-rata Skor'],
                'rows' => $laporan['rekapEvaluasi']->map(fn ($item) => [
                    $item->dosen, $item->mata_kuliah, $item->kelas, $item->jumlah_responden, $item->rata_rata,
                ]),
            ],
        ];
    }

    private function buatLaporan(Request $request): array
    {
        $query = Krs::with([
            'mahasiswa.prodi.fakultas',
            'mahasiswa.kelas',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'khs',
            'presensis',
            'kuesioner',
        ]);
        $this->terapkanFilter($query, $request);
        $krs = $query->get();

        $nilai = $krs->pluck('khs')->filter()->values();
        $presensi = $krs->flatMap(fn (Krs $item) => $item->presensis)->values();
        $detailMahasiswa = $this->detailMahasiswa($krs);
        $kehadiranRendah = $this->kehadiranRendah($krs);

        $distribusiNilai = collect(self::NILAI_HURUF)
            ->mapWithKeys(fn (string $huruf) => [$huruf => $nilai->where('nilai_huruf', $huruf)->count()]);

        $rekapNilai = $krs
            ->filter(fn (Krs $item) => $item->khs !== null)
            ->groupBy('jadwal_id')
            ->filter(fn ($items, $jadwalId) => $jadwalId !== null)
            ->map(function (Collection $items) {
                $pertama = $items->first();
                $jadwal = $pertama->jadwal;

                return (object) [
                    'mata_kuliah' => $jadwal?->mataKuliah?->nama_mk ?? '-',
                    'dosen' => $jadwal?->dosen?->nama ?? '-',
                    'kelas' => $jadwal?->kelas?->nama_kelas ?? '-',
                    'jumlah_mahasiswa' => $items->pluck('mahasiswa_id')->unique()->count(),
                    'rata_nilai' => round((float) $items->avg('khs.nilai_angka'), 2),
                    'rata_bobot' => round((float) $items->avg('khs.bobot'), 2),
                    'tertinggi' => $items->max('khs.nilai_angka'),
                    'terendah' => $items->min('khs.nilai_angka'),
                ];
            })
            ->sortBy('mata_kuliah')
            ->values();

        $rekapPresensi = [
            'hadir' => $presensi->where('status', 'Hadir')->count(),
            'izin' => $presensi->where('status', 'Izin')->count(),
            'sakit' => $presensi->where('status', 'Sakit')->count(),
            'alpha' => $presensi->where('status', 'Alpha')->count(),
        ];
        $totalPresensi = array_sum($rekapPresensi);
        $rataKehadiran = $totalPresensi > 0
            ? round(($rekapPresensi['hadir'] / $totalPresensi) * 100, 1)
            : 0;

        $rekapKrs = [
            'menunggu' => $krs->where('status', 'Menunggu')->count(),
            'disetujui' => $krs->where('status', 'Disetujui')->count(),
            'ditolak' => $krs->where('status', 'Ditolak')->count(),
            'diambil_legacy' => $krs->where('status', 'Diambil')->count(),
            'mahasiswa_mengajukan' => $krs->pluck('mahasiswa_id')->filter()->unique()->count(),
            'sks_disetujui' => $krs->where('status', 'Disetujui')->sum(
                fn (Krs $item) => (int) ($item->jadwal?->mataKuliah?->sks ?? 0)
            ),
        ];

        $wajibKuesioner = $krs->filter(
            fn (Krs $item) => $item->status === 'Disetujui' && $item->khs !== null
        );
        $kuesionerRows = $wajibKuesioner->filter(fn (Krs $item) => $item->kuesioner !== null)->values();
        $kuesioners = $kuesionerRows->pluck('kuesioner')->values();
        $rekapEvaluasi = $this->rekapEvaluasi($kuesionerRows);
        $evaluasiDosen = $this->rataEvaluasiDosen($kuesionerRows);
        $evaluasiMataKuliah = $this->rataEvaluasiMataKuliah($kuesionerRows);

        $rekapKuesioner = [
            'jumlah_responden' => $kuesioners->count(),
            'sudah_mengisi' => $kuesionerRows->count(),
            'belum_mengisi' => $wajibKuesioner->count() - $kuesionerRows->count(),
            'rata_rata' => $kuesioners->isNotEmpty()
                ? round((float) $kuesioners->avg('rata_rata'), 2)
                : 0,
        ];

        $komentarKuesioner = $kuesionerRows
            ->filter(fn (Krs $item) => filled($item->kuesioner?->komentar))
            ->sortByDesc(fn (Krs $item) => $item->kuesioner?->submitted_at)
            ->take(10)
            ->map(function (Krs $item) {
                return (object) [
                    'kode' => $item->kuesioner?->kode_responden,
                    'dosen' => $item->jadwal?->dosen?->nama ?? '-',
                    'mata_kuliah' => $item->jadwal?->mataKuliah?->nama_mk ?? '-',
                    'komentar' => $item->kuesioner?->komentar,
                    'tanggal' => $item->kuesioner?->submitted_at,
                ];
            })
            ->values();

        $ipTersedia = $detailMahasiswa->pluck('ip')->filter(fn ($ip) => $ip !== null);

        return [
            'ringkasan' => [
                'jumlah_mahasiswa' => $krs->pluck('mahasiswa_id')->filter()->unique()->count(),
                'jumlah_mata_kuliah' => $krs->pluck('jadwal.mata_kuliah_id')->filter()->unique()->count(),
                'rata_ip' => $ipTersedia->isNotEmpty() ? round((float) $ipTersedia->avg(), 2) : 0,
                'rata_kehadiran' => $rataKehadiran,
                'mahasiswa_kehadiran_rendah' => $kehadiranRendah->pluck('mahasiswa_id')->unique()->count(),
            ],
            'distribusiNilai' => $distribusiNilai,
            'rekapNilai' => $rekapNilai,
            'rekapPresensi' => $rekapPresensi,
            'kehadiranRendah' => $kehadiranRendah,
            'rekapKrs' => $rekapKrs,
            'rekapKuesioner' => $rekapKuesioner,
            'rekapEvaluasi' => $rekapEvaluasi,
            'evaluasiDosen' => $evaluasiDosen,
            'evaluasiMataKuliah' => $evaluasiMataKuliah,
            'komentarKuesioner' => $komentarKuesioner,
            'detailMahasiswa' => $detailMahasiswa,
            'chartNilai' => [
                'labels' => $distribusiNilai->keys()->values(),
                'data' => $distribusiNilai->values(),
            ],
            'chartPresensi' => [
                'labels' => ['Hadir', 'Izin', 'Sakit', 'Alpha'],
                'data' => array_values($rekapPresensi),
            ],
            'chartEvaluasi' => [
                'labels' => $evaluasiDosen->take(10)->pluck('dosen')->values(),
                'data' => $evaluasiDosen->take(10)->pluck('rata_rata')->values(),
            ],
        ];
    }

    private function detailMahasiswa(Collection $krs): Collection
    {
        return $krs->groupBy('mahasiswa_id')
            ->filter(fn ($items, $id) => $id !== null)
            ->map(function (Collection $items) {
                $mahasiswa = $items->first()->mahasiswa;
                $nilai = $items->filter(fn (Krs $item) => $item->khs !== null);
                $sksNilai = $nilai->sum(fn (Krs $item) => (int) ($item->jadwal?->mataKuliah?->sks ?? 0));
                $mutu = $nilai->sum(fn (Krs $item) => (float) ($item->khs?->bobot ?? 0) * (int) ($item->jadwal?->mataKuliah?->sks ?? 0));
                $presensi = $items->flatMap(fn (Krs $item) => $item->presensis);

                return (object) [
                    'mahasiswa_id' => $mahasiswa?->id,
                    'nim' => $mahasiswa?->nim ?? '-',
                    'nama' => $mahasiswa?->nama ?? '-',
                    'prodi' => $mahasiswa?->prodi?->nama_prodi ?? '-',
                    'kelas' => $mahasiswa?->kelas?->nama_kelas ?? '-',
                    'total_sks' => $items->where('status', 'Disetujui')->sum(
                        fn (Krs $item) => (int) ($item->jadwal?->mataKuliah?->sks ?? 0)
                    ),
                    'ip' => $sksNilai > 0 ? round($mutu / $sksNilai, 2) : null,
                    'kehadiran' => $presensi->isNotEmpty()
                        ? round(($presensi->where('status', 'Hadir')->count() / $presensi->count()) * 100, 1)
                        : null,
                ];
            })
            ->sortBy('nama')
            ->values();
    }

    private function kehadiranRendah(Collection $krs): Collection
    {
        return $krs->map(function (Krs $item) {
            $presensi = $item->presensis;
            if ($presensi->isEmpty()) {
                return null;
            }

            $persentase = round(($presensi->where('status', 'Hadir')->count() / $presensi->count()) * 100, 1);
            if ($persentase >= 75) {
                return null;
            }

            return (object) [
                'mahasiswa_id' => $item->mahasiswa_id,
                'nim' => $item->mahasiswa?->nim ?? '-',
                'nama' => $item->mahasiswa?->nama ?? '-',
                'prodi' => $item->mahasiswa?->prodi?->nama_prodi ?? '-',
                'kelas' => $item->mahasiswa?->kelas?->nama_kelas ?? '-',
                'mata_kuliah' => $item->jadwal?->mataKuliah?->nama_mk ?? '-',
                'hadir' => $presensi->where('status', 'Hadir')->count(),
                'izin' => $presensi->where('status', 'Izin')->count(),
                'sakit' => $presensi->where('status', 'Sakit')->count(),
                'alpha' => $presensi->where('status', 'Alpha')->count(),
                'persentase' => $persentase,
            ];
        })->filter()->sortBy('persentase')->values();
    }

    private function rekapEvaluasi(Collection $krs): Collection
    {
        return $krs->groupBy('jadwal_id')
            ->filter(fn ($items, $jadwalId) => $jadwalId !== null)
            ->map(function (Collection $items) {
                $jadwal = $items->first()->jadwal;

                return (object) [
                    'dosen' => $jadwal?->dosen?->nama ?? '-',
                    'mata_kuliah' => $jadwal?->mataKuliah?->nama_mk ?? '-',
                    'kelas' => $jadwal?->kelas?->nama_kelas ?? '-',
                    'jumlah_responden' => $items->count(),
                    'rata_rata' => round((float) $items->avg('kuesioner.rata_rata'), 2),
                ];
            })->sortByDesc('rata_rata')->values();
    }

    private function rataEvaluasiDosen(Collection $krs): Collection
    {
        return $krs->groupBy(fn (Krs $item) => $item->jadwal?->dosen_id)
            ->filter(fn ($items, $dosenId) => $dosenId !== null)
            ->map(fn (Collection $items) => (object) [
                'dosen' => $items->first()->jadwal?->dosen?->nama ?? '-',
                'jumlah_responden' => $items->count(),
                'rata_rata' => round((float) $items->avg('kuesioner.rata_rata'), 2),
            ])->sortByDesc('rata_rata')->values();
    }

    private function rataEvaluasiMataKuliah(Collection $krs): Collection
    {
        return $krs->groupBy(fn (Krs $item) => $item->jadwal?->mata_kuliah_id)
            ->filter(fn ($items, $mataKuliahId) => $mataKuliahId !== null)
            ->map(fn (Collection $items) => (object) [
                'mata_kuliah' => $items->first()->jadwal?->mataKuliah?->nama_mk ?? '-',
                'jumlah_responden' => $items->count(),
                'rata_rata' => round((float) $items->avg('kuesioner.rata_rata'), 2),
            ])->sortByDesc('rata_rata')->values();
    }

    private function terapkanFilter(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('tahun_akademik'), fn (Builder $q) => $q->where('tahun_akademik', $request->tahun_akademik))
            ->when($request->filled('semester_akademik'), fn (Builder $q) => $q->where('semester_akademik', $request->semester_akademik))
            ->when($request->filled('fakultas_id'), fn (Builder $q) => $q->whereHas('mahasiswa.prodi', fn (Builder $prodi) => $prodi->where('fakultas_id', $request->fakultas_id)))
            ->when($request->filled('prodi_id'), fn (Builder $q) => $q->whereHas('mahasiswa', fn (Builder $mahasiswa) => $mahasiswa->where('prodi_id', $request->prodi_id)))
            ->when($request->filled('kelas_id'), fn (Builder $q) => $q->whereHas('mahasiswa', fn (Builder $mahasiswa) => $mahasiswa->where('kelas_id', $request->kelas_id)))
            ->when($request->filled('mata_kuliah_id'), fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $jadwal) => $jadwal->where('mata_kuliah_id', $request->mata_kuliah_id)))
            ->when($request->filled('dosen_id'), fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $jadwal) => $jadwal->where('dosen_id', $request->dosen_id)));
    }

    private function dataFilter(): array
    {
        return [
            'tahunAkademik' => Krs::whereNotNull('tahun_akademik')->distinct()->orderByDesc('tahun_akademik')->pluck('tahun_akademik'),
            'fakultas' => Fakultas::orderBy('nama_fakultas')->get(),
            'prodis' => Prodi::with('fakultas')->orderBy('nama_prodi')->get(),
            'kelases' => Kelas::with('prodi')->orderBy('nama_kelas')->get(),
            'mataKuliahs' => MataKuliah::orderBy('nama_mk')->get(),
            'dosens' => Dosen::orderBy('nama')->get(),
        ];
    }

    private function validasiFilter(Request $request): void
    {
        $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:255'],
            'semester_akademik' => ['nullable', 'in:Ganjil,Genap'],
            'fakultas_id' => ['nullable', 'integer', 'exists:fakultas,id'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ]);
    }

    private function pastikanAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    private function paginateCollection(Collection $items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ]
        );
    }

    private function deskripsiFilter(Request $request): string
    {
        $bagian = [
            $request->filled('tahun_akademik') ? 'Tahun '.$request->tahun_akademik : null,
            $request->filled('semester_akademik') ? 'Semester '.$request->semester_akademik : null,
            $request->filled('fakultas_id') ? 'Fakultas '.Fakultas::find($request->fakultas_id)?->nama_fakultas : null,
            $request->filled('prodi_id') ? 'Prodi '.Prodi::find($request->prodi_id)?->nama_prodi : null,
            $request->filled('kelas_id') ? 'Kelas '.Kelas::find($request->kelas_id)?->nama_kelas : null,
            $request->filled('mata_kuliah_id') ? 'Mata Kuliah '.MataKuliah::find($request->mata_kuliah_id)?->nama_mk : null,
            $request->filled('dosen_id') ? 'Dosen '.Dosen::find($request->dosen_id)?->nama : null,
        ];

        return collect($bagian)->filter()->implode(' | ') ?: 'Semua data';
    }
}
