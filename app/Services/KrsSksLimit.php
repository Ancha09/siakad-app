<?php

namespace App\Services;

use App\Models\PeriodeKrs;

class KrsSksLimit
{
    public const DEFAULT_MAX_SKS = 22;

    public function forPeriod(?PeriodeKrs $periodeKrs): int
    {
        $periodLimit = $periodeKrs?->maksimal_sks;

        return $periodLimit !== null && (int) $periodLimit > 0
            ? min((int) $periodLimit, self::DEFAULT_MAX_SKS)
            : self::DEFAULT_MAX_SKS;
    }
}
