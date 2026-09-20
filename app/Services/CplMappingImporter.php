<?php

namespace App\Services;

use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
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

        $courses = MataKuliah::query()->get();
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
                    $currentCpl = Cpl::updateOrCreate(
                        ['program_studi_id' => $program->id, 'kode_cpl' => $code],
                        [
                            'nama_cpl' => trim($matches[2]),
                            'deskripsi' => null,
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
                $course = $this->matchCourse($courses, $sourceCode, $sourceName, $program->id);

                CplMataKuliah::updateOrCreate(
                    ['cpl_id' => $currentCpl->id, 'kode_sumber' => $sourceCode],
                    [
                        'mata_kuliah_id' => $course?->id,
                        'nama_sumber' => $sourceName,
                        'semester' => (int) $row['C'],
                        'sks' => (int) $row['D'],
                    ]
                );

                $result['mappings']++;
                if ($course !== null) {
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

    private function matchCourse(Collection $courses, string $code, string $name, int $programId): ?MataKuliah
    {
        $normalizedCode = $this->normalizeCode($code);
        $byCode = $courses->first(fn (MataKuliah $course) => $this->normalizeCode($course->kode_mk) === $normalizedCode);
        if ($byCode !== null) {
            return $byCode;
        }

        $normalizedName = $this->normalizeName($name);
        $sameProgram = $courses->first(fn (MataKuliah $course) => (int) $course->prodi_id === $programId
            && $this->normalizeName($course->nama_mk) === $normalizedName);

        return $sameProgram ?? $courses->first(
            fn (MataKuliah $course) => $this->normalizeName($course->nama_mk) === $normalizedName
        );
    }

    private function cleanSourceName(string $name): string
    {
        return trim((string) preg_replace('/\s*\[[^\]]+\]\s*/u', ' ', $name));
    }

    private function normalizeCode(mixed $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '', Str::ascii((string) $value)));
    }

    private function normalizeName(mixed $value): string
    {
        $value = str_replace('&', ' dan ', Str::lower(Str::ascii($this->cleanSourceName((string) $value))));

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }
}
