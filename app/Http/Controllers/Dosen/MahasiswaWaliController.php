<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use App\Services\MahasiswaNilaiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MahasiswaWaliController extends Controller
{
    public function index(Request $request)
    {
        $dosen = $this->authenticatedLecturer($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'angkatan' => ['nullable', 'integer', 'between:1900,2200'],
            'semester' => ['nullable', 'integer', 'between:1,20'],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $mahasiswas = Mahasiswa::query()
            ->with([
                'prodi',
                'krs' => fn ($query) => $query
                    ->where(fn ($regular) => $regular
                        ->where('is_manual', false)
                        ->orWhereNull('is_manual'))
                    ->latest('id'),
            ])
            ->where('dosen_wali_id', $dosen->id)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $identity) use ($search) {
                    $identity->where('nama', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%");
                });
            })
            ->when($filters['prodi_id'] ?? null, fn (Builder $query, int $prodiId) => $query
                ->where('prodi_id', $prodiId))
            ->when($filters['angkatan'] ?? null, fn (Builder $query, int $angkatan) => $query
                ->where('angkatan', $angkatan))
            ->when($filters['semester'] ?? null, fn (Builder $query, int $semester) => $query
                ->where('semester', $semester))
            ->orderBy('nama')
            ->orderBy('nim')
            ->paginate(10)
            ->withQueryString();

        $advisorStudents = Mahasiswa::query()->where('dosen_wali_id', $dosen->id);
        $prodis = Prodi::query()
            ->whereIn('id', (clone $advisorStudents)->whereNotNull('prodi_id')->select('prodi_id'))
            ->orderBy('nama_prodi')
            ->get(['id', 'nama_prodi']);
        $angkatans = (clone $advisorStudents)
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan');
        $semesters = (clone $advisorStudents)
            ->whereNotNull('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        return view('dosen.mahasiswa-wali.index', compact(
            'dosen',
            'mahasiswas',
            'prodis',
            'angkatans',
            'semesters'
        ));
    }

    public function show(
        Request $request,
        Mahasiswa $mahasiswa,
        MahasiswaNilaiService $nilaiService,
        LegacyListNavigation $navigation
    ) {
        $dosen = $this->authenticatedLecturer($request);
        abort_unless((int) $mahasiswa->dosen_wali_id === (int) $dosen->id, 403);

        $mahasiswa->load(['prodi', 'kelas', 'dosenWali']);

        $khs = Khs::with([
            'dosenManual',
            'krs.jadwal.mataKuliah',
            'krs.mataKuliahManual',
            'krs.dosenManual',
            'krs.jadwal.dosen',
        ])
            ->whereHas('krs', fn (Builder $query) => $query
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('status', 'Disetujui'))
            ->orderBy('tahun_akademik')
            ->orderBy('semester_akademik')
            ->orderBy('id')
            ->get();

        $khsPerSemester = $khs
            ->groupBy(function (Khs $item): int {
                $semester = (int) ($item->krs?->semester
                    ?? $item->krs?->mata_kuliah_efektif?->semester
                    ?? 0);

                return max(0, $semester);
            })
            ->sortKeys(SORT_NUMERIC);

        $ipsPerSemester = $khsPerSemester
            ->map(fn ($nilaiSemester) => $nilaiService->hitungIndeks($nilaiSemester));
        $nilaiFinal = $khs->filter(fn (Khs $item) => $item->nilai_angka !== null
            && $item->nilai_huruf !== null
            && $item->bobot !== null
            && $item->sks_efektif > 0);
        $totalSks = $nilaiFinal->sum(fn (Khs $item) => $item->sks_efektif);
        $ipk = $nilaiFinal->isEmpty() ? null : $nilaiService->hitungIndeks($nilaiFinal);

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where(fn (Builder $query) => $query
                ->where('is_manual', false)
                ->orWhereNull('is_manual'))
            ->orderByDesc('tahun_akademik')
            ->orderByDesc('semester_akademik')
            ->orderBy('id')
            ->get();

        $krsPerPeriode = $krs
            ->filter(fn (Krs $item) => filled($item->tahun_akademik) && filled($item->semester_akademik))
            ->groupBy(fn (Krs $item) => $item->tahun_akademik.'|'.$item->semester_akademik)
            ->map(function ($items) {
                $first = $items->first();
                $pending = $items->whereIn('status', ['Menunggu', 'Diambil'])->count();
                $rejected = $items->where('status', 'Ditolak')->count();

                return [
                    'tahun_akademik' => $first->tahun_akademik,
                    'semester_akademik' => $first->semester_akademik,
                    'jumlah_mata_kuliah' => $items->count(),
                    'total_sks' => $items->where('status', '!=', 'Ditolak')
                        ->sum(fn (Krs $item) => (int) ($item->mata_kuliah_efektif?->sks ?? 0)),
                    'status' => $pending > 0 ? 'Menunggu' : ($rejected > 0 ? 'Perlu Revisi' : 'Disetujui'),
                ];
            })
            ->sortByDesc(fn (array $period) => $period['tahun_akademik'].'|'.$period['semester_akademik'])
            ->values();

        $listUrl = $navigation->returnUrl($request, 'dosen.mahasiswa-wali');

        return view('dosen.mahasiswa-wali.show', compact(
            'dosen',
            'mahasiswa',
            'khsPerSemester',
            'ipsPerSemester',
            'totalSks',
            'ipk',
            'krsPerPeriode',
            'listUrl'
        ));
    }

    private function authenticatedLecturer(Request $request): Dosen
    {
        return $request->user()->dosen()->firstOrFail();
    }
}
