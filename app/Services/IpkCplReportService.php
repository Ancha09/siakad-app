<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\Dosen;
use App\Models\Khs;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Support\MiningCplCatalog;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IpkCplReportService
{
    private const PASSING_SCORE = 60;

    public function __construct(private readonly CplMappingImporter $mappingMatcher, private readonly CplManualOverrides $overrides) {}

    public function cplOverview(Prodi $program, array $filters = [], bool $includeCourseDetails = true): array
    {
        $gradeStats = filled($filters['tahun_akademik'] ?? null)
            ? $this->courseGradeStatistics($program, $filters)
            : collect();

        $allCpls = Cpl::query()
            ->with(['mappings.mataKuliah'])
            ->where('program_studi_id', $program->id)
            ->whereIn('kode_cpl', array_keys(MiningCplCatalog::all()))
            ->orderBy('sort_order')
            ->limit(9)
            ->get();

        $allRows = $allCpls->map(function (Cpl $cpl) use ($gradeStats, $filters, $includeCourseDetails, $program) {
            $cpl->mappings->each(fn (CplMataKuliah $mapping) => $mapping->setRelation('cpl', $cpl));
            $mappings = $this->filteredMappings($cpl->mappings, $filters);
            $courses = $mappings->map(
                fn (CplMataKuliah $mapping) => $this->mappingResult($mapping, $gradeStats, $program)
            )->values();
            $withGrades = $courses->filter(fn (object $course) => $course->rata_bobot !== null && $course->sks > 0);
            $calculatedSks = $withGrades->sum('sks');
            $totalSks = $courses->sum('sks');
            $ipkCpl = $calculatedSks > 0
                ? round($withGrades->sum('mutu_sks') / $calculatedSks, 2)
                : null;

            return (object) [
                'cpl' => $cpl,
                'kode_cpl' => $cpl->kode_cpl,
                'nama_cpl' => $cpl->nama_cpl,
                'jumlah_mata_kuliah' => $courses->count(),
                'total_sks' => $totalSks,
                'sks_dihitung' => $calculatedSks,
                'mata_kuliah_bernilai' => $withGrades->count(),
                'ipk_cpl' => $ipkCpl,
                'courses' => $includeCourseDetails ? $courses : collect(),
            ];
        })->values();
        $rows = $allRows
            ->when($filters['cpl_id'] ?? null, fn (Collection $items, $cplId) => $items
                ->where('cpl.id', (int) $cplId))
            ->values();

        $allMappings = $allCpls->flatMap(function (Cpl $cpl) {
            return $cpl->mappings->each(fn (CplMataKuliah $mapping) => $mapping->setRelation('cpl', $cpl));
        })->values();
        $unmatchedMappings = $allMappings
            ->reject(fn (CplMataKuliah $mapping) => $mapping->mataKuliah !== null
                && $this->overrides->accepted($mapping, $program))
            ->sortBy('kode_sumber', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
        $chartRows = $allRows->take(9)->values();
        $manualNotes = $this->overrides->notes($allMappings, $program);

        return [
            'program' => $program,
            'rows' => $rows,
            'chart' => [
                'labels' => $chartRows->pluck('kode_cpl')->all(),
                'ipk' => $chartRows->pluck('ipk_cpl')->all(),
            ],
            'mappingSummary' => [
                'jumlah_cpl' => $allCpls->count(),
                'jumlah_mapping' => $allMappings->count(),
                'manual_override' => $manualNotes->count(),
                'aman' => $allMappings->count() - $unmatchedMappings->count() - $manualNotes->count(),
                'bermasalah' => $unmatchedMappings->count(),
                'cocok_master' => $allMappings->count() - $unmatchedMappings->count(),
                'belum_cocok_master' => $unmatchedMappings
                    ->unique(fn (CplMataKuliah $mapping) => $this->mappingMatcher->sourceKey(
                        $mapping->kode_sumber,
                        $mapping->nama_sumber
                    ))
                    ->count(),
            ],
            'manualOverrides' => $manualNotes,
            'unmatchedMappings' => $unmatchedMappings,
            'summary' => [
                'jumlah_cpl' => $rows->count(),
                'jumlah_mapping' => $rows->sum('jumlah_mata_kuliah'),
                'cpl_bernilai' => $rows->whereNotNull('ipk_cpl')->count(),
            ],
        ];
    }

    public function cplDetail(Prodi $program, Cpl $cpl, array $filters = []): array
    {
        abort_unless((int) $cpl->program_studi_id === (int) $program->id, 404);
        $filters['cpl_id'] = $cpl->id;
        $overview = $this->cplOverview($program, $filters);
        $row = $overview['rows']->first();
        abort_if($row === null, 404, 'Mapping CPL tidak ditemukan untuk filter tersebut.');

        return $overview + ['row' => $row];
    }

    public function mappedCourseDetail(
        Prodi $program,
        Cpl $cpl,
        CplMataKuliah $mapping,
        array $filters = []
    ): array {
        abort_unless((int) $cpl->program_studi_id === (int) $program->id
            && (int) $mapping->cpl_id === (int) $cpl->id, 404);
        abort_if($this->filteredMappings(collect([$mapping]), $filters)->isEmpty(), 404);

        $mapping->loadMissing('mataKuliah');
        abort_if($mapping->mataKuliah === null || ! $this->overrides->accepted($mapping, $program), 404, 'Mapping mata kuliah belum cocok dengan master Teknik Pertambangan.');
        $grades = null;
        if ($mapping->mata_kuliah_id !== null) {
            $gradeIds = $this->latestGradeRowsQuery($program, $filters)
                ->where('course_id', $mapping->mata_kuliah_id)
                ->select('khs_id');
            $grades = Khs::query()
                ->with(['krs.mahasiswa.prodi', 'krs.mataKuliahManual', 'krs.jadwal.mataKuliah'])
                ->whereIn('khs.id', $gradeIds)
                ->join('krs', 'krs.id', '=', 'khs.krs_id')
                ->join('mahasiswas', 'mahasiswas.id', '=', 'krs.mahasiswa_id')
                ->select('khs.*')
                ->orderBy('mahasiswas.nama')
                ->orderBy('khs.id')
                ->paginate(50)
                ->withQueryString();
        }

        $grades ??= Khs::query()->whereRaw('1 = 0')->paginate(50)->withQueryString();

        return [
            'program' => $program,
            'cpl' => $cpl,
            'mapping' => $mapping,
            'grades' => $grades,
        ];
    }

    public function cplFilterOptions(Prodi $program): array
    {
        $cpls = Cpl::query()
            ->with('mappings.mataKuliah')
            ->where('program_studi_id', $program->id)
            ->orderBy('sort_order')
            ->get();

        return [
            'tahunAkademiks' => $this->academicYears($program),
            'angkatans' => Mahasiswa::query()
                ->where('prodi_id', $program->id)
                ->whereNotNull('angkatan')
                ->distinct()
                ->orderByDesc('angkatan')
                ->pluck('angkatan'),
            'cplOptions' => $cpls,
            'courseOptions' => $cpls->flatMap(fn (Cpl $cpl) => $cpl->mappings->each(fn (CplMataKuliah $mapping) => $mapping->setRelation('cpl', $cpl)))
                ->filter(fn (CplMataKuliah $mapping) => $mapping->mataKuliah !== null
                    && $this->overrides->accepted($mapping, $program))
                ->unique('mata_kuliah_id')
                ->sortBy('kode_sumber')
                ->values(),
        ];
    }

    public function latestAcademicYear(Prodi $program): ?string
    {
        return $this->academicYears($program)->first();
    }

    private function academicYears(Prodi $program): Collection
    {
        return DB::table('khs')
            ->join('krs', 'krs.id', '=', 'khs.krs_id')
            ->join('mahasiswas', 'mahasiswas.id', '=', 'krs.mahasiswa_id')
            ->where('mahasiswas.prodi_id', $program->id)
            ->where('krs.status', 'Disetujui')
            ->selectRaw("COALESCE(NULLIF(khs.tahun_akademik, ''), krs.tahun_akademik) AS academic_year")
            ->distinct()
            ->pluck('academic_year')
            ->map(fn ($year) => $this->normalizeAcademicYear($year))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Statistik ringkas dihitung oleh database. Halaman utama tidak pernah
     * mengambil seluruh record KHS atau relasi mahasiswa ke memory PHP.
     */
    private function courseGradeStatistics(Prodi $program, array $filters): Collection
    {
        return $this->latestGradeRowsQuery($program, $filters)
            ->whereNotNull('bobot')
            ->selectRaw('course_id, AVG(bobot) AS average_weight, COUNT(DISTINCT mahasiswa_id) AS student_count')
            ->groupBy('course_id')
            ->get()
            ->keyBy(fn (object $row) => (int) $row->course_id);
    }

    /**
     * Menghasilkan satu nilai final terbaru per mahasiswa dan mata kuliah.
     * Filter tahun/prodi diterapkan sebelum ROW_NUMBER agar database hanya
     * merangking subset yang dibutuhkan oleh laporan aktif.
     */
    private function latestGradeRowsQuery(Prodi $program, array $filters): QueryBuilder
    {
        $courseExpression = 'COALESCE(jadwals.mata_kuliah_id, krs.mata_kuliah_id)';
        $academicYearExpression = "REPLACE(REPLACE(TRIM(COALESCE(NULLIF(khs.tahun_akademik, ''), krs.tahun_akademik)), '-', '/'), ' ', '')";
        $academicSemesterExpression = "COALESCE(NULLIF(TRIM(khs.semester_akademik), ''), NULLIF(TRIM(krs.semester_akademik), ''))";

        $rankedGrades = DB::table('khs')
            ->join('krs', 'krs.id', '=', 'khs.krs_id')
            ->join('mahasiswas', 'mahasiswas.id', '=', 'krs.mahasiswa_id')
            ->leftJoin('jadwals', 'jadwals.id', '=', 'krs.jadwal_id')
            ->where('krs.status', 'Disetujui')
            ->where('mahasiswas.prodi_id', $program->id)
            ->whereRaw($courseExpression.' IS NOT NULL')
            ->whereRaw($academicSemesterExpression.' IS NOT NULL')
            ->where(function ($query) {
                $query->whereNotNull('khs.nilai_angka')
                    ->orWhereNotNull('khs.nilai_huruf')
                    ->orWhereNotNull('khs.bobot');
            })
            ->when(filled($filters['tahun_akademik'] ?? null), function ($query) use ($filters, $academicYearExpression) {
                $query->whereRaw($academicYearExpression.' = ?', [
                    $this->normalizeAcademicYear($filters['tahun_akademik']),
                ]);
            })
            ->when(filled($filters['angkatan'] ?? null), fn ($query) => $query
                ->where('mahasiswas.angkatan', (int) $filters['angkatan']))
            ->selectRaw('khs.id AS khs_id')
            ->selectRaw('krs.mahasiswa_id AS mahasiswa_id')
            ->selectRaw($courseExpression.' AS course_id')
            ->selectRaw('khs.bobot AS bobot')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY krs.mahasiswa_id, '.$courseExpression.' ORDER BY khs.updated_at DESC, khs.id DESC) AS grade_rank');

        return DB::query()
            ->fromSub($rankedGrades, 'ranked_grades')
            ->where('grade_rank', 1);
    }

    private function programGrades(Prodi $program, array $filters): Collection
    {
        return $this->latestFinalGrades()
            ->filter(fn (Khs $grade) => $this->hasAnyGradeValue($grade)
                && (int) $grade->krs?->mahasiswa?->prodi_id === (int) $program->id)
            ->filter(fn (Khs $grade) => empty($filters['tahun_akademik'])
                || $this->normalizeAcademicYear($this->academicYear($grade)) === $this->normalizeAcademicYear($filters['tahun_akademik']))
            ->filter(fn (Khs $grade) => empty($filters['angkatan'])
                || (int) $grade->krs?->mahasiswa?->angkatan === (int) $filters['angkatan'])
            ->unique(fn (Khs $grade) => implode('|', [
                $grade->krs?->mahasiswa_id,
                $grade->krs?->mata_kuliah_efektif?->id,
            ]))
            ->values();
    }

    private function filteredMappings(Collection $mappings, array $filters): Collection
    {
        [$minimumSemester, $maximumSemester] = $this->studyYearRange($filters['tahun_studi'] ?? null);

        return $mappings
            ->filter(fn (CplMataKuliah $mapping) => $minimumSemester === null
                || ($mapping->semester !== null
                    && $mapping->semester >= $minimumSemester
                    && $mapping->semester <= $maximumSemester))
            ->filter(fn (CplMataKuliah $mapping) => empty($filters['mata_kuliah_id'])
                || (int) $mapping->mata_kuliah_id === (int) $filters['mata_kuliah_id'])
            ->values();
    }

    private function mappingResult(CplMataKuliah $mapping, Collection $gradeStats, Prodi $program): object
    {
        $isLinked = $mapping->mataKuliah !== null && $this->overrides->accepted($mapping, $program);
        $stats = $isLinked ? $gradeStats->get($mapping->mata_kuliah_id) : null;
        $averageWeight = $stats === null ? null : round((float) $stats->average_weight, 2);
        $sks = (int) ($mapping->sks ?? $mapping->mataKuliah?->sks ?? 0);

        return (object) [
            'mapping' => $mapping,
            'mata_kuliah_id' => $mapping->mata_kuliah_id,
            'kode_mata_kuliah' => $mapping->kode_sumber,
            'nama_mata_kuliah' => $mapping->nama_sumber,
            'semester' => $mapping->semester,
            'sks' => $sks,
            'jumlah_mahasiswa' => (int) ($stats->student_count ?? 0),
            'rata_bobot' => $averageWeight,
            'mutu_sks' => $averageWeight === null ? null : round($averageWeight * $sks, 2),
            'terhubung' => $isLinked,
        ];
    }

    private function studyYearRange(mixed $studyYear): array
    {
        if (! in_array((int) $studyYear, [1, 2, 3, 4], true)) {
            return [null, null];
        }

        $minimum = (((int) $studyYear - 1) * 2) + 1;

        return [$minimum, $minimum + 1];
    }

    public function report(array $filters = []): array
    {
        $filteredGrades = $this->finalGrades($filters);

        $rows = $filteredGrades
            ->groupBy(fn (Khs $grade) => $this->groupKey($grade))
            ->map(fn (Collection $grades) => $this->aggregateGroup($grades))
            ->sortBy(fn (object $row) => implode('|', [
                $row->tahun_akademik,
                $row->semester_akademik,
                $row->prodi,
                str_pad((string) ($row->semester_angka ?? 0), 2, '0', STR_PAD_LEFT),
                $row->kode_mata_kuliah,
            ]), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $numericScores = $filteredGrades
            ->pluck('nilai_angka')
            ->filter(fn ($score) => is_numeric($score));

        return [
            'rows' => $rows,
            'summary' => [
                'total_mata_kuliah' => $rows->pluck('mata_kuliah_id')->filter()->unique()->count(),
                'total_data_nilai' => $filteredGrades->count(),
                'rata_rata_keseluruhan' => $numericScores->isNotEmpty()
                    ? round((float) $numericScores->avg(), 2)
                    : null,
            ],
        ];
    }

    /**
     * Nilai final terbaru. Collection ini selalu dibaca langsung dari KHS/KRS
     * agar perubahan pada Input Nilai Lama segera terlihat pada seluruh laporan.
     */
    public function finalGrades(array $filters = []): Collection
    {
        return $this->applyFilters($this->latestFinalGrades(), $filters)
            ->filter(fn (Khs $grade) => $this->hasAnyGradeValue($grade))
            ->values();
    }

    public function courseDetail(MataKuliah $course, array $filters): array
    {
        $filters['mata_kuliah_id'] = $course->id;
        $grades = $this->finalGrades($filters)
            ->sortBy(fn (Khs $grade) => strtolower((string) $grade->krs?->mahasiswa?->nama))
            ->values();

        return [
            'course' => $course,
            'grades' => $grades,
            'summary' => $grades->isEmpty() ? null : $this->aggregateGroup($grades),
        ];
    }

    public function filterOptions(): array
    {
        $years = Khs::query()
            ->whereNotNull('tahun_akademik')
            ->pluck('tahun_akademik')
            ->merge(Khs::query()
                ->whereHas('krs', fn ($query) => $query->whereNotNull('tahun_akademik'))
                ->with('krs:id,tahun_akademik')
                ->get()
                ->pluck('krs.tahun_akademik'))
            ->map(fn ($year) => $this->normalizeAcademicYear($year))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return [
            'tahunAkademiks' => $years,
            'prodis' => Prodi::orderBy('nama_prodi')->get(['id', 'nama_prodi', 'jenjang']),
            'angkatans' => Mahasiswa::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
            'semesterAngkas' => $this->latestFinalGrades()
                ->map(fn (Khs $grade) => $this->courseSemester($grade))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'mataKuliahs' => MataKuliah::orderBy('kode_mk')->get(['id', 'kode_mk', 'nama_mk']),
            'dosens' => Dosen::orderBy('nama')->get(['id', 'nidn', 'nama']),
        ];
    }

    public function filterDescription(array $filters): string
    {
        $parts = [];

        if (! empty($filters['tahun_akademik'])) {
            $parts[] = 'Tahun '.$filters['tahun_akademik'];
        }
        if (! empty($filters['semester_akademik'])) {
            $parts[] = 'Semester '.$filters['semester_akademik'];
        }
        if (! empty($filters['prodi_id'])) {
            $parts[] = 'Prodi '.(Prodi::find($filters['prodi_id'])?->nama_prodi ?? '-');
        }
        if (! empty($filters['angkatan'])) {
            $parts[] = 'Angkatan '.$filters['angkatan'];
        }
        if (! empty($filters['semester_angka'])) {
            $parts[] = 'Semester Angka '.$filters['semester_angka'];
        }
        if (! empty($filters['mata_kuliah_id'])) {
            $course = MataKuliah::find($filters['mata_kuliah_id']);
            $parts[] = 'Mata Kuliah '.($course ? $course->kode_mk.' - '.$course->nama_mk : '-');
        }
        if (! empty($filters['dosen_id'])) {
            $parts[] = 'Dosen '.(Dosen::find($filters['dosen_id'])?->nama ?? '-');
        }

        return $parts === [] ? 'Semua data nilai final' : implode(' | ', $parts);
    }

    private function latestFinalGrades(): Collection
    {
        return Khs::query()
            ->with([
                'dosenManual',
                'krs.mahasiswa.prodi',
                'krs.mahasiswa.kelas',
                'krs.mataKuliahManual.prodi',
                'krs.dosenManual',
                'krs.prodiManual',
                'krs.jadwal.mataKuliah.prodi',
                'krs.jadwal.dosen',
            ])
            ->whereHas('krs', fn ($query) => $query->where('status', 'Disetujui'))
            ->get()
            ->filter(fn (Khs $grade) => $grade->krs?->mahasiswa_id !== null
                && $grade->krs?->mata_kuliah_efektif?->id !== null
                && filled($this->academicYear($grade))
                && filled($this->academicSemester($grade)))
            ->sortByDesc(fn (Khs $grade) => sprintf(
                '%s|%020d',
                $grade->updated_at?->format('Y-m-d H:i:s.u') ?? '0000-00-00 00:00:00.000000',
                $grade->id
            ))
            ->unique(fn (Khs $grade) => implode('|', [
                $grade->krs->mahasiswa_id,
                $grade->krs->mata_kuliah_efektif->id,
                $this->normalizeAcademicYear($this->academicYear($grade)),
                strtolower($this->academicSemester($grade)),
            ]))
            ->values();
    }

    private function applyFilters(Collection $grades, array $filters): Collection
    {
        return $grades->filter(function (Khs $grade) use ($filters) {
            $krs = $grade->krs;
            $course = $krs?->mata_kuliah_efektif;
            $lecturer = $grade->dosen_efektif;
            $program = $krs?->prodi_efektif;
            $cohort = $krs?->angkatan ?? $krs?->mahasiswa?->angkatan;

            return (empty($filters['tahun_akademik'])
                    || $this->normalizeAcademicYear($this->academicYear($grade)) === $this->normalizeAcademicYear($filters['tahun_akademik']))
                && (empty($filters['semester_akademik'])
                    || strcasecmp($this->academicSemester($grade), $filters['semester_akademik']) === 0)
                && (empty($filters['prodi_id']) || (int) $program?->id === (int) $filters['prodi_id'])
                && (empty($filters['angkatan']) || (int) $cohort === (int) $filters['angkatan'])
                && (empty($filters['semester_angka']) || $this->courseSemester($grade) === (int) $filters['semester_angka'])
                && (empty($filters['mata_kuliah_id']) || (int) $course?->id === (int) $filters['mata_kuliah_id'])
                && (empty($filters['dosen_id']) || (int) $lecturer?->id === (int) $filters['dosen_id']);
        });
    }

    private function aggregateGroup(Collection $grades): object
    {
        $first = $grades->first();
        $course = $first->krs->mata_kuliah_efektif;
        $program = $first->krs->prodi_efektif;
        $numericScores = $grades->pluck('nilai_angka')->filter(fn ($value) => is_numeric($value));
        $weights = $grades->pluck('bobot')->filter(fn ($value) => is_numeric($value));
        $letters = $grades->pluck('nilai_huruf')->filter(fn ($value) => filled($value));
        $distribution = collect(['A', 'B', 'C', 'D', 'E'])
            ->mapWithKeys(fn (string $letter) => [
                $letter => $letters->filter(fn ($value) => str_starts_with(strtoupper(trim((string) $value)), $letter))->count(),
            ]);
        $studentCount = $grades->pluck('krs.mahasiswa_id')->filter()->unique()->count();
        $lecturers = $grades
            ->map(fn (Khs $grade) => $grade->dosen_efektif?->nama)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $passingPercentage = $numericScores->isNotEmpty()
            ? round(($numericScores->filter(fn ($score) => (float) $score >= self::PASSING_SCORE)->count() / $numericScores->count()) * 100, 2)
            : null;

        return (object) [
            'tahun_akademik' => $this->normalizeAcademicYear($this->academicYear($first)),
            'semester_akademik' => $this->academicSemester($first),
            'prodi_id' => $program?->id,
            'prodi' => $program?->nama_prodi ?? 'Tidak diketahui',
            'semester_angka' => $this->courseSemester($first),
            'mata_kuliah_id' => $course->id,
            'kode_mata_kuliah' => $course->kode_mk,
            'nama_mata_kuliah' => $course->nama_mk,
            'dosen' => $lecturers->isNotEmpty() ? $lecturers->implode(', ') : '-',
            'jumlah_mahasiswa' => $studentCount,
            'rata_nilai' => $numericScores->isNotEmpty() ? round((float) $numericScores->avg(), 2) : null,
            'rata_bobot' => $weights->isNotEmpty() ? round((float) $weights->avg(), 2) : null,
            'nilai_a' => $distribution['A'],
            'nilai_b' => $distribution['B'],
            'nilai_c' => $distribution['C'],
            'nilai_d' => $distribution['D'],
            'nilai_e' => $distribution['E'],
            'persentase_lulus' => $passingPercentage,
            'keterangan' => $numericScores->count() === $studentCount
                && $weights->count() === $studentCount
                && $letters->count() === $studentCount
                    ? 'Data nilai lengkap'
                    : 'Data nilai belum lengkap',
        ];
    }

    private function groupKey(Khs $grade): string
    {
        return implode('|', [
            $this->normalizeAcademicYear($this->academicYear($grade)),
            strtolower($this->academicSemester($grade)),
            $grade->krs?->prodi_efektif?->id ?? 0,
            $this->courseSemester($grade) ?? 0,
            $grade->krs?->mata_kuliah_efektif?->id ?? 0,
        ]);
    }

    private function courseSemester(Khs $grade): ?int
    {
        foreach ([$grade->krs?->mata_kuliah_efektif?->semester, $grade->krs?->semester] as $value) {
            if (preg_match('/\d+/', (string) $value, $match)) {
                return (int) $match[0];
            }
        }

        return null;
    }

    private function hasAnyGradeValue(Khs $grade): bool
    {
        return is_numeric($grade->nilai_angka)
            || is_numeric($grade->bobot)
            || filled($grade->nilai_huruf);
    }

    private function academicYear(Khs $grade): string
    {
        return (string) ($grade->tahun_akademik ?: $grade->krs?->tahun_akademik);
    }

    private function academicSemester(Khs $grade): string
    {
        return (string) ($grade->semester_akademik ?: $grade->krs?->semester_akademik);
    }

    private function normalizeAcademicYear(mixed $year): string
    {
        return str_replace('-', '/', preg_replace('/\s+/', '', trim((string) $year)) ?? '');
    }
}
