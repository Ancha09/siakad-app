<?php

namespace App\Console\Commands;

use App\Services\KtmService;
use Illuminate\Console\Command;

class CleanupKtmTemporaryPhotos extends Command
{
    protected $signature = 'ktm:cleanup-temporary {--hours=24 : Hapus file temporary yang lebih lama dari jumlah jam ini}';

    protected $description = 'Membersihkan foto temporary KTM yang tidak difinalkan';

    public function handle(KtmService $ktmService): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $ktmService->cleanupTemporary($hours);

        $this->info("{$deleted} file temporary KTM dibersihkan.");

        return self::SUCCESS;
    }
}
