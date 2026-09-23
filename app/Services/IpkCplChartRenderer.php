<?php

namespace App\Services;

final class IpkCplChartRenderer
{
    private const WIDTH = 1200;

    private const HEIGHT = 380;

    /**
     * Membuat grafik statis dari payload ringkas yang juga dipakai Chart.js.
     * PNG ini aman untuk DomPDF dan PhpSpreadsheet serta tidak membutuhkan
     * JavaScript/browser saat proses export.
     */
    public function png(array $chart, string $title): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagepng')) {
            return null;
        }

        $labels = array_values(array_map('strval', $chart['labels'] ?? []));
        $rawValues = array_values($chart['ipk'] ?? []);
        $values = collect($labels)->map(function (string $label, int $index) use ($rawValues) {
            $value = $rawValues[$index] ?? null;

            return is_numeric($value) ? max(0, min(4, (float) $value)) : null;
        })->all();

        if ($labels === []) {
            return null;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($image === false) {
            return null;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = imagecolorallocate($image, 15, 42, 85);
        $blue = imagecolorallocate($image, 51, 89, 216);
        $blueBorder = imagecolorallocate($image, 35, 67, 174);
        $grid = imagecolorallocate($image, 219, 230, 241);
        $muted = imagecolorallocate($image, 100, 116, 139);
        $empty = imagecolorallocate($image, 226, 232, 240);

        imagefilledrectangle($image, 0, 0, self::WIDTH - 1, self::HEIGHT - 1, $white);

        $fontRegular = $this->fontPath('DejaVuSans.ttf');
        $fontBold = $this->fontPath('DejaVuSans-Bold.ttf');
        $this->centeredText($image, $title, 18, self::WIDTH / 2, 31, $navy, $fontBold);

        $left = 74;
        $right = 28;
        $top = 62;
        $bottom = 66;
        $plotWidth = self::WIDTH - $left - $right;
        $plotHeight = self::HEIGHT - $top - $bottom;

        for ($step = 0; $step <= 8; $step++) {
            $value = $step / 2;
            $y = (int) round($top + $plotHeight - (($value / 4) * $plotHeight));
            imageline($image, $left, $y, self::WIDTH - $right, $y, $grid);
            $this->rightText($image, number_format($value, 1), 9, $left - 10, $y + 4, $muted, $fontRegular);
        }

        imageline($image, $left, $top, $left, $top + $plotHeight, $navy);
        imageline($image, $left, $top + $plotHeight, self::WIDTH - $right, $top + $plotHeight, $navy);

        $count = count($labels);
        $slot = $plotWidth / max(1, $count);
        $barWidth = (int) max(28, min(74, $slot * 0.52));
        $hasValue = false;

        foreach ($labels as $index => $label) {
            $centerX = $left + ($slot * $index) + ($slot / 2);
            $value = $values[$index] ?? null;

            if ($value !== null) {
                $hasValue = true;
                $barHeight = (int) round(($value / 4) * $plotHeight);
                $x1 = (int) round($centerX - ($barWidth / 2));
                $x2 = $x1 + $barWidth;
                $y1 = $top + $plotHeight - $barHeight;
                imagefilledrectangle($image, $x1, $y1, $x2, $top + $plotHeight - 1, $blue);
                imagerectangle($image, $x1, $y1, $x2, $top + $plotHeight - 1, $blueBorder);
                $this->centeredText($image, number_format($value, 2), 9, $centerX, max($top + 13, $y1 - 7), $navy, $fontBold);
            } else {
                $x1 = (int) round($centerX - ($barWidth / 2));
                $x2 = $x1 + $barWidth;
                imagefilledrectangle($image, $x1, $top + $plotHeight - 3, $x2, $top + $plotHeight - 1, $empty);
            }

            $this->centeredText($image, $label, 10, $centerX, $top + $plotHeight + 25, $navy, $fontBold);
        }

        if (! $hasValue) {
            $this->centeredText(
                $image,
                'Belum ada data IPK CPL untuk filter ini.',
                13,
                $left + ($plotWidth / 2),
                $top + ($plotHeight / 2),
                $muted,
                $fontRegular
            );
        }

        $this->centeredText($image, 'Skala IPK 0,00 - 4,00', 8, self::WIDTH / 2, self::HEIGHT - 12, $muted, $fontRegular);

        ob_start();
        imagepng($image, null, 6);
        $png = ob_get_clean();
        imagedestroy($image);

        return is_string($png) && $png !== '' ? $png : null;
    }

    public function dataUri(array $chart, string $title): ?string
    {
        $png = $this->png($chart, $title);

        return $png === null ? null : 'data:image/png;base64,'.base64_encode($png);
    }

    private function fontPath(string $filename): ?string
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'dompdf'
            .DIRECTORY_SEPARATOR.'dompdf'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'fonts'
            .DIRECTORY_SEPARATOR.$filename;

        return is_file($path) ? $path : null;
    }

    private function centeredText($image, string $text, int $size, float $centerX, int $baseline, int $color, ?string $font): void
    {
        if ($font !== null && function_exists('imagettftext')) {
            $bounds = imagettfbbox($size, 0, $font, $text);
            $width = is_array($bounds) ? $bounds[2] - $bounds[0] : 0;
            imagettftext($image, $size, 0, (int) round($centerX - ($width / 2)), $baseline, $color, $font, $text);

            return;
        }

        $fontId = 3;
        imagestring($image, $fontId, (int) round($centerX - ((imagefontwidth($fontId) * strlen($text)) / 2)), $baseline - 12, $text, $color);
    }

    private function rightText($image, string $text, int $size, int $rightX, int $baseline, int $color, ?string $font): void
    {
        if ($font !== null && function_exists('imagettftext')) {
            $bounds = imagettfbbox($size, 0, $font, $text);
            $width = is_array($bounds) ? $bounds[2] - $bounds[0] : 0;
            imagettftext($image, $size, 0, $rightX - $width, $baseline, $color, $font, $text);

            return;
        }

        $fontId = 2;
        imagestring($image, $fontId, $rightX - (imagefontwidth($fontId) * strlen($text)), $baseline - 10, $text, $color);
    }
}
