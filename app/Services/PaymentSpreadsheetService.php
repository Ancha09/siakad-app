<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\TagihanMahasiswa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class PaymentSpreadsheetService
{
    public const HEADINGS = ['nim', 'nama', 'jenis_tagihan', 'deskripsi', 'nominal_pokok', 'biaya_layanan', 'jatuh_tempo', 'boleh_cicil'];

    public function preview(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'File harus workbook XLSX yang valid.']);
        }
        if ($zip->numFiles > 2000) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'Struktur workbook terlalu besar. Gunakan template sederhana.']);
        }
        $size = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $size += $zip->statIndex($i)['size'];
        }
        $zip->close();
        if ($size > 40 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Workbook terlalu besar setelah dibuka. Pecah menjadi beberapa file.']);
        }
        $reader = IOFactory::createReader('Xlsx');
        $info = $reader->listWorksheetInfo($path);
        $limit = (int) config('payments.import_limit');
        if (! $info || $info[0]['totalRows'] > $limit + 1 || $info[0]['totalColumns'] > 20) {
            throw ValidationException::withMessages(['file' => "Maksimal {$limit} baris pada sheet pertama. Gunakan template yang disediakan."]);
        }
        $reader->setLoadSheetsOnly([$info[0]['worksheetName']]);
        $reader->setReadFilter(new class($limit) implements IReadFilter
        {
            public function __construct(private readonly int $limit) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= $this->limit + 1 && Coordinate::columnIndexFromString($columnAddress) <= 20;
            }
        });
        $book = $reader->load($path);
        try {
            $sheet = $book->getSheet(0);
            $headers = [];
            for ($col = 1; $col <= $info[0]['totalColumns']; $col++) {
                $headers[$col] = mb_strtolower(trim((string) $sheet->getCell([$col, 1])->getValue()));
            }
            $required = ['nim', 'jenis_tagihan', 'deskripsi', 'nominal_pokok', 'boleh_cicil'];
            if (array_diff($required, $headers) || count(array_filter($headers)) !== count(array_unique(array_filter($headers)))) {
                throw ValidationException::withMessages(['file' => 'Header tidak lengkap atau ganda. Gunakan template import tagihan.']);
            }
            $rows = [];
            $errors = [];
            $seen = [];
            for ($line = 2; $line <= $info[0]['totalRows']; $line++) {
                $raw = [];
                $formula = false;
                foreach ($headers as $col => $name) {
                    $cell = $sheet->getCell([$col, $line]);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        $formula = true;
                    }
                    if (in_array($name, self::HEADINGS, true)) {
                        $raw[$name] = $cell->getValue();
                    }
                }
                if (! count(array_filter($raw, fn ($value) => $value !== null && $value !== ''))) {
                    continue;
                }
                if ($formula) {
                    $errors[] = "Baris {$line}: rumus Excel tidak diterima; tempel sebagai nilai.";

                    continue;
                }
                $raw = array_map(fn ($value) => is_scalar($value) ? trim((string) $value) : '', $raw);
                $raw['biaya_layanan'] = ($raw['biaya_layanan'] ?? '') !== ''
                    ? $raw['biaya_layanan']
                    : config('payments.default_service_fee');
                $raw['jatuh_tempo'] = ($raw['jatuh_tempo'] ?? '') !== '' ? $raw['jatuh_tempo'] : null;
                if ($raw['jatuh_tempo'] !== null && is_numeric($raw['jatuh_tempo'])) {
                    $raw['jatuh_tempo'] = Date::excelToDateTimeObject((float) $raw['jatuh_tempo'])->format('Y-m-d');
                }
                $raw['boleh_cicil'] = match (strtolower($raw['boleh_cicil'] ?? '')) {
                    '1', 'true' => 1, '0', 'false', '' => 0, default => 'invalid',
                };
                $validator = Validator::make($raw, [
                    'nim' => ['required', 'string', 'max:50'], 'nama' => ['nullable', 'string', 'max:255'],
                    'jenis_tagihan' => ['required', 'string', 'max:100'], 'deskripsi' => ['required', 'string', 'max:2000'],
                    'nominal_pokok' => ['required', 'integer', 'min:'.config('payments.minimum_payment'), 'max:1000000000'],
                    'biaya_layanan' => ['required', 'integer', 'min:0', 'max:1000000000'],
                    'jatuh_tempo' => ['nullable', 'date_format:Y-m-d'], 'boleh_cicil' => ['required', 'boolean'],
                ], ['nominal_pokok.min' => 'Nominal pembayaran minimal Rp600.000.']);
                if ($validator->fails()) {
                    $errors[] = 'Baris '.$line.': '.implode(' ', $validator->errors()->all());

                    continue;
                }
                $student = Mahasiswa::where('nim', $raw['nim'])->first();
                if (! $student) {
                    $errors[] = "Baris {$line}: NIM {$raw['nim']} tidak ditemukan.";

                    continue;
                }
                if (($raw['nama'] ?? '') !== '' && mb_strtolower($raw['nama']) !== mb_strtolower(trim($student->nama))) {
                    $errors[] = "Baris {$line}: nama tidak sesuai master untuk NIM {$raw['nim']}.";

                    continue;
                }
                if ((int) $raw['nominal_pokok'] + (int) $raw['biaya_layanan'] > config('payments.max_amount')) {
                    $errors[] = "Baris {$line}: total tagihan melebihi batas.";

                    continue;
                }
                $row = $validator->validated() + ['mahasiswa_id' => $student->id];
                $fingerprint = StudentBillingService::fingerprint($row);
                $row['duplicate'] = isset($seen[$fingerprint]) || TagihanMahasiswa::where('fingerprint', $fingerprint)->exists();
                $row['nama'] = $student->nama;
                $row['line'] = $line;
                $rows[] = $row;
                $seen[$fingerprint] = true;
            }
            if (! $rows && ! $errors) {
                $errors[] = 'File belum berisi data tagihan.';
            }

            return ['rows' => $rows, 'errors' => $errors, 'duplicates' => count(array_filter($rows, fn ($row) => $row['duplicate']))];
        } finally {
            $book->disconnectWorksheets();
        }
    }

    public function download(string $filename, array $headings, iterable $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('SIAKAD Sandbox');
                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
                $allRows = (function () use ($headings, $rows) {
                    yield $headings;
                    yield from $rows;
                })();
                $line = 0;
                foreach ($allRows as $index => $values) {
                    // Use a separate counter: generators may reuse numeric keys.
                    $line++;
                    foreach (array_values($values) as $column => $value) {
                        $sheet->setCellValueExplicit([$column + 1, $line], $value ?? '', is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                    }
                }
                $last = Coordinate::stringFromColumnIndex(count($headings));
                $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A1:'.$last.'1')->getFill()->setFillType('solid')->getStartColor()->setRGB('123D72');
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:'.$last.($line ?? 1));
                for ($i = 1; $i <= count($headings); $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(23);
                }
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
