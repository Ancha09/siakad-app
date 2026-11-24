<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Krs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BapPresensiService
{
    /**
     * Mempersiapkan data BAP dan Presensi secara efisien (tanpa N+1 query).
     */
    public function prepareBapData(Jadwal $jadwal): array
    {
        $jadwal->loadMissing([
            'mataKuliah.prodi.fakultas',
            'dosen.prodi',
            'dosens',
            'kelas',
            'ruangan',
            'presensiPertemuans' => fn ($q) => $q->orderBy('pertemuan'),
        ]);

        $krsQuery = Krs::with([
            'mahasiswa.prodi',
            'mahasiswa.kelas',
            'presensis',
        ])
        ->where('is_manual', false)
        ->where('jadwal_id', $jadwal->id);

        if (! empty($jadwal->tahun_akademik)) {
            $krsQuery->where('tahun_akademik', $jadwal->tahun_akademik);
        }

        if (! empty($jadwal->semester_akademik)) {
            $krsQuery->where('semester_akademik', $jadwal->semester_akademik);
        }

        if (! empty($jadwal->kelas_id)) {
            $krsQuery->where(function ($q) use ($jadwal) {
                $q->where('kelas_id', $jadwal->kelas_id)
                  ->orWhereNull('kelas_id')
                  ->orWhereHas('mahasiswa', fn ($m) => $m->where('kelas_id', $jadwal->kelas_id));
            });
        }

        $krs = $krsQuery->get();

        // Hitung statistik per mahasiswa secara in-memory (0 query tambahan)
        foreach ($krs as $item) {
            $item->hadir = $item->presensis->where('status', 'Hadir')->count();
            $item->izin = $item->presensis->where('status', 'Izin')->count();
            $item->sakit = $item->presensis->where('status', 'Sakit')->count();
            $item->alpha = $item->presensis->where('status', 'Alpha')->count();
            $total = $item->hadir + $item->izin + $item->sakit + $item->alpha;
            $item->total_pertemuan_mhs = $total;
            $item->persentase = $total > 0 ? round(($item->hadir / $total) * 100, 1) : 0;
        }

        $pertemuans = $jadwal->presensiPertemuans;
        $totalPeserta = $krs->count();

        // Hitung rekap kehadiran per sesi pertemuan secara in-memory (0 query tambahan)
        $presensiByPertemuan = $krs->flatMap->presensis->groupBy('pertemuan');

        foreach ($pertemuans as $p) {
            $records = $presensiByPertemuan->get($p->pertemuan, collect());
            $p->total_hadir = $records->where('status', 'Hadir')->count();
            $p->total_izin = $records->where('status', 'Izin')->count();
            $p->total_sakit = $records->where('status', 'Sakit')->count();
            $p->total_alpha = $records->where('status', 'Alpha')->count();
        }

        $rataRataKehadiranKelas = $totalPeserta > 0
            ? round($krs->avg('persentase'), 1)
            : 0;

        $prodi = $jadwal->mataKuliah?->prodi ?? $jadwal->dosen?->prodi;

        return [
            'jadwal' => $jadwal,
            'krs' => $krs,
            'pertemuans' => $pertemuans,
            'totalPeserta' => $totalPeserta,
            'rataRataKehadiranKelas' => $rataRataKehadiranKelas,
            'prodi' => $prodi,
        ];
    }

    /**
     * Mempersiapkan data PDF lengkap dengan pemrosesan aset gambar aman (base64 & thumbnailing).
     */
    public function preparePdfData(Jadwal $jadwal): array
    {
        $data = $this->prepareBapData($jadwal);

        // Kop & Logo STTMI
        $data['kopBase64'] = $this->imageToThumbnailBase64(public_path('images/kop-sttmi.jpeg.jpeg'), 800, 150);
        $data['logoBase64'] = $this->imageToThumbnailBase64(public_path('images/logo_sttmi.jpeg'), 120, 120);

        // Pre-process foto bukti pertemuan agar tidak membebani memori DomPDF
        foreach ($data['pertemuans'] as $p) {
            $p->foto_base64 = $this->imageToThumbnailBase64($p->foto, 160, 120);
        }

        return $data;
    }

    /**
     * Generate instance PDF DomPDF yang sudah diset paper-nya.
     */
    public function generatePdf(Jadwal $jadwal): \Barryvdh\DomPDF\PDF
    {
        $data = $this->preparePdfData($jadwal);

        return Pdf::loadView('admin.presensi.bap-pdf', $data)
            ->setPaper('a4', 'portrait');
    }

    /**
     * Download file PDF BAP dengan nama file yang aman dari error header HTTP.
     */
    public function downloadPdf(Jadwal $jadwal)
    {
        $pdf = $this->generatePdf($jadwal);

        $kodeMk = preg_replace('/[\/\\\\:*?"<>|]+/', '-', $jadwal->mataKuliah?->kode_mk ?? 'MK');
        $namaMk = Str::slug($jadwal->mataKuliah?->nama_mk ?? 'Matkul');
        $namaKelas = Str::slug($jadwal->kelas?->nama_kelas ?? $jadwal->kelas ?? 'Kelas');
        $filename = str_replace(['/', '\\'], '-', 'BAP_' . $kodeMk . '_' . $namaMk . '_' . $namaKelas . '_' . now()->format('Ymd') . '.pdf');

        return $pdf->download($filename);
    }

    /**
     * Sanitasi nama file hasil download agar bebas dari karakter ilegal, CRLF, dan traversal.
     */
    public function sanitizeFilename(Jadwal $jadwal): string
    {
        $kodeMk = preg_replace('/[\/\\\\:*?"<>|]+/', '-', $jadwal->mataKuliah?->kode_mk ?? 'MK');
        $namaMk = Str::slug($jadwal->mataKuliah?->nama_mk ?? 'Matkul');
        $namaKelas = Str::slug($jadwal->kelas?->nama_kelas ?? $jadwal->kelas ?? 'Kelas');

        return str_replace(['/', '\\'], '-', 'BAP_' . $kodeMk . '_' . $namaMk . '_' . $namaKelas . '_' . now()->format('Ymd') . '.pdf');
    }

    /**
     * Mengubah file gambar lokal menjadi thumbnail base64 data URI secara aman.
     * Mencegah memori exhaustion pada foto smartphone resolusi tinggi (5-10MB).
     */
    public function imageToThumbnailBase64(?string $photoPath, int $maxWidth = 160, int $maxHeight = 120): ?string
    {
        if (empty($photoPath)) {
            return null;
        }

        // Resolusi path file lokal (Tolak URL eksternal http/https untuk hindari loopback timeout)
        if (preg_match('/^https?:\/\//i', $photoPath)) {
            return null;
        }

        $fullPath = null;
        if (file_exists($photoPath) && is_file($photoPath)) {
            $fullPath = $photoPath;
        } elseif (file_exists(public_path('storage/' . $photoPath)) && is_file(public_path('storage/' . $photoPath))) {
            $fullPath = public_path('storage/' . $photoPath);
        } elseif (file_exists(storage_path('app/public/' . $photoPath)) && is_file(storage_path('app/public/' . $photoPath))) {
            $fullPath = storage_path('app/public/' . $photoPath);
        } elseif (file_exists(public_path($photoPath)) && is_file(public_path($photoPath))) {
            $fullPath = public_path($photoPath);
        }

        if (! $fullPath) {
            return null;
        }

        try {
            $fileSize = @filesize($fullPath);
            if ($fileSize === false || $fileSize === 0) {
                return null;
            }

            // Jika file gambar sudah sangat kecil (< 30 KB), langsung encode base64
            if ($fileSize < 30 * 1024) {
                $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
                $content = @file_get_contents($fullPath);
                if ($content !== false) {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }

            // Jika ekstensi GD tidak ada, encode langsung bila ukuran di bawah 1.5MB
            if (! extension_loaded('gd') || ! function_exists('imagecreatefromstring')) {
                if ($fileSize <= 1536 * 1024) {
                    $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
                    $content = @file_get_contents($fullPath);
                    if ($content !== false) {
                        return 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                }
                return null;
            }

            $imgData = @file_get_contents($fullPath);
            if ($imgData === false) {
                return null;
            }

            $srcImg = @imagecreatefromstring($imgData);
            if (! $srcImg) {
                return null;
            }

            $origWidth = imagesx($srcImg);
            $origHeight = imagesy($srcImg);

            if ($origWidth <= 0 || $origHeight <= 0) {
                imagedestroy($srcImg);
                return null;
            }

            // Skalakan ukuran dengan tetap mempertahankan rasio aspek
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1.0);
            $newWidth = max(1, (int) round($origWidth * $ratio));
            $newHeight = max(1, (int) round($origHeight * $ratio));

            $thumb = imagecreatetruecolor($newWidth, $newHeight);

            // Pertahankan transparansi PNG jika ada
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);

            imagecopyresampled($thumb, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            ob_start();
            imagejpeg($thumb, null, 75);
            $thumbData = ob_get_clean();

            imagedestroy($srcImg);
            imagedestroy($thumb);

            if ($thumbData) {
                return 'data:image/jpeg;base64,' . base64_encode($thumbData);
            }
        } catch (\Throwable $e) {
            // Tangani error gambar secara graceful
            return null;
        }

        return null;
    }
}
