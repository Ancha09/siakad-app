<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExporter
{
    /**
     * @param  array<int, array{title:string, headings:array<int, mixed>, rows:iterable<int, array<int, mixed>>}>  $sheets
     */
    public function excel(string $filename, string $title, string $filter, array $sheets): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $spreadsheet->getProperties()
            ->setCreator('SIAKAD STTMI')
            ->setTitle($title)
            ->setSubject($filter);

        foreach ($sheets as $index => $definition) {
            $sheet = $spreadsheet->createSheet($index);
            $sheet->setTitle($this->sheetTitle($definition['title']));
            $headings = array_values($definition['headings']);
            $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($headings)));

            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue('A2', 'Bagian: '.$definition['title']);
            $sheet->mergeCells("A3:{$lastColumn}3");
            $sheet->setCellValue('A3', 'Filter: '.$filter.' | Dibuat: '.now()->format('d-m-Y H:i').' WIB');

            $this->writeRow($sheet, 5, $headings);
            $rowNumber = 6;
            foreach ($definition['rows'] as $row) {
                $this->writeRow($sheet, $rowNumber++, array_values($row));
            }

            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F2A55']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(30);
            $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('1D4ED8');
            $sheet->getStyle("A3:{$lastColumn}3")->getFont()->setSize(9)->getColor()->setRGB('64748B');
            $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3359D8']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getRowDimension(5)->setRowHeight(28);

            if ($rowNumber > 6) {
                $sheet->getStyle("A5:{$lastColumn}".($rowNumber - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D7DDEB');
                $sheet->getStyle("A6:{$lastColumn}".($rowNumber - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            }

            foreach (range(1, count($headings)) as $column) {
                $letter = Coordinate::stringFromColumnIndex($column);
                $sheet->getColumnDimension($letter)->setAutoSize(true);
            }

            $sheet->freezePane('A6');
            $sheet->setAutoFilter("A5:{$lastColumn}5");
            $sheet->setShowGridlines(false);
            $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(.4)->setRight(.3)->setBottom(.4)->setLeft(.3);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    private function writeRow($sheet, int $rowNumber, array $values): void
    {
        foreach ($values as $index => $value) {
            $coordinate = Coordinate::stringFromColumnIndex($index + 1).$rowNumber;

            if (is_int($value) || is_float($value)) {
                $sheet->setCellValue($coordinate, $value);
            } else {
                $sheet->setCellValueExplicit($coordinate, (string) ($value ?? '-'), DataType::TYPE_STRING);
            }
        }
    }

    private function sheetTitle(string $title): string
    {
        $title = preg_replace('~[\\\\/?*\[\]:]~', ' ', $title) ?: 'Laporan';

        return mb_substr(trim($title), 0, 31);
    }
}
