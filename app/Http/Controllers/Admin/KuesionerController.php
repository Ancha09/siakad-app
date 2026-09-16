<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\MataKuliah;
use App\Services\LegacyListNavigation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KuesionerController extends Controller
{
    private const EFFECTIVE_DOSEN_SQL = 'CASE WHEN krs.is_manual = 1 THEN krs.dosen_id ELSE COALESCE(jadwals.dosen_id, krs.dosen_id) END';

    private const EFFECTIVE_MATA_KULIAH_SQL = 'COALESCE(jadwals.mata_kuliah_id, krs.mata_kuliah_id)';

    public function index(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $filters = $this->validatedFilters($request);
        $dosenQuery = Dosen::query()
            ->with('prodi')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nidn', 'like', "%{$search}%");
                });
            })
            ->when($filters['dosen_id'] ?? null, fn ($query, $dosenId) => $query->whereKey($dosenId));

        // Statistik dan status dihitung hanya untuk dosen yang cocok dengan filter nama/dosen.
        // Filter akademik diterapkan pada jawaban sehingga dosen tanpa jawaban tetap bisa tampil.
        $dosenIds = (clone $dosenQuery)->pluck('id');
        $evaluationRows = $this->evaluationRows($filters)
            ->when(
                $dosenIds->isNotEmpty(),
                fn (QueryBuilder $query) => $query->whereIn(DB::raw(self::EFFECTIVE_DOSEN_SQL), $dosenIds),
                fn (QueryBuilder $query) => $query->whereRaw('1 = 0')
            );

        $evaluatedDosenIds = (clone $evaluationRows)
            ->selectRaw(self::EFFECTIVE_DOSEN_SQL.' AS effective_dosen_id')
            ->distinct()
            ->pluck('effective_dosen_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $totalDosen = $dosenIds->count();
        $totalDinilai = $evaluatedDosenIds->count();
        $totalBelumDinilai = max(0, $totalDosen - $totalDinilai);
        $totalResponden = (clone $evaluationRows)->count('kuesioners.id');

        if (($filters['status_evaluasi'] ?? null) === 'sudah') {
            $dosenQuery->whereIn('id', $evaluatedDosenIds);
        } elseif (($filters['status_evaluasi'] ?? null) === 'belum') {
            $dosenQuery->whereNotIn('id', $evaluatedDosenIds);
        }

        $evaluasiDosen = $dosenQuery
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        $pageDosenIds = $evaluasiDosen->getCollection()->pluck('id');
        $pageKrsIds = $pageDosenIds->isEmpty()
            ? collect()
            : (clone $evaluationRows)
                ->whereIn(DB::raw(self::EFFECTIVE_DOSEN_SQL), $pageDosenIds)
                ->pluck('kuesioners.krs_id');

        $jawabanPerDosen = $this->loadAnswers($pageKrsIds)
            ->groupBy(fn (Kuesioner $item) => $item->krs?->dosen_efektif?->id);

        $evaluasiDosen->setCollection(
            $evaluasiDosen->getCollection()->map(function (Dosen $dosen) use ($jawabanPerDosen) {
                /** @var EloquentCollection<int, Kuesioner> $jawaban */
                $jawaban = $jawabanPerDosen->get($dosen->id, new EloquentCollection);

                return (object) [
                    'dosen' => $dosen,
                    'jumlah_responden' => $jawaban->count(),
                    'rata_rata' => $jawaban->isEmpty() ? null : round((float) $jawaban->avg(fn (Kuesioner $item) => $item->rata_rata), 2),
                    'mata_kuliahs' => $this->relatedCourses($jawaban),
                    'periode' => $this->relatedPeriods($jawaban),
                ];
            })
        );

        return view('admin.kuesioner.index', [
            'evaluasiDosen' => $evaluasiDosen,
            'totalDosen' => $totalDosen,
            'totalDinilai' => $totalDinilai,
            'totalBelumDinilai' => $totalBelumDinilai,
            'totalResponden' => $totalResponden,
            'dosens' => Dosen::orderBy('nama')->get(['id', 'nama', 'nidn']),
            'mataKuliahs' => MataKuliah::orderBy('nama_mk')->get(['id', 'kode_mk', 'nama_mk']),
            'tahunAkademik' => Krs::whereNotNull('tahun_akademik')
                ->distinct()
                ->orderByDesc('tahun_akademik')
                ->pluck('tahun_akademik'),
        ]);
    }

    public function show(Request $request, Dosen $dosen, LegacyListNavigation $navigation)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $filters = $this->validatedFilters($request);
        $krsIds = $this->evaluationRows($filters)
            ->whereRaw(self::EFFECTIVE_DOSEN_SQL.' = ?', [$dosen->id])
            ->pluck('kuesioners.krs_id');

        $jawaban = $this->loadAnswers($krsIds);
        $rataPertanyaan = collect(Kuesioner::PERTANYAAN)
            ->mapWithKeys(fn (string $label, string $kolom) => [
                $kolom => $jawaban->isEmpty() ? null : round((float) $jawaban->avg($kolom), 2),
            ]);

        $komentar = Kuesioner::query()
            ->with($this->answerRelations())
            ->whereIn('krs_id', $krsIds)
            ->whereNotNull('komentar')
            ->where('komentar', '<>', '')
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.kuesioner.show', [
            'dosen' => $dosen->loadMissing('prodi'),
            'jumlahResponden' => $jawaban->count(),
            'rataRata' => $jawaban->isEmpty() ? null : round((float) $jawaban->avg(fn (Kuesioner $item) => $item->rata_rata), 2),
            'rataPertanyaan' => $rataPertanyaan,
            'pertanyaan' => Kuesioner::PERTANYAAN,
            'mataKuliahs' => $this->relatedCourses($jawaban),
            'kelases' => $this->relatedClasses($jawaban),
            'periode' => $this->relatedPeriods($jawaban),
            'komentar' => $komentar,
            'returnUrl' => $navigation->returnUrl($request, 'admin.kuesioner'),
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester_akademik' => ['nullable', 'in:Ganjil,Genap'],
            'status_evaluasi' => ['nullable', 'in:sudah,belum'],
        ]);
    }

    private function evaluationRows(array $filters): QueryBuilder
    {
        return DB::table('kuesioners')
            ->join('krs', 'kuesioners.krs_id', '=', 'krs.id')
            ->leftJoin('jadwals', 'krs.jadwal_id', '=', 'jadwals.id')
            ->whereNotNull(DB::raw(self::EFFECTIVE_DOSEN_SQL))
            ->when($filters['tahun_akademik'] ?? null, fn (QueryBuilder $query, $tahun) => $query->where('krs.tahun_akademik', $tahun))
            ->when($filters['semester_akademik'] ?? null, fn (QueryBuilder $query, $semester) => $query->where('krs.semester_akademik', $semester))
            ->when($filters['mata_kuliah_id'] ?? null, fn (QueryBuilder $query, $mataKuliahId) => $query->whereRaw(self::EFFECTIVE_MATA_KULIAH_SQL.' = ?', [(int) $mataKuliahId]));
    }

    /** @return EloquentCollection<int, Kuesioner> */
    private function loadAnswers(Collection $krsIds): EloquentCollection
    {
        if ($krsIds->isEmpty()) {
            return new EloquentCollection;
        }

        return Kuesioner::query()
            ->with($this->answerRelations())
            ->whereIn('krs_id', $krsIds)
            ->get();
    }

    private function answerRelations(): array
    {
        return [
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.jadwal.kelas',
            'krs.mataKuliahManual',
            'krs.dosenManual',
            'krs.kelasManual',
        ];
    }

    private function relatedCourses(EloquentCollection $jawaban): Collection
    {
        return $jawaban
            ->map(fn (Kuesioner $item) => $item->krs?->mata_kuliah_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_mk')
            ->values();
    }

    private function relatedClasses(EloquentCollection $jawaban): Collection
    {
        return $jawaban
            ->map(fn (Kuesioner $item) => $item->krs?->kelas_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();
    }

    private function relatedPeriods(EloquentCollection $jawaban): Collection
    {
        return $jawaban
            ->map(function (Kuesioner $item) {
                $tahun = $item->krs?->tahun_akademik;
                $semester = $item->krs?->semester_akademik;

                return $tahun ? trim($tahun.($semester ? " - {$semester}" : '')) : null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
