<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\Prodi;
use App\Services\KrsCardService;
use App\Services\LegacyListNavigation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KrsController extends Controller
{
    public function studentIndex(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'semester' => ['nullable', 'integer', 'between:1,14'],
            'semester_akademik' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $query = Krs::query()
            ->with(['mahasiswa.prodi', 'mahasiswa.kelas'])
            ->where('is_manual', false)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('mahasiswa', fn (Builder $student) => $student
                    ->where('nim', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%"));
            })
            ->when($filters['angkatan'] ?? null, fn (Builder $query, $angkatan) => $query
                ->whereHas('mahasiswa', fn (Builder $student) => $student
                    ->where('angkatan', $angkatan)
                    ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('angkatan', $angkatan))))
            ->when($filters['prodi_id'] ?? null, fn (Builder $query, $prodiId) => $query
                ->whereHas('mahasiswa', fn (Builder $student) => $student->where('prodi_id', $prodiId)))
            ->when($filters['kelas_id'] ?? null, fn (Builder $query, $kelasId) => $query
                ->whereHas('mahasiswa', fn (Builder $student) => $student->where('kelas_id', $kelasId)))
            ->when($filters['tahun_akademik'] ?? null, fn (Builder $query, $tahun) => $query->where('tahun_akademik', $tahun))
            ->when($filters['semester_akademik'] ?? null, fn (Builder $query, $semester) => $query->where('semester_akademik', $semester));

        $query->when($filters['semester'] ?? null, fn (Builder $query, $semester) => $query
            ->whereHas('mahasiswa', fn (Builder $student) => $student
                ->where('semester', $semester)
                ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('semester', $semester))));

        $summaries = $query
            ->select(['mahasiswa_id', 'tahun_akademik', 'semester_akademik'])
            ->selectRaw('COUNT(*) AS jumlah_mata_kuliah')
            ->groupBy('mahasiswa_id', 'tahun_akademik', 'semester_akademik')
            ->orderByDesc('tahun_akademik')
            ->orderByDesc('semester_akademik')
            ->paginate(10)
            ->withQueryString();

        $studentIds = $summaries->getCollection()->pluck('mahasiswa_id')->unique();
        $periodKey = fn (int $mahasiswaId, string $tahunAkademik, string $semesterAkademik): string => implode('|', [
            $mahasiswaId,
            $tahunAkademik,
            $semesterAkademik,
        ]);
        $periodRecords = Krs::query()
            ->with(['jadwal.mataKuliah'])
            ->where('is_manual', false)
            ->whereIn('mahasiswa_id', $studentIds)
            ->get()
            ->groupBy(fn (Krs $item) => $periodKey(
                $item->mahasiswa_id,
                $item->tahun_akademik,
                $item->semester_akademik
            ));
        $summaries->setCollection($summaries->getCollection()->map(function (Krs $summary) use ($periodKey, $periodRecords) {
            $key = $periodKey($summary->mahasiswa_id, $summary->tahun_akademik, $summary->semester_akademik);
            $records = $periodRecords->get($key, collect());
            $summary->setRelation('periodRecords', $records);
            $summary->setAttribute('total_sks', $records
                ->where('status', '!=', 'Ditolak')
                ->sum(fn (Krs $item) => (int) ($item->jadwal?->mataKuliah?->sks ?? 0)));
            $summary->setAttribute('semester_studi', $summary->mahasiswa?->semester
                ?? $summary->mahasiswa?->kelas?->semester);

            return $summary;
        }));

        return view('admin.krs-mahasiswa.index', [
            'summaries' => $summaries,
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
            'angkatans' => Mahasiswa::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
            'tahunAkademiks' => Krs::where('is_manual', false)->whereNotNull('tahun_akademik')->distinct()->orderByDesc('tahun_akademik')->pluck('tahun_akademik'),
            'studentSuggestions' => Mahasiswa::where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nim', 'nama']),
        ]);
    }

    public function studentShow(Request $request, Mahasiswa $mahasiswa, KrsCardService $cards, LegacyListNavigation $navigation)
    {
        $period = $this->validatedPeriod($request);
        $data = $cards->data($mahasiswa, $period['tahun_akademik'], $period['semester_akademik']);
        abort_if($data['krsRecords']->isEmpty(), 404, 'Data KRS pada periode tersebut tidak ditemukan.');

        return view('admin.krs-mahasiswa.show', $data + [
            'returnUrl' => $navigation->returnUrl($request, 'admin.krs-mahasiswa.index'),
        ]);
    }

    public function studentCardPdf(Request $request, Mahasiswa $mahasiswa, KrsCardService $cards)
    {
        $period = $this->validatedPeriod($request);
        $data = $cards->data($mahasiswa, $period['tahun_akademik'], $period['semester_akademik']);
        abort_if($data['printableRecords']->isEmpty(), 404, 'Tidak ada KRS yang dapat dicetak pada periode tersebut.');

        return Pdf::loadView('krs.card-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download($cards->filename($mahasiswa, $data['semesterStudi']));
    }

    private function validatedPeriod(Request $request): array
    {
        return $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_akademik' => ['required', Rule::in(['Ganjil', 'Genap'])],
        ]);
    }

    // ===================== INDEX =====================

    public function index(Request $request)
    {
        // ===================== DATA FAKULTAS =====================

        $fakultas = Fakultas::orderBy('nama_fakultas')
            ->get();

        // ===================== DATA PRODI =====================

        $prodis = Prodi::with('fakultas')
            ->orderBy('nama_prodi')
            ->get();

        // ===================== DATA KELAS =====================

        $kelases = Kelas::with([
            'prodi.fakultas',
        ])
            ->orderBy('angkatan', 'desc')
            ->orderBy('nama_kelas')
            ->get();

        // ===================== DATA DOSEN =====================

        $dosens = Dosen::orderBy('nama')
            ->get();

        // ===================== DATA MAHASISWA =====================

        $mahasiswas = Mahasiswa::with([
            'prodi.fakultas',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        // ===================== QUERY KRS =====================

        $query = Krs::with([
            'mahasiswa.prodi.fakultas',
            'mahasiswa.kelas',
            'jadwal.mataKuliah.prodi.fakultas',
            'jadwal.dosen',
            'jadwal.ruangan',
            'jadwal.kelas.prodi.fakultas',
        ])->where('is_manual', false);

        // ===================== SEARCH =====================

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                // Cari mahasiswa
                $q->whereHas('mahasiswa', function ($mhs) use ($search) {

                    $mhs->whereLike('nim', '%'.$search.'%')
                        ->orWhereLike('nama', '%'.$search.'%');

                })

                // Cari mata kuliah
                    ->orWhereHas('jadwal.mataKuliah', function ($mk) use ($search) {

                        $mk->whereLike('kode_mk', '%'.$search.'%')
                            ->orWhereLike('nama_mk', '%'.$search.'%');

                    })

                // Cari dosen
                    ->orWhereHas('jadwal.dosen', function ($dosen) use ($search) {

                        $dosen->whereLike('nama', '%'.$search.'%'
                        );

                    });

            });
        }

        // ===================== FILTER FAKULTAS =====================

        if ($request->filled('fakultas_id')) {

            $query->whereHas('mahasiswa.prodi', function ($q) use ($request) {

                $q->where(
                    'fakultas_id',
                    $request->fakultas_id
                );

            });
        }

        // ===================== FILTER PRODI =====================

        if ($request->filled('prodi_id')) {

            $query->whereHas('mahasiswa', function ($q) use ($request) {

                $q->where(
                    'prodi_id',
                    $request->prodi_id
                );

            });
        }

        // ===================== FILTER KELAS =====================

        if ($request->filled('kelas_id')) {

            $query->whereHas('mahasiswa', function ($q) use ($request) {

                $q->where(
                    'kelas_id',
                    $request->kelas_id
                );

            });
        }

        // ===================== FILTER ANGKATAN =====================

        if ($request->filled('angkatan')) {

            $query->whereHas('mahasiswa.kelas', function ($q) use ($request) {

                $q->where(
                    'angkatan',
                    $request->angkatan
                );

            });
        }

        // ===================== FILTER DOSEN =====================

        if ($request->filled('dosen_id')) {

            $query->whereHas('jadwal', function ($q) use ($request) {

                $q->where(
                    'dosen_id',
                    $request->dosen_id
                );

            });
        }

        // ===================== FILTER MAHASISWA =====================

        if ($request->filled('mahasiswa_id')) {

            $query->where(
                'mahasiswa_id',
                $request->mahasiswa_id
            );
        }

        // ===================== FILTER STATUS =====================

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        // ===================== FILTER SEMESTER AKADEMIK =====================

        if ($request->filled('semester_akademik')) {

            $query->where(
                'semester_akademik',
                $request->semester_akademik
            );
        }

        // ===================== FILTER TAHUN AKADEMIK =====================

        if ($request->filled('tahun_akademik')) {

            $query->where(
                'tahun_akademik',
                $request->tahun_akademik
            );
        }

        // ===================== HASIL =====================

        $krs = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ===================== TAHUN AKADEMIK =====================

        $tahunAkademiks = Krs::select('tahun_akademik')
            ->whereNotNull('tahun_akademik')
            ->distinct()
            ->orderBy('tahun_akademik', 'desc')
            ->pluck('tahun_akademik');

        // ===================== ANGKATAN =====================

        $angkatans = Kelas::select('angkatan')
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderBy('angkatan', 'desc')
            ->pluck('angkatan');

        // ===================== RETURN VIEW =====================

        return view(
            'admin.krs.index',
            compact(
                'krs',
                'fakultas',
                'prodis',
                'kelases',
                'dosens',
                'mahasiswas',
                'tahunAkademiks',
                'angkatans'
            )
        );
    }

    // ===================== CREATE =====================

    public function create()
    {
        $mahasiswas = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'dosen',
            'ruangan',
            'kelas.prodi',
        ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view(
            'admin.krs.create',
            compact(
                'mahasiswas',
                'jadwals'
            )
        );
    }

    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'jadwal_id' => 'required|exists:jadwals,id',
            'status' => 'required|in:Diambil,Disetujui,Ditolak',
            'tahun_akademik' => 'required',
            'semester_akademik' => 'required|in:Ganjil,Genap',
        ]);

        // Cek duplikasi

        $cek = Krs::where(
            'mahasiswa_id',
            $request->mahasiswa_id
        )
            ->where(
                'jadwal_id',
                $request->jadwal_id
            )
            ->first();

        if ($cek) {

            return back()
                ->withInput()
                ->withErrors([
                    'jadwal_id' => 'Mahasiswa sudah mengambil jadwal ini.',
                ]);
        }

        Krs::create([
            'mahasiswa_id' => $request->mahasiswa_id,
            'jadwal_id' => $request->jadwal_id,
            'status' => $request->status,
            'tahun_akademik' => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil ditambahkan.'
            );
    }

    // ===================== EDIT =====================

    public function edit(Krs $kr)
    {
        abort_if($kr->is_manual, 404);
        $mahasiswas = Mahasiswa::with([
            'prodi',
            'kelas',
        ])
            ->orderBy('nama')
            ->get();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'dosen',
            'ruangan',
            'kelas.prodi',
        ])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view(
            'admin.krs.edit',
            [
                'krs' => $kr,
                'mahasiswas' => $mahasiswas,
                'jadwals' => $jadwals,
            ]
        );
    }

    // ===================== UPDATE =====================

    public function update(
        Request $request,
        Krs $kr
    ) {

        abort_if($kr->is_manual, 404);

        $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswas,id',
            'jadwal_id' => 'required|exists:jadwals,id',
            'status' => 'required|in:Diambil,Disetujui,Ditolak',
            'tahun_akademik' => 'required',
            'semester_akademik' => 'required|in:Ganjil,Genap',
        ]);

        // Cek duplikasi

        $cek = Krs::where(
            'mahasiswa_id',
            $request->mahasiswa_id
        )
            ->where(
                'jadwal_id',
                $request->jadwal_id
            )
            ->where(
                'id',
                '!=',
                $kr->id
            )
            ->first();

        if ($cek) {

            return back()
                ->withInput()
                ->withErrors([
                    'jadwal_id' => 'Mahasiswa sudah mengambil jadwal ini.',
                ]);
        }

        $kr->update([
            'mahasiswa_id' => $request->mahasiswa_id,
            'jadwal_id' => $request->jadwal_id,
            'status' => $request->status,
            'tahun_akademik' => $request->tahun_akademik,
            'semester_akademik' => $request->semester_akademik,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil diperbarui.'
            );
    }

    // ===================== DELETE =====================

    public function destroy(Krs $kr)
    {
        abort_if($kr->is_manual, 404);
        $kr->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.krs'))
            ->with(
                'success',
                'Data KRS berhasil dihapus.'
            );
    }
}
