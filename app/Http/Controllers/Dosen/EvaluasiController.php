<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Services\LecturerEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EvaluasiController extends Controller
{
    public function __construct(private readonly LecturerEvaluationService $evaluations) {}

    public function index(Request $request)
    {
        $dosen = $this->authenticatedLecturer($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request), $dosen);
        $report = $this->evaluations->report($dosen, $filters);

        return view('dosen.evaluasi.index', array_merge(
            $report,
            $this->evaluations->filterOptions($dosen),
            [
                'dosen' => $dosen,
                'activeFilters' => $filters,
                'komentar' => $this->evaluations->comments($report['krsIds'])
                    ->paginate(10)
                    ->withQueryString(),
            ]
        ));
    }

    public function pdf(Request $request)
    {
        $dosen = $this->authenticatedLecturer($request);
        $filters = $this->evaluations->withDefaultPeriod($this->validatedFilters($request), $dosen);
        $report = $this->evaluations->report($dosen, $filters);

        return Pdf::loadView('evaluasi.detail-pdf', array_merge($report, [
            'dosen' => $dosen,
            'komentar' => $this->evaluations->comments($report['krsIds'])->get(),
            'reportTitle' => 'Evaluasi Saya',
            'deskripsiFilter' => $this->filterDescription($filters),
        ]))
            ->setPaper('a4', 'portrait')
            ->download('evaluasi-saya-'.Str::slug($dosen->nidn ?: $dosen->nama).'.pdf');
    }

    private function authenticatedLecturer(Request $request): Dosen
    {
        abort_unless($request->user()?->role === 'dosen', 403);

        $dosen = $request->user()->loadMissing('dosen.prodi')->dosen;
        abort_if($dosen === null, 404, 'Data dosen untuk akun ini tidak ditemukan.');

        return $dosen;
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'semester_akademik' => ['nullable', 'in:Ganjil,Genap'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);
    }

    private function filterDescription(array $filters): string
    {
        $parts = [
            filled($filters['mata_kuliah_id'] ?? null) ? 'Mata Kuliah: '.MataKuliah::find($filters['mata_kuliah_id'])?->nama_mk : null,
            filled($filters['semester_akademik'] ?? null) ? 'Semester: '.$filters['semester_akademik'] : null,
            filled($filters['tahun_akademik'] ?? null) ? 'Tahun: '.$filters['tahun_akademik'] : null,
            filled($filters['kelas_id'] ?? null) ? 'Kelas: '.Kelas::find($filters['kelas_id'])?->nama_kelas : null,
        ];

        return collect($parts)->filter()->implode(' | ') ?: 'Semua evaluasi';
    }
}
