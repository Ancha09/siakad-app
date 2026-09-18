<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use Illuminate\Support\Collection;

class AvailableKrsScheduleService
{
    /**
     * Ambil jadwal berdasarkan periode, prodi, dan semester studi mahasiswa.
     * Kelas lama pada mahasiswa maupun jadwal sengaja tidak dijadikan syarat.
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

        $candidates = $query->get()
            ->filter(fn (Jadwal $jadwal) => $this->matchesPeriod($jadwal, $periodeKrs))
            ->filter(fn (Jadwal $jadwal) => $this->matchesStudyProgramAndSemester($jadwal, $mahasiswa))
            ->values();

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
        $value = strtolower(preg_replace('/\s+/', '', trim((string) $semester)) ?? '');

        return match ($value) {
            'ganjil', 'semesterganjil', '1', 'semester1' => 'Ganjil',
            'genap', 'semestergenap', '2', 'semester2' => 'Genap',
            default => null,
        };
    }

    private function matchesStudyProgramAndSemester(Jadwal $jadwal, Mahasiswa $mahasiswa): bool
    {
        $course = $jadwal->mataKuliah;

        if (! $course) {
            return false;
        }

        $curriculumEntries = $course->kurikulums
            ->filter(fn ($kurikulum) => (int) $kurikulum->prodi_id === (int) $mahasiswa->prodi_id);

        if ($curriculumEntries->isNotEmpty()) {
            return $mahasiswa->semester === null
                || $curriculumEntries->contains(
                    fn ($kurikulum) => (int) $kurikulum->pivot->semester === (int) $mahasiswa->semester
                );
        }

        $courseProgramId = $course->prodi_id;
        $programMatches = $courseProgramId === null
            || ($mahasiswa->prodi_id !== null
                && (int) $courseProgramId === (int) $mahasiswa->prodi_id);

        $courseSemester = $course->semester;
        $semesterMatches = $courseSemester === null
            || $mahasiswa->semester === null
            || (int) $courseSemester === (int) $mahasiswa->semester;

        return $programMatches && $semesterMatches;
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
