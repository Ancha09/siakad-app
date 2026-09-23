<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Support\MiningCplCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $overrides = app(CplManualOverrides::class);
        $courses = MataKuliah::query()->with('prodi')->get();
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, true);
        $result = [
            'program_studi_id' => $program->id,
            'program_studi' => $program->nama_prodi,
            'cpls' => 0,
            'mappings' => 0,
            'matched' => 0,
            'manual_overrides' => 0,
            'unmatched' => [],
        ];

        DB::transaction(function () use ($rows, $program, $courses, $overrides, &$result) {
            Prodi::query()->whereKey($program->id)->lockForUpdate()->firstOrFail();
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
                $decision = $overrides->decision($sourceCode, $sourceName, $currentCpl->kode_cpl, $program);
                $problem = null;
                if ($decision !== null) {
                    try {
                        $course = $overrides->resolve($decision, $program, (int) $row['D'], (int) $row['C'], true);
                    } catch (RuntimeException $exception) {
                        $course = null;
                        $problem = $exception->getMessage();
                    }
                } else {
                    $course = $this->matchCourse($courses, $sourceCode, $sourceName, $program);
                }
                $existing = CplMataKuliah::query()
                    ->where('cpl_id', $currentCpl->id)
                    ->where('kode_sumber', $sourceCode)
                    ->first()
                    ?? CplMataKuliah::query()
                        ->where('cpl_id', $currentCpl->id)
                        ->get()
                        ->first(fn (CplMataKuliah $mapping) => $this->sourceKey($mapping->kode_sumber, $mapping->nama_sumber)
                            === $this->sourceKey($sourceCode, $sourceName));
                $existingCourse = $existing?->mata_kuliah_id
                    ? $courses->firstWhere('id', $existing->mata_kuliah_id)
                    : null;
                $resolvedCourse = $course
                    ?? ($decision === null && $existingCourse && $this->isSafeMatch($existingCourse, $sourceCode, $sourceName, $program)
                        ? $existingCourse
                        : null);

                if ($resolvedCourse !== null && CplMataKuliah::query()
                    ->where('cpl_id', $currentCpl->id)
                    ->where('mata_kuliah_id', $resolvedCourse->id)
                    ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                    ->exists()) {
                    $resolvedCourse = null;
                }

                $mappingValues = [
                    'kode_sumber' => $sourceCode,
                    'mata_kuliah_id' => $resolvedCourse?->id,
                    'nama_sumber' => $sourceName,
                    'semester' => (int) $row['C'],
                    'sks' => (int) $row['D'],
                ];
                if ($existing !== null) {
                    $existing->fill($mappingValues)->save();
                } else {
                    $existing = CplMataKuliah::create(['cpl_id' => $currentCpl->id] + $mappingValues);
                }

                if ($decision !== null && $resolvedCourse !== null
                    && ($existing->wasRecentlyCreated || $existing->wasChanged('mata_kuliah_id'))) {
                    $log = ['mapping_id' => $existing->id, 'course_id' => $resolvedCourse->id,
                        'decision' => $decision, 'updated_at' => $existing->updated_at?->toDateTimeString()];
                    DB::afterCommit(fn () => Log::info('CPL manual override imported', $log));
                }

                $result['mappings']++;
                if ($resolvedCourse !== null) {
                    $result['matched']++;
                    $result['manual_overrides'] += $decision !== null ? 1 : 0;
                } else {
                    $result['unmatched'][] = [
                        'kode' => $sourceCode,
                        'nama' => $sourceName,
                        'cpl' => $currentCpl->kode_cpl,
                        'reason' => $problem ?? 'Tidak ditemukan master aman atau target sudah dipakai pada CPL yang sama.',
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
        $byCode = $this->selectUnambiguousCandidate(
            $this->exactCodeCandidates($eligible, $code, $program),
            $code,
            $name,
            $program
        );
        if ($byCode !== null) {
            return $byCode;
        }

        $reviewedTargetCode = $this->reviewedTargetCode($code, $name);
        if ($reviewedTargetCode === null) {
            return null;
        }

        return $this->selectUnambiguousCandidate(
            $eligible->filter(fn (MataKuliah $course) => $this->normalizeBaseCode($course->kode_mk) === $reviewedTargetCode),
            $code,
            $name,
            $program
        );
    }

    private function selectUnambiguousCandidate(
        Collection $candidates,
        string $sourceCode,
        string $sourceName,
        Prodi $program
    ): ?MataKuliah {
        if ($candidates->isEmpty()) {
            return null;
        }

        $programCandidates = $candidates->filter(
            fn (MataKuliah $course) => (int) $course->prodi_id === (int) $program->id
        );
        $preferred = $programCandidates->isNotEmpty()
            ? $programCandidates
            : $candidates->filter(fn (MataKuliah $course) => $course->prodi_id === null);

        if (! $this->requiresVerifiedNameAgreement($sourceCode) && $preferred->count() === 1) {
            return $preferred->first();
        }

        $normalizedName = $this->normalizeComparableCourseName($sourceName);
        $sameName = $preferred->filter(
            fn (MataKuliah $course) => $this->normalizeComparableCourseName($course->nama_mk) === $normalizedName
        );

        return $sameName->count() === 1 ? $sameName->first() : null;
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

    public function exactCodeCandidates(Collection $courses, string $code, Prodi $program): Collection
    {
        $sourceCode = $this->normalizeBaseCode($code);

        return $this->eligibleCourses($courses, $program, $code)
            ->filter(fn (MataKuliah $course) => $sourceCode !== ''
                && $this->normalizeBaseCode($course->kode_mk) === $sourceCode)
            ->values();
    }

    public function sourceKey(string $code, string $name): string
    {
        return $this->normalizeBaseCode($code).'|'.$this->normalizeName($name);
    }

    public function normalizeCourseCode(mixed $value): string
    {
        return $this->normalizeBaseCode($value);
    }

    public function courseCodeParts(mixed $value): ?array
    {
        $value = preg_replace('/\s*\((TP|TG)\)\s*$/iu', '', (string) $value);
        $value = strtoupper(Str::ascii(trim((string) $value)));

        if (! preg_match('/^([A-Z]+)[\s-]*(\d+)$/', $value, $matches)) {
            return null;
        }

        return ['prefix' => $matches[1], 'number' => $matches[2]];
    }

    public function normalizeCourseName(mixed $value): string
    {
        return $this->normalizeName($value);
    }

    public function isSafeMatch(MataKuliah $course, string $sourceCode, string $sourceName, Prodi $program): bool
    {
        if (! $this->isCourseEligible($course, $program, $sourceCode)) {
            return false;
        }

        $codeMatches = $this->normalizeBaseCode($course->kode_mk) === $this->normalizeBaseCode($sourceCode);
        $nameMatches = $this->normalizeComparableCourseName($course->nama_mk)
            === $this->normalizeComparableCourseName($sourceName);
        $reviewedTargetCode = $this->reviewedTargetCode($sourceCode, $sourceName);
        $reviewedAliasMatches = $reviewedTargetCode !== null
            && $this->normalizeBaseCode($course->kode_mk) === $reviewedTargetCode
            && $nameMatches;

        if ($reviewedAliasMatches) {
            return true;
        }

        return $codeMatches
            && (! $this->requiresVerifiedNameAgreement($sourceCode) || $nameMatches);
    }

    public function matchReason(MataKuliah $course, string $sourceCode, string $sourceName): string
    {
        $codeMatches = $this->normalizeBaseCode($course->kode_mk) === $this->normalizeBaseCode($sourceCode);
        $nameMatches = $this->normalizeComparableCourseName($course->nama_mk)
            === $this->normalizeComparableCourseName($sourceName);
        $reviewedTargetCode = $this->reviewedTargetCode($sourceCode, $sourceName);

        if ($reviewedTargetCode !== null
            && $this->normalizeBaseCode($course->kode_mk) === $reviewedTargetCode
            && $nameMatches) {
            return 'Alias kode kurikulum TP terverifikasi dan nama mata kuliah cocok';
        }

        if ($codeMatches) {
            return $nameMatches
                ? 'Kode dan nama mata kuliah cocok setelah normalisasi'
                : 'Kode sama setelah normalisasi';
        }

        if ($nameMatches) {
            return 'Nama mata kuliah sama setelah normalisasi';
        }

        return 'Tidak ada kecocokan exact';
    }

    private function cleanSourceName(string $name): string
    {
        return trim((string) preg_replace('/\s*\[[^\]]+\]\s*/u', ' ', $name));
    }

    private function normalizeBaseCode(mixed $value): string
    {
        $parts = $this->courseCodeParts($value);
        if ($parts !== null) {
            return $parts['prefix'].$parts['number'];
        }

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

    /**
     * Menyamakan variasi nama yang sudah diverifikasi dari sumber CPL TP.
     * Alias sengaja dibatasi; nama berbeda tidak boleh dianggap cocok hanya
     * karena kode mata kuliahnya sama.
     */
    private function normalizeComparableCourseName(mixed $value): string
    {
        $value = preg_replace('/\s*\((?:P|TP|TG)\)\s*$/iu', '', (string) $value);
        $normalized = $this->normalizeName($value);

        return match ($normalized) {
            'pendidikankewarganegaraan' => 'kewarganegaraan',
            'pengantarsisteminformasigeografigis' => 'pengantargis',
            default => $normalized,
        };
    }

    /**
     * Dua kode pada file CPL diketahui bentrok dengan master TP yang mempunyai
     * nama berbeda. Untuk pasangan ini kode saja tidak cukup sebagai bukti.
     */
    private function requiresVerifiedNameAgreement(string $sourceCode): bool
    {
        return in_array($this->normalizeBaseCode($sourceCode), [
            'GL301',
            'GL401',
            'KU302',
            'TA601',
        ], true);
    }

    private function reviewedTargetCode(string $sourceCode, string $sourceName): ?string
    {
        $key = $this->normalizeBaseCode($sourceCode).'|'.$this->normalizeComparableCourseName($sourceName);

        return match ($key) {
            'GL301|geologistruktur' => 'GL401',
            'GL401|petrologi' => 'GL301',
            default => null,
        };
    }
}
