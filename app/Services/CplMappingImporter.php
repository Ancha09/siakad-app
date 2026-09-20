<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Support\MiningCplCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class CplMappingImporter
{
    public function import(string $path, string $programName = 'Teknik Pertambangan'): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("File mapping CPL tidak ditemukan: {$path}");
        }

        $program = $this->findProgram($programName);
        if ($program === null) {
            throw new RuntimeException("Program Studi {$programName} tidak ditemukan.");
        }

        $courses = MataKuliah::query()->with('prodi')->get();
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
        $result = [
            'program_studi_id' => $program->id,
            'program_studi' => $program->nama_prodi,
            'cpls' => 0,
            'mappings' => 0,
            'matched' => 0,
            'unmatched' => [],
        ];

        DB::transaction(function () use ($rows, $program, $courses, &$result) {
            $currentCpl = null;
            $seenCpls = [];

            foreach ($rows as $row) {
                $firstCell = trim((string) ($row['A'] ?? ''));

                if (preg_match('/^CPL\s*(\d+)\s*[—–-]\s*(.+)$/iu', $firstCell, $matches)) {
                    $number = (int) $matches[1];
                    $code = 'CPL '.$number;
                    $metadata = MiningCplCatalog::forCode($code) ?? [
                        'deskripsi' => null,
                        'turunan_visi_misi' => null,
                        'cpl_kkni' => null,
                    ];
                    $currentCpl = Cpl::updateOrCreate(
                        ['program_studi_id' => $program->id, 'kode_cpl' => $code],
                        [
                            'nama_cpl' => trim($matches[2]),
                            ...$metadata,
                            'sort_order' => $number,
                        ]
                    );
                    $seenCpls[$currentCpl->id] = true;

                    continue;
                }

                if ($currentCpl === null || ! $this->isCourseRow($row)) {
                    continue;
                }

                $sourceCode = trim((string) $row['A']);
                $sourceName = $this->cleanSourceName((string) $row['B']);
                $course = $this->matchCourse($courses, $sourceCode, $sourceName, $program);
                $existing = CplMataKuliah::query()
                    ->where('cpl_id', $currentCpl->id)
                    ->where('kode_sumber', $sourceCode)
                    ->first();
                $existingCourse = $existing?->mata_kuliah_id
                    ? $courses->firstWhere('id', $existing->mata_kuliah_id)
                    : null;
                $resolvedCourse = $course
                    ?? ($existingCourse && $this->isCourseEligible($existingCourse, $program, $sourceCode)
                        ? $existingCourse
                        : null);

                if ($resolvedCourse !== null && CplMataKuliah::query()
                    ->where('cpl_id', $currentCpl->id)
                    ->where('mata_kuliah_id', $resolvedCourse->id)
                    ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                    ->exists()) {
                    $resolvedCourse = null;
                }

                CplMataKuliah::updateOrCreate(
                    ['cpl_id' => $currentCpl->id, 'kode_sumber' => $sourceCode],
                    [
                        'mata_kuliah_id' => $resolvedCourse?->id,
                        'nama_sumber' => $sourceName,
                        'semester' => (int) $row['C'],
                        'sks' => (int) $row['D'],
                    ]
                );

                $result['mappings']++;
                if ($resolvedCourse !== null) {
                    $result['matched']++;
                } else {
                    $result['unmatched'][] = [
                        'kode' => $sourceCode,
                        'nama' => $sourceName,
                        'cpl' => $currentCpl->kode_cpl,
                    ];
                }
            }

            $result['cpls'] = count($seenCpls);
        });

        $result['unmatched'] = collect($result['unmatched'])->unique(fn (array $item) => $item['kode'].'|'.$item['nama'])->values()->all();

        return $result;
    }

    private function findProgram(string $name): ?Prodi
    {
        $normalized = $this->normalizeName($name);

        return Prodi::query()->get()->first(
            fn (Prodi $program) => $this->normalizeName($program->nama_prodi) === $normalized
                || str_contains($this->normalizeName($program->nama_prodi), 'pertambangan')
        );
    }

    private function isCourseRow(array $row): bool
    {
        return filled($row['A'] ?? null)
            && filled($row['B'] ?? null)
            && is_numeric($row['C'] ?? null)
            && is_numeric($row['D'] ?? null);
    }

    public function matchCourse(Collection $courses, string $code, string $name, Prodi $program): ?MataKuliah
    {
        $eligible = $this->eligibleCourses($courses, $program, $code);
        $normalizedCode = $this->normalizeBaseCode($code);
        $byCode = $eligible->first(fn (MataKuliah $course) => $this->normalizeBaseCode($course->kode_mk) === $normalizedCode);
        if ($byCode !== null) {
            return $byCode;
        }

        $normalizedName = $this->normalizeName($name);
        $sameProgram = $eligible->first(fn (MataKuliah $course) => (int) $course->prodi_id === (int) $program->id
            && $this->normalizeName($course->nama_mk) === $normalizedName);

        return $sameProgram ?? $eligible->first(
            fn (MataKuliah $course) => $course->prodi_id === null
                && $this->normalizeName($course->nama_mk) === $normalizedName
        );
    }

    public function eligibleCourses(Collection $courses, Prodi $program, ?string $sourceCode = null): Collection
    {
        return $courses
            ->filter(fn (MataKuliah $course) => $this->isCourseEligible($course, $program, $sourceCode))
            ->sortBy(fn (MataKuliah $course) => sprintf(
                '%d|%s',
                (int) $course->prodi_id === (int) $program->id ? 0 : 1,
                $course->kode_mk
            ), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function isCourseEligible(MataKuliah $course, Prodi $program, ?string $sourceCode = null): bool
    {
        $programAlias = $this->programAlias($program);
        $sourceAlias = $this->codeSuffix($sourceCode);
        $courseAlias = $this->codeSuffix($course->kode_mk);

        if ($sourceAlias !== null && $sourceAlias !== $programAlias) {
            return false;
        }

        if ($courseAlias !== null && $courseAlias !== $programAlias) {
            return false;
        }

        return (int) $course->prodi_id === (int) $program->id || $course->prodi_id === null;
    }

    public function suggestCourses(Collection $courses, string $code, string $name, Prodi $program, int $limit = 3): Collection
    {
        $sourceCode = $this->normalizeBaseCode($code);
        $sourceName = $this->normalizeName($name);

        return $this->eligibleCourses($courses, $program, $code)
            ->map(function (MataKuliah $course) use ($sourceCode, $sourceName) {
                $candidateCode = $this->normalizeBaseCode($course->kode_mk);
                $candidateName = $this->normalizeName($course->nama_mk);
                $codeDistance = levenshtein($sourceCode, $candidateCode);
                similar_text($sourceName, $candidateName, $nameSimilarity);
                $samePrefix = substr($sourceCode, 0, 2) === substr($candidateCode, 0, 2);
                $score = 0;
                $reason = null;

                if ($sourceCode !== '' && $sourceCode === $candidateCode) {
                    $score = 100;
                    $reason = 'Kode sama setelah normalisasi';
                } elseif ($samePrefix && $codeDistance <= 2) {
                    $score = 85 - ($codeDistance * 5);
                    $reason = 'Kode mirip';
                } elseif ($sourceName !== '' && $nameSimilarity >= 60) {
                    $score = (int) round($nameSimilarity);
                    $reason = 'Nama mirip '.number_format($nameSimilarity, 0).'%';
                }

                return $reason === null ? null : (object) compact('course', 'score', 'reason');
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function sourceKey(string $code, string $name): string
    {
        return $this->normalizeBaseCode($code).'|'.$this->normalizeName($name);
    }

    private function cleanSourceName(string $name): string
    {
        return trim((string) preg_replace('/\s*\[[^\]]+\]\s*/u', ' ', $name));
    }

    private function normalizeBaseCode(mixed $value): string
    {
        $value = preg_replace('/\s*\((TP|TG)\)\s*$/iu', '', (string) $value);

        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', Str::ascii((string) $value)));
    }

    private function codeSuffix(mixed $value): ?string
    {
        return preg_match('/\(\s*(TP|TG)\s*\)\s*$/iu', (string) $value, $matches)
            ? strtoupper($matches[1])
            : null;
    }

    private function programAlias(Prodi $program): string
    {
        $name = $this->normalizeName($program->nama_prodi);

        return match (true) {
            str_contains($name, 'pertambangan') => 'TP',
            str_contains($name, 'geologi') => 'TG',
            default => strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', $program->kode_prodi)),
        };
    }

    private function normalizeName(mixed $value): string
    {
        $value = str_replace('&', ' dan ', Str::lower(Str::ascii($this->cleanSourceName((string) $value))));

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }
}
