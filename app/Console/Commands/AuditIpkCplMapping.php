<?php

namespace App\Console\Commands;

use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\CplMappingImporter;
use Illuminate\Console\Command;

class AuditIpkCplMapping extends Command
{
    protected $signature = 'ipk-cpl:audit-mapping
        {--prodi=Teknik Pertambangan : Nama program studi yang diaudit}';

    protected $description = 'Audit read-only mapping CPL yang belum cocok atau terhubung ke master yang mencurigakan';

    public function handle(CplMappingImporter $matcher): int
    {
        $requestedProgram = $matcher->normalizeCourseName((string) $this->option('prodi'));
        $program = Prodi::query()->get()->first(function (Prodi $item) use ($matcher, $requestedProgram) {
            $name = $matcher->normalizeCourseName($item->nama_prodi);

            return $name === $requestedProgram
                || ($requestedProgram === 'teknikpertambangan' && str_contains($name, 'pertambangan'));
        });

        if ($program === null) {
            $this->error('Program studi target tidak ditemukan.');

            return self::FAILURE;
        }

        $courses = MataKuliah::query()->with('prodi')->get();
        $mappings = CplMataKuliah::query()
            ->with(['cpl', 'mataKuliah.prodi'])
            ->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $program->id))
            ->orderBy('kode_sumber')
            ->get();
        $safeCount = $mappings->filter(fn (CplMataKuliah $mapping) => $mapping->mataKuliah !== null
            && $matcher->isSafeMatch(
                $mapping->mataKuliah,
                $mapping->kode_sumber,
                $mapping->nama_sumber,
                $program
            ))->count();

        $problemGroups = $mappings
            ->reject(fn (CplMataKuliah $mapping) => $mapping->mataKuliah !== null
                && $matcher->isSafeMatch(
                    $mapping->mataKuliah,
                    $mapping->kode_sumber,
                    $mapping->nama_sumber,
                    $program
                ))
            ->groupBy(fn (CplMataKuliah $mapping) => $matcher->sourceKey(
                $mapping->kode_sumber,
                $mapping->nama_sumber
            ));

        $rows = $problemGroups->map(function ($group) use ($courses, $matcher, $program) {
            /** @var CplMataKuliah $mapping */
            $mapping = $group->first();
            $safeCandidate = $matcher->matchCourse(
                $courses,
                $mapping->kode_sumber,
                $mapping->nama_sumber,
                $program
            );
            $exactCodeCandidates = $safeCandidate === null
                ? $matcher->exactCodeCandidates($courses, $mapping->kode_sumber, $program)
                : collect();
            $currentMasters = $group->map(fn (CplMataKuliah $item) => $item->mataKuliah
                ? $item->mataKuliah->kode_mk.' - '.$item->mataKuliah->nama_mk
                : 'Belum terhubung')
                ->unique()
                ->implode('; ');

            if ($safeCandidate !== null) {
                $candidate = $safeCandidate->kode_mk.' - '.$safeCandidate->nama_mk;
                $reason = $matcher->matchReason($safeCandidate, $mapping->kode_sumber, $mapping->nama_sumber)
                    .'; dapat diperbaiki aman dengan command import';
            } elseif ($exactCodeCandidates->isNotEmpty()) {
                $candidate = $exactCodeCandidates->map(fn (MataKuliah $course) => $course->kode_mk.' - '.$course->nama_mk)
                    ->implode('; ');
                $reason = 'Master berkode exact ditemukan, tetapi nama mata kuliah tidak cocok; tidak dipasang otomatis';
            } else {
                $candidate = '-';
                $reason = 'Tidak ditemukan master dengan kode exact';
            }

            return [
                $mapping->kode_sumber,
                $mapping->nama_sumber,
                $group->pluck('cpl.kode_cpl')->filter()->unique()->sort()->implode(', '),
                $currentMasters,
                $candidate,
                $reason,
            ];
        })->values()->all();

        $this->info('Program studi: '.$program->nama_prodi);
        $this->line('Total mapping: '.$mappings->count());
        $this->line('Mapping aman: '.$safeCount);
        $this->warn('Mapping bermasalah: '.($mappings->count() - $safeCount));
        $this->warn('Mata kuliah sumber unik bermasalah: '.$problemGroups->count());

        if ($rows === []) {
            $this->info('Semua mapping sudah cocok dengan master Teknik Pertambangan.');

            return self::SUCCESS;
        }

        $this->table(
            ['Kode Excel', 'Mata Kuliah Excel', 'CPL', 'Master Saat Ini', 'Kandidat Master', 'Alasan'],
            $rows
        );
        $this->newLine();
        $this->comment('Command ini read-only. Jalankan ipk-cpl:import hanya setelah hasil audit diperiksa.');

        return self::SUCCESS;
    }
}
