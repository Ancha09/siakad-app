<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class KuesionerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $jawabanQuery = Kuesioner::with([
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.jadwal.kelas',
        ]);
        $this->filterKuesioner($jawabanQuery, $request);

        if ($request->status_pengisian === 'belum') {
            $jawabanQuery->whereRaw('1 = 0');
        }

        $semuaJawaban = (clone $jawabanQuery)->get();
        $rekapDosen = $semuaJawaban
            ->groupBy(fn (Kuesioner $item) => $item->krs->jadwal_id)
            ->map(function ($items) {
                $pertama = $items->first();
                $rataPertanyaan = collect(array_keys(Kuesioner::PERTANYAAN))
                    ->mapWithKeys(fn (string $kolom) => [$kolom => round($items->avg($kolom), 2)]);

                return (object) [
                    'jadwal' => $pertama->krs->jadwal,
                    'jumlah_responden' => $items->count(),
                    'rata_rata' => round($items->avg('rata_rata'), 2),
                    'rata_pertanyaan' => $rataPertanyaan,
                ];
            })
            ->sortBy(fn ($item) => $item->jadwal->dosen->nama ?? '');

        $jawaban = (clone $jawabanQuery)
            ->latest('submitted_at')
            ->paginate(10, ['*'], 'jawaban_page')
            ->withQueryString();

        $statusQuery = Krs::with([
            'mahasiswa.prodi',
            'mahasiswa.kelas',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'kuesioner',
        ])
            ->where('status', 'Disetujui')
            ->whereHas('khs');
        $this->filterKrs($statusQuery, $request);

        // Ringkasan selalu menunjukkan populasi pada filter akademik,
        // sedangkan filter status hanya membatasi tabel di bawahnya.
        $totalWajib = (clone $statusQuery)->count();
        $totalSudah = (clone $statusQuery)->whereHas('kuesioner')->count();
        $totalBelum = (clone $statusQuery)->whereDoesntHave('kuesioner')->count();

        if ($request->status_pengisian === 'sudah') {
            $statusQuery->whereHas('kuesioner');
        } elseif ($request->status_pengisian === 'belum') {
            $statusQuery->whereDoesntHave('kuesioner');
        }

        $statusMahasiswa = $statusQuery
            ->latest()
            ->paginate(10, ['*'], 'status_page')
            ->withQueryString();

        return view('admin.kuesioner.index', [
            'jawaban' => $jawaban,
            'rekapDosen' => $rekapDosen,
            'statusMahasiswa' => $statusMahasiswa,
            'totalWajib' => $totalWajib,
            'totalSudah' => $totalSudah,
            'totalBelum' => $totalBelum,
            'pertanyaan' => Kuesioner::PERTANYAAN,
            'dosens' => Dosen::orderBy('nama')->get(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahs' => MataKuliah::orderBy('nama_mk')->get(),
            'tahunAkademik' => Krs::whereNotNull('tahun_akademik')->distinct()->orderByDesc('tahun_akademik')->pluck('tahun_akademik'),
        ]);
    }

    private function filterKuesioner(Builder $query, Request $request): void
    {
        $query->whereHas('krs', function (Builder $krs) use ($request) {
            $this->filterKrs($krs, $request);
        });
    }

    private function filterKrs(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('tahun_akademik'), fn (Builder $q) => $q->where('tahun_akademik', $request->tahun_akademik))
            ->when($request->filled('semester_akademik'), fn (Builder $q) => $q->where('semester_akademik', $request->semester_akademik))
            ->when($request->filled('prodi_id'), fn (Builder $q) => $q->whereHas('mahasiswa', fn (Builder $m) => $m->where('prodi_id', $request->prodi_id)))
            ->when($request->filled('kelas_id'), fn (Builder $q) => $q->whereHas('mahasiswa', fn (Builder $m) => $m->where('kelas_id', $request->kelas_id)))
            ->when($request->filled('dosen_id'), fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $j) => $j->where('dosen_id', $request->dosen_id)))
            ->when($request->filled('mata_kuliah_id'), fn (Builder $q) => $q->whereHas('jadwal', fn (Builder $j) => $j->where('mata_kuliah_id', $request->mata_kuliah_id)));
    }
}
