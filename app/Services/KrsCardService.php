<?php

namespace App\Services;

use App\Models\Krs;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use ZipArchive;

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
            ->where(fn ($query) => $query
                ->where('is_manual', false)
                ->orWhereNull('is_manual'))
            ->where('tahun_akademik', $tahunAkademik)
            ->where('semester_akademik', $semesterAkademik)
            ->orderBy('id')
            ->get();
    }

    /** @param Collection<int, Krs> $records */
    public function isApprovedForDownload(Collection $records): bool
    {
        return $records->isNotEmpty()
            && $records->every(fn (Krs $item) => $item->status === 'Disetujui');
    }

    public function data(
        Mahasiswa $mahasiswa,
        string $tahunAkademik,
        string $semesterAkademik,
        bool $includeTemplateAssets = false
    ): array {
        $mahasiswa->loadMissing(['prodi.fakultas', 'dosenWali']);
        $records = $this->records($mahasiswa, $tahunAkademik, $semesterAkademik);
        $printableRecords = $records->where('status', '!=', 'Ditolak')->values();
        $semesterStudi = $mahasiswa->semester
            ?? $printableRecords->map(fn (Krs $item) => $item->mata_kuliah_efektif?->semester)->filter()->max();
        $approvalStatus = match (true) {
            $records->isNotEmpty() && $records->every(fn (Krs $item) => $item->status === 'Disetujui') => 'Disetujui',
            $records->contains(fn (Krs $item) => $item->status === 'Ditolak') => 'Ditolak',
            default => 'Menunggu',
        };

        return [
            'mahasiswa' => $mahasiswa,
            'krsRecords' => $records,
            'printableRecords' => $printableRecords,
            'tahunAkademik' => $tahunAkademik,
            'semesterAkademik' => $semesterAkademik,
            'semesterStudi' => $semesterStudi,
            'approvalStatus' => $approvalStatus,
            'totalSks' => $printableRecords->sum(
                fn (Krs $item) => (int) ($item->mata_kuliah_efektif?->sks ?? 0)
            ),
            'dosenWali' => $mahasiswa->dosenWali,
            'namaKetuaProgramStudi' => $mahasiswa->prodi?->ketua_program_studi_nama,
            'nipKetuaProgramStudi' => $mahasiswa->prodi?->ketua_program_studi_nip,
            'templateLetterhead' => $includeTemplateAssets ? $this->templateLetterhead() : null,
        ];
    }

    public function filename(Mahasiswa $mahasiswa, string $tahunAkademik, string $semesterAkademik): string
    {
        $safeNim = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '', $mahasiswa->nim));
        $safeYear = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '-', Str::ascii($tahunAkademik)), '-');
        $safeSemester = trim((string) preg_replace('/[^A-Za-z0-9_-]/', '', Str::ascii($semesterAkademik)));

        return "KRS-{$safeNim}-{$safeYear}-{$safeSemester}.pdf";
    }

    private function templateLetterhead(): ?string
    {
        $templatePath = collect([
            resource_path('template/krs-template.docx'),
            resource_path('templates/krs-template.docx'),
        ])->first(fn (string $path) => is_file($path));

        if ($templatePath === null || ! class_exists(ZipArchive::class)) {
            return null;
        }

        $templateContents = @file_get_contents($templatePath);
        if ($templateContents === false) {
            return null;
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'sttmi-krs-template-');
        if ($temporaryPath === false) {
            return null;
        }

        try {
            if (file_put_contents($temporaryPath, $templateContents) === false) {
                return null;
            }

            $archive = new ZipArchive;
            if ($archive->open($temporaryPath) !== true) {
                return null;
            }

            try {
                $letterhead = $archive->getFromName('word/media/image1.png');
            } finally {
                $archive->close();
            }

            return $letterhead === false
                ? null
                : 'data:image/png;base64,'.base64_encode($letterhead);
        } finally {
            @unlink($temporaryPath);
        }
    }
}
