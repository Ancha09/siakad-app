<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\MataKuliah;
use App\Services\LecturerEvaluationService;
use App\Services\LegacyListNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KuesionerController extends Controller
{
    public function __construct(private readonly LecturerEvaluationService $evaluations) {}

    public function index(Request $request)
    {
        $this->ensureAdmin($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request));

        return view('admin.kuesioner.index', array_merge(
            $this->overviewData($filters, true),
            $this->filterOptions(),
            ['activeFilters' => $filters]
        ));
    }

    public function show(Request $request, Dosen $dosen, LegacyListNavigation $navigation)
    {
        $this->ensureAdmin($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request), $dosen);
        $report = $this->evaluations->report($dosen, $filters);

        return view('admin.kuesioner.show', array_merge($report, [
            'dosen' => $dosen->loadMissing('prodi'),
            'komentar' => $this->evaluations->comments($report['krsIds'])
                ->paginate(10)
                ->withQueryString(),
            'returnUrl' => $navigation->returnUrl($request, 'admin.kuesioner'),
            'activeFilters' => $filters,
        ]));
    }

    public function pdfDetail(Request $request, Dosen $dosen)
    {
        $this->ensureAdmin($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request), $dosen);
        $report = $this->evaluations->report($dosen, $filters);

        return Pdf::loadView('evaluasi.detail-pdf', array_merge($report, [
            'dosen' => $dosen->loadMissing('prodi'),
            'komentar' => $this->evaluations->comments($report['krsIds'])->get(),
            'reportTitle' => 'Detail Evaluasi Dosen',
            'deskripsiFilter' => $this->filterDescription($filters),
        ]))
            ->setPaper('a4', 'portrait')
            ->download('evaluasi-dosen-'.Str::slug($dosen->nidn ?: $dosen->nama).'.pdf');
    }

    public function pdf(Request $request)
    {
        $this->ensureAdmin($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request));

        return Pdf::loadView('admin.kuesioner.pdf-full', array_merge(
            $this->overviewData($filters, false),
            [
                'reportTitle' => 'Laporan Evaluasi Dosen',
                'deskripsiFilter' => $this->filterDescription($filters),
            ]
        ))
            ->setPaper('a4', 'landscape')
            ->download('laporan-evaluasi-dosen-'.now()->format('Ymd-His').'.pdf');
    }

    private function overviewData(array $filters, bool $paginate): array
    {
        $dosenQuery = Dosen::query()
            ->with('prodi')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nidn', 'like', "%{$search}%");
                });
            })
            ->when($filters['dosen_id'] ?? null, fn ($query, $dosenId) => $query->whereKey($dosenId));

        $dosenIds = (clone $dosenQuery)->pluck('id');
        $evaluationRows = $this->evaluations->rows($filters)
            ->when(
                $dosenIds->isNotEmpty(),
                fn (QueryBuilder $query) => $query->whereIn(DB::raw(LecturerEvaluationService::EFFECTIVE_DOSEN_SQL), $dosenIds),
                fn (QueryBuilder $query) => $query->whereRaw('1 = 0')
            );

        $evaluatedDosenIds = (clone $evaluationRows)
            ->selectRaw(LecturerEvaluationService::EFFECTIVE_DOSEN_SQL.' AS effective_dosen_id')
            ->distinct()
            ->pluck('effective_dosen_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $totalDosen = $dosenIds->count();
        $totalDinilai = $evaluatedDosenIds->count();
        $totalBelumDinilai = max(0, $totalDosen - $totalDinilai);
        $totalResponden = (clone $evaluationRows)->distinct()->count('kuesioners.id');

        if (($filters['status_evaluasi'] ?? null) === 'sudah') {
            $dosenQuery->whereIn('id', $evaluatedDosenIds);
        } elseif (($filters['status_evaluasi'] ?? null) === 'belum') {
            $dosenQuery->whereNotIn('id', $evaluatedDosenIds);
        }

        $records = $paginate
            ? $dosenQuery->orderBy('nama')->paginate(10)->withQueryString()
            : $dosenQuery->orderBy('nama')->get();
        $dosenCollection = $paginate ? $records->getCollection() : $records;
        $recordDosenIds = $dosenCollection->pluck('id');
        $recordKrsIds = $recordDosenIds->isEmpty()
            ? collect()
            : (clone $evaluationRows)
                ->whereIn(DB::raw(LecturerEvaluationService::EFFECTIVE_DOSEN_SQL), $recordDosenIds)
                ->pluck('kuesioners.krs_id')
                ->unique()
                ->values();
        $jawabanPerDosen = $this->evaluations->answers($recordKrsIds)
            ->groupBy(fn (Kuesioner $item) => $this->evaluations->lecturerId($item));

        $mapped = $dosenCollection->map(function (Dosen $dosen) use ($jawabanPerDosen) {
            /** @var EloquentCollection<int, Kuesioner> $jawaban */
            $jawaban = $jawabanPerDosen->get($dosen->id, new EloquentCollection);

            return (object) [
                'dosen' => $dosen,
                'jumlah_responden' => $jawaban->count(),
                'rata_rata' => $jawaban->isEmpty()
                    ? null
                    : round((float) $jawaban->avg(fn (Kuesioner $item) => $item->rata_rata), 2),
                'mata_kuliahs' => $this->evaluations->relatedCourses($jawaban),
                'periode' => $this->evaluations->relatedPeriods($jawaban),
            ];
        });

        if ($paginate) {
            $records->setCollection($mapped);
        } else {
            $records = $mapped;
        }

        return [
            'evaluasiDosen' => $records,
            'totalDosen' => $totalDosen,
            'totalDinilai' => $totalDinilai,
            'totalBelumDinilai' => $totalBelumDinilai,
            'totalResponden' => $totalResponden,
        ];
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester_akademik' => ['nullable', 'in:Ganjil,Genap'],
            'status_evaluasi' => ['nullable', 'in:sudah,belum'],
        ]);
    }

    private function filterOptions(): array
    {
        return [
            'dosens' => Dosen::orderBy('nama')->get(['id', 'nama', 'nidn']),
            'mataKuliahs' => MataKuliah::orderBy('nama_mk')->get(['id', 'kode_mk', 'nama_mk']),
            'tahunAkademik' => Krs::whereNotNull('tahun_akademik')
                ->distinct()
                ->orderByDesc('tahun_akademik')
                ->pluck('tahun_akademik'),
        ];
    }

    private function filterDescription(array $filters): string
    {
        $parts = [
            filled($filters['search'] ?? null) ? 'Pencarian: '.$filters['search'] : null,
            filled($filters['dosen_id'] ?? null) ? 'Dosen: '.Dosen::find($filters['dosen_id'])?->nama : null,
            filled($filters['mata_kuliah_id'] ?? null) ? 'Mata Kuliah: '.MataKuliah::find($filters['mata_kuliah_id'])?->nama_mk : null,
            filled($filters['semester_akademik'] ?? null) ? 'Semester: '.$filters['semester_akademik'] : null,
            filled($filters['tahun_akademik'] ?? null) ? 'Tahun: '.$filters['tahun_akademik'] : null,
            filled($filters['status_evaluasi'] ?? null) ? 'Status: '.($filters['status_evaluasi'] === 'sudah' ? 'Sudah dinilai' : 'Belum dinilai') : null,
        ];

        return collect($parts)->filter()->implode(' | ') ?: 'Semua data';
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
