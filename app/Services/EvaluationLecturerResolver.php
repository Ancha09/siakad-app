<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Krs;

class EvaluationLecturerResolver
{
    public function resolveId(Krs $krs): ?int
    {
        $krs->loadMissing([
            'kuesioner',
            'khs.dosenManual',
            'dosenManual',
            'jadwal',
            'mahasiswa:id,kelas_id',
        ]);

        if ($krs->kuesioner?->dosen_id) {
            return (int) $krs->kuesioner->dosen_id;
        }

        // Nilai manual merupakan override eksplisit. Nilai null juga harus dihormati
        // dan tidak boleh diam-diam kembali ke dosen jadwal lama.
        if ($krs->khs?->is_manual && $krs->khs?->dosen_override) {
            return $krs->khs->dosen_id ? (int) $krs->khs->dosen_id : null;
        }

        if ($krs->dosen_id) {
            return (int) $krs->dosen_id;
        }

        $courseId = $krs->kuesioner?->mata_kuliah_id
            ?? $krs->mata_kuliah_id
            ?? $krs->jadwal?->mata_kuliah_id;
        $classId = $krs->kuesioner?->kelas_id
            ?? $krs->kelas_id
            ?? $krs->mahasiswa?->kelas_id;
        $academicYear = $krs->kuesioner?->tahun_akademik ?? $krs->tahun_akademik;
        $academicSemester = $krs->kuesioner?->semester_akademik ?? $krs->semester_akademik;

        if ($this->scheduleMatchesAcademicContext(
            $krs->jadwal,
            $courseId,
            $classId,
            $academicYear,
            $academicSemester
        )) {
            return $krs->jadwal?->dosen_id ? (int) $krs->jadwal->dosen_id : null;
        }

        if (! $courseId || ! $academicYear || ! $academicSemester) {
            return null;
        }

        $lecturerIds = Jadwal::query()
            ->where('mata_kuliah_id', $courseId)
            ->where('tahun_akademik', $academicYear)
            ->where('semester_akademik', $academicSemester)
            ->when($classId, fn ($query) => $query->where('kelas_id', $classId))
            ->whereNotNull('dosen_id')
            ->distinct()
            ->pluck('dosen_id');

        // Jangan memilih "jadwal pertama" jika data periode tersebut ambigu.
        return $lecturerIds->count() === 1 ? (int) $lecturerIds->first() : null;
    }

    private function scheduleMatchesAcademicContext(
        ?Jadwal $jadwal,
        ?int $courseId,
        ?int $classId,
        ?string $academicYear,
        ?string $academicSemester
    ): bool {
        if (! $jadwal?->dosen_id) {
            return false;
        }

        return (! $courseId || (int) $jadwal->mata_kuliah_id === (int) $courseId)
            && (! $classId || (int) $jadwal->kelas_id === (int) $classId)
            && (! $academicYear || $jadwal->tahun_akademik === $academicYear)
            && (! $academicSemester || $jadwal->semester_akademik === $academicSemester);
    }
}
