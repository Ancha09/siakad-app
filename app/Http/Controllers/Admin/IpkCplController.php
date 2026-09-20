<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\IpkCplReportService;
use App\Services\LegacyListNavigation;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IpkCplController extends Controller
{
    public function index()
    {
        $programs = Prodi::query()->orderBy('nama_prodi')->get();
        $mining = $programs->first(fn (Prodi $program) => str_contains(Str::lower($program->nama_prodi), 'pertambangan'));
        $geology = $programs->first(fn (Prodi $program) => str_contains(Str::lower($program->nama_prodi), 'geologi'));

        return view('admin.ipk-cpl.index', compact('mining', 'geology'));
    }

    public function program(Request $request, Prodi $prodi, IpkCplReportService $reports)
    {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);

        return view('admin.ipk-cpl.program', $reports->cplOverview($prodi, $filters)
            + $reports->cplFilterOptions($prodi)
            + ['filters' => $filters]);
    }

    public function cpl(
        Request $request,
        Prodi $prodi,
        Cpl $cpl,
        IpkCplReportService $reports,
        LegacyListNavigation $navigation
    ) {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);

        return view('admin.ipk-cpl.cpl', $reports->cplDetail($prodi, $cpl, $filters) + [
            'filters' => $filters,
            'returnUrl' => $navigation->returnUrl($request, 'admin.ipk-cpl.program', ['prodi' => $prodi]),
        ]);
    }

    public function course(
        Request $request,
        Prodi $prodi,
        Cpl $cpl,
        CplMataKuliah $mapping,
        IpkCplReportService $reports,
        LegacyListNavigation $navigation
    ) {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);

        return view('admin.ipk-cpl.course', $reports->mappedCourseDetail($prodi, $cpl, $mapping, $filters) + [
            'filters' => $filters,
            'returnUrl' => $navigation->returnUrl($request, 'admin.ipk-cpl.cpl', [
                'prodi' => $prodi,
                'cpl' => $cpl,
            ]),
        ]);
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

    private function validatedCplFilters(Request $request): array
    {
        return $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'tahun_studi' => ['nullable', Rule::in(['1', '2', '3', '4'])],
            'cpl_id' => ['nullable', 'integer', 'exists:cpls,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'program_studi_id' => ['nullable', 'integer', 'exists:prodis,id'],
        ]);
    }

    private function ensureMiningProgram(Prodi $program): void
    {
        abort_unless(str_contains(Str::lower($program->nama_prodi), 'pertambangan'), 404);
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
