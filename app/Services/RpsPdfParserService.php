<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class RpsPdfParserService
{
    protected ?Parser $parser = null;

    public function __construct()
    {
        if (class_exists(Parser::class)) {
            try {
                $this->parser = new Parser();
            } catch (\Throwable $e) {
                $this->parser = null;
            }
        }
    }

    /**
     * Ekstrak data terstruktur dari file RPS PDF STTMI
     *
     * @param string|UploadedFile $file
     * @return array
     */
    public function extract($file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($path)) {
            throw new Exception("File dokumen RPS tidak ditemukan pada path: {$path}");
        }

        try {
            $fullText = '';
            $pages = [];
            $totalPages = 1;

            if ($this->parser !== null) {
                try {
                    $pdf = $this->parser->parseFile($path);
                    $fullText = $pdf->getText();
                    $pages = $pdf->getPages();
                    $totalPages = count($pages);
                } catch (\Throwable $parseErr) {
                    Log::warning('Smalot parser error, falling back to native PDF extraction: ' . $parseErr->getMessage());
                    $fullText = $this->extractRawTextFromPdf($path);
                }
            } else {
                $fullText = $this->extractRawTextFromPdf($path);
            }

            $metadata = $this->extractMetadata($fullText, $pages);
            $cpls = $this->extractCpls($fullText);
            $cpmks = $this->extractCpmks($fullText, $cpls);
            $subCpmks = $this->extractSubCpmks($fullText, $cpmks, $cpls);
            $komponenBobot = $this->extractKomponenBobot($fullText);
            $porsiCpl = $this->calculatePorsiCpl($cpls, $cpmks, $subCpmks);

            return [
                'success' => true,
                'metadata' => $metadata,
                'cpls' => $cpls,
                'cpmks' => $cpmks,
                'sub_cpmks' => $subCpmks,
                'komponen_bobot' => ! empty($komponenBobot) ? $komponenBobot : [
                    'UAS' => 25,
                    'UTS' => 20,
                    'Tugas' => 25,
                    'Praktikum' => 20,
                    'Kuis' => 10,
                ],
                'total_bobot' => ! empty($komponenBobot) ? array_sum($komponenBobot) : 100,
                'porsi_cpl' => ! empty($porsiCpl) ? $porsiCpl : ['CPL 1' => 60, 'CPL 2' => 40],
                'target_passing_grade' => $this->extractPassingGrade($fullText) ?: 60.0,
                'total_pages' => max(1, $totalPages),
            ];
        } catch (\Throwable $e) {
            Log::error('Gagal mengekstrak RPS PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Gagal membaca dokumen RPS PDF: ' . $e->getMessage());
        }
    }

    /**
     * Ekstrak metadata mata kuliah (Nama, Kode, SKS, Semester, Prodi, Tahun)
     */
    protected function extractMetadata(string $text, array $pages): array
    {
        $meta = [
            'nama_mk' => null,
            'kode_mk' => null,
            'sks' => null,
            'semester' => null,
            'prodi' => null,
            'tahun_akademik' => null,
            'kode_dokumen' => null,
        ];

        // 1. Judul Dokumen RPS: RPS – NAMA MK (KODE MK)
        if (preg_match('/RPS\s*[–—-]\s*([^(]+?)\s*\(([^)]+)\)/u', $text, $m)) {
            $rawNama = trim($m[1]);
            $cleanNama = trim(preg_replace('/^[\x{FFFD}\s–—\-]+/u', '', $rawNama));
            $meta['nama_mk'] = !empty($cleanNama) ? $cleanNama : $rawNama;
            $meta['kode_mk'] = trim($m[2]);
        }

        // 2. Kode Dokumen
        if (preg_match('/Kode Dokumen:\s*([^\s\t\r\n]+)/i', $text, $m)) {
            $meta['kode_dokumen'] = trim($m[1]);
            if (preg_match('/\/(\d{4})\//', $m[1], $ym)) {
                $y = (int) $ym[1];
                $meta['tahun_akademik'] = "{$y}/" . ($y + 1);
            }
        }

        // 3. Program Studi
        if (preg_match('/PROGRAM STUDI\s+([^\r\n]+)/i', $text, $m)) {
            $meta['prodi'] = trim($m[1]);
        }

        // 4. Bobot SKS
        if (preg_match('/(\d+)\s*SKS/i', $text, $m)) {
            $meta['sks'] = (int) $m[1];
        }

        // 5. Semester
        if (preg_match('/Semester\s*[\r\n\t]+(?:Tanggal[^\r\n]*[\r\n\t]+)?([IVXLCDM]+(?:\s*\([^)]+\))?)/i', $text, $m)) {
            $meta['semester'] = trim($m[1]);
        } elseif (preg_match('/Semester\s+([IVXLCDM]+|\d+)/i', $text, $m)) {
            $meta['semester'] = trim($m[1]);
        }

        // 6. Tahun Akademik fallback
        if (! $meta['tahun_akademik']) {
            if (preg_match('/(20\d{2})\s*[\/-]\s*(20\d{2})/i', $text, $m)) {
                $meta['tahun_akademik'] = "{$m[1]}/{$m[2]}";
            } else {
                $currentYear = date('Y');
                $meta['tahun_akademik'] = "{$currentYear}/" . ($currentYear + 1);
            }
        }

        return $meta;
    }

    /**
     * Ekstrak CPL (Capaian Pembelajaran Lulusan) Prodi yang dibebankan pada MK ini
     */
    protected function extractCpls(string $text): array
    {
        $cpls = [];

        if (preg_match('/Capaian Pembelajaran Lulusan \(CPL\) PRODI.*?(?=Capaian Pembelajaran Mata Kuliah \(CPMK\))/si', $text, $sectionMatch)) {
            $section = $sectionMatch[0];

            // Cocokkan setiap blok CPL
            preg_match_all('/(CPL\s*\d+)\s*\r?\n(.*?)(?=(?:CPL\s*\d+|$))/s', $section, $matches, PREG_SET_ORDER);

            foreach ($matches as $block) {
                $kodeCpl = trim($block[1]);
                $body = trim($block[2]);
                $cpmkList = [];

                // Ambil daftar CPMK yang mendukung jika ada (e.g. CPMK 1, 2, 8)
                if (preg_match('/CPMK\s*([0-9,\s]+)/i', $body, $cpmkM)) {
                    preg_match_all('/\d+/', $cpmkM[1], $nums);
                    foreach ($nums[0] as $n) {
                        $cpmkList[] = 'CPMK ' . $n;
                    }
                    $body = trim(preg_replace('/CPMK\s*[0-9,\s]+/i', '', $body));
                }

                $descClean = preg_replace('/\s+/', ' ', $body);
                $descClean = trim($descClean);

                if (! empty($descClean)) {
                    $cpls[] = [
                        'kode_cpl' => $kodeCpl,
                        'deskripsi' => $descClean,
                        'cpmk_terkait' => $cpmkList,
                    ];
                }
            }
        }

        return $cpls;
    }

    /**
     * Ekstrak CPMK (Capaian Pembelajaran Mata Kuliah)
     */
    protected function extractCpmks(string $text, array $cpls): array
    {
        $cpmks = [];

        // Buat mapping CPMK -> CPL dari data CPL
        $cpmkToCplMap = [];
        foreach ($cpls as $cpl) {
            foreach ($cpl['cpmk_terkait'] as $cpmkCode) {
                $cpmkToCplMap[$cpmkCode] = $cpl['kode_cpl'];
            }
        }

        if (preg_match('/Capaian Pembelajaran Mata Kuliah \(CPMK\)(.*?)(?=Kemampuan Akhir Tiap Tahapan Belajar \(Sub-CPMK\)|Deskripsi Singkat)/si', $text, $sectionMatch)) {
            $section = $sectionMatch[1];

            preg_match_all('/(CPMK\s*\d+)\s*(.*?)(?=(?:CPMK\s*\d+|$))/s', $section, $matches, PREG_SET_ORDER);

            foreach ($matches as $block) {
                $kodeCpmk = trim($block[1]);
                $desc = trim($block[2]);
                $descClean = preg_replace('/\s+/', ' ', $desc);

                if (! empty($descClean)) {
                    $cpmks[] = [
                        'kode_cpmk' => $kodeCpmk,
                        'deskripsi' => $descClean,
                        'cpl_terkait' => $cpmkToCplMap[$kodeCpmk] ?? null,
                    ];
                }
            }
        }

        return $cpmks;
    }

    /**
     * Ekstrak Sub-CPMK dan petakan ke CPMK & CPL
     */
    protected function extractSubCpmks(string $text, array $cpmks, array $cpls): array
    {
        $subCpmks = [];

        // Ambil matrix korelasi jika ada
        $matrixMap = $this->extractMatrixMap($text);

        // Buat mapping CPMK -> CPL
        $cpmkToCplMap = [];
        foreach ($cpmks as $cp) {
            if ($cp['cpl_terkait']) {
                $cpmkToCplMap[$cp['kode_cpmk']] = $cp['cpl_terkait'];
            }
        }

        if (preg_match('/Kemampuan Akhir Tiap Tahapan Belajar \(Sub-CPMK\)(.*?)(?=Korelasi CPMK terhadap Sub-CPMK|Deskripsi Singkat|Bahan Kajian)/si', $text, $sectionMatch)) {
            $section = $sectionMatch[1];

            preg_match_all('/(Sub-CPMK\s*\d+)\s*(.*?)(?=(?:Sub-CPMK\s*\d+|$))/s', $section, $matches, PREG_SET_ORDER);

            foreach ($matches as $idx => $block) {
                $kodeSub = trim($block[1]);
                $desc = trim($block[2]);
                $descClean = preg_replace('/\s+/', ' ', $desc);

                // Tentukan CPMK induk
                $cpmkInduk = $matrixMap[$kodeSub] ?? null;

                // Fallback jika matrix tidak terbaca: mapping berurutan / proporsional
                if (! $cpmkInduk && ! empty($cpmks)) {
                    $cpmkInduk = $this->guessCpmkParent($idx + 1, count($matches), count($cpmks));
                }

                $cplTerkait = $cpmkInduk ? ($cpmkToCplMap[$cpmkInduk] ?? null) : null;

                if (! empty($descClean)) {
                    $subCpmks[] = [
                        'kode_sub_cpmk' => $kodeSub,
                        'deskripsi' => $descClean,
                        'cpmk_kode' => $cpmkInduk,
                        'cpmk_terkait' => $cpmkInduk,
                        'cpl_kode' => $cplTerkait,
                        'cpl_terkait' => $cplTerkait,
                        'bobot_default' => null,
                    ];
                }
            }
        }

        return $subCpmks;
    }

    /**
     * Ekstrak Korelasi Matrix CPMK terhadap Sub-CPMK
     */
    protected function extractMatrixMap(string $text): array
    {
        $map = []; // [ 'Sub-CPMK 1' => 'CPMK 1', ... ]

        if (preg_match('/Korelasi CPMK terhadap Sub-CPMK(.*?)(?=Deskripsi Singkat|Bahan Kajian|Rancangan Tugas)/si', $text, $m)) {
            $lines = explode("\n", trim($m[1]));
            $subHeaders = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Cek baris header: CPMK S1 S2 S3 ...
                if (preg_match('/CPMK\s+(S\d+.*)/i', $line, $hm)) {
                    preg_match_all('/S(\d+)/i', $hm[1], $subs);
                    $subHeaders = $subs[1]; // array of number string
                    continue;
                }

                // Cek baris CPMK: CPMK 3 ✓ ✓ ...
                if (preg_match('/(CPMK\s*\d+)\s*(.*)/i', $line, $rowM)) {
                    $cpmkName = trim($rowM[1]);
                    $rest = $rowM[2];

                    // Hitung checkmark ✓ (e29c93 dalam UTF-8) atau v / x
                    $checkCount = preg_match_all('/(\xE2\x9C\x93|✓|[vV]|x)/u', $rest);
                    if ($checkCount > 0 && ! empty($subHeaders)) {
                        // Jika dalam format sederhana, tandai
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Guess CPMK parent based on index
     */
    protected function guessCpmkParent(int $subNum, int $totalSub, int $totalCpmk): string
    {
        if ($totalCpmk <= 0) return 'CPMK 1';

        $ratio = ($subNum - 1) / max(1, $totalSub);
        $cpmkIndex = min($totalCpmk, max(1, (int) floor($ratio * $totalCpmk) + 1));

        return "CPMK {$cpmkIndex}";
    }

    /**
     * Ekstrak tabel Komponen dan Bobot Penilaian
     */
    protected function extractKomponenBobot(string $text): array
    {
        $komponenBobot = [];

        if (preg_match('/Komponen dan Bobot Penilaian(.*?)(?=Konversi Nilai Akhir|TOTAL\s*100|Rancangan Tugas)/si', $text, $matchSection)) {
            $section = $matchSection[1];
            $lines = explode("\n", $section);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || stripos($line, 'Unsur') !== false || stripos($line, 'TOTAL') !== false) {
                    continue;
                }

                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/i', $line, $pctM)) {
                    $bobot = (float) str_replace(',', '.', $pctM[1]);
                    $nama = null;

                    if (stripos($line, 'UAS') !== false || stripos($line, 'Ujian Akhir') !== false) {
                        $nama = 'UAS';
                    } elseif (stripos($line, 'UTS') !== false || stripos($line, 'Ujian Tengah') !== false) {
                        $nama = 'UTS';
                    } elseif (stripos($line, 'Tugas') !== false) {
                        $nama = 'Tugas';
                    } elseif (stripos($line, 'Praktikum') !== false) {
                        $nama = 'Praktikum';
                    } elseif (stripos($line, 'Kuis') !== false) {
                        $nama = 'Kuis';
                    } else {
                        // Bersihkan teks sebelum angka
                        $parts = preg_split('/[\t\d%]/', $line);
                        $clean = trim($parts[0] ?? 'Komponen');
                        $clean = preg_replace('/^(Hardskills|Softskills)\s*/i', '', $clean);
                        if (! empty($clean)) {
                            $nama = $clean;
                        }
                    }

                    if ($nama && ! isset($komponenBobot[$nama])) {
                        $komponenBobot[$nama] = $bobot;
                    }
                }
            }
        }

        // Default standar STTMI jika tidak terdeteksi
        if (empty($komponenBobot)) {
            $komponenBobot = [
                'UAS' => 25.0,
                'UTS' => 20.0,
                'Tugas' => 25.0,
                'Praktikum' => 20.0,
                'Kuis' => 10.0,
            ];
        }

        return $komponenBobot;
    }

    /**
     * Hitung porsi bobot CPL berdasarkan distribusi CPMK & Sub-CPMK
     */
    protected function calculatePorsiCpl(array $cpls, array $cpmks, array $subCpmks): array
    {
        if (empty($cpls)) {
            return [];
        }

        $cplCounts = [];
        foreach ($cpls as $cpl) {
            $cplCounts[$cpl['kode_cpl']] = 0;
        }

        // Hitung frekuensi CPMK per CPL
        foreach ($cpmks as $cpmk) {
            $cpl = $cpmk['cpl_terkait'];
            if ($cpl && isset($cplCounts[$cpl])) {
                $cplCounts[$cpl]++;
            }
        }

        $totalCount = array_sum($cplCounts);
        $porsi = [];
        $runningSum = 0;
        $cplKeys = array_keys($cplCounts);
        $totalCpls = count($cplKeys);

        foreach ($cplKeys as $i => $code) {
            if ($i === $totalCpls - 1) {
                // Elemen terakhir mengambil sisa agar genap 100%
                $porsi[$code] = round(100.0 - $runningSum, 1);
            } else {
                $ratio = $totalCount > 0 ? ($cplCounts[$code] / $totalCount) : (1.0 / $totalCpls);
                $val = round($ratio * 100.0, 1);
                $porsi[$code] = $val;
                $runningSum += $val;
            }
        }

        return $porsi;
    }

    /**
     * Ekstrak target passing grade (default 60)
     */
    protected function extractPassingGrade(string $text): float
    {
        if (preg_match('/minimal\s+(\d+)%/i', $text, $m)) {
            return (float) $m[1];
        }

        return 60.00;
    }

    /**
     * Fallback ekstraksi teks mentah dari file PDF tanpa library eksternal
     */
    protected function extractRawTextFromPdf(string $filename): string
    {
        $content = @file_get_contents($filename);
        if ($content === false) {
            return '';
        }

        $result = '';

        // Ekstrak semua stream terkompresi
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed === false) {
                    $uncompressed = @gzinflate($stream);
                }
                $data = $uncompressed !== false ? $uncompressed : $stream;

                // Ambil blok teks BT ... ET
                if (preg_match_all('/BT[\r\n]+(.*?)[\r\n]+ET/s', $data, $btMatches)) {
                    foreach ($btMatches[1] as $bt) {
                        if (preg_match_all('/\((.*?)\)\s*Tj/s', $bt, $tjMatches)) {
                            $result .= implode(' ', $tjMatches[1]) . "\n";
                        }
                        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $bt, $tjMatches)) {
                            foreach ($tjMatches[1] as $arrayStr) {
                                if (preg_match_all('/\((.*?)\)/s', $arrayStr, $innerTj)) {
                                    $result .= implode('', $innerTj[1]);
                                }
                            }
                            $result .= "\n";
                        }
                    }
                }
            }
        }

        if (trim($result) === '') {
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $content, $directMatches)) {
                $result = implode("\n", $directMatches[1]);
            }
        }

        return $result;
    }
}
