<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'between:1,14'],
            'status_akses' => ['nullable', Rule::in(['belum_dibuka', 'dibuka', 'ditutup'])],
            'page' => ['nullable', 'integer', 'between:1,100000'],
        ]);

        $students = Mahasiswa::query()
            ->with([
                'prodi',
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
            ->when($filters['angkatan'] ?? null, fn (Builder $query, $angkatan) => $query
                ->where('angkatan', $angkatan))
            ->when($filters['semester'] ?? null, fn (Builder $query, $semester) => $query
                ->where('semester', $semester));

        if (! empty($filters['status_akses'])) {
            $this->applyAccessFilter($students, $periodeKrs, $filters['status_akses']);
        }

        return view('admin.periode_krs.students', [
            'periodeKrs' => $periodeKrs,
            'mahasiswas' => $students->orderBy('nama')->paginate(10)->withQueryString(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'angkatans' => Mahasiswa::whereNotNull('angkatan')->distinct()->orderByDesc('angkatan')->pluck('angkatan'),
            'studentSuggestions' => Mahasiswa::where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nim', 'nama']),
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

        DB::transaction(function () use ($request, $periodeKrs, $mahasiswa, $isOpen) {
            if ($isOpen && $periodeKrs->access_mode === 'closed') {
                $periodeKrs->update(['access_mode' => 'selected']);
            } elseif (! $isOpen && $periodeKrs->access_mode === 'all') {
                $periodeKrs->update(['access_mode' => 'all_except']);
            }

            $this->saveStudentAccess(
                $periodeKrs,
                $mahasiswa->id,
                $isOpen,
                $request->user()->id
            );
        });

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
            if ($isOpen && $periodeKrs->access_mode === 'closed') {
                $periodeKrs->update(['access_mode' => 'selected']);
            } elseif (! $isOpen && $periodeKrs->access_mode === 'all') {
                $periodeKrs->update(['access_mode' => 'all_except']);
            }

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

        if (! $current->exists) {
            $mode = $periodeKrs->access_mode ?? 'selected';
            if (($isOpen && in_array($mode, ['all', 'all_except'], true))
                || (! $isOpen && in_array($mode, ['closed', 'selected'], true))) {
                return;
            }
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
        return view('admin.periode_krs.create', $this->studentAccessFormData());
    }

    // ===================== STORE =====================

    public function store(Request $request)
    {
        $data = $this->validatedPeriod($request);
        $studentIds = $this->validatedStudentIds($data);

        if ($data['minimal_sks'] > $data['maksimal_sks']) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        DB::transaction(function () use ($request, $data, $studentIds) {
            $periodeKrs = PeriodeKrs::create($this->periodAttributes($data));
            $this->syncFormAccess($periodeKrs, $studentIds, $request->user()->id);
        });

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
        $selectedAccessIds = $this->selectedAccessIds($periodeKrs);

        return view(
            'admin.periode_krs.edit',
            $this->studentAccessFormData() + compact('periodeKrs', 'selectedAccessIds')
        );
    }

    // ===================== UPDATE =====================

    public function update(
        Request $request,
        PeriodeKrs $periodeKrs
    ) {
        $data = $this->validatedPeriod($request);
        $studentIds = $this->validatedStudentIds($data);

        if ($data['minimal_sks'] > $data['maksimal_sks']) {
            return back()
                ->withInput()
                ->withErrors([
                    'minimal_sks' => 'Minimal SKS tidak boleh lebih besar dari maksimal SKS.',
                ]);
        }

        DB::transaction(function () use ($request, $periodeKrs, $data, $studentIds) {
            $periodeKrs->update($this->periodAttributes($data));
            $this->syncFormAccess($periodeKrs, $studentIds, $request->user()->id);
        });

        return redirect()
            ->to(app(LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs'))
            ->with(
                'success',
                'Periode KRS berhasil diperbarui.'
            );
    }

    private function validatedPeriod(Request $request): array
    {
        $data = $request->validate([
            'tahun_akademik' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'minimal_sks' => ['required', 'integer', 'min:0', 'max:30'],
            'maksimal_sks' => ['required', 'integer', 'min:1', 'max:30'],
            'status' => ['required', Rule::in(['Dibuka', 'Ditutup'])],
            'access_mode' => ['nullable', Rule::in(PeriodeKrs::ACCESS_MODES)],
            'mahasiswa_ids' => ['nullable', 'array', 'max:5000'],
            'mahasiswa_ids.*' => ['integer', 'distinct', 'exists:mahasiswas,id'],
            'keterangan' => ['nullable', 'string'],
        ]);
        $data['access_mode'] ??= 'closed';

        return $data;
    }

    private function validatedStudentIds(array $data): array
    {
        $ids = collect($data['mahasiswa_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($data['access_mode'] === 'selected' && $ids->isEmpty()) {
            throw ValidationException::withMessages([
                'mahasiswa_ids' => 'Pilih minimal satu mahasiswa untuk mode hanya mahasiswa tertentu.',
            ]);
        }

        $activeCount = Mahasiswa::query()->whereIn('id', $ids)->where('is_active', true)->count();
        if ($activeCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'mahasiswa_ids' => 'Pilihan hanya boleh berisi mahasiswa aktif.',
            ]);
        }

        return $ids->all();
    }

    private function periodAttributes(array $data): array
    {
        return collect($data)->only([
            'tahun_akademik',
            'semester',
            'tanggal_mulai',
            'tanggal_selesai',
            'minimal_sks',
            'maksimal_sks',
            'status',
            'access_mode',
            'keterangan',
        ])->all();
    }

    private function studentAccessFormData(): array
    {
        return [
            'mahasiswas' => Mahasiswa::with('prodi')
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(),
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'angkatans' => Mahasiswa::where('is_active', true)
                ->whereNotNull('angkatan')
                ->distinct()
                ->orderByDesc('angkatan')
                ->pluck('angkatan'),
        ];
    }

    private function selectedAccessIds(PeriodeKrs $periodeKrs): array
    {
        $mode = $periodeKrs->access_mode ?? 'selected';
        if (! in_array($mode, ['selected', 'all_except'], true)) {
            return [];
        }

        return $periodeKrs->aksesMahasiswa()
            ->where('status_akses', $mode === 'selected')
            ->pluck('mahasiswa_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syncFormAccess(PeriodeKrs $periodeKrs, array $studentIds, int $adminId): void
    {
        if (! in_array($periodeKrs->access_mode, ['selected', 'all_except'], true)) {
            return;
        }

        $selected = collect($studentIds);
        $selectedStatus = $periodeKrs->access_mode === 'selected';
        $existing = $periodeKrs->aksesMahasiswa()
            ->whereHas('mahasiswa', fn (Builder $query) => $query->where('is_active', true))
            ->get();

        foreach ($existing as $access) {
            $this->saveStudentAccess(
                $periodeKrs,
                $access->mahasiswa_id,
                $selected->contains($access->mahasiswa_id) ? $selectedStatus : ! $selectedStatus,
                $adminId
            );
        }

        foreach ($selected->diff($existing->pluck('mahasiswa_id')) as $studentId) {
            $this->saveStudentAccess($periodeKrs, (int) $studentId, $selectedStatus, $adminId);
        }
    }

    private function applyAccessFilter(Builder $students, PeriodeKrs $periodeKrs, string $status): void
    {
        $mode = $periodeKrs->access_mode ?? 'selected';
        $relation = fn (Builder $query, bool $isOpen) => $query
            ->where('periode_krs_id', $periodeKrs->id)
            ->where('status_akses', $isOpen);

        if ($status === 'belum_dibuka') {
            $students->whereDoesntHave('aksesPeriodeKrs', fn (Builder $query) => $query
                ->where('periode_krs_id', $periodeKrs->id));

            return;
        }

        if ($status === 'dibuka') {
            match ($mode) {
                'all' => null,
                'all_except' => $students->whereDoesntHave('aksesPeriodeKrs', fn (Builder $query) => $relation($query, false)),
                'selected' => $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $relation($query, true)),
                default => $students->whereRaw('1 = 0'),
            };

            return;
        }

        match ($mode) {
            'closed' => null,
            'all' => $students->whereRaw('1 = 0'),
            'all_except' => $students->whereHas('aksesPeriodeKrs', fn (Builder $query) => $relation($query, false)),
            default => $students->whereDoesntHave('aksesPeriodeKrs', fn (Builder $query) => $relation($query, true)),
        };
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
