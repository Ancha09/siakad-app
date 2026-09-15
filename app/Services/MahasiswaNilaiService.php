<?php

namespace App\Services;

use App\Models\Khs;
use Illuminate\Support\Collection;

class MahasiswaNilaiService
{
    public function ringkasanMahasiswa(int $mahasiswaId): array
    {
        $khs = Khs::with(['krs.jadwal.mataKuliah', 'krs.mataKuliahManual', 'krs.kuesioner'])
            ->whereHas('krs', fn ($query) => $query
                ->where('mahasiswa_id', $mahasiswaId)
                ->where('status', 'Disetujui'))
            ->get();

        return $this->ringkasan($khs);
    }

    public function ringkasan(Collection $khs): array
    {
        $tertunda = $khs->filter(fn (Khs $item) => ! $item->is_manual && $item->krs?->kuesioner === null)->count();
        $ipkAktual = $this->hitungIndeks($khs);

        return [
            'ipk_aktual' => $ipkAktual,
            'ipk_terlihat' => $khs->isNotEmpty() && $tertunda === 0 ? $ipkAktual : null,
            'jumlah_nilai' => $khs->count(),
            'kuesioner_tertunda' => $tertunda,
            'terkunci' => $tertunda > 0,
        ];
    }

    public function hitungIndeks(Collection $khs): float
    {
        $totalSks = $khs->sum(fn (Khs $item) => $item->sks_efektif);

        if ($totalSks <= 0) {
            return 0.0;
        }

        $totalMutu = $khs->sum(function (Khs $item) {
            $sks = $item->sks_efektif;

            return $sks * (float) ($item->bobot ?? 0);
        });

        return round($totalMutu / $totalSks, 2);
    }
}
