<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cpl;
use App\Models\CplMataKuliah;
use App\Models\MataKuliah;
use App\Models\Prodi;
use App\Services\CplMappingImporter;
use App\Services\IpkCplReportService;
use App\Services\LegacyListNavigation;
use App\Services\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IpkCplController extends Controller
{
    public function index()
    {
        $programs = Prodi::query()->orderBy('nama_prodi')->get();
        $mining = $programs->first(fn (Prodi $program) => str_contains(Str::lower($program->nama_prodi), 'pertambangan'));
        $geology = $programs->first(fn (Prodi $program) => str_contains(Str::lower($program->nama_prodi), 'geologi'));

        return view('admin.ipk-cpl.index', compact('mining', 'geology'));
    }

    public function program(
        Request $request,
        Prodi $prodi,
        IpkCplReportService $reports
    ) {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);
        $options = $reports->cplFilterOptions($prodi);
        $filters['tahun_akademik'] ??= $options['tahunAkademiks']->first();
        $report = $reports->cplOverview($prodi, $filters, false);

        return view('admin.ipk-cpl.program', $report
            + $options
            + compact('filters'));
    }

    public function updateMapping(
        Request $request,
        Prodi $prodi,
        Cpl $cpl,
        CplMataKuliah $mapping,
        CplMappingImporter $matcher,
        LegacyListNavigation $navigation
    ) {
        $this->ensureMiningProgram($prodi);
        abort_unless((int) $cpl->program_studi_id === (int) $prodi->id
            && (int) $mapping->cpl_id === (int) $cpl->id, 404);

        $data = $request->validate([
            'mata_kuliah_id' => ['required', 'integer', 'exists:mata_kuliahs,id'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);
        $course = MataKuliah::with('prodi')->findOrFail($data['mata_kuliah_id']);

        if (! $matcher->isCourseEligible($course, $prodi, $mapping->kode_sumber)) {
            throw ValidationException::withMessages([
                'mata_kuliah_id' => 'Pilih mata kuliah Teknik Pertambangan atau mata kuliah umum tanpa prodi.',
            ]);
        }

        $relatedMappings = CplMataKuliah::query()
            ->with('cpl')
            ->whereNull('mata_kuliah_id')
            ->whereHas('cpl', fn ($query) => $query->where('program_studi_id', $prodi->id))
            ->get()
            ->filter(fn (CplMataKuliah $item) => $matcher->sourceKey($item->kode_sumber, $item->nama_sumber)
                === $matcher->sourceKey($mapping->kode_sumber, $mapping->nama_sumber));

        $hasDuplicate = $relatedMappings->contains(fn (CplMataKuliah $item) => CplMataKuliah::query()
            ->where('cpl_id', $item->cpl_id)
            ->where('mata_kuliah_id', $course->id)
            ->where('id', '!=', $item->id)
            ->exists());
        if ($hasDuplicate) {
            throw ValidationException::withMessages([
                'mata_kuliah_id' => 'Mata kuliah tersebut sudah terhubung pada CPL yang sama.',
            ]);
        }

        DB::transaction(fn () => CplMataKuliah::query()
            ->whereKey($relatedMappings->pluck('id'))
            ->update(['mata_kuliah_id' => $course->id, 'updated_at' => now()]));

        return redirect($navigation->returnUrl($request, 'admin.ipk-cpl.program', ['prodi' => $prodi]))
            ->with('success', $mapping->kode_sumber.' berhasil dihubungkan ke '.$course->kode_mk.' - '.$course->nama_mk
                .' pada '.$relatedMappings->count().' mapping CPL.');
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
        $filters['tahun_akademik'] ??= $reports->latestAcademicYear($prodi);

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
        $filters['tahun_akademik'] ??= $reports->latestAcademicYear($prodi);

        return view('admin.ipk-cpl.course', $reports->mappedCourseDetail($prodi, $cpl, $mapping, $filters) + [
            'filters' => $filters,
            'returnUrl' => $navigation->returnUrl($request, 'admin.ipk-cpl.cpl', [
                'prodi' => $prodi,
                'cpl' => $cpl,
            ]),
        ]);
    }

    public function cplExcel(
        Request $request,
        Prodi $prodi,
        IpkCplReportService $reports,
        ReportExporter $exporter
    ) {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);
        $filters['tahun_akademik'] ??= $reports->latestAcademicYear($prodi);
        $report = $reports->cplOverview($prodi, $filters);
        $sheets = [
            [
                'title' => 'Ringkasan CPL',
                'headings' => ['Kode CPL', 'Deskripsi', 'Turunan Visi-Misi', 'CPL KKNI', 'IPK CPL', 'Mata Kuliah', 'Mata Kuliah Bernilai', 'Total SKS Mapping', 'SKS Dihitung'],
                'rows' => $report['rows']->map(fn (object $row) => [
                    $row->kode_cpl,
                    $row->cpl->deskripsi ?: '-',
                    $row->cpl->turunan_visi_misi ?: '-',
                    $row->cpl->cpl_kkni ?: '-',
                    $row->ipk_cpl ?? '-',
                    $row->jumlah_mata_kuliah,
                    $row->mata_kuliah_bernilai,
                    $row->total_sks,
                    $row->sks_dihitung,
                ]),
            ],
            [
                'title' => 'Detail Mata Kuliah',
                'headings' => ['CPL', 'Kode Mata Kuliah', 'Nama Mata Kuliah', 'Semester', 'SKS', 'Mahasiswa Bernilai', 'Rata-rata Nilai Mutu', 'Mutu x SKS'],
                'rows' => $report['rows']->flatMap(fn (object $row) => $row->courses->map(fn (object $course) => [
                    $row->kode_cpl,
                    $course->kode_mata_kuliah,
                    $course->nama_mata_kuliah,
                    $course->semester ?? '-',
                    $course->sks,
                    $course->jumlah_mahasiswa,
                    $course->rata_bobot ?? '-',
                    $course->mutu_sks ?? '-',
                ])),
            ],
        ];

        if ($report['unmatchedMappings']->isNotEmpty()) {
            $sheets[] = [
                'title' => 'Mapping Belum Cocok',
                'headings' => ['CPL', 'Kode Excel', 'Nama Mata Kuliah Excel', 'Semester', 'SKS'],
                'rows' => $report['unmatchedMappings']->map(fn (CplMataKuliah $mapping) => [
                    $mapping->cpl?->kode_cpl ?? '-',
                    $mapping->kode_sumber,
                    $mapping->nama_sumber,
                    $mapping->semester ?? '-',
                    $mapping->sks ?? '-',
                ]),
            ];
        }

        return $exporter->excel(
            'laporan-ipk-cpl-teknik-pertambangan-'.now()->format('Ymd-His').'.xlsx',
            'Laporan IPK CPL Teknik Pertambangan',
            $this->cplFilterDescription($prodi, $filters),
            $sheets
        );
    }

    public function cplPdf(Request $request, Prodi $prodi, IpkCplReportService $reports)
    {
        $this->ensureMiningProgram($prodi);
        $filters = $this->validatedCplFilters($request);
        $filters['tahun_akademik'] ??= $reports->latestAcademicYear($prodi);
        $report = $reports->cplOverview($prodi, $filters);

        return Pdf::loadView('admin.ipk-cpl.pdf', $report + [
            'filters' => $filters,
            'filterDescription' => $this->cplFilterDescription($prodi, $filters),
        ])->setPaper('a4', 'landscape')
            ->download('laporan-ipk-cpl-teknik-pertambangan-'.now()->format('Ymd-His').'.pdf');
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
            'page' => ['nullable', 'integer', 'between:1,100000'],
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

    private function cplFilterDescription(Prodi $program, array $filters): string
    {
        $parts = [$program->jenjang.' '.$program->nama_prodi];
        $parts[] = filled($filters['tahun_akademik'] ?? null)
            ? 'Tahun Akademik '.$filters['tahun_akademik']
            : 'Semua Tahun Akademik';

        if (! empty($filters['angkatan'])) {
            $parts[] = 'Angkatan '.$filters['angkatan'];
        }
        if (! empty($filters['tahun_studi'])) {
            $parts[] = 'Tahun Studi '.$filters['tahun_studi'];
        }
        if (! empty($filters['cpl_id'])) {
            $parts[] = Cpl::find($filters['cpl_id'])?->kode_cpl ?? 'CPL tidak ditemukan';
        }

        return implode(' | ', $parts);
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
