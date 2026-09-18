<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Services\AvailableKrsScheduleService;
use App\Services\LegacyListNavigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class KrsController extends Controller
{
    public function index(Request $request)
    {
        $dosen = $this->authenticatedLecturer();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester_akademik' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $baseQuery = Krs::query()
            ->where('krs.is_manual', false)
            ->whereHas('jadwal.mataKuliah')
            ->whereHas('mahasiswa', function (Builder $student) use ($dosen, $filters) {
                $student->where('dosen_wali_id', $dosen->id)
                    ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                        $query->where(function (Builder $identity) use ($search) {
                            $identity->where('nim', 'like', "%{$search}%")
                                ->orWhere('nama', 'like', "%{$search}%");
                        });
                    });
            })
            ->when($filters['tahun_akademik'] ?? null, fn (Builder $query, string $year) => $query
                ->where('krs.tahun_akademik', $year))
            ->when($filters['semester_akademik'] ?? null, fn (Builder $query, string $semester) => $query
                ->where('krs.semester_akademik', $semester));

        $menunggu = (clone $baseQuery)->whereIn('krs.status', ['Menunggu', 'Diambil'])->count();
        $disetujui = (clone $baseQuery)->where('krs.status', 'Disetujui')->count();
        $ditolak = (clone $baseQuery)->where('krs.status', 'Ditolak')->count();

        $summaries = $baseQuery
            ->leftJoin('jadwals', 'jadwals.id', '=', 'krs.jadwal_id')
            ->leftJoin('mata_kuliahs', 'mata_kuliahs.id', '=', 'jadwals.mata_kuliah_id')
            ->select([
                'krs.mahasiswa_id',
                'krs.tahun_akademik',
                'krs.semester_akademik',
            ])
            ->selectRaw('COUNT(krs.id) AS jumlah_mata_kuliah')
            ->selectRaw('COALESCE(SUM(COALESCE(mata_kuliahs.sks, 0)), 0) AS total_sks')
            ->selectRaw("SUM(CASE WHEN krs.status IN ('Menunggu', 'Diambil') THEN 1 ELSE 0 END) AS jumlah_menunggu")
            ->selectRaw("SUM(CASE WHEN krs.status = 'Disetujui' THEN 1 ELSE 0 END) AS jumlah_disetujui")
            ->selectRaw("SUM(CASE WHEN krs.status = 'Ditolak' THEN 1 ELSE 0 END) AS jumlah_ditolak")
            ->groupBy('krs.mahasiswa_id', 'krs.tahun_akademik', 'krs.semester_akademik')
            ->with('mahasiswa.prodi')
            ->orderByDesc('krs.tahun_akademik')
            ->orderByDesc('krs.semester_akademik')
            ->orderBy('krs.mahasiswa_id')
            ->paginate(10)
            ->withQueryString();

        $tahunAkademiks = Krs::query()
            ->where('is_manual', false)
            ->whereHas('mahasiswa', fn (Builder $student) => $student
                ->where('dosen_wali_id', $dosen->id))
            ->whereNotNull('tahun_akademik')
            ->distinct()
            ->orderByDesc('tahun_akademik')
            ->pluck('tahun_akademik');

        return view('dosen.krs.index', compact(
            'dosen',
            'summaries',
            'tahunAkademiks',
            'menunggu',
            'disetujui',
            'ditolak'
        ));
    }

    public function show(
        Request $request,
        Mahasiswa $mahasiswa,
        AvailableKrsScheduleService $scheduleService
    ) {
        $dosen = $this->authenticatedLecturer();
        abort_unless((int) $mahasiswa->dosen_wali_id === (int) $dosen->id, 404);

        $period = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:20'],
            'semester_akademik' => ['required', 'string', 'max:30'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);
        $year = $scheduleService->normalizeAcademicYear($period['tahun_akademik']);
        $semester = $scheduleService->normalizeAcademicSemester($period['semester_akademik']);
        abort_if($semester === null, 404);

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('is_manual', false)
            ->whereHas('jadwal.mataKuliah')
            ->whereRaw(
                "REPLACE(REPLACE(TRIM(tahun_akademik), ' ', ''), '-', '/') = ?",
                [$year]
            )
            ->get()
            ->filter(fn (Krs $item) => $scheduleService->normalizeAcademicSemester(
                $item->semester_akademik
            ) === $semester)
            ->sortBy(fn (Krs $item) => strtolower(
                ($item->jadwal?->mataKuliah?->kode_mk ?? '').'|'.
                ($item->jadwal?->mataKuliah?->nama_mk ?? '').'|'.
                str_pad((string) $item->id, 10, '0', STR_PAD_LEFT)
            ))
            ->values();

        abort_if($krs->isEmpty(), 404);
        $mahasiswa->load(['prodi', 'dosenWali']);
        $totalSks = $krs->sum(fn (Krs $item) => (int) ($item->jadwal?->mataKuliah?->sks ?? 0));
        $periodeKrs = PeriodeKrs::query()
            ->whereRaw(
                "REPLACE(REPLACE(TRIM(tahun_akademik), ' ', ''), '-', '/') = ?",
                [$year]
            )
            ->get()
            ->first(fn (PeriodeKrs $periode) => $scheduleService->normalizeAcademicSemester(
                $periode->semester
            ) === $semester);
        $listUrl = app(LegacyListNavigation::class)->returnUrl($request, 'dosen.krs');

        return view('dosen.krs.show', compact(
            'dosen',
            'mahasiswa',
            'krs',
            'totalSks',
            'periodeKrs',
            'year',
            'semester',
            'listUrl'
        ));
    }

    public function setujui(Request $request, int $id)
    {
        $dosen = $this->authenticatedLecturer();
        $krs = $this->advisorKrs($id, $dosen);
        $returnUrl = app(LegacyListNavigation::class)->returnUrl($request, 'dosen.krs');

        if (! in_array($krs->status, ['Menunggu', 'Diambil'], true)) {
            return redirect()->to($returnUrl)
                ->with('error', 'KRS ini tidak dapat disetujui karena statusnya sudah diproses.');
        }

        $krs->update([
            'status' => 'Disetujui',
            'alasan_penolakan' => null,
        ]);

        return redirect()->to($returnUrl)
            ->with('success', 'KRS mahasiswa berhasil disetujui.');
    }

    public function tolak(Request $request, int $id)
    {
        $dosen = $this->authenticatedLecturer();
        $krs = $this->advisorKrs($id, $dosen);

        $data = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:1000'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 1000 karakter.',
        ]);

        $returnUrl = app(LegacyListNavigation::class)->returnUrl($request, 'dosen.krs');

        if (! in_array($krs->status, ['Menunggu', 'Diambil'], true)) {
            return redirect()->to($returnUrl)
                ->with('error', 'KRS ini tidak dapat ditolak karena statusnya sudah diproses.');
        }

        $krs->update([
            'status' => 'Ditolak',
            'alasan_penolakan' => $data['alasan_penolakan'],
        ]);

        return redirect()->to($returnUrl)
            ->with('success', 'KRS mahasiswa berhasil ditolak dan alasan penolakan telah disimpan.');
    }

    private function authenticatedLecturer(): Dosen
    {
        return Dosen::where('user_id', Auth::id())->firstOrFail();
    }

    private function advisorKrs(int $id, Dosen $dosen): Krs
    {
        return Krs::where('id', $id)
            ->where('is_manual', false)
            ->whereHas('mahasiswa', fn (Builder $student) => $student
                ->where('dosen_wali_id', $dosen->id))
            ->firstOrFail();
    }
}
