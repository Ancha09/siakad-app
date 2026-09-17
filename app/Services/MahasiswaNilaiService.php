<?php

namespace App\Services;

use App\Models\Khs;
use Illuminate\Support\Collection;

class MahasiswaNilaiService
{
    public const LOCKED_PLACEHOLDER = 'Isi kuesioner untuk melihat nilai';

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
        $tertunda = $khs->filter(fn (Khs $item) => $item->krs?->kuesioner === null)->count();
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
        $nilaiFinal = $khs->filter(fn (Khs $item) => $item->nilai_angka !== null
            && $item->nilai_huruf !== null
            && $item->bobot !== null
            && $item->sks_efektif > 0);

        $totalSks = $nilaiFinal->sum(fn (Khs $item) => $item->sks_efektif);

        if ($totalSks <= 0) {
            return 0.0;
        }

        $totalMutu = $nilaiFinal->sum(function (Khs $item) {
            $sks = $item->sks_efektif;

            return $sks * (float) ($item->bobot ?? 0);
        });

        return round($totalMutu / $totalSks, 2);
    }

    public function sembunyikanNilaiTerkunci(Collection $khs): void
    {
        // Request-local masking protects Blade and serialization without changing database grades.
        $khs->each(function (Khs $item) {
            if ($item->krs?->kuesioner !== null) {
                return;
            }
            foreach (['nilai_angka', 'nilai_huruf', 'bobot', 'indeks', 'status_lulus', 'lulus', 'ip', 'ips', 'ipk'] as $field) {
                if (array_key_exists($field, $item->getAttributes())) {
                    $item->setAttribute($field, null);
                }
            }
        });
    }
}
