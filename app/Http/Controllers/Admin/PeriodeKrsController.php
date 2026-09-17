<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\PeriodeKrs;
use App\Models\PeriodeKrsMahasiswa;
use App\Models\Prodi;
use App\Services\LegacyListNavigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PeriodeKrsController extends Controller
{
    // ===================== INDEX =====================

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $periodeKrs = PeriodeKrs::query()
            ->when($filters['tahun_akademik'] ?? null, fn (Builder $query, string $tahun) => $query
                ->where('tahun_akademik', $tahun))
            ->when($filters['semester'] ?? null, fn (Builder $query, string $semester) => $query
                ->where('semester', $semester))
            ->latest()
            ->paginate(10)->withQueryString();

        return view(
            'admin.periode_krs.index',
            [
                'periodeKrs' => $periodeKrs,
                'tahunAkademiks' => PeriodeKrs::whereNotNull('tahun_akademik')
                    ->distinct()
                    ->orderByDesc('tahun_akademik')
                    ->pluck('tahun_akademik'),
            ]
        );
    }

    public function students(Request $request, PeriodeKrs $periodeKrs)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'between:1,14'],
            'status_akses' => ['nullable', Rule::in(['belum_dibuka', 'dibuka', 'ditutup'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $students = Mahasiswa::query()
            ->with([
                'prodi',
                'kelas',
                'aksesPeriodeKrs' => fn ($query) => $query->where('periode_krs_id', $periodeKrs->id),
            ])
            ->where('is_active', true)
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $student) use ($search) {
                    $student->where('nim', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%");
                });
            })
            ->when($filters['prodi_id'] ?? null, fn (Builder $query, $prodiId) => $query
                ->where('prodi_id', $prodiId))
            ->when($filters['kelas_id'] ?? null, fn (Builder $query, $kelasId) => $query
                ->where('kelas_id', $kelasId))
            ->when($filters['angkatan'] ?? null, function (Builder $query, $angkatan) {
                $query->where(function (Builder $student) use ($angkatan) {
                    $student->where('angkatan', $angkatan)
                        ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('angkatan', $angkatan));
                });
            })
            ->when($filters['semester'] ?? null, function (Builder $query, $semester) {
                $query->where(function (Builder $student) use ($semester) {
                    $student->where('semester', $semester)
                        ->orWhereHas('kelas', fn (Builder $kelas) => $kelas->where('semester', $semester));
                });
            });

        if (($filters['status_akses'] ?? null) === 'dibuka') {
            $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id)
                ->where('status_akses', true));
        } elseif (($filters['status_akses'] ?? null) === 'ditutup') {
            $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id)
                ->where('status_akses', false));
        } elseif (($filters['status_akses'] ?? null) === 'belum_dibuka') {
            $students->whereDoesntHave('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id));
        }

        return view('admin.periode_krs.students', [
            'periodeKrs' => $periodeKrs,
            'mahasiswas' => $students->orderBy('nama')->paginate(10)->withQueryString(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
            'angkatans' => Mahasiswa::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
        ]);
    }

    public function updateStudentAccess(Request $request, PeriodeKrs $periodeKrs, Mahasiswa $mahasiswa)
    {
        $data = $request->validate([
            'status_akses' => ['required', 'boolean'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);

        abort_unless($mahasiswa->is_active, 404);
        $isOpen = (bool) $data['status_akses'];

        DB::transaction(fn () => $this->saveStudentAccess(
            $periodeKrs,
            $mahasiswa->id,
            $isOpen,
            $request->user()->id
        ));

        $message = $isOpen
            ? "Akses KRS {$mahasiswa->nama} berhasil dibuka."
            : "Akses KRS {$mahasiswa->nama} berhasil ditutup.";

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(
                $request,
                'admin.periode-krs.students',
                ['periodeKrs' => $periodeKrs]
            ))
            ->with('success', $message);
    }

    public function updateBulkStudentAccess(Request $request, PeriodeKrs $periodeKrs)
    {
        $data = $request->validate([
            'mahasiswa_ids' => ['required', 'array', 'min:1', 'max:500'],
            'mahasiswa_ids.*' => ['required', 'integer', 'distinct', 'exists:mahasiswas,id'],
            'status_akses' => ['required', 'boolean'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ]);

        $studentIds = Mahasiswa::query()
            ->whereIn('id', $data['mahasiswa_ids'])
            ->where('is_active', true)
            ->pluck('id');

        if ($studentIds->count() !== count($data['mahasiswa_ids'])) {
            throw ValidationException::withMessages([
                'mahasiswa_ids' => 'Pilihan hanya boleh berisi mahasiswa aktif.',
            ]);
        }

        $isOpen = (bool) $data['status_akses'];
        DB::transaction(function () use ($request, $periodeKrs, $studentIds, $isOpen) {
            foreach ($studentIds as $studentId) {
                $this->saveStudentAccess($periodeKrs, $studentId, $isOpen, $request->user()->id);
            }
        });

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(
                $request,
                'admin.periode-krs.students',
                ['periodeKrs' => $periodeKrs]
            ))
            ->with(
                'success',
                $studentIds->count().' akses KRS mahasiswa berhasil '.($isOpen ? 'dibuka.' : 'ditutup.')
            );
    }

    private function saveStudentAccess(
        PeriodeKrs $periodeKrs,
        int $studentId,
        bool $isOpen,
        int $adminId
    ): void {
        $current = PeriodeKrsMahasiswa::firstOrNew([
            'periode_krs_id' => $periodeKrs->id,
            'mahasiswa_id' => $studentId,
        ]);

        if (! $isOpen && ! $current->exists) {
            return;
        }

        $current->fill([
            'status_akses' => $isOpen,
            'tanggal_dibuka' => $isOpen ? now() : $current->tanggal_dibuka,
            'tanggal_ditutup' => $isOpen ? null : now(),
            'dibuka_oleh' => $isOpen ? $adminId : $current->dibuka_oleh,
        ])->save();
    }

    // ===================== CREATE =====================

    public function create()
    {
        return view('admin.periode_krs.create');
    }

    // ===================== STORE =====================

    public function store(Request $request)
    {
        $request->validate([
            'tahun_akademik' => 'required|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks' => 'required|integer|min:0|max:30',
            'maksimal_sks' => 'required|integer|min:1|max:30',
            'status' => 'required|in:Dibuka,Ditutup',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        PeriodeKrs::create([
            'tahun_akademik' => $request->tahun_akademik,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_sks' => $request->maksimal_sks,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil ditambahkan.'
            );
    }

    // ===================== EDIT =====================

    public function edit(PeriodeKrs $periodeKrs)
    {
        return view(
            'admin.periode_krs.edit',
            compact('periodeKrs')
        );
    }

    // ===================== UPDATE =====================

    public function update(
        Request $request,
        PeriodeKrs $periodeKrs
    ) {
        $request->validate([
            'tahun_akademik' => 'required|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'minimal_sks' => 'required|integer|min:0|max:30',
            'maksimal_sks' => 'required|integer|min:1|max:30',
            'status' => 'required|in:Dibuka,Ditutup',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->minimal_sks > $request->maksimal_sks) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        $periodeKrs->update([
            'tahun_akademik' => $request->tahun_akademik,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'minimal_sks' => $request->minimal_sks,
            'maksimal_sks' => $request->maksimal_sks,
            'status' => $request->status,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil diperbarui.'
            );
    }

    // ===================== TOGGLE STATUS =====================

    public function toggleStatus(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->update([
            'status' => $periodeKrs->status === 'Dibuka'
                ? 'Ditutup'
                : 'Dibuka',
        ]);

        return back()->with(
            'success',
            'Status periode KRS berhasil diubah.'
        );
    }

    // ===================== DELETE =====================

    public function destroy(PeriodeKrs $periodeKrs)
    {
        $periodeKrs->delete();

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil dihapus.'
            );
    }
}
