<?php

namespace App\Services;

use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CplManualOverrides
{
    public function __construct(private readonly CplMappingImporter $matcher) {}

    public function definitions(): array
    {
        return [
            ['source_code' => 'KU 302', 'source_name' => 'Matriks Ruang Vektor', 'cpls' => ['CPL 1'],
                'target_code' => 'KU 302 (TP)', 'target_name' => 'Dasar Komputasi',
                'reason' => 'Keputusan admin/prodi: ekuivalensi perubahan kurikulum untuk akreditasi.'],
            ['source_code' => 'TA 106', 'source_name' => 'Pengantar Ilmu Kebumian & Pertambangan', 'cpls' => ['CPL 1'],
                'target_code' => 'KU 106', 'target_name' => 'Pengantar Ilmu Kebumian dan Pertambangan',
                'reason' => 'Keputusan admin/prodi: nama ekuivalen dengan prefix kode berbeda.'],
            ['source_code' => 'TA 601', 'source_name' => 'MK Pilihan 2', 'cpls' => ['CPL 2', 'CPL 7'],
                'target_code' => 'TA 601', 'target_name' => 'MK Pilihan 2',
                'reason' => 'Keputusan admin/prodi: gunakan master MK Pilihan 2 berkode TA 601.'],
        ];
    }

    public function decision(string $code, string $name, string $cpl, Prodi $program): ?array
    {
        if (! str_contains($this->matcher->normalizeCourseName($program->nama_prodi), 'pertambangan')
            || preg_match('/\(\s*TG\s*\)\s*$/i', $code)) {
            return null;
        }

        foreach ($this->definitions() as $definition) {
            if ($this->matcher->sourceKey($code, $name) === $this->matcher->sourceKey($definition['source_code'], $definition['source_name'])
                && in_array($cpl, $definition['cpls'], true)) {
                return $definition;
            }
        }

        return null;
    }

    public function targetMatches(MataKuliah $course, array $decision, Prodi $program): bool
    {
        // TA 601 must belong to TP; approved general courses may have no program.
        return ($decision['target_code'] !== 'TA 601' || (int) $course->prodi_id === (int) $program->id)
            && $this->matcher->isCourseEligible($course, $program)
            && $this->matcher->normalizeCourseCode($course->kode_mk) === $this->matcher->normalizeCourseCode($decision['target_code'])
            && $this->matcher->normalizeCourseName($course->nama_mk) === $this->matcher->normalizeCourseName($decision['target_name']);
    }

    public function applied(CplMataKuliah $mapping, Prodi $program): ?array
    {
        $decision = $this->decision($mapping->kode_sumber, $mapping->nama_sumber, $mapping->cpl->kode_cpl, $program);

        return $decision && $mapping->mataKuliah && $this->targetMatches($mapping->mataKuliah, $decision, $program)
            ? $decision : null;
    }

    public function accepted(CplMataKuliah $mapping, Prodi $program): bool
    {
        $decision = $this->decision($mapping->kode_sumber, $mapping->nama_sumber, $mapping->cpl->kode_cpl, $program);
        if ($decision !== null) {
            return $this->applied($mapping, $program) !== null;
        }

        return $mapping->mataKuliah !== null && $this->matcher->isSafeMatch(
            $mapping->mataKuliah, $mapping->kode_sumber, $mapping->nama_sumber, $program
        );
    }

    public function resolve(array $decision, Prodi $program, ?int $sks, ?int $semester, bool $create = false): MataKuliah
    {
        $courses = MataKuliah::query()->get();
        $targets = $courses->filter(fn (MataKuliah $course) => $this->targetMatches($course, $decision, $program));
        $programTargets = $targets->where('prodi_id', $program->id);
        $targets = $programTargets->isNotEmpty() ? $programTargets : $targets;
        if ($targets->count() === 1) {
            return $targets->first();
        }
        if ($targets->count() > 1) {
            throw new RuntimeException('Master target ambigu: '.$decision['target_code'].'; perlu memilih satu master TP.');
        }
        if ($decision['target_code'] !== 'TA 601') {
            throw new RuntimeException('Master target TP belum tersedia: '.$decision['target_code'].' - '.$decision['target_name']);
        }

        // Refuse normalized-code collisions rather than renaming or merging another master.
        if ($courses->contains(fn (MataKuliah $course) => $this->matcher->normalizeCourseCode($course->kode_mk) === 'TA601')) {
            throw new RuntimeException('Kode TA 601 sudah digunakan master dengan nama/prodi berbeda; perlu verifikasi admin.');
        }
        if ($sks === null || $sks < 1 || $sks > 255 || $semester === null || $semester < 1 || $semester > 14) {
            throw new RuntimeException('TA 601 membutuhkan SKS (1-255) dan semester (1-14) dari mapping Excel CPL.');
        }
        if (! $create) {
            throw new RuntimeException('Master TA 601 belum ada; SKS dan semester tersedia. Jalankan ipk-cpl:apply-overrides.');
        }

        return MataKuliah::firstOrCreate(
            ['kode_mk' => 'TA 601', 'nama_mk' => 'MK Pilihan 2', 'prodi_id' => $program->id],
            ['sks' => $sks, 'semester' => $semester]
        );
    }

    public function notes(Collection $mappings, Prodi $program): Collection
    {
        return $mappings->map(function (CplMataKuliah $mapping) use ($program) {
            $decision = $this->applied($mapping, $program);

            return $decision === null ? null : [
                'source' => $mapping->kode_sumber.' - '.$mapping->nama_sumber,
                'cpl' => $mapping->cpl->kode_cpl,
                'target' => $mapping->mataKuliah->kode_mk.' - '.$mapping->mataKuliah->nama_mk,
                'reason' => $decision['reason'],
                'updated_at' => $mapping->updated_at?->toDateTimeString(),
            ];
        })->filter()->values();
    }

    public function apply(Prodi $program): array
    {
        return DB::transaction(function () use ($program) {
            // Serialize this operation with imports for the same program.
            Prodi::query()->whereKey($program->id)->lockForUpdate()->firstOrFail();
            $mappings = CplMataKuliah::with('cpl')->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $program->id))->get();
            $result = ['updated' => 0, 'created' => false, 'problems' => []];
            foreach ($mappings as $mapping) {
                $decision = $this->decision($mapping->kode_sumber, $mapping->nama_sumber, $mapping->cpl->kode_cpl, $program);
                if ($decision === null) {
                    continue;
                }
                try {
                    $target = $this->resolve($decision, $program, $mapping->sks, $mapping->semester, true);
                } catch (RuntimeException $exception) {
                    $result['problems'][] = $mapping->kode_sumber.' / '.$mapping->cpl->kode_cpl.': '.$exception->getMessage();

                    continue;
                }
                $result['created'] = $result['created'] || $target->wasRecentlyCreated;
                if (CplMataKuliah::where('cpl_id', $mapping->cpl_id)->where('mata_kuliah_id', $target->id)->where('id', '!=', $mapping->id)->exists()) {
                    $result['problems'][] = $mapping->kode_sumber.': target sudah dipakai mapping lain pada CPL yang sama.';

                    continue;
                }
                if ((int) $mapping->mata_kuliah_id !== (int) $target->id) {
                    $previous = $mapping->mata_kuliah_id;
                    $mapping->update(['mata_kuliah_id' => $target->id]);
                    $result['updated']++;
                    DB::afterCommit(fn () => Log::info('CPL manual override applied', [
                        'mapping_id' => $mapping->id, 'previous_course_id' => $previous,
                        'course_id' => $target->id, 'decision' => $decision, 'updated_at' => $mapping->updated_at?->toDateTimeString(),
                    ]));
                }
            }

            return $result;
        });
    }
}
