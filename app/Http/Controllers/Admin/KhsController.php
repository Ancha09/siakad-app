<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Khs;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Services\IpkCplReportService;
use App\Services\LegacyListNavigation;
use App\Services\MahasiswaNilaiService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class KhsController extends Controller
{
    // =====================================================
    // INDEX
    // =====================================================

    public function index(
        Request $request,
        IpkCplReportService $reports,
        MahasiswaNilaiService $nilaiService
    ) {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'tahun_akademik' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
            'semester_akademik' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'semester_angka' => ['nullable', 'integer', 'between:1,14'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $grades = $reports->finalGrades($filters);
        if (filled($filters['search'] ?? null)) {
            $search = mb_strtolower(trim($filters['search']));
            $grades = $grades->filter(function (Khs $grade) use ($search) {
                $student = $grade->krs?->mahasiswa;

                return str_contains(mb_strtolower((string) $student?->nama), $search)
                    || str_contains(mb_strtolower((string) $student?->nim), $search);
            })->values();
        }

        $rows = $grades
            ->groupBy(fn (Khs $grade) => implode('|', [
                $grade->krs?->mahasiswa_id,
                $this->academicYear($grade),
                strtolower($this->academicSemester($grade)),
            ]))
            ->map(function (Collection $semesterGrades) use ($nilaiService) {
                /** @var Khs $first */
                $first = $semesterGrades->first();
                $student = $first->krs->mahasiswa;
                $semesterNumbers = $semesterGrades
                    ->map(fn (Khs $grade) => $this->courseSemester($grade))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                return (object) [
                    'mahasiswa' => $student,
                    'mahasiswa_id' => $student->id,
                    'tahun_akademik' => $this->academicYear($first),
                    'semester_akademik' => $this->academicSemester($first),
                    'semester_angka' => $semesterNumbers->isEmpty() ? '-' : $semesterNumbers->implode(', '),
                    'jumlah_mata_kuliah' => $semesterGrades->count(),
                    'total_sks' => $semesterGrades->sum(fn (Khs $grade) => $grade->sks_efektif),
                    'ips' => $nilaiService->hitungIndeks($semesterGrades),
                ];
            })
            ->sort(function (object $left, object $right) {
                return [$right->tahun_akademik, $this->semesterOrder($right->semester_akademik), $right->mahasiswa->nama]
                    <=> [$left->tahun_akademik, $this->semesterOrder($left->semester_akademik), $left->mahasiswa->nama];
            })
            ->values();

        $options = $reports->filterOptions();

        return view('admin.khs.index', [
            'summaries' => $this->paginate($rows, 10),
            'prodis' => $options['prodis'],
            'angkatans' => $options['angkatans'],
            'semesterAngkas' => $options['semesterAngkas'],
            'tahunAkademiks' => $options['tahunAkademiks'],
            'studentSuggestions' => Mahasiswa::query()
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nim', 'nama']),
        ]);
    }

    public function show(
        Request $request,
        Mahasiswa $mahasiswa,
        IpkCplReportService $reports,
        MahasiswaNilaiService $nilaiService,
        LegacyListNavigation $navigation
    ) {
        $period = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:20', 'regex:/^\d{4}[\/-]\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);

        $allStudentGrades = $reports->finalGrades()
            ->filter(fn (Khs $grade) => (int) $grade->krs?->mahasiswa_id === (int) $mahasiswa->id)
            ->values();
        $semesterGrades = $allStudentGrades
            ->filter(fn (Khs $grade) => $this->academicYear($grade) === str_replace('-', '/', $period['tahun_akademik'])
                && strcasecmp($this->academicSemester($grade), $period['semester_akademik']) === 0)
            ->sortBy(fn (Khs $grade) => $grade->krs?->mata_kuliah_efektif?->kode_mk)
            ->values();

        abort_if($semesterGrades->isEmpty(), 404, 'Data KHS pada semester tersebut tidak ditemukan.');

        $targetPeriod = $this->periodOrder($period['tahun_akademik'], $period['semester_akademik']);
        $cumulativeGrades = $allStudentGrades
            ->filter(fn (Khs $grade) => $this->periodOrder($this->academicYear($grade), $this->academicSemester($grade)) <= $targetPeriod)
            ->values();
        $mahasiswa->loadMissing(['prodi', 'kelas']);

        return view('admin.khs.show', [
            'mahasiswa' => $mahasiswa,
            'grades' => $semesterGrades,
            'tahunAkademik' => str_replace('-', '/', $period['tahun_akademik']),
            'semesterAkademik' => $period['semester_akademik'],
            'totalSks' => $semesterGrades->sum(fn (Khs $grade) => $grade->sks_efektif),
            'ips' => $nilaiService->hitungIndeks($semesterGrades),
            'ipk' => $nilaiService->hitungIndeks($cumulativeGrades),
            'returnUrl' => $navigation->returnUrl($request, 'admin.khs'),
        ]);
    }

    private function academicYear(Khs $grade): string
    {
        return str_replace('-', '/', (string) ($grade->tahun_akademik ?: $grade->krs?->tahun_akademik));
    }

    private function academicSemester(Khs $grade): string
    {
        return (string) ($grade->semester_akademik ?: $grade->krs?->semester_akademik);
    }

    private function courseSemester(Khs $grade): ?int
    {
        foreach ([$grade->krs?->mata_kuliah_efektif?->semester, $grade->krs?->semester] as $value) {
            if (preg_match('/\d+/', (string) $value, $match)) {
                return (int) $match[0];
            }
        }

        return null;
    }

    private function semesterOrder(string $semester): int
    {
        return strcasecmp($semester, 'Genap') === 0 ? 2 : 1;
    }

    private function periodOrder(string $year, string $semester): int
    {
        preg_match('/\d{4}/', $year, $match);

        return ((int) ($match[0] ?? 0) * 10) + $this->semesterOrder($semester);
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

    // =====================================================
    // CREATE
    // =====================================================
    // Untuk sementara tetap ada agar tidak merusak route lama.
    // Tombol tambah tidak ditampilkan di halaman admin.
    // =====================================================

    public function create()
    {
        $krs = Krs::with([
            'mahasiswa.kelas',
            'mahasiswa.prodi',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'khs',
        ])
            ->where('status', 'Disetujui')
            ->whereDoesntHave('khs')
            ->latest()
            ->get();

        return view(
            'admin.khs.create',
            compact('krs')
        );
    }

    // =====================================================
    // STORE
    // =====================================================

    public function store(Request $request)
    {
        $request->validate([
            'krs_id' => [
                'required',
                'exists:krs,id',
            ],

            'nilai_angka' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        // ===================== AMBIL KRS =====================

        $krs = Krs::with([
            'mahasiswa',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->findOrFail($request->krs_id);

        // ===================== CEK STATUS KRS =====================

        if ($krs->status !== 'Disetujui') {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KHS hanya dapat dibuat untuk KRS yang sudah disetujui.'
                );
        }

        // ===================== CEK DUPLIKAT =====================

        $sudahAda = Khs::where(
            'krs_id',
            $krs->id
        )->exists();

        if ($sudahAda) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KRS tersebut sudah memiliki data KHS.'
                );
        }

        // ===================== KONVERSI NILAI =====================

        [$huruf, $bobot] = $this->konversiNilai(
            $request->nilai_angka
        );

        // ===================== SIMPAN =====================

        Khs::create([
            'krs_id' => $krs->id,
            'nilai_angka' => $request->nilai_angka,
            'nilai_huruf' => $huruf,
            'bobot' => $bobot,
            'tahun_akademik' => $krs->tahun_akademik,
            'semester_akademik' => $krs->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.khs'))
            ->with(
                'success',
                'Data KHS berhasil ditambahkan.'
            );
    }

    // =====================================================
    // EDIT
    // =====================================================

    public function edit(Khs $kh)
    {
        $krs = Krs::with([
            'mahasiswa.kelas',
            'mahasiswa.prodi',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
            'khs',
        ])
            ->where('status', 'Disetujui')
            ->where(function ($query) use ($kh) {

                $query
                    ->whereDoesntHave('khs')
                    ->orWhereHas(
                        'khs',
                        function ($q) use ($kh) {

                            $q->where(
                                'id',
                                $kh->id
                            );
                        }
                    );
            })
            ->latest()
            ->get();

        return view(
            'admin.khs.edit',
            [
                'khs' => $kh,
                'krs' => $krs,
            ]
        );
    }

    // =====================================================
    // UPDATE
    // =====================================================

    public function update(
        Request $request,
        Khs $kh
    ) {

        $request->validate([
            'krs_id' => [
                'required',
                'exists:krs,id',
            ],

            'nilai_angka' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);

        $krs = Krs::with([
            'mahasiswa',
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.ruangan',
        ])
            ->findOrFail($request->krs_id);

        // ===================== CEK STATUS =====================

        if ($krs->status !== 'Disetujui') {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KHS hanya dapat menggunakan KRS yang sudah disetujui.'
                );
        }

        // ===================== CEK DUPLIKAT =====================

        $sudahAda = Khs::where(
            'krs_id',
            $krs->id
        )
            ->where(
                'id',
                '!=',
                $kh->id
            )
            ->exists();

        if ($sudahAda) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'KRS tersebut sudah digunakan oleh KHS lain.'
                );
        }

        // ===================== KONVERSI NILAI =====================

        [$huruf, $bobot] = $this->konversiNilai(
            $request->nilai_angka
        );

        // ===================== UPDATE =====================

        $kh->update([
            'krs_id' => $krs->id,
            'nilai_angka' => $request->nilai_angka,
            'nilai_huruf' => $huruf,
            'bobot' => $bobot,
            'tahun_akademik' => $krs->tahun_akademik,
            'semester_akademik' => $krs->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.khs'))
            ->with(
                'success',
                'Data KHS berhasil diperbarui.'
            );
    }

    // =====================================================
    // DELETE
    // =====================================================

    public function destroy(Khs $kh)
    {
        $kh->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.khs'))
            ->with(
                'success',
                'Data KHS berhasil dihapus.'
            );
    }

    // =====================================================
    // KONVERSI NILAI
    // =====================================================

    private function konversiNilai($nilai)
    {
        $nilai = (float) $nilai;

        if ($nilai >= 85) {
            return ['A', 4.00];

        } elseif ($nilai >= 80) {
            return ['A-', 3.75];

        } elseif ($nilai >= 75) {
            return ['B+', 3.50];

        } elseif ($nilai >= 70) {
            return ['B', 3.00];

        } elseif ($nilai >= 65) {
            return ['B-', 2.75];

        } elseif ($nilai >= 60) {
            return ['C+', 2.50];

        } elseif ($nilai >= 55) {
            return ['C', 2.00];

        } elseif ($nilai >= 40) {
            return ['D', 1.00];

        } else {
            return ['E', 0.00];
        }
    }
}
