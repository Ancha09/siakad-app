<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class KtmService
{
    public const DISK = 'local';

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function storeTemporary(Mahasiswa $mahasiswa, UploadedFile $photo): string
    {
        if ($mahasiswa->ktm_photo_locked) {
            throw new RuntimeException('Foto KTM sudah dikirim dan tidak dapat diganti.');
        }

        $mime = $photo->getMimeType();
        $imageInfo = @getimagesize($photo->getRealPath());

        if (! in_array($mime, self::ALLOWED_MIMES, true) || $imageInfo === false) {
            throw new RuntimeException('File foto tidak valid. Gunakan JPG, PNG, atau WEBP.');
        }

        $bytes = file_get_contents($photo->getRealPath());
        $source = $bytes === false ? false : @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new RuntimeException('Isi file tidak dapat dibaca sebagai gambar.');
        }

        $normalized = $this->normalizePortrait($source);
        imagedestroy($source);

        ob_start();
        imagejpeg($normalized, null, 86);
        $normalizedBytes = ob_get_clean();
        imagedestroy($normalized);

        if (! is_string($normalizedBytes)) {
            throw new RuntimeException('Foto gagal diproses.');
        }

        $directory = $this->temporaryDirectory($mahasiswa);
        Storage::disk(self::DISK)->deleteDirectory($directory);

        $path = $directory.'/'.Str::uuid().'.jpg';
        Storage::disk(self::DISK)->put($path, $normalizedBytes);

        return $path;
    }

    public function temporaryPath(Mahasiswa $mahasiswa): ?string
    {
        $files = Storage::disk(self::DISK)->files($this->temporaryDirectory($mahasiswa));

        return collect($files)
            ->sortByDesc(fn (string $path): int => Storage::disk(self::DISK)->lastModified($path))
            ->first();
    }

    public function cancelTemporary(Mahasiswa $mahasiswa): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->temporaryDirectory($mahasiswa));
    }

    public function finalize(Mahasiswa $mahasiswa): Mahasiswa
    {
        $temporaryPath = $this->temporaryPath($mahasiswa);

        if (! $temporaryPath || ! Storage::disk(self::DISK)->exists($temporaryPath)) {
            throw new RuntimeException('Foto preview tidak ditemukan. Silakan upload kembali.');
        }

        $finalPath = 'ktm/final/'.$mahasiswa->getKey().'/'.Str::uuid().'.jpg';

        return DB::transaction(function () use ($mahasiswa, $temporaryPath, $finalPath): Mahasiswa {
            /** @var Mahasiswa $lockedStudent */
            $lockedStudent = Mahasiswa::query()->lockForUpdate()->findOrFail($mahasiswa->getKey());

            if ($lockedStudent->ktm_photo_locked) {
                throw new RuntimeException('Foto KTM sudah dikirim dan tidak dapat diganti.');
            }

            if (! Storage::disk(self::DISK)->move($temporaryPath, $finalPath)) {
                throw new RuntimeException('Foto KTM gagal difinalkan. Silakan coba kembali.');
            }

            try {
                $lockedStudent->update([
                    'ktm_photo_path' => $finalPath,
                    'ktm_photo_uploaded_at' => now(),
                    'ktm_photo_locked' => true,
                    'ktm_photo_reset_at' => null,
                    'ktm_photo_reset_by' => null,
                ]);
            } catch (\Throwable $exception) {
                Storage::disk(self::DISK)->delete($finalPath);
                throw $exception;
            }

            Storage::disk(self::DISK)->deleteDirectory($this->temporaryDirectory($lockedStudent));

            return $lockedStudent->fresh(['prodi.fakultas']);
        });
    }

    public function reset(Mahasiswa $mahasiswa, User $admin): void
    {
        $oldPath = null;

        DB::transaction(function () use ($mahasiswa, $admin, &$oldPath): void {
            /** @var Mahasiswa $lockedStudent */
            $lockedStudent = Mahasiswa::query()->lockForUpdate()->findOrFail($mahasiswa->getKey());
            $oldPath = $lockedStudent->ktm_photo_path;

            $lockedStudent->update([
                'ktm_photo_path' => null,
                'ktm_photo_uploaded_at' => null,
                'ktm_photo_locked' => false,
                'ktm_photo_reset_at' => now(),
                'ktm_photo_reset_by' => $admin->getKey(),
            ]);
        });

        if ($oldPath) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        $this->cancelTemporary($mahasiswa);
    }

    public function renderCard(Mahasiswa $mahasiswa, string $photoPath): string
    {
        $templatePath = resource_path('templates/ktm/ktm-classic.jpg');

        if (! is_file($templatePath)) {
            throw new RuntimeException('Template KTM belum tersedia.');
        }

        $canvas = @imagecreatefromjpeg($templatePath);
        $photoBytes = Storage::disk(self::DISK)->get($photoPath);
        $photo = @imagecreatefromstring($photoBytes);

        if ($canvas === false || $photo === false) {
            throw new RuntimeException('Template atau foto KTM tidak dapat dibaca.');
        }

        $this->placePhoto($canvas, $photo, 154, 531, 238, 326);
        imagedestroy($photo);

        $ink = imagecolorallocate($canvas, 25, 38, 82);
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');
        $prodi = $mahasiswa->prodi?->nama_prodi ?: '-';
        $fakultas = $mahasiswa->prodi?->fakultas?->nama_fakultas ?: '-';

        $fields = [
            ['NIM', $mahasiswa->nim ?: '-'],
            ['Nama', $mahasiswa->nama ?: '-'],
            ['Program Studi', $prodi],
            ['Fakultas', $fakultas],
            ['Alamat', $mahasiswa->alamat ?: '-'],
        ];

        $y = 550;
        foreach ($fields as [$label, $value]) {
            $lines = $this->wrapText($label.': '.$value, $font, 26, 930);
            foreach ($lines as $line) {
                $this->drawText($canvas, $line, 445, $y, $ink, $font, 26, 930);
                $y += 48;
            }
            $y += 7;
        }

        ob_start();
        imagepng($canvas, null, 7);
        $output = ob_get_clean();
        imagedestroy($canvas);

        if (! is_string($output)) {
            throw new RuntimeException('KTM gagal dirender.');
        }

        return $output;
    }

    public function cleanupTemporary(int $olderThanHours = 24): int
    {
        $disk = Storage::disk(self::DISK);
        $threshold = now()->subHours(max(1, $olderThanHours))->timestamp;
        $deleted = 0;

        foreach ($disk->allFiles('ktm/temp') as $path) {
            if ($disk->lastModified($path) < $threshold && $disk->delete($path)) {
                $deleted++;
            }
        }

        foreach ($disk->allDirectories('ktm/temp') as $directory) {
            if ($disk->files($directory) === [] && $disk->directories($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }

        return $deleted;
    }

    private function temporaryDirectory(Mahasiswa $mahasiswa): string
    {
        return 'ktm/temp/'.$mahasiswa->getKey();
    }

    private function normalizePortrait(\GdImage $source): \GdImage
    {
        $targetWidth = 600;
        $targetHeight = 800;
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $sourceX = (int) round(($sourceWidth - $cropWidth) / 2);
            $sourceY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $sourceX = 0;
            $sourceY = (int) round(($sourceHeight - $cropHeight) / 2);
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $target;
    }

    private function placePhoto(\GdImage $canvas, \GdImage $photo, int $x, int $y, int $width, int $height): void
    {
        imagecopyresampled($canvas, $photo, $x, $y, 0, 0, $width, $height, imagesx($photo), imagesy($photo));
        $border = imagecolorallocate($canvas, 255, 193, 31);
        imagesetthickness($canvas, 5);
        imagerectangle($canvas, $x, $y, $x + $width, $y + $height, $border);
    }

    /** @return array<int, string> */
    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        if (! is_file($font)) {
            return [Str::limit($text, 70)];
        }

        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line.' '.$word);
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = $box ? abs($box[2] - $box[0]) : 0;
            if ($line !== '' && $width > $maxWidth) {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return array_slice($lines, 0, 3);
    }

    private function drawText(\GdImage $canvas, string $text, int $x, int $y, int $color, string $font, int $size, int $maxWidth): void
    {
        if (! is_file($font)) {
            imagestring($canvas, 5, $x, $y - 18, $text, $color);

            return;
        }

        while ($size > 17) {
            $box = imagettfbbox($size, 0, $font, $text);
            if (! $box || abs($box[2] - $box[0]) <= $maxWidth) {
                break;
            }
            $size--;
        }

        imagettftext($canvas, $size, 0, $x, $y, $color, $font, $text);
    }
}
