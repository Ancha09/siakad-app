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
     * Ekstrak data terstruktur secara universal dari file RPS PDF STTMI
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
            $rubrikLabRatios = $this->extractRubrikLab($fullText);
            $cpmks = $this->extractCpmks($fullText, $cpls);
            $komponenBobot = $this->extractKomponenBobot($fullText);
            $subCpmks = $this->extractSubCpmks($fullText, $cpmks, $cpls, $komponenBobot, $rubrikLabRatios);
            $weeklyMeta = $this->enrichSubCpmksWithWeeklyPlan($fullText, $subCpmks, $komponenBobot, $cpmks);
            $porsiCpl = $this->calculatePorsiCpl($cpls, $cpmks, $subCpmks, $komponenBobot, $rubrikLabRatios, $weeklyMeta);

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
                'rubrik_lab_cpl' => $rubrikLabRatios,
                'weekly_meta' => $weeklyMeta,
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
            'semester_int' => 1,
            'prodi' => null,
            'tahun_akademik' => null,
            'kode_dokumen' => null,
        ];

        // 1. Judul Dokumen RPS: RPS – NAMA MK (KODE MK)
        if (preg_match('/RPS\s*[–—\-]\s*([^(]+?)\s*\(([^)]+)\)/u', $text, $m)) {
            $rawNama = trim($m[1]);
            $cleanNama = trim(preg_replace('/^[\x{FFFD}\s–—\-]+/u', '', $rawNama));
            $meta['nama_mk'] = ! empty($cleanNama) ? $cleanNama : $rawNama;
            $meta['kode_mk'] = trim($m[2]);
        } elseif (preg_match('/Mata Kuliah\s*\/\s*Kode\s+([^\r\n\/]+?)\s*\/\s*([A-Z]{2,4}\s*\d{3})/i', $text, $m)) {
            $meta['nama_mk'] = trim($m[1]);
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
        } elseif (preg_match('/SKS\s*\/\s*Semester\s+\d+\s*SKS\s*\/\s*([IVXLCDM]+(?:\s*\([^)]+\))?|\d+)/i', $text, $m)) {
            $meta['semester'] = trim($m[1]);
        } elseif (preg_match('/Semester\s+([IVXLCDM]+|\d+)/i', $text, $m)) {
            $meta['semester'] = trim($m[1]);
        }

        if ($meta['semester']) {
            $meta['semester_int'] = $this->parseSemesterInt($meta['semester']);
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
     * Konversi teks semester (mis: "I (Satu)", "V (Lima)", "3") ke integer
     */
    public function parseSemesterInt(?string $semStr): int
    {
        if (! $semStr) {
            return 1;
        }
        if (preg_match('/\b(\d+)\b/', $semStr, $m)) {
            return max(1, (int) $m[1]);
        }
        $romanMap = [
            'VIII' => 8, 'VII' => 7, 'VI' => 6, 'IV' => 4,
            'V' => 5, 'III' => 3, 'II' => 2, 'I' => 1,
        ];
        foreach ($romanMap as $rom => $val) {
            if (preg_match('/\b' . $rom . '\b/i', $semStr)) {
                return $val;
            }
        }
        return 1;
    }

    /**
     * Ekstrak CPL (Capaian Pembelajaran Lulusan) Prodi yang dibebankan pada MK ini
     */
    protected function extractCpls(string $text): array
    {
        $cpls = [];

        if (preg_match('/Capaian Pembelajaran Lulusan \(CPL\) PRODI.*?(?=Capaian Pembelajaran Mata Kuliah \(CPMK\))/si', $text, $sectionMatch)) {
            $section = $sectionMatch[0];

            preg_match_all('/(CPL\s*\d+)\s*\r?\n(.*?)(?=(?:CPL\s*\d+|$))/s', $section, $matches, PREG_SET_ORDER);

            foreach ($matches as $block) {
                $kodeCpl = preg_replace('/\s+/', ' ', strtoupper(trim($block[1])));
                $body = trim($block[2]);
                $cpmkList = [];

                if (preg_match('/CPMK\s*([0-9,\s–\-dan]+)/i', $body, $cpmkM)) {
                    preg_match_all('/\d+/', $cpmkM[1], $nums);
                    foreach ($nums[0] as $n) {
                        $cpmkList[] = 'CPMK ' . $n;
                    }
                    $body = trim(preg_replace('/CPMK\s*[0-9,\s–\-dan]+/i', '', $body));
                }

                $descClean = trim(preg_replace('/\s+/', ' ', $body));

                if (! empty($descClean)) {
                    $cpls[] = [
                        'kode_cpl' => $kodeCpl,
                        'deskripsi' => $descClean,
                        'cpmk_terkait' => array_values(array_unique($cpmkList)),
                    ];
                }
            }
        }

        return $cpls;
    }

    /**
     * Ekstrak Rubrik Penilaian Laboratorium (jika ada) -> rasio CPL (misal: CPL 1: 0.55, CPL 3: 0.30, CPL 5: 0.15)
     */
    protected function extractRubrikLab(string $text): array
    {
        $ratios = [];

        if (preg_match('/Rubrik Penilaian Laboratorium(.*?)(?=Komponen dan Bobot Penilaian|Konversi Nilai Akhir|Rancangan Tugas|$)/si', $text, $rubMatch)) {
            $rubSec = $rubMatch[1];
            if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*%\s*(?:[\r\n\t\s]+)(CPL\s*\d+)/i', $rubSec, $rm, PREG_SET_ORDER)) {
                $rawSum = 0.0;
                $rawMap = [];
                foreach ($rm as $rItem) {
                    $pct = (float) str_replace(',', '.', $rItem[1]);
                    $cplK = preg_replace('/\s+/', ' ', strtoupper(trim($rItem[2])));
                    $rawMap[$cplK] = ($rawMap[$cplK] ?? 0.0) + $pct;
                    $rawSum += $pct;
                }
                if ($rawSum > 0) {
                    foreach ($rawMap as $k => $v) {
                        $ratios[$k] = round($v / $rawSum, 4);
                    }
                }
            }
        }

        return $ratios;
    }

    /**
     * Ekstrak CPMK (Capaian Pembelajaran Mata Kuliah)
     */
    protected function extractCpmks(string $text, array $cpls): array
    {
        $cpmks = [];

        // Petakan setiap CPMK ke seluruh CPL yang mendukungnya (bukan menimpa 1-to-1)
        $cpmkToCplsMap = [];
        $cplSpecificity = [];
        foreach ($cpls as $cpl) {
            $code = $cpl['kode_cpl'];
            $cplSpecificity[$code] = count($cpl['cpmk_terkait']) ?: 99;
            foreach ($cpl['cpmk_terkait'] as $cpmkCode) {
                $cpmkToCplsMap[$cpmkCode][] = $code;
            }
        }

        if (preg_match('/Capaian Pembelajaran Mata Kuliah \(CPMK\)(.*?)(?=Kemampuan Akhir Tiap Tahapan Belajar \(Sub-CPMK\)|Deskripsi Singkat)/si', $text, $sectionMatch)) {
            $section = $sectionMatch[1];

            preg_match_all('/(CPMK\s*\d+)\s*(.*?)(?=(?:CPMK\s*\d+|$))/s', $section, $matches, PREG_SET_ORDER);

            foreach ($matches as $block) {
                $kodeCpmk = preg_replace('/\s+/', ' ', strtoupper(trim($block[1])));
                $descClean = trim(preg_replace('/\s+/', ' ', $block[2]));

                if (! empty($descClean)) {
                    $relatedCpls = array_values(array_unique($cpmkToCplsMap[$kodeCpmk] ?? []));
                    // Pilih CPL utama: jika ada CPL yang khusus menunjuk CPMK ini saja (spesifisitas lebih tinggi), prioritaskan untuk label tunggal, tetapi simpan semua di cpls_terkait
                    $primaryCpl = $relatedCpls[0] ?? null;
                    if (count($relatedCpls) > 1) {
                        $sorted = $relatedCpls;
                        usort($sorted, fn ($a, $b) => ($cplSpecificity[$a] ?? 99) <=> ($cplSpecificity[$b] ?? 99));
                        $primaryCpl = $sorted[0];
                    }

                    $cpmks[] = [
                        'kode_cpmk' => $kodeCpmk,
                        'deskripsi' => $descClean,
                        'cpl_terkait' => $primaryCpl,
                        'cpls_terkait' => $relatedCpls,
                    ];
                }
            }
        }

        return $cpmks;
    }

    /**
     * Ekstrak Sub-CPMK (termasuk Sub-CPMK Lab / Praktikum) dan petakan ke CPMK & CPL
     */
    protected function extractSubCpmks(string $text, array $cpmks, array $cpls, array $komponenBobot = [], array $rubrikLabRatios = []): array
    {
        $subCpmks = [];

        if (preg_match('/Kemampuan Akhir Tiap Tahapan Belajar \(Sub-CPMK\)(.*?)(?=Korelasi CPMK terhadap Sub-CPMK|Deskripsi Singkat|Bahan Kajian)/si', $text, $sectionMatch)) {
            $section = $sectionMatch[1];

            preg_match_all('/(Sub-CPMK\s*(?:\d+|Lab(?:\s*\d+)?|Praktikum(?:\s*\d+)?))\s*(.*?)(?=(?:Sub-CPMK\s*(?:\d+|Lab|Praktikum)|$))/si', $section, $matches, PREG_SET_ORDER);

            $subCodes = [];
            foreach ($matches as $m) {
                $subCodes[] = $this->normalizeSubCpmkCode($m[1]);
            }

            // Ambil matrix korelasi dari tabel checkmark PDF
            $matrixMap = $this->extractMatrixMap($text, $subCodes, $cpmks);

            // Buat lookup CPMK
            $cpmkLookup = [];
            foreach ($cpmks as $cp) {
                $cpmkLookup[$cp['kode_cpmk']] = $cp;
            }

            foreach ($matches as $idx => $block) {
                $kodeSub = $this->normalizeSubCpmkCode($block[1]);
                $descClean = trim(preg_replace('/\s+/', ' ', $block[2]));

                // Tentukan CPMK induk dari tabel korelasi
                $cpmkInduk = $matrixMap[$kodeSub] ?? null;

                if (! $cpmkInduk && ! empty($cpmks)) {
                    $cpmkInduk = $this->guessCpmkParent($idx + 1, count($matches), count($cpmks));
                }

                $cpmkObj = $cpmkInduk ? ($cpmkLookup[$cpmkInduk] ?? null) : null;
                $cplTerkait = $cpmkObj['cpl_terkait'] ?? null;
                $cplsTerkait = $cpmkObj['cpls_terkait'] ?? ($cplTerkait ? [$cplTerkait] : []);

                $isLabSub = stripos($kodeSub, 'lab') !== false || stripos($kodeSub, 'praktikum') !== false;
                $bobotDefault = null;
                if ($isLabSub && isset($komponenBobot['Praktikum'])) {
                    $bobotDefault = (float) $komponenBobot['Praktikum'];
                }

                if (! empty($descClean)) {
                    $subCpmks[] = [
                        'kode_sub_cpmk' => $kodeSub,
                        'deskripsi' => $descClean,
                        'cpmk_kode' => $cpmkInduk,
                        'cpmk_terkait' => $cpmkInduk,
                        'cpl_kode' => $cplTerkait,
                        'cpl_terkait' => $cplTerkait,
                        'cpls_terkait' => $cplsTerkait,
                        'bobot_default' => $bobotDefault,
                    ];
                }
            }
        }

        return $subCpmks;
    }

    /**
     * Normalisasi penulisan kode Sub-CPMK (mis: "Sub-CPMK 1", "Sub-CPMK Lab")
     */
    protected function normalizeSubCpmkCode(string $raw): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($raw));
        if (preg_match('/^Sub-CPMK\s*(\d+)$/i', $clean, $m)) {
            return 'Sub-CPMK ' . $m[1];
        }
        if (preg_match('/^Sub-CPMK\s*Lab/i', $clean)) {
            return 'Sub-CPMK Lab';
        }
        return $clean;
    }

    /**
     * Ekstrak Korelasi Matrix CPMK terhadap Sub-CPMK secara akurat dari tanda centang (✓)
     */
    protected function extractMatrixMap(string $text, array $subCodes, array $cpmks = []): array
    {
        $map = [];
        $totalSubs = count($subCodes);
        if ($totalSubs === 0) {
            return $map;
        }

        if (preg_match('/Korelasi CPMK terhadap Sub-CPMK(.*?)(?=Deskripsi Singkat|Bahan Kajian|Rancangan Tugas)/si', $text, $m)) {
            $lines = explode("\n", trim($m[1]));
            $rowChecks = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Lewati baris header: CPMK S1 S2 S3 ...
                if (preg_match('/^CPMK\s+S1\b/i', $line)) {
                    continue;
                }

                if (preg_match('/^(CPMK\s*\d+)\b(.*)$/i', $line, $rowM)) {
                    $cpmkName = preg_replace('/\s+/', ' ', strtoupper(trim($rowM[1])));
                    $rest = $rowM[2];

                    $checkCount = preg_match_all('/(\xE2\x9C\x93|✓|√)/u', $rest);
                    if ($checkCount > 0) {
                        $rowChecks[] = [
                            'cpmk' => $cpmkName,
                            'count' => $checkCount,
                        ];
                    }
                }
            }

            $totalCheckmarks = array_sum(array_column($rowChecks, 'count'));

            // Jika jumlah tanda centang melebihi jumlah Sub-CPMK (misal ada 1 baris CPMK praktikum lintas-minggu tanpa kolom SL khusus),
            // cari apakah mengecualikan baris CPMK lintas-minggu tersebut menghasilkan jumlah centang yang pas dengan $totalSubs
            if ($totalCheckmarks > $totalSubs && count($rowChecks) > 1) {
                $diff = $totalCheckmarks - $totalSubs;
                $filteredRows = [];
                $skipped = false;

                // Prioritaskan melewati CPMK laboratorium lintas-minggu yang memiliki jumlah centang == $diff
                for ($i = count($rowChecks) - 1; $i >= 0; $i--) {
                    if (! $skipped && $rowChecks[$i]['count'] === $diff && $i > 0) {
                        $skipped = true;
                        continue;
                    }
                    $filteredRows[] = $rowChecks[$i];
                }
                if ($skipped) {
                    $rowChecks = array_reverse($filteredRows);
                }
            }

            $cursor = 0;
            foreach ($rowChecks as $rc) {
                for ($c = 0; $c < $rc['count']; $c++) {
                    if (isset($subCodes[$cursor])) {
                        $map[$subCodes[$cursor]] = $rc['cpmk'];
                        $cursor++;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Ekstrak Rincian Pembelajaran Tiap Pertemuan (16 Minggu) dan perkaya Sub-CPMK dengan bobot & jenis penilaian
     */
    protected function enrichSubCpmksWithWeeklyPlan(string $text, array &$subCpmks, array $komponenBobot, array $cpmks = []): array
    {
        $weeklyMeta = [
            'uts_bobot' => (float) ($komponenBobot['UTS'] ?? 20.0),
            'uas_bobot' => (float) ($komponenBobot['UAS'] ?? 25.0),
            'praktikum_bobot' => (float) ($komponenBobot['Praktikum'] ?? 0.0),
            'meetings' => [],
        ];

        if (! preg_match('/Rincian Pembelajaran Tiap Pertemuan.*?(?=Rincian Praktikum|Rubrik Penilaian Laboratorium|Komponen dan Bobot Penilaian)/si', $text, $m)) {
            return $weeklyMeta;
        }

        $sec = $m[0];
        // Bersihkan header tabel yang berulang di pergantian halaman PDF
        $sec = preg_replace('/Per-\s*temu\s*an\s*ke-.*?Indikator\s+Kriteria\s*&\s*Teknik\s+Luring\s+Daring\s*/si', "\n", $sec);

        preg_match_all('/(?:^|\n)\s*(\d{1,2})\s*(?=\r?\n|\s*(?:Mahasiswa\s+mampu|Review\s+materi|UJIAN\s+TENGAH|EVALUASI\s+TENGAH|UJIAN\s+AKHIR|EVALUASI\s+AKHIR))(.*?)(?=(?:\n\s*\d{1,2}\s*(?:\r?\n|\s*(?:Mahasiswa\s+mampu|Review\s+materi|UJIAN\s+TENGAH|EVALUASI\s+TENGAH|UJIAN\s+AKHIR|EVALUASI\s+AKHIR)))|$)/si', $sec, $rows, PREG_SET_ORDER);

        $activeMeetings = [];
        foreach ($rows as $r) {
            $mg = (int) trim($r[1]);
            $body = trim($r[2]);
            $bobot = 0.0;

            if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*%/', $body, $pm)) {
                foreach (array_reverse($pm[1]) as $cand) {
                    $val = (float) str_replace(',', '.', $cand);
                    if (abs($val - 60.0) > 0.01) {
                        $bobot = $val;
                        break;
                    }
                }
            }

            $isReview = (bool) preg_match('/^Review\s+materi/i', $body);
            $isUts = ($mg === 8 || preg_match('/(?:UJIAN|EVALUASI)\s+TENGAH\s+SEMESTER/i', $body));
            $isUas = ($mg === 16 || preg_match('/(?:UJIAN|EVALUASI)\s+AKHIR\s+SEMESTER/i', $body));

            $jenis = 'Tugas terstruktur';
            if ($isUts) {
                $jenis = 'UTS';
                if ($bobot > 0) {
                    $weeklyMeta['uts_bobot'] = $bobot;
                }
            } elseif ($isUas) {
                $jenis = 'UAS';
                if ($bobot > 0) {
                    $weeklyMeta['uas_bobot'] = $bobot;
                }
            } elseif ($isReview) {
                $jenis = 'Review';
            } elseif (preg_match('/Proyek|Presentasi\s*\+\s*diskusi;\s*rubrik\s*presentasi/i', $body)) {
                $jenis = 'Proyek kelompok';
            } elseif (preg_match('/Laporan\s+praktikum/i', $body)) {
                $jenis = 'Laporan praktikum';
            } elseif (preg_match('/studi\s+kasus/i', $body)) {
                $jenis = 'Tugas studi kasus';
            } elseif (preg_match('/Kuis/i', $body)) {
                $jenis = 'Kuis';
            }

            $meetingItem = [
                'minggu' => $mg,
                'jenis' => $jenis,
                'bobot' => $bobot,
                'is_uts' => $isUts,
                'is_uas' => $isUas,
                'is_review' => $isReview,
                'paruh' => $mg < 8 ? 1 : 2,
                'sub_count' => max(1, preg_match_all('/Mahasiswa\s+mampu/i', $body)),
            ];
            $weeklyMeta['meetings'][] = $meetingItem;

            if (! $isUts && ! $isUas && ! $isReview) {
                $activeMeetings[] = $meetingItem;
            }
        }

        // Petakan activeMeetings ke theory Sub-CPMKs (non-Lab)
        $theoryIndices = [];
        foreach ($subCpmks as $idx => $sub) {
            $isLab = stripos($sub['kode_sub_cpmk'], 'lab') !== false || stripos($sub['kode_sub_cpmk'], 'praktikum') !== false;
            if ($isLab) {
                $subCpmks[$idx]['paruh'] = 'lab';
                $subCpmks[$idx]['jenis_penilaian'] = 'Praktikum laboratorium';
                $subCpmks[$idx]['pertemuan'] = '1-16';
                if (empty($subCpmks[$idx]['bobot_default']) && $weeklyMeta['praktikum_bobot'] > 0) {
                    $subCpmks[$idx]['bobot_default'] = $weeklyMeta['praktikum_bobot'];
                }
            } else {
                $theoryIndices[] = $idx;
            }
        }

        $theoryCount = count($theoryIndices);
        $meetingCount = count($activeMeetings);

        if ($theoryCount > 0 && $meetingCount > 0) {
            if ($theoryCount === $meetingCount) {
                // Pemetaan 1-ke-1 langsung (misal 13 pertemuan aktif -> Sub-CPMK 1 s.d. Sub-CPMK 13)
                foreach ($theoryIndices as $i => $subIdx) {
                    $mItem = $activeMeetings[$i];
                    $subCpmks[$subIdx]['bobot_default'] = $mItem['bobot'];
                    $subCpmks[$subIdx]['jenis_penilaian'] = $mItem['jenis'];
                    $subCpmks[$subIdx]['pertemuan'] = (string) $mItem['minggu'];
                    $subCpmks[$subIdx]['paruh'] = $mItem['paruh'];
                }
            } else {
                // Jika ada pertemuan yang memuat 2 Sub-CPMK sekaligus (misal P14 memuat Sub-CPMK 12 & 13)
                $tCursor = 0;
                foreach ($activeMeetings as $mIdx => $mItem) {
                    $remainingMeetings = $meetingCount - $mIdx;
                    $remainingSubs = $theoryCount - $tCursor;
                    $assignCount = ($remainingSubs > $remainingMeetings && ($mItem['sub_count'] > 1 || $remainingMeetings === 1))
                        ? min($mItem['sub_count'], $remainingSubs - $remainingMeetings + 1)
                        : 1;
                    $splitWeight = round($mItem['bobot'] / max(1, $assignCount), 2);

                    for ($c = 0; $c < $assignCount && $tCursor < $theoryCount; $c++) {
                        $subIdx = $theoryIndices[$tCursor++];
                        $subCpmks[$subIdx]['bobot_default'] = $splitWeight;
                        $subCpmks[$subIdx]['jenis_penilaian'] = $mItem['jenis'];
                        $subCpmks[$subIdx]['pertemuan'] = (string) $mItem['minggu'];
                        $subCpmks[$subIdx]['paruh'] = $mItem['paruh'];
                    }
                }
            }
        }

        // Pastikan jika ada CPMK praktikum lintas-minggu (misal CPMK 10 -> CPL 6 pada RPS tanpa Sub-CPMK Lab terpisah),
        // Sub-CPMK yang berjenis 'Laporan praktikum' ditautkan ke CPMK/CPL tersebut agar seluruh CPL terwakili
        $mappedCpls = array_unique(array_filter(array_column($subCpmks, 'cpl_kode')));
        $unmappedLabCpmks = [];
        foreach ($cpmks as $cp) {
            if (! empty($cp['cpl_terkait']) && ! in_array($cp['cpl_terkait'], $mappedCpls, true)) {
                $unmappedLabCpmks[] = $cp;
            }
        }

        if (! empty($unmappedLabCpmks)) {
            $targetCp = $unmappedLabCpmks[0];
            foreach ($subCpmks as $idx => $sub) {
                if (($sub['jenis_penilaian'] ?? '') === 'Laporan praktikum') {
                    $subCpmks[$idx]['cpmk_kode'] = $targetCp['kode_cpmk'];
                    $subCpmks[$idx]['cpmk_terkait'] = $targetCp['kode_cpmk'];
                    $subCpmks[$idx]['cpl_kode'] = $targetCp['cpl_terkait'];
                    $subCpmks[$idx]['cpl_terkait'] = $targetCp['cpl_terkait'];
                    $subCpmks[$idx]['cpls_terkait'] = $targetCp['cpls_terkait'] ?? [$targetCp['cpl_terkait']];
                    break;
                }
            }
        }

        return $weeklyMeta;
    }

    /**
     * Guess CPMK parent based on index
     */
    protected function guessCpmkParent(int $subNum, int $totalSub, int $totalCpmk): string
    {
        if ($totalCpmk <= 0) {
            return 'CPMK 1';
        }

        $ratio = ($subNum - 1) / max(1, $totalSub);
        $cpmkIndex = min($totalCpmk, max(1, (int) floor($ratio * $totalCpmk) + 1));

        return "CPMK {$cpmkIndex}";
    }

    /**
     * Ekstrak tabel Komponen dan Bobot Penilaian (mendukung baris multi-line PDF)
     */
    protected function extractKomponenBobot(string $text): array
    {
        $komponenBobot = [];

        if (preg_match('/Komponen dan Bobot Penilaian(.*?)(?=Konversi Nilai Akhir|TOTAL\s*100|Rancangan Tugas)/si', $text, $matchSection)) {
            $section = $matchSection[1];
            $lines = explode("\n", $section);
            $buffer = '';

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || stripos($line, 'Unsur') !== false || stripos($line, 'TOTAL') !== false) {
                    continue;
                }

                // Abaikan baris keterangan deskriptif setelah persentase
                if (preg_match('/^(Akumulasi|Rata-rata|Diambil dari|Setara)/i', $line) && ! str_contains($line, '%')) {
                    continue;
                }

                $buffer = trim($buffer . ' ' . $line);

                if (preg_match('/(\d+(?:[.,]\d+)?)\s*%/i', $buffer, $pctM)) {
                    $bobot = (float) str_replace(',', '.', $pctM[1]);
                    $nama = null;

                    if (stripos($buffer, 'UAS') !== false || stripos($buffer, 'Ujian Akhir') !== false) {
                        $nama = 'UAS';
                    } elseif (stripos($buffer, 'UTS') !== false || stripos($buffer, 'Ujian Tengah') !== false) {
                        $nama = 'UTS';
                    } elseif (stripos($buffer, 'Praktikum') !== false) {
                        $nama = 'Praktikum';
                    } elseif (stripos($buffer, 'Tugas') !== false) {
                        $nama = 'Tugas';
                    } elseif (stripos($buffer, 'Kuis') !== false) {
                        $nama = 'Kuis';
                    } else {
                        $parts = preg_split('/[\t\d%]/', $buffer);
                        $clean = trim($parts[0] ?? 'Komponen');
                        $clean = preg_replace('/^(Hardskills(?:\s*\+\s*Softskills)?|Softskills)\s*/i', '', $clean);
                        if (! empty($clean)) {
                            $nama = $clean;
                        }
                    }

                    if ($nama && ! isset($komponenBobot[$nama])) {
                        $komponenBobot[$nama] = $bobot;
                    }

                    $buffer = '';
                }
            }
        }

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
     * Hitung porsi bobot CPL secara presisi berdasarkan bobot aktual RPS (Mingguan + UTS + UAS + Rubrik Lab)
     * sehingga 100% sinkron dengan Matriks Alokasi Bobot Instrumen OBE.
     */
    protected function calculatePorsiCpl(
        array $cpls,
        array $cpmks,
        array $subCpmks,
        array $komponenBobot = [],
        array $rubrikLabRatios = [],
        array $weeklyMeta = []
    ): array {
        if (empty($cpls)) {
            return [];
        }

        $cplWeights = [];
        foreach ($cpls as $cpl) {
            $cplWeights[$cpl['kode_cpl']] = 0.0;
        }

        $hasLabSub = false;
        $paruh1Subs = [];
        $paruh2Subs = [];

        foreach ($subCpmks as $idx => $sub) {
            $paruh = $sub['paruh'] ?? null;
            $isLab = ($paruh === 'lab') || stripos($sub['kode_sub_cpmk'], 'lab') !== false || stripos($sub['kode_sub_cpmk'], 'praktikum') !== false;
            if ($isLab) {
                $hasLabSub = true;
            } elseif ($paruh == 1) {
                $paruh1Subs[] = $idx;
            } elseif ($paruh == 2) {
                $paruh2Subs[] = $idx;
            }
        }

        // Jika paruh belum terpetakan dari tabel 16 minggu, bagi dua Sub-CPMK non-Lab
        if (empty($paruh1Subs) && empty($paruh2Subs) && ! empty($subCpmks)) {
            $nonLabIndices = [];
            foreach ($subCpmks as $idx => $sub) {
                $isLab = stripos($sub['kode_sub_cpmk'], 'lab') !== false || stripos($sub['kode_sub_cpmk'], 'praktikum') !== false;
                if (! $isLab) {
                    $nonLabIndices[] = $idx;
                }
            }
            $half = max(1, (int) floor(count($nonLabIndices) / 2));
            $paruh1Subs = array_slice($nonLabIndices, 0, $half);
            $paruh2Subs = array_slice($nonLabIndices, $half);
        }

        $utsWeight = (float) ($weeklyMeta['uts_bobot'] ?? ($komponenBobot['UTS'] ?? 20.0));
        $uasWeight = (float) ($weeklyMeta['uas_bobot'] ?? ($komponenBobot['UAS'] ?? 25.0));

        $utsAlloc = $this->distributeWeightClean($paruh1Subs, $utsWeight);
        $uasAlloc = $this->distributeWeightClean($paruh2Subs, $uasWeight);

        // Hitung total bobot per Sub-CPMK (mingguan + UTS + UAS) dan distribusikan ke CPL
        foreach ($subCpmks as $idx => $sub) {
            $isLab = ($sub['paruh'] ?? null) === 'lab' || stripos($sub['kode_sub_cpmk'], 'lab') !== false || stripos($sub['kode_sub_cpmk'], 'praktikum') !== false;
            $weeklyW = (float) ($sub['bobot_default'] ?? 0.0);
            $utsW = (float) ($utsAlloc[$idx] ?? 0.0);
            $uasW = (float) ($uasAlloc[$idx] ?? 0.0);
            $totalSubWeight = $isLab ? $weeklyW : ($weeklyW + $utsW + $uasW);

            if ($totalSubWeight <= 0) {
                continue;
            }

            // Jika ini Sub-CPMK Lab dan ada Rubrik Penilaian Laboratorium (mis: CPL 1: 55%, CPL 3: 30%, CPL 5: 15%)
            if ($isLab && ! empty($rubrikLabRatios)) {
                foreach ($rubrikLabRatios as $cplCode => $ratio) {
                    if (! isset($cplWeights[$cplCode])) {
                        $cplWeights[$cplCode] = 0.0;
                    }
                    $cplWeights[$cplCode] += round($totalSubWeight * $ratio, 2);
                }
            } elseif (! empty($sub['cpls_terkait']) && count($sub['cpls_terkait']) > 1) {
                // Jika Sub-CPMK terkait ke beberapa CPL tanpa tabel rubrik lab, bagi rata ke CPL terkait
                $share = $totalSubWeight / count($sub['cpls_terkait']);
                foreach ($sub['cpls_terkait'] as $cplCode) {
                    $cplWeights[$cplCode] = ($cplWeights[$cplCode] ?? 0.0) + round($share, 2);
                }
            } else {
                $cplCode = $sub['cpl_kode'] ?? ($sub['cpl_terkait'] ?? null);
                if ($cplCode && isset($cplWeights[$cplCode])) {
                    $cplWeights[$cplCode] += $totalSubWeight;
                }
            }
        }

        $totalCalculated = array_sum($cplWeights);

        // Jika hasil akumulasi bobot valid (> 0), normalisasikan ke 100%
        if ($totalCalculated > 0) {
            $porsi = [];
            $runningSum = 0.0;
            $keys = array_keys($cplWeights);
            $lastIdx = count($keys) - 1;

            foreach ($keys as $i => $code) {
                if ($i === $lastIdx) {
                    $porsi[$code] = round(100.0 - $runningSum, 2);
                } else {
                    $val = round(($cplWeights[$code] / $totalCalculated) * 100.0, 2);
                    $porsi[$code] = $val;
                    $runningSum += $val;
                }
            }

            // Pastikan tidak ada CPL yang tercantum di RPS bernilai 0%
            $zeroKeys = array_keys(array_filter($porsi, fn ($v) => $v <= 0));
            if (empty($zeroKeys)) {
                return $porsi;
            }
        }

        // Fallback proporsional berbasis frekuensi CPMK (tanpa menimpa CPL yang berbagi CPMK)
        $cplCounts = [];
        foreach ($cpls as $cpl) {
            $cplCounts[$cpl['kode_cpl']] = max(1, count($cpl['cpmk_terkait']));
        }

        $totalCount = array_sum($cplCounts);
        $porsi = [];
        $runningSum = 0.0;
        $cplKeys = array_keys($cplCounts);
        $totalCpls = count($cplKeys);

        foreach ($cplKeys as $i => $code) {
            if ($i === $totalCpls - 1) {
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
     * Bagi bobot (misal UTS 20% atau UAS 25%) secara merata dengan kelipatan 0.5
     */
    public function distributeWeightClean(array $keys, float $totalWeight): array
    {
        $count = count($keys);
        if ($count === 0 || $totalWeight <= 0) {
            return [];
        }

        $base = floor(($totalWeight / $count) * 2) / 2;
        $alloc = [];
        $sum = 0.0;

        foreach ($keys as $k) {
            $alloc[$k] = $base;
            $sum += $base;
        }

        $rem = round($totalWeight - $sum, 2);
        $idx = $count - 1;
        while ($rem >= 0.49 && $idx >= 0) {
            $alloc[$keys[$idx]] = round($alloc[$keys[$idx]] + 0.5, 2);
            $rem = round($rem - 0.5, 2);
            $idx--;
            if ($idx < 0) {
                $idx = $count - 1;
            }
        }

        if (abs($rem) > 0.01) {
            $alloc[$keys[$count - 1]] = round($alloc[$keys[$count - 1]] + $rem, 2);
        }

        return $alloc;
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

        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $uncompressed = @gzuncompress($stream);
                if ($uncompressed === false) {
                    $uncompressed = @gzinflate($stream);
                }
                $data = $uncompressed !== false ? $uncompressed : $stream;

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
