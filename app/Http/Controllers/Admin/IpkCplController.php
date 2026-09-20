<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MataKuliah;
use App\Services\IpkCplReportService;
use App\Services\LegacyListNavigation;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class IpkCplController extends Controller
{
    public function index(Request $request, IpkCplReportService $reports)
    {
        $filters = $this->validatedFilters($request);
        $report = $reports->report($filters);
        $report['rows'] = $this->paginate($report['rows'], 15);

        return view('admin.ipk-cpl.index', $report + $reports->filterOptions());
    }

    public function excel(Request $request, IpkCplReportService $reports, ReportExporter $exporter)
    {
        $filters = $this->validatedFilters($request);
        $report = $reports->report($filters);

        return $exporter->excel(
            'ipk-cpl-'.now()->format('Ymd-His').'.xlsx',
            'IPK CPL - Rekap Nilai Mata Kuliah',
            $reports->filterDescription($filters),
            [[
                'title' => 'Rekap Mata Kuliah',
                'headings' => [
                    'Tahun Akademik', 'Semester Akademik', 'Program Studi', 'Semester Angka', 'Kode MK', 'Mata Kuliah', 'Dosen',
                    'Mahasiswa', 'Rata Nilai', 'Rata Bobot', 'A', 'B', 'C', 'D', 'E', 'Kelulusan', 'Keterangan',
                ],
                'rows' => $report['rows']->map(fn (object $row) => [
                    $row->tahun_akademik,
                    $row->semester_akademik,
                    $row->prodi,
                    $row->semester_angka ?? '-',
                    $row->kode_mata_kuliah,
                    $row->nama_mata_kuliah,
                    $row->dosen,
                    $row->jumlah_mahasiswa,
                    $row->rata_nilai ?? '-',
                    $row->rata_bobot ?? '-',
                    $row->nilai_a,
                    $row->nilai_b,
                    $row->nilai_c,
                    $row->nilai_d,
                    $row->nilai_e,
                    $row->persentase_lulus === null ? '-' : $row->persentase_lulus.'%',
                    $row->keterangan,
                ]),
            ]]
        );
    }

    public function show(
        Request $request,
        MataKuliah $mataKuliah,
        IpkCplReportService $reports,
        LegacyListNavigation $navigation
    ) {
        $filters = $this->validatedFilters($request, true);
        $detail = $reports->courseDetail($mataKuliah, $filters);
        abort_if($detail['grades']->isEmpty(), 404, 'Data nilai mata kuliah untuk filter tersebut tidak ditemukan.');

        return view('admin.ipk-cpl.show', $detail + [
            'filters' => $filters,
            'returnUrl' => $navigation->returnUrl($request, 'admin.ipk-cpl.index'),
        ]);
    }

    private function validatedFilters(Request $request, bool $requirePeriod = false): array
    {
        return $request->validate([
            'page' => ['nullable', 'integer', 'between:1,100000'],
            'tahun_akademik' => [$requirePeriod ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
            'semester_akademik' => [$requirePeriod ? 'required' : 'nullable', Rule::in(['Ganjil', 'Genap'])],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester_angka' => ['nullable', 'integer', 'between:1,14'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
        ]);
    }

    private function paginate(Collection $rows, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
