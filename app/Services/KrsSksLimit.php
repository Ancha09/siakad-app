<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;

class KrsSksLimit
{
    public const DEFAULT_MAX_SKS = 22;

    public const INFORMATIKA_MAX_SKS = 24;

    public function forPeriod(?PeriodeKrs $periodeKrs, ?Mahasiswa $mahasiswa = null): int
    {
        $isInformatika = $this->isInformatika($mahasiswa);
        $maxCap = $isInformatika
            ? self::INFORMATIKA_MAX_SKS
            : self::DEFAULT_MAX_SKS;

        $periodLimit = $periodeKrs?->maksimal_sks;

        if ($periodLimit === null || (int) $periodLimit <= 0) {
            return $maxCap;
        }

        // Khusus mahasiswa Prodi Informatika: tetap mendapatkan batas maksimal 24 SKS
        // meskipun batas periode berada pada angka standar (22 atau 24 SKS) dan tanpa syarat IPK.
        if ($isInformatika && (int) $periodLimit >= self::DEFAULT_MAX_SKS) {
            return self::INFORMATIKA_MAX_SKS;
        }

        return min((int) $periodLimit, $maxCap);
    }

    public function isInformatika(?Mahasiswa $mahasiswa): bool
    {
        if (! $mahasiswa) {
            return false;
        }

        $prodi = $mahasiswa->relationLoaded('prodi')
            ? $mahasiswa->prodi
            : $mahasiswa->prodi()->first();

        if (! $prodi) {
            return false;
        }

        $nama = strtolower(trim((string) $prodi->nama_prodi));
        $kode = strtoupper(trim((string) $prodi->kode_prodi));

        return str_contains($nama, 'informatika')
            || in_array($kode, ['IF', 'TI', 'INF', '55201'], true);
    }
}
