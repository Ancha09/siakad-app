<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AvailableKrsScheduleService
{
    private array $lastAudit = [];

    /**
     * Ambil jadwal berdasarkan periode, prodi/kurikulum, dan paritas semester.
     * Kelas dan semester studi mahasiswa sengaja tidak dijadikan syarat.
     */
    public function forStudent(
        Mahasiswa $mahasiswa,
        PeriodeKrs $periodeKrs,
        array|Collection $excludedScheduleIds = [],
        array|Collection $excludedCourseIds = []
    ): Collection {
        $excludedScheduleIds = collect($excludedScheduleIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $excludedCourseIds = collect($excludedCourseIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->merge(Jadwal::query()
                ->whereIn('id', $excludedScheduleIds)
                ->whereNotNull('mata_kuliah_id')
                ->pluck('mata_kuliah_id'))
            ->unique()
            ->values()
            ->all();

        $normalizedYear = $this->normalizeAcademicYear($periodeKrs->tahun_akademik);

        $baseQuery = Jadwal::query()
            ->with([
                'mataKuliah',
                'mataKuliah.kurikulums',
                'dosen',
                'ruangan',
            ])
            ->whereNotNull('mata_kuliah_id');

        $candidateCountBeforeFilter = (clone $baseQuery)->count();
        $yearCandidates = $baseQuery
            ->whereRaw(
                "REPLACE(REPLACE(TRIM(tahun_akademik), ' ', ''), '-', '/') = ?",
                [$normalizedYear]
            )
            ->get()
            ->values();
        $semesterCandidates = $yearCandidates
            ->filter(fn (Jadwal $jadwal) => $this->matchesPeriod($jadwal, $periodeKrs))
            ->values();

        $requiredParity = $this->requiredParity($periodeKrs->semester);
        $parityCandidates = $requiredParity === null
            ? collect()
            : $semesterCandidates
                ->filter(fn (Jadwal $jadwal) => $this->matchesRequiredParity(
                    $jadwal,
                    $mahasiswa,
                    $requiredParity
                ))
                ->values();
        $programCandidates = $parityCandidates
            ->filter(fn (Jadwal $jadwal) => $this->matchesStudyProgram($jadwal, $mahasiswa))
            ->values();
        $candidates = $programCandidates
            ->reject(fn (Jadwal $jadwal) => in_array((int) $jadwal->id, $excludedScheduleIds, true)
                || in_array((int) $jadwal->mata_kuliah_id, $excludedCourseIds, true))
            ->values();

        $this->lastAudit = [
            'candidate_count_before_filter' => $candidateCountBeforeFilter,
            'candidate_count_after_academic_year' => $yearCandidates->count(),
            'candidate_count_after_academic_semester' => $semesterCandidates->count(),
            'candidate_count_after_parity' => $parityCandidates->count(),
            'candidate_count_after_study_program' => $programCandidates->count(),
            'candidate_count_after_taken_exclusion' => $candidates->count(),
            'included_examples' => $candidates->take(5)->map(fn (Jadwal $jadwal) => [
                'jadwal_id' => $jadwal->id,
                'kode_mk' => $jadwal->mataKuliah?->kode_mk,
                'nama_mk' => $jadwal->mataKuliah?->nama_mk,
            ])->values()->all(),
        ];

        if (app()->environment('local')) {
            Log::debug('KRS available schedule audit', [
                'periode_krs_id' => $periodeKrs->id,
                'mahasiswa_id' => $mahasiswa->id,
                'required_parity' => $requiredParity,
            ] + $this->lastAudit);
        }

        return $candidates
            ->unique('id')
            ->sort(function (Jadwal $left, Jadwal $right) {
                $leftKey = $this->sortKey($left);
                $rightKey = $this->sortKey($right);

                return $leftKey <=> $rightKey;
            })
            ->values();
    }

    public function lastAudit(): array
    {
        return $this->lastAudit;
    }

    public function matchesPeriod(object $record, PeriodeKrs $periodeKrs): bool
    {
        return $this->normalizeAcademicYear($record->tahun_akademik ?? null)
                === $this->normalizeAcademicYear($periodeKrs->tahun_akademik)
            && $this->normalizeAcademicSemester($record->semester_akademik ?? null)
                === $this->normalizeAcademicSemester($periodeKrs->semester);
    }

    public function normalizeAcademicYear(?string $year): string
    {
        return str_replace('-', '/', preg_replace('/\s+/', '', trim((string) $year)) ?? '');
    }

    public function normalizeAcademicSemester(string|int|null $semester): ?string
    {
        $value = strtolower(preg_replace('/[\s_-]+/', '', trim((string) $semester)) ?? '');

        return match ($value) {
            'ganjil', 'semesterganjil', 'gasal', 'semestergasal', '1', 'semester1', 'odd' => 'Ganjil',
            'genap', 'semestergenap', '2', 'semester2', 'even' => 'Genap',
            default => null,
        };
    }

    public function requiredParity(string|int|null $semester): ?string
    {
        return match ($this->normalizeAcademicSemester($semester)) {
            'Ganjil' => 'odd',
            'Genap' => 'even',
            default => null,
        };
    }

    public function normalizeCourseSemester(mixed $semester): ?int
    {
        if (is_int($semester)) {
            return $semester > 0 ? $semester : null;
        }

        if (is_float($semester) && floor($semester) === $semester) {
            $number = (int) $semester;

            return $number > 0 ? $number : null;
        }

        if (! is_string($semester) && ! is_numeric($semester)) {
            return null;
        }

        preg_match('/\d+/', trim((string) $semester), $matches);
        $number = isset($matches[0]) ? (int) $matches[0] : 0;

        return $number > 0 ? $number : null;
    }

    public function extractCourseSemesterNumber(mixed $semester): ?int
    {
        return $this->normalizeCourseSemester($semester);
    }

    public function isCourseAllowedForPeriod(mixed $courseSemester, string|int|null $periodSemester): bool
    {
        $requiredParity = $this->requiredParity($periodSemester);

        return $requiredParity !== null
            && $this->semesterMatchesRequiredParity($courseSemester, $requiredParity);
    }

    public function semesterNumberForStudent(
        Jadwal $jadwal,
        Mahasiswa $mahasiswa,
        ?string $requiredParity = null
    ): ?int {
        $curriculumEntries = $this->matchingCurriculumEntries($jadwal, $mahasiswa);
        $courseSemester = $this->extractCourseSemesterNumber($jadwal->mataKuliah?->semester);
        $courseProgramId = $jadwal->mataKuliah?->prodi_id;
        $usesMasterCourseSemester = $courseProgramId === null
            || ($mahasiswa->prodi_id !== null && (int) $courseProgramId === (int) $mahasiswa->prodi_id);

        if ($usesMasterCourseSemester && $courseSemester !== null) {
            if ($requiredParity !== null
                && ! $this->semesterMatchesRequiredParity($courseSemester, $requiredParity)) {
                return null;
            }

            return $courseSemester;
        }

        if ($curriculumEntries->isNotEmpty()) {
            return $curriculumEntries
                ->map(fn ($kurikulum) => $this->extractCourseSemesterNumber($kurikulum->pivot->semester))
                ->filter(fn (?int $semester) => $semester !== null)
                ->when($requiredParity !== null, fn (Collection $semesters) => $semesters
                    ->filter(fn (int $semester) => $this->semesterMatchesRequiredParity(
                        $semester,
                        $requiredParity
                    )))
                ->sort()
                ->first();
        }

        $semester = $courseSemester;

        if ($semester === null) {
            return null;
        }

        if ($requiredParity !== null
            && ! $this->semesterMatchesRequiredParity($semester, $requiredParity)) {
            return null;
        }

        return $semester;
    }

    private function matchesStudyProgram(Jadwal $jadwal, Mahasiswa $mahasiswa): bool
    {
        $course = $jadwal->mataKuliah;

        if (! $course) {
            return false;
        }

        if ($this->matchingCurriculumEntries($jadwal, $mahasiswa)->isNotEmpty()) {
            return true;
        }

        $courseProgramId = $course->prodi_id;
        return $courseProgramId === null
            || ($mahasiswa->prodi_id !== null
                && (int) $courseProgramId === (int) $mahasiswa->prodi_id);
    }

    private function matchesRequiredParity(
        Jadwal $jadwal,
        Mahasiswa $mahasiswa,
        string $requiredParity
    ): bool {
        return $this->semesterNumberForStudent($jadwal, $mahasiswa, $requiredParity) !== null;
    }

    private function matchingCurriculumEntries(Jadwal $jadwal, Mahasiswa $mahasiswa): Collection
    {
        if (! $jadwal->mataKuliah || $mahasiswa->prodi_id === null) {
            return collect();
        }

        return $jadwal->mataKuliah->kurikulums
            ->filter(fn ($kurikulum) => (int) $kurikulum->prodi_id === (int) $mahasiswa->prodi_id)
            ->values();
    }

    private function semesterMatchesRequiredParity(mixed $semester, string $requiredParity): bool
    {
        $number = $this->normalizeCourseSemester($semester);

        if ($number === null) {
            return false;
        }

        return $requiredParity === 'odd'
            ? $number % 2 === 1
            : $number % 2 === 0;
    }

    private function sortKey(Jadwal $jadwal): array
    {
        $dayOrder = array_search($jadwal->hari, [
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu',
        ], true);

        return [
            $dayOrder === false ? 99 : $dayOrder,
            (string) $jadwal->jam_mulai,
            (int) $jadwal->id,
        ];
    }
}
