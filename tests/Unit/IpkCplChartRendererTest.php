<?php

use App\Services\IpkCplChartRenderer;
use App\Services\ReportExporter;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

uses(TestCase::class);

test('IPK CPL chart renderer creates a bounded PNG for CPL 1 through CPL 8', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('Ekstensi GD tidak tersedia.');
    }

    $renderer = new IpkCplChartRenderer;
    $png = $renderer->png([
        'labels' => ['CPL 1', 'CPL 2', 'CPL 3', 'CPL 4', 'CPL 5', 'CPL 6', 'CPL 7', 'CPL 8'],
        'ipk' => [3.2, 3.4, null, 2.9, 3.1, 3.8, 3.0, 3.5],
    ], 'Grafik IPK CPL Tahun Akademik 2025/2026');

    expect($png)->not->toBeNull()
        ->and(substr($png, 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(strlen($png))->toBeLessThan(500_000);

    $image = imagecreatefromstring($png);
    expect(imagesx($image))->toBe(1200)
        ->and(imagesy($image))->toBe(380);
    imagedestroy($image);
});

test('the same IPK CPL chart PNG is embedded in Excel and accepted by DomPDF', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('Ekstensi GD tidak tersedia.');
    }

    $renderer = new IpkCplChartRenderer;
    $png = $renderer->png([
        'labels' => ['CPL 1', 'CPL 2', 'CPL 3', 'CPL 4', 'CPL 5', 'CPL 6', 'CPL 7', 'CPL 8'],
        'ipk' => [3.2, 3.4, 3.0, 2.9, 3.1, 3.8, 3.0, 3.5],
    ], 'Grafik IPK CPL Tahun Akademik 2025/2026');

    $response = app(ReportExporter::class)->excel(
        'ipk-cpl-test.xlsx',
        'Laporan IPK CPL Teknik Pertambangan',
        'Tahun Akademik 2025/2026',
        [
            ['title' => 'Grafik IPK CPL', 'image_png' => $png],
            ['title' => 'Ringkasan CPL', 'headings' => ['Kode CPL', 'IPK CPL'], 'rows' => [['CPL 1', 3.2]]],
        ]
    );

    ob_start();
    $response->sendContent();
    $xlsx = ob_get_clean();
    $temporaryFile = tempnam(sys_get_temp_dir(), 'ipk-cpl-chart-');
    file_put_contents($temporaryFile, $xlsx);

    try {
        $workbook = IOFactory::load($temporaryFile);
        expect($workbook->getSheetCount())->toBe(2)
            ->and($workbook->getSheet(0)->getTitle())->toBe('Grafik IPK CPL')
            ->and($workbook->getSheet(0)->getDrawingCollection())->toHaveCount(1)
            ->and($workbook->getSheet(1)->getCell('A5')->getValue())->toBe('Kode CPL');
        $workbook->disconnectWorksheets();
    } finally {
        @unlink($temporaryFile);
    }

    $dompdf = new Dompdf;
    $dompdf->loadHtml('<html><body><img style="width:700px" src="data:image/png;base64,'.base64_encode($png).'"></body></html>');
    $dompdf->render();
    expect(substr($dompdf->output(), 0, 4))->toBe('%PDF');
});
