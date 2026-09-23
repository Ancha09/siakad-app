<?php

namespace App\Console\Commands;

use App\Models\Prodi;
use App\Services\CplManualOverrides;
use Illuminate\Console\Command;

class ApplyIpkCplOverrides extends Command
{
    protected $signature = 'ipk-cpl:apply-overrides';

    protected $description = 'Terapkan keputusan prodi untuk empat mapping CPL TP dan siapkan master TA 601 secara idempotent';

    public function handle(CplManualOverrides $overrides): int
    {
        $programs = Prodi::query()->where('nama_prodi', 'like', '%Pertambangan%')->get();
        if ($programs->count() !== 1) {
            $this->error('Diperlukan tepat satu program studi Teknik Pertambangan.');

            return self::FAILURE;
        }
        $result = $overrides->apply($programs->first());
        $this->info('Mapping diperbarui: '.$result['updated']);
        $this->info($result['created'] ? 'Master TA 601 dibuat.' : 'Tidak ada master baru dibuat.');
        foreach ($result['problems'] as $problem) {
            $this->error($problem);
        }
        $this->call('ipk-cpl:audit-mapping', ['--prodi' => $programs->first()->nama_prodi]);

        return $result['problems'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
