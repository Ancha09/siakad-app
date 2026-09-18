<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LecturerEvaluationService
{
    public function __construct(private readonly EvaluationLecturerResolver $lecturerResolver) {}

    public const EFFECTIVE_DOSEN_SQL = <<<'SQL'
        CASE
            WHEN kuesioners.dosen_id IS NOT NULL THEN kuesioners.dosen_id
            WHEN khs.is_manual = 1 AND khs.dosen_override = 1 THEN khs.dosen_id
            WHEN krs.dosen_id IS NOT NULL THEN krs.dosen_id
            WHEN jadwals.dosen_id IS NOT NULL
                AND (COALESCE(kuesioners.mata_kuliah_id, krs.mata_kuliah_id) IS NULL OR jadwals.mata_kuliah_id = COALESCE(kuesioners.mata_kuliah_id, krs.mata_kuliah_id))
                AND (COALESCE(kuesioners.kelas_id, krs.kelas_id, mahasiswas.kelas_id) IS NULL OR jadwals.kelas_id = COALESCE(kuesioners.kelas_id, krs.kelas_id, mahasiswas.kelas_id))
                AND (COALESCE(kuesioners.tahun_akademik, krs.tahun_akademik) IS NULL OR jadwals.tahun_akademik = COALESCE(kuesioners.tahun_akademik, krs.tahun_akademik))
                AND (COALESCE(kuesioners.semester_akademik, krs.semester_akademik) IS NULL OR jadwals.semester_akademik = COALESCE(kuesioners.semester_akademik, krs.semester_akademik))
                THEN jadwals.dosen_id
            ELSE (
                SELECT MIN(jadwal_tepat.dosen_id)
                FROM jadwals AS jadwal_tepat
                WHERE jadwal_tepat.mata_kuliah_id = COALESCE(kuesioners.mata_kuliah_id, krs.mata_kuliah_id, jadwals.mata_kuliah_id)
                    AND jadwal_tepat.tahun_akademik = COALESCE(kuesioners.tahun_akademik, krs.tahun_akademik)
                    AND jadwal_tepat.semester_akademik = COALESCE(kuesioners.semester_akademik, krs.semester_akademik)
                    AND (COALESCE(kuesioners.kelas_id, krs.kelas_id, mahasiswas.kelas_id) IS NULL OR jadwal_tepat.kelas_id = COALESCE(kuesioners.kelas_id, krs.kelas_id, mahasiswas.kelas_id))
                    AND jadwal_tepat.dosen_id IS NOT NULL
                HAVING COUNT(DISTINCT jadwal_tepat.dosen_id) = 1
            )
        END
        SQL;

    public const EFFECTIVE_MATA_KULIAH_SQL = 'COALESCE(kuesioners.mata_kuliah_id, krs.mata_kuliah_id, jadwals.mata_kuliah_id)';

    public const EFFECTIVE_KELAS_SQL = 'COALESCE(kuesioners.kelas_id, krs.kelas_id, jadwals.kelas_id, mahasiswas.kelas_id)';

    public const EFFECTIVE_TAHUN_SQL = 'COALESCE(kuesioners.tahun_akademik, krs.tahun_akademik)';

    public const EFFECTIVE_SEMESTER_SQL = 'COALESCE(kuesioners.semester_akademik, krs.semester_akademik)';

    public function rows(array $filters = []): QueryBuilder
    {
        return DB::table('kuesioners')
            ->join('krs', 'kuesioners.krs_id', '=', 'krs.id')
            ->leftJoin('khs', 'khs.krs_id', '=', 'krs.id')
            ->leftJoin('jadwals', 'krs.jadwal_id', '=', 'jadwals.id')
            ->leftJoin('mahasiswas', 'krs.mahasiswa_id', '=', 'mahasiswas.id')
            ->whereNotNull(DB::raw(self::EFFECTIVE_DOSEN_SQL))
            ->when($filters['tahun_akademik'] ?? null, fn (QueryBuilder $query, $tahun) => $query->whereRaw(self::EFFECTIVE_TAHUN_SQL.' = ?', [$tahun]))
            ->when($filters['semester_akademik'] ?? null, fn (QueryBuilder $query, $semester) => $query->whereRaw(self::EFFECTIVE_SEMESTER_SQL.' = ?', [$semester]))
            ->when($filters['mata_kuliah_id'] ?? null, fn (QueryBuilder $query, $mataKuliahId) => $query->whereRaw(self::EFFECTIVE_MATA_KULIAH_SQL.' = ?', [(int) $mataKuliahId]))
            ->when($filters['kelas_id'] ?? null, fn (QueryBuilder $query, $kelasId) => $query->whereRaw(self::EFFECTIVE_KELAS_SQL.' = ?', [(int) $kelasId]));
    }

    public function krsIdsForLecturer(Dosen $dosen, array $filters = []): Collection
    {
        return $this->rows($filters)
            ->whereRaw(self::EFFECTIVE_DOSEN_SQL.' = ?', [$dosen->id])
            ->pluck('kuesioners.krs_id')
            ->unique()
            ->values();
    }

    public function withDefaultPeriod(array $filters, ?Dosen $dosen = null): array
    {
        if (filled($filters['tahun_akademik'] ?? null)
            && filled($filters['semester_akademik'] ?? null)) {
            return $filters;
        }

        $periodQuery = $this->rows($filters);
        if ($dosen) {
            $periodQuery->whereRaw(self::EFFECTIVE_DOSEN_SQL.' = ?', [$dosen->id]);
        }

        $period = $periodQuery
            ->selectRaw(self::EFFECTIVE_TAHUN_SQL.' AS periode_tahun')
            ->selectRaw(self::EFFECTIVE_SEMESTER_SQL.' AS periode_semester')
            ->whereNotNull(DB::raw(self::EFFECTIVE_TAHUN_SQL))
            ->whereNotNull(DB::raw(self::EFFECTIVE_SEMESTER_SQL))
            ->orderByRaw(self::EFFECTIVE_TAHUN_SQL.' DESC')
            ->orderByRaw('CASE '.self::EFFECTIVE_SEMESTER_SQL." WHEN 'Genap' THEN 2 WHEN 'Ganjil' THEN 1 ELSE 0 END DESC")
            ->first();

        if ($period) {
            if (! filled($filters['tahun_akademik'] ?? null)) {
                $filters['tahun_akademik'] = $period->periode_tahun;
            }
            if (! filled($filters['semester_akademik'] ?? null)) {
                $filters['semester_akademik'] = $period->periode_semester;
            }
        }

        return $filters;
    }

    /** @return EloquentCollection<int, Kuesioner> */
    public function answers(Collection $krsIds): EloquentCollection
    {
        if ($krsIds->isEmpty()) {
            return new EloquentCollection;
        }

        return Kuesioner::query()
            ->with($this->answerRelations())
            ->whereIn('krs_id', $krsIds)
            ->get();
    }

    public function report(Dosen $dosen, array $filters = []): array
    {
        $krsIds = $this->krsIdsForLecturer($dosen, $filters);
        $answers = $this->answers($krsIds);

        return [
            'jumlahResponden' => $answers->count(),
            'rataRata' => $answers->isEmpty()
                ? null
                : round((float) $answers->avg(fn (Kuesioner $item) => $item->rata_rata), 2),
            'rataPertanyaan' => collect(Kuesioner::PERTANYAAN)
                ->mapWithKeys(fn (string $label, string $column) => [
                    $column => $answers->isEmpty() ? null : round((float) $answers->avg($column), 2),
                ]),
            'pertanyaan' => Kuesioner::PERTANYAAN,
            'mataKuliahs' => $this->relatedCourses($answers),
            'kelases' => $this->relatedClasses($answers),
            'periode' => $this->relatedPeriods($answers),
            'krsIds' => $krsIds,
        ];
    }

    public function lecturerId(Kuesioner $evaluation): ?int
    {
        if ($evaluation->dosen_id) {
            return (int) $evaluation->dosen_id;
        }

        if (! $evaluation->krs) {
            return null;
        }

        // Gunakan instance evaluasi yang sedang dihitung supaya resolver membaca
        // snapshot mata kuliah/kelas/periode yang sama dengan query rekap SQL.
        $evaluation->krs->setRelation('kuesioner', $evaluation);

        return $this->lecturerResolver->resolveId($evaluation->krs);
    }

    public function comments(Collection $krsIds): EloquentBuilder
    {
        return Kuesioner::query()
            ->with($this->answerRelations())
            ->whereIn('krs_id', $krsIds)
            ->whereNotNull('komentar')
            ->where('komentar', '<>', '')
            ->latest('submitted_at');
    }

    public function filterOptions(Dosen $dosen): array
    {
        $answers = $this->answers($this->krsIdsForLecturer($dosen));

        return [
            'filterMataKuliahs' => $this->relatedCourses($answers),
            'filterKelases' => $this->relatedClasses($answers),
            'filterTahunAkademik' => $answers
                ->map(fn (Kuesioner $item) => $item->tahun_akademik ?? $item->krs?->tahun_akademik)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),
        ];
    }

    public function relatedCourses(EloquentCollection $answers): Collection
    {
        return $answers
            ->map(fn (Kuesioner $item) => $item->mataKuliah ?? $item->krs?->mata_kuliah_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_mk')
            ->values();
    }

    public function relatedClasses(EloquentCollection $answers): Collection
    {
        return $answers
            ->map(fn (Kuesioner $item) => $item->kelas ?? $item->krs?->kelas_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();
    }

    public function relatedPeriods(EloquentCollection $answers): Collection
    {
        return $answers
            ->map(function (Kuesioner $item) {
                $tahun = $item->tahun_akademik ?? $item->krs?->tahun_akademik;
                $semester = $item->semester_akademik ?? $item->krs?->semester_akademik;

                return $tahun ? trim($tahun.($semester ? " - {$semester}" : '')) : null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function answerRelations(): array
    {
        return [
            'dosen',
            'mataKuliah',
            'kelas',
            'krs.kuesioner:id,krs_id,dosen_id',
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.jadwal.kelas',
            'krs.mataKuliahManual',
            'krs.dosenManual',
            'krs.kelasManual',
            'krs.khs.dosenManual',
            'krs.mahasiswa:id,kelas_id',
            'krs.mahasiswa.kelas',
        ];
    }
}
