<?php

namespace App\Services;

use App\Models\Khs;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentCplAssessmentService
{
    private const ASPECT_LABELS = [
        'CPL 1' => 'Kemampuan Rekayasa, Sains, dan Matematika',
        'CPL 2' => 'Solusi Berkelanjutan dan Lingkungan',
        'CPL 3' => 'Komunikasi dan Laporan Teknis',
        'CPL 4' => 'Etika Profesi',
        'CPL 5' => 'Kerja Sama dan Jejaring',
        'CPL 6' => 'Penelitian dan Analisis Data',
        'CPL 7' => 'Pengembangan Diri dan Teknologi',
        'CPL 8' => 'Kewirausahaan dan Industri',
    ];

    public function __construct(private readonly IpkCplReportService $reports) {}

    public function supports(?Prodi $program): bool
    {
        return $program !== null
            && str_contains(Str::lower($program->nama_prodi), 'pertambangan');
    }

    public function profile(Mahasiswa $student, array $filters = [], bool $respectStudentVisibility = false): array
    {
        return $this->profiles(collect([$student]), $filters, $respectStudentVisibility)
            ->get($student->id, $this->emptyProfile($student));
    }

    /** @param Collection<int, Mahasiswa> $students */
    public function profiles(Collection $students, array $filters = [], bool $respectStudentVisibility = false): Collection
    {
        $students->each(fn (Mahasiswa $student) => $student->loadMissing('prodi'));
        $result = collect();

        foreach ($students->groupBy('prodi_id') as $programStudents) {
            /** @var Mahasiswa $first */
            $first = $programStudents->first();
            $program = $first?->prodi;

            if (! $this->supports($program)) {
                $programStudents->each(fn (Mahasiswa $student) => $result->put(
                    $student->id,
                    $this->emptyProfile($student, false)
                ));

                continue;
            }

            $mappingGroups = $this->reports->activeMappingsForStudentAssessment($program);
            $grades = $this->gradesForStudents(
                $programStudents->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $filters,
                $respectStudentVisibility
            )->groupBy(fn (Khs $grade) => (int) $grade->krs->mahasiswa_id);

            $programStudents->each(function (Mahasiswa $student) use ($mappingGroups, $grades, $result) {
                $studentGrades = $grades->get($student->id, collect())
                    ->keyBy(fn (Khs $grade) => (int) $grade->krs->mata_kuliah_efektif->id);

                $aspects = $mappingGroups->map(function (object $group) use ($studentGrades) {
                    $courses = $group->mappings
                        ->map(function ($mapping) use ($studentGrades) {
                            $grade = $studentGrades->get((int) $mapping->mata_kuliah_id);
                            if (! $grade || $grade->bobot === null) {
                                return null;
                            }

                            $sks = $grade->sks_efektif > 0
                                ? $grade->sks_efektif
                                : (int) ($mapping->sks ?? $mapping->mataKuliah?->sks ?? 0);
                            if ($sks <= 0) {
                                return null;
                            }

                            return (object) [
                                'mata_kuliah_id' => $mapping->mata_kuliah_id,
                                'kode_mata_kuliah' => $mapping->mataKuliah?->kode_mk ?? $mapping->kode_sumber,
                                'nama_mata_kuliah' => $mapping->mataKuliah?->nama_mk ?? $mapping->nama_sumber,
                                'semester' => $mapping->semester ?? $mapping->mataKuliah?->semester,
                                'sks' => $sks,
                                'bobot' => round((float) $grade->bobot, 2),
                                'mutu_sks' => round((float) $grade->bobot * $sks, 2),
                                'tahun_akademik' => $grade->tahun_akademik ?: $grade->krs?->tahun_akademik,
                            ];
                        })
                        ->filter()
                        ->unique('mata_kuliah_id')
                        ->values();
                    $totalSks = (int) $courses->sum('sks');
                    $score = $totalSks > 0 ? round((float) $courses->sum('mutu_sks') / $totalSks, 2) : null;

                    return (object) [
                        'kode_cpl' => $group->cpl->kode_cpl,
                        'label' => self::ASPECT_LABELS[$group->cpl->kode_cpl] ?? $group->cpl->nama_cpl,
                        'score' => $score,
                        'category' => $this->category($score),
                        'total_sks' => $totalSks,
                        'courses' => $courses,
                    ];
                })->values();
                $scored = $aspects->whereNotNull('score');

                $result->put($student->id, [
                    'student' => $student,
                    'supported' => true,
                    'aspects' => $aspects,
                    'strongest' => $scored->sortByDesc('score')->first(),
                    'weakest' => $scored->sortBy('score')->first(),
                    'scored_aspects' => $scored->count(),
                ]);
            });
        }

        return $result;
    }

    public function academicYearsForStudents(Collection $students): Collection
    {
        $ids = $students->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return collect();
        }

        return DB::table('khs')
            ->join('krs', 'krs.id', '=', 'khs.krs_id')
            ->whereIn('krs.mahasiswa_id', $ids)
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

    public function category(?float $score): string
    {
        return match (true) {
            $score === null => 'Belum Ada Data',
            $score >= 3.50 => 'Sangat Baik',
            $score >= 3.00 => 'Baik',
            $score >= 2.50 => 'Cukup',
            default => 'Perlu Ditingkatkan',
        };
    }

    private function gradesForStudents(array $studentIds, array $filters, bool $respectStudentVisibility): Collection
    {
        return Khs::query()
            ->with([
                'krs.jadwal.mataKuliah',
                'krs.mataKuliahManual',
                'krs.kuesioner',
            ])
            ->whereNotNull('nilai_angka')
            ->whereNotNull('nilai_huruf')
            ->whereNotNull('bobot')
            ->whereHas('krs', fn ($query) => $query
                ->whereIn('mahasiswa_id', $studentIds)
                ->where('status', 'Disetujui'))
            ->get()
            ->filter(fn (Khs $grade) => $grade->krs?->mata_kuliah_efektif !== null
                && $grade->sks_efektif > 0)
            ->filter(fn (Khs $grade) => blank($filters['tahun_akademik'] ?? null)
                || $this->normalizeAcademicYear($grade->tahun_akademik ?: $grade->krs?->tahun_akademik)
                    === $this->normalizeAcademicYear($filters['tahun_akademik']))
            ->filter(fn (Khs $grade) => ! $respectStudentVisibility || $grade->krs?->kuesioner !== null)
            ->sortByDesc(fn (Khs $grade) => sprintf(
                '%s|%010d',
                $grade->updated_at?->format('YmdHis.u') ?? '',
                $grade->id
            ))
            ->unique(fn (Khs $grade) => $grade->krs->mahasiswa_id.'|'.$grade->krs->mata_kuliah_efektif->id)
            ->values();
    }

    private function normalizeAcademicYear(mixed $year): string
    {
        return str_replace('-', '/', preg_replace('/\s+/', '', trim((string) $year)) ?? '');
    }

    private function emptyProfile(Mahasiswa $student, bool $supported = true): array
    {
        return [
            'student' => $student,
            'supported' => $supported,
            'aspects' => collect(),
            'strongest' => null,
            'weakest' => null,
            'scored_aspects' => 0,
        ];
    }
}
