<?php

namespace App\Console\Commands;

use App\Services\CplMappingImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportIpkCplMapping extends Command
{
    protected $signature = 'ipk-cpl:import
        {file? : Lokasi file Excel mapping CPL}
        {--prodi=Teknik Pertambangan : Nama program studi target}';

    protected $description = 'Import mapping CPL dan mata kuliah dari file ipkcpl.xlsx';

    public function handle(CplMappingImporter $importer): int
    {
        $path = $this->argument('file') ?: database_path('data/ipkcpl.xlsx');

        try {
            $result = $importer->import($path, (string) $this->option('prodi'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Program studi: {$result['program_studi']}");
        $this->info("CPL terimport: {$result['cpls']}");
        $this->info("Mapping terimport: {$result['mappings']}");
        $this->info("Mapping cocok master: {$result['matched']}");
        $this->info('Mapping manual override: '.$result['manual_overrides']);
        $this->warn('Sumber unik belum cocok master: '.count($result['unmatched']));

        if ($result['unmatched'] !== []) {
            $this->table(
                ['Kode', 'Mata Kuliah', 'CPL', 'Alasan'],
                array_map(fn (array $item) => [$item['kode'], $item['nama'], $item['cpl'], $item['reason']], $result['unmatched'])
            );
        }

        return self::SUCCESS;
    }
}
