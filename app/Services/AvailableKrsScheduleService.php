<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AvailableKrsScheduleService
{
    /**
     * Ambil jadwal berdasarkan periode, prodi/kurikulum, dan paritas semester.
     * Kelas dan semester studi mahasiswa sengaja tidak dijadikan syarat.
     */
    public function forStudent(
        Mahasiswa $mahasiswa,
        PeriodeKrs $periodeKrs,
        array|Collection $excludedScheduleIds = []
    ): Collection {
        $excludedScheduleIds = collect($excludedScheduleIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $normalizedYear = $this->normalizeAcademicYear($periodeKrs->tahun_akademik);

        $query = Jadwal::query()
            ->with([
                'mataKuliah',
                'mataKuliah.kurikulums',
                'dosen',
                'ruangan',
            ])
            ->whereNotNull('mata_kuliah_id')
            // Mengakomodasi data lama seperti 2026-2027 atau 2026 / 2027.
            ->whereRaw(
                "REPLACE(REPLACE(TRIM(tahun_akademik), ' ', ''), '-', '/') = ?",
                [$normalizedYear]
            );

        if ($excludedScheduleIds !== []) {
            $query->whereNotIn('id', $excludedScheduleIds);
        }

        $candidatesBeforeParity = $query->get()
            ->filter(fn (Jadwal $jadwal) => $this->matchesPeriod($jadwal, $periodeKrs))
            ->filter(fn (Jadwal $jadwal) => $this->matchesStudyProgram($jadwal, $mahasiswa))
            ->values();

        $requiredParity = $this->requiredParity($periodeKrs->semester);
        $candidates = $requiredParity === null
            ? collect()
            : $candidatesBeforeParity
                ->filter(fn (Jadwal $jadwal) => $this->matchesRequiredParity(
                    $jadwal,
                    $mahasiswa,
                    $requiredParity
                ))
                ->values();

        if (app()->environment('local')) {
            Log::debug('KRS schedule parity filter', [
                'periode_krs_id' => $periodeKrs->id,
                'mahasiswa_id' => $mahasiswa->id,
                'required_parity' => $requiredParity,
                'candidate_count_before_parity' => $candidatesBeforeParity->count(),
                'candidate_count_after_parity' => $candidates->count(),
            ]);
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
            'ganjil', 'semesterganjil', '1', 'semester1', 'odd' => 'Ganjil',
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

    public function semesterNumberForStudent(
        Jadwal $jadwal,
        Mahasiswa $mahasiswa,
        ?string $requiredParity = null
    ): ?int {
        $curriculumEntries = $this->matchingCurriculumEntries($jadwal, $mahasiswa);

        if ($curriculumEntries->isNotEmpty()) {
            return $curriculumEntries
                ->map(fn ($kurikulum) => $this->normalizeCourseSemester($kurikulum->pivot->semester))
                ->filter(fn (?int $semester) => $semester !== null)
                ->when($requiredParity !== null, fn (Collection $semesters) => $semesters
                    ->filter(fn (int $semester) => $this->semesterMatchesRequiredParity(
                        $semester,
                        $requiredParity
                    )))
                ->sort()
                ->first();
        }

        $semester = $this->normalizeCourseSemester($jadwal->mataKuliah?->semester);

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
