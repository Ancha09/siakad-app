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
    public const EFFECTIVE_DOSEN_SQL = 'CASE WHEN krs.is_manual = 1 THEN krs.dosen_id ELSE COALESCE(jadwals.dosen_id, krs.dosen_id) END';

    public const EFFECTIVE_MATA_KULIAH_SQL = 'COALESCE(jadwals.mata_kuliah_id, krs.mata_kuliah_id)';

    public const EFFECTIVE_KELAS_SQL = 'COALESCE(jadwals.kelas_id, krs.kelas_id, mahasiswas.kelas_id)';

    public function rows(array $filters = []): QueryBuilder
    {
        return DB::table('kuesioners')
            ->join('krs', 'kuesioners.krs_id', '=', 'krs.id')
            ->leftJoin('jadwals', 'krs.jadwal_id', '=', 'jadwals.id')
            ->leftJoin('mahasiswas', 'krs.mahasiswa_id', '=', 'mahasiswas.id')
            ->whereNotNull(DB::raw(self::EFFECTIVE_DOSEN_SQL))
            ->when($filters['tahun_akademik'] ?? null, fn (QueryBuilder $query, $tahun) => $query->where('krs.tahun_akademik', $tahun))
            ->when($filters['semester_akademik'] ?? null, fn (QueryBuilder $query, $semester) => $query->where('krs.semester_akademik', $semester))
            ->when($filters['mata_kuliah_id'] ?? null, fn (QueryBuilder $query, $mataKuliahId) => $query->whereRaw(self::EFFECTIVE_MATA_KULIAH_SQL.' = ?', [(int) $mataKuliahId]))
            ->when($filters['kelas_id'] ?? null, fn (QueryBuilder $query, $kelasId) => $query->whereRaw(self::EFFECTIVE_KELAS_SQL.' = ?', [(int) $kelasId]));
    }

    public function krsIdsForLecturer(Dosen $dosen, array $filters = []): Collection
    {
        return $this->rows($filters)
            ->whereRaw(self::EFFECTIVE_DOSEN_SQL.' = ?', [$dosen->id])
            ->pluck('kuesioners.krs_id');
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
                ->map(fn (Kuesioner $item) => $item->krs?->tahun_akademik)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),
        ];
    }

    public function relatedCourses(EloquentCollection $answers): Collection
    {
        return $answers
            ->map(fn (Kuesioner $item) => $item->krs?->mata_kuliah_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_mk')
            ->values();
    }

    public function relatedClasses(EloquentCollection $answers): Collection
    {
        return $answers
            ->map(fn (Kuesioner $item) => $item->krs?->kelas_efektif)
            ->filter()
            ->unique('id')
            ->sortBy('nama_kelas')
            ->values();
    }

    public function relatedPeriods(EloquentCollection $answers): Collection
    {
        return $answers
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

    private function answerRelations(): array
    {
        return [
            'krs.jadwal.mataKuliah',
            'krs.jadwal.dosen',
            'krs.jadwal.kelas',
            'krs.mataKuliahManual',
            'krs.dosenManual',
            'krs.kelasManual',
            'krs.mahasiswa:id,kelas_id',
            'krs.mahasiswa.kelas',
        ];
    }
}
