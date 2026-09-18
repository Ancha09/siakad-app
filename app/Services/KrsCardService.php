<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class KrsCardService
{
    /** @return Collection<int, Krs> */
    public function records(Mahasiswa $mahasiswa, string $tahunAkademik, string $semesterAkademik): Collection
    {
        return Krs::query()
            ->with([
                'jadwal.mataKuliah',
                'jadwal.dosen',
                'jadwal.ruangan',
                'jadwal.kelas',
                'mataKuliahManual',
                'dosenManual',
                'kelasManual',
            ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('is_manual', false)
            ->where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->orderBy('id')
            ->get();
    }

    public function data(Mahasiswa $mahasiswa, string $tahunAkademik, string $semesterAkademik): array
    {
        $mahasiswa->loadMissing(['prodi.fakultas', 'dosenWali']);
        $records = $this->records($mahasiswa, $tahunAkademik, $semesterAkademik);
        $printableRecords = $records->where('status', '!=', 'Ditolak')->values();
        $semesterStudi = $mahasiswa->semester
            ?? $printableRecords->map(fn (Krs $item) => $item->mata_kuliah_efektif?->semester)->filter()->max();
        $ketuaProdi = $mahasiswa->prodi_id
            ? Dosen::query()
                ->where('prodi_id', $mahasiswa->prodi_id)
                ->where(function ($query) {
                    $query->where('jabatan', 'like', '%Ketua Program Studi%')
                        ->orWhere('jabatan', 'like', '%Kaprodi%');
                })
                ->orderBy('nama')
                ->first()
            : null;

        return [
            'mahasiswa' => $mahasiswa,
            'krsRecords' => $records,
            'printableRecords' => $printableRecords,
            'tahunAkademik' => $tahunAkademik,
            'semesterAkademik' => $semesterAkademik,
            'semesterStudi' => $semesterStudi,
            'totalSks' => $printableRecords->sum(
                fn (Krs $item) => (int) ($item->mata_kuliah_efektif?->sks ?? 0)
            ),
            'dosenWali' => $mahasiswa->dosenWali,
            'ketuaProdi' => $ketuaProdi,
        ];
    }

    public function filename(Mahasiswa $mahasiswa, int|string|null $semester): string
    {
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9 _-]/', '', Str::ascii($mahasiswa->nama)));
        $safeNim = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '', $mahasiswa->nim));

        return 'KRS Semester '.($semester ?: '-')."-{$safeName}-{$safeNim}.pdf";
    }
}
